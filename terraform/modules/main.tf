# VPC Module
module "vpc" {
  source = "./vpc"

  environment        = var.environment
  project_name      = var.project_name
  vpc_cidr          = var.vpc_cidr
  availability_zones = var.availability_zones
}

# Security Groups Module
module "security_groups" {
  source = "./security-groups"

  environment     = var.environment
  project_name    = var.project_name
  vpc_id         = module.vpc.vpc_id
  vpc_cidr_block = module.vpc.vpc_cidr_block
}

# IAM Module
module "iam" {
  source = "./iam"

  environment  = var.environment
  project_name = var.project_name
}

# EC2 Module
module "ec2" {
  source = "./ec2"

  environment             = var.environment
  project_name           = var.project_name
  instance_type          = var.instance_type
  key_name               = var.key_name
  min_size               = var.min_size
  max_size               = var.max_size
  desired_capacity       = var.desired_capacity
  security_group_id      = module.security_groups.ec2_security_group_id
  instance_profile_name  = module.iam.instance_profile_name
  
  # Add the missing arguments
  private_subnet_ids     = module.vpc.private_subnet_ids
  public_subnet_ids      = module.vpc.public_subnet_ids
}

# ALB Module
module "alb" {
  source = "./alb"

  environment       = var.environment
  project_name     = var.project_name
  public_subnet_ids = module.vpc.public_subnet_ids
  security_group_id = module.security_groups.alb_security_group_id
  target_group_arn  = module.ec2.target_group_arn
}

# RDS Module
module "rds" {
  source = "./rds"

  environment             = var.environment
  project_name           = var.project_name
  private_subnet_ids     = module.vpc.private_subnet_ids
  security_group_id      = module.security_groups.rds_security_group_id
  db_instance_class      = var.db_instance_class
  db_allocated_storage   = var.db_allocated_storage
  backup_retention_period = var.backup_retention_period
  multi_az              = var.multi_az
}

# Deployment Module (CodePipeline)
module "deployment" {
  source = "./deployment"

  environment              = var.environment
  project_name            = var.project_name
  github_owner            = var.github_owner
  github_repo             = var.github_repo
  github_branch           = var.github_branch
  codedeploy_role_arn     = module.iam.codedeploy_role_arn
  codepipeline_role_arn   = module.iam.codepipeline_role_arn
  codebuild_role_arn      = module.iam.codebuild_role_arn
  autoscaling_group_name  = module.ec2.autoscaling_group_name
  target_group_name       = module.ec2.target_group_name
}