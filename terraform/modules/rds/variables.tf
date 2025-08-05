variable "environment" {
  description = "Environment name (dev, stage, prod)"
  type        = string
}

variable "project_name" {
  description = "Name of the project"
  type        = string
}

variable "private_subnet_ids" {
  description = "List of private subnet IDs"
  type        = list(string)
}

variable "security_group_id" {
  description = "Security group ID for RDS"
  type        = string
}

variable "db_instance_class" {
  description = "RDS instance class"
  type        = string
  default     = "db.t3.micro"
}

variable "db_allocated_storage" {
  description = "Allocated storage for RDS instance"
  type        = number
  default     = 20
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "simpleapp"
}

variable "db_username" {
  description = "Database username"
  type        = string
  default     = "admin"
}

variable "backup_retention_period" {
  description = "Backup retention period in days"
  type        = number
  default     = 1
}

variable "multi_az" {
  description = "Whether to deploy RDS instance in multiple AZ"
  type        = bool
  default     = false
}