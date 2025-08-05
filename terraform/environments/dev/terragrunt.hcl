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
  environment   = "dev"
  aws_region    = "us-east-1"

  # VPC Configuration
  vpc_cidr          = "10.0.0.0/16"
  availability_zones = ["us-east-1a", "us-east-1b"]

  # EC2 Configuration
  instance_type     = "t2.micro"
  min_size         = 1
  max_size         = 2
  desired_capacity = 1
  key_name         = "" # Optional: Add your EC2 key pair name

  # RDS Configuration
  db_instance_class        = "db.t3.micro"
  db_allocated_storage     = 20
  backup_retention_period  = 1
  multi_az                = false
}