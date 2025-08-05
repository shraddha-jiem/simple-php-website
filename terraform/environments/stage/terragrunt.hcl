# Include root terragrunt configuration
include "root" {
  path = find_in_parent_folders("root.hcl")
}

# Reference the terraform source
terraform {
  source = "../..//modules"
}

# Environment-specific inputs
inputs = {
  environment   = "stage"
  aws_region    = "us-east-1"

  # GitHub Configuration
  github_branch = "stage"  # Manual trigger on stage branch pushes

  # VPC Configuration
  vpc_cidr          = "10.1.0.0/16"
  availability_zones = ["us-east-1a", "us-east-1b"]

  # EC2 Configuration
  instance_type     = "t2.small"
  min_size         = 1
  max_size         = 3
  desired_capacity = 2
  key_name         = "" # Optional: Add your EC2 key pair name

  # RDS Configuration
  db_instance_class        = "db.t3.small"
  db_allocated_storage     = 50
  backup_retention_period  = 7
  multi_az                = true
}