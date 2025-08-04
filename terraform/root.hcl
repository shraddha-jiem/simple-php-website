# Root Terragrunt configuration
# This file contains the common configuration for all environments

# Configure remote state backend
remote_state {
  backend = "s3"
  
  config = {
    bucket         = "iac-trial-simple-php-website-terraform-state-${get_env("AWS_ACCOUNT_ID", "034946978905")}"
    key            = "${path_relative_to_include()}/terraform.tfstate"
    region         = "us-east-1"
    use_lockfile   = true
    dynamodb_table = "simple-php-website-terraform-locks"
  }
  
  generate = {
    path      = "backend.tf"
    if_exists = "overwrite_terragrunt"
  }
}

# Generate provider configuration
generate "provider" {
  path = "provider.tf"
  if_exists = "overwrite_terragrunt"
  contents = <<EOF
terraform {
  required_version = ">= 1.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
  
  default_tags {
    tags = {
      Environment = var.environment
      Project     = "simple-php-website"
      ManagedBy   = "terraform"
    }
  }
}
EOF
}

# Input variables that will be available to all environments
inputs = {
  project_name = "simple-php-website"
}