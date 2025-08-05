variable "environment" {
  description = "Environment name (dev, stage, prod)"
  type        = string
}

variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "github_owner" {
  description = "GitHub repository owner"
  type        = string
  default     = "shraddha-jiem"
}

variable "github_repo" {
  description = "GitHub repository name"
  type        = string
  default     = "simple-php-website"
}

variable "github_branch" {
  description = "GitHub branch to track"
  type        = string
  default     = "master-terraform"
}

variable "codedeploy_role_arn" {
  description = "ARN of the CodeDeploy service role"
  type        = string
}

variable "codepipeline_role_arn" {
  description = "ARN of the CodePipeline service role"
  type        = string
}

variable "codebuild_role_arn" {
  description = "ARN of the CodeBuild service role"
  type        = string
}

variable "autoscaling_group_name" {
  description = "Name of the Auto Scaling Group for deployment"
  type        = string
}

variable "target_group_name" {
  description = "Name of the target group for load balancer"
  type        = string
}