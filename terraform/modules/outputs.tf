output "vpc_id" {
  description = "ID of the VPC"
  value       = module.vpc.vpc_id
}

output "alb_dns_name" {
  description = "DNS name of the Application Load Balancer"
  value       = module.alb.alb_dns_name
}

output "rds_endpoint" {
  description = "RDS instance endpoint"
  value       = module.rds.db_instance_endpoint
}

output "db_password_secret_arn" {
  description = "ARN of the secret containing database password"
  value       = module.rds.db_password_secret_arn
}

output "autoscaling_group_name" {
  description = "Name of the Auto Scaling Group"
  value       = module.ec2.autoscaling_group_name
}

output "codedeploy_role_arn" {
  description = "ARN of the CodeDeploy service role"
  value       = module.iam.codedeploy_role_arn
}

output "codepipeline_role_arn" {
  description = "ARN of the CodePipeline service role"
  value       = module.iam.codepipeline_role_arn
}

output "codebuild_role_arn" {
  description = "ARN of the CodeBuild service role"
  value       = module.iam.codebuild_role_arn
}

output "codepipeline_name" {
  description = "Name of the CodePipeline"
  value       = module.deployment.codepipeline_name
}

output "codedeploy_app_name" {
  description = "Name of the CodeDeploy application"
  value       = module.deployment.codedeploy_app_name
}