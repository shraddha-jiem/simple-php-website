variable "environment" {
  description = "Environment name (dev, stage, prod)"
  type        = string
}

variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "codestar_connection_arn" {
  description = "ARN of the CodeStar connection for GitHub"
  type        = string
}