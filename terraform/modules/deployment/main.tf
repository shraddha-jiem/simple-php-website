# S3 Bucket for CodePipeline artifacts
resource "aws_s3_bucket" "codepipeline_artifacts" {
  bucket = "${var.project_name}-${var.environment}-codepipeline-artifacts-${random_string.bucket_suffix.result}"
  force_destroy = true  # Add this line to allow deletion of non-empty bucket
}

resource "aws_s3_bucket_versioning" "codepipeline_artifacts" {
  bucket = aws_s3_bucket.codepipeline_artifacts.id
  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "codepipeline_artifacts" {
  bucket = aws_s3_bucket.codepipeline_artifacts.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "codepipeline_artifacts" {
  bucket = aws_s3_bucket.codepipeline_artifacts.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "random_string" "bucket_suffix" {
  length  = 8
  special = false
  upper   = false
}

# CodeBuild Project
resource "aws_codebuild_project" "main" {
  name          = "${var.project_name}-${var.environment}-build"
  description   = "Build project for ${var.project_name} ${var.environment}"
  service_role  = var.codebuild_role_arn

  artifacts {
    type = "CODEPIPELINE"
  }

  environment {
    compute_type                = "BUILD_GENERAL1_SMALL"
    image                      = "aws/codebuild/amazonlinux2-x86_64-standard:3.0"
    type                       = "LINUX_CONTAINER"
    image_pull_credentials_type = "CODEBUILD"
  }

  source {
    type = "CODEPIPELINE"
    buildspec = "buildspec.yml"
  }

  tags = {
    Name = "${var.project_name}-${var.environment}-build"
  }
}

# CodeDeploy Application
resource "aws_codedeploy_app" "main" {
  compute_platform = "Server"
  name             = "${var.project_name}-${var.environment}-app"
}

# CodeDeploy Deployment Group
resource "aws_codedeploy_deployment_group" "main" {
  app_name              = aws_codedeploy_app.main.name
  deployment_group_name = "${var.project_name}-${var.environment}-deployment-group"
  service_role_arn      = var.codedeploy_role_arn

  deployment_config_name = "CodeDeployDefault.AllAtOnce"

  # Use both Auto Scaling groups and EC2 tag filters
  autoscaling_groups = [var.autoscaling_group_name]

  # Add EC2 tag filters to identify instances
  ec2_tag_filter {
    key   = "Environment"
    type  = "KEY_AND_VALUE"
    value = var.environment
  }

  ec2_tag_filter {
    key   = "Project"
    type  = "KEY_AND_VALUE"
    value = var.project_name
  }

  auto_rollback_configuration {
    enabled = true
    events  = ["DEPLOYMENT_FAILURE"]
  }

  load_balancer_info {
    target_group_info {
      name = var.target_group_name
    }
  }
}

# Create SSM parameters for deployment configuration
resource "aws_ssm_parameter" "project_name" {
  name  = "/app/config/project_name"
  type  = "String"
  value = var.project_name

  tags = {
    Environment = var.environment
    Project     = var.project_name
  }
}

resource "aws_ssm_parameter" "environment" {
  name  = "/app/config/environment"
  type  = "String"
  value = var.environment

  tags = {
    Environment = var.environment
    Project     = var.project_name
  }
}

# CodeStar Connection
resource "aws_codestarconnections_connection" "github" {
  name          = "${var.project_name}-${var.environment}-gh-conn"
  provider_type = "GitHub"
  
  tags = {
    Name = "${var.project_name}-${var.environment}-github-connection"
  }
}

# CodePipeline
resource "aws_codepipeline" "main" {
  name     = "${var.project_name}-${var.environment}-pipeline"
  role_arn = var.codepipeline_role_arn

  # Add pipeline type V2
  pipeline_type = "V2"

  # V2 requires explicit execution mode
  execution_mode = "QUEUED"  # or "PARALLEL"

  artifact_store {
    location = aws_s3_bucket.codepipeline_artifacts.bucket
    type     = "S3"
  }

  stage {
    name = "Source"

    action {
      name             = "Source"
      category         = "Source"
      owner            = "AWS"
      provider         = "CodeStarSourceConnection"
      version          = "1"
      output_artifacts = ["source_output"]

      configuration = {
        ConnectionArn           = aws_codestarconnections_connection.github.arn
        FullRepositoryId        = "${var.github_owner}/${var.github_repo}"
        BranchName              = var.github_branch
        DetectChanges           = var.environment == "dev" ? "true" : "false"
        OutputArtifactFormat    = "CODE_ZIP"
      }
    }
  }

  stage {
    name = "Build"

    action {
      name             = "Build"
      category         = "Build"
      owner            = "AWS"
      provider         = "CodeBuild"
      input_artifacts  = ["source_output"]
      output_artifacts = ["build_output"]
      version          = "1"

      configuration = {
        ProjectName = aws_codebuild_project.main.name
      }
    }
  }

  stage {
    name = "Deploy"

    action {
      name            = "Deploy"
      category        = "Deploy"
      owner           = "AWS"
      provider        = "CodeDeploy"
      input_artifacts = ["build_output"]
      version         = "1"

      configuration = {
        ApplicationName     = aws_codedeploy_app.main.name
        DeploymentGroupName = aws_codedeploy_deployment_group.main.deployment_group_name
      }
    }
  }

  tags = {
    Name = "${var.project_name}-${var.environment}-pipeline"
  }
}

# CloudWatch Event Rule for GitHub push events (only for dev environment)
resource "aws_cloudwatch_event_rule" "github_push" {
  count = var.environment == "dev" ? 1 : 0
  
  name        = "${var.project_name}-${var.environment}-github-push"
  description = "Trigger CodePipeline on GitHub push to ${var.github_branch} branch"

  # Use CodePipeline state change events instead
  event_pattern = jsonencode({
    source      = ["aws.codepipeline"]
    detail-type = ["CodePipeline Source Action State Change"]
    detail = {
      pipeline = [aws_codepipeline.main.name]
      action   = ["Source"]
      state    = ["STARTED"]
    }
  })

  tags = {
    Name = "${var.project_name}-${var.environment}-github-push-rule"
  }
}

# CloudWatch Event Target
resource "aws_cloudwatch_event_target" "codepipeline" {
  count     = var.environment == "dev" ? 1 : 0
  rule      = aws_cloudwatch_event_rule.github_push[0].name
  target_id = "TriggerCodePipeline"
  arn       = aws_codepipeline.main.arn

  role_arn = aws_iam_role.eventbridge_role[0].arn
}

# IAM Role for EventBridge
resource "aws_iam_role" "eventbridge_role" {
  count = var.environment == "dev" ? 1 : 0
  name  = "${var.project_name}-${var.environment}-eventbridge-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action = "sts:AssumeRole"
        Effect = "Allow"
        Principal = {
          Service = "events.amazonaws.com"
        }
      }
    ]
  })

  tags = {
    Name = "${var.project_name}-${var.environment}-eventbridge-role"
  }
}

# IAM Policy for EventBridge role
resource "aws_iam_role_policy" "eventbridge_policy" {
  count = var.environment == "dev" ? 1 : 0
  name  = "${var.project_name}-${var.environment}-eventbridge-policy"
  role  = aws_iam_role.eventbridge_role[0].id

  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "codepipeline:StartPipelineExecution"
        ]
        Resource = aws_codepipeline.main.arn
      }
    ]
  })
}

