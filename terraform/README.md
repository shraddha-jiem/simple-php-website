# Infrastructure Setup with Terraform and Terragrunt

This directory contains the infrastructure as code (IaC) setup for the Simple PHP Website using Terraform and Terragrunt.

## Overview

The infrastructure includes:
- **VPC** with public and private subnets across multiple AZs
- **EC2 instances** in Auto Scaling Groups behind an Application Load Balancer
- **RDS MySQL database** in private subnets
- **Security Groups** for proper network segmentation
- **IAM roles** for CodePipeline, CodeBuild, and CodeDeploy
- **CodePipeline** for CI/CD with GitHub integration

## Directory Structure

```
terraform/
├── terragrunt.hcl                 # Root Terragrunt configuration
├── modules/                       # Terraform modules
│   ├── vpc/                      # VPC and networking
│   ├── security-groups/          # Security groups
│   ├── ec2/                      # Auto Scaling Group and Launch Template
│   ├── rds/                      # RDS MySQL database
│   ├── alb/                      # Application Load Balancer
│   ├── iam/                      # IAM roles and policies
│   ├── main.tf                   # Main module configuration
│   ├── variables.tf              # Input variables
│   └── outputs.tf                # Output values
└── environments/                  # Environment configurations
    ├── dev/
    │   └── terragrunt.hcl        # Dev environment config
    └── stage/
        └── terragrunt.hcl        # Stage environment config
```

## Prerequisites

1. **AWS CLI** configured with appropriate permissions
2. **Terraform** (>= 1.0)
3. **Terragrunt** (>= 0.45)

### AWS Permissions Required

Your AWS user/role needs permissions for:
- EC2, VPC, RDS, ALB, Auto Scaling
- IAM roles and policies
- S3 buckets for Terraform state
- DynamoDB for state locking
- CodePipeline, CodeBuild, CodeDeploy
- Secrets Manager
- CodeStarConnection
- SSM

## Deployment Instructions

### 1. Initialize Backend (First Time Only)

Create the S3 bucket and DynamoDB table for Terraform state:

```bash
# Replace 123456789012 with your AWS account ID
aws s3 mb s3://simple-php-website-terraform-state-123456789012

aws dynamodb create-table \
  --table-name simple-php-website-terraform-locks \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5
```

### 2. Deploy Dev Environment

```bash
cd terraform/environments/dev
terragrunt init
terragrunt plan
terragrunt apply
```

### 3. Deploy Stage Environment

```bash
cd terraform/environments/stage
terragrunt init
terragrunt plan
terragrunt apply
```

### 4. Access the Application

After deployment, get the load balancer DNS name:

```bash
cd terraform/environments/dev  # or stage
terragrunt output alb_dns_name
```

Access your application at: `http://<alb-dns-name>`

## Environment Differences

| Resource | Dev | Stage |
|----------|-----|-------|
| VPC CIDR | 10.0.0.0/16 | 10.1.0.0/16 |
| EC2 Instance Type | t2.micro | t2.small |
| RDS Instance Type | db.t3.micro | db.t3.small |
| Auto Scaling Min/Max | 1/2 | 1/3 |
| RDS Multi-AZ | No | Yes |
| Backup Retention | 1 day | 7 days |

## CI/CD Pipeline

The pipeline automatically:
1. **Source**: Monitors the main branch of the GitHub repository
2. **Build**: Uses CodeBuild to package the application
3. **Deploy**: Uses CodeDeploy to deploy to EC2 instances

### Triggering Deployments

Deployments are triggered automatically when code is pushed to the main branch. You can also trigger manually from the AWS Console.

## Customization

### Adding New Environments

1. Create a new directory under `environments/`
2. Copy the `terragrunt.hcl` from dev or stage
3. Modify the inputs for your new environment
4. Run `terragrunt apply`

### Modifying Resources

Edit the appropriate module under `terraform/modules/` and run `terragrunt apply` in the affected environments.

## Cleanup

To destroy an environment:

```bash
cd terraform/environments/<environment>
terragrunt destroy
```

**Warning**: This will permanently delete all resources including databases and data.

## Troubleshooting

### Common Issues

1. **State Lock**: If Terragrunt gets stuck, check DynamoDB for locks
2. **Permissions**: Verify AWS IAM permissions for all required services
3. **Region**: Ensure you're deploying to the correct AWS region

### Logs

- **CodeBuild logs**: Check CloudWatch logs for build failures
- **CodeDeploy logs**: Check EC2 instances `/var/log/aws/codedeploy-agent/`
- **Application logs**: Check Apache logs on EC2 instances

## Security Considerations

- RDS databases are in private subnets with no public access
- Security groups follow least privilege principle
- Secrets are managed via AWS Secrets Manager
- All resources are tagged for cost tracking and management

## Cost Optimization

- Dev environment uses smaller instance types
- Dev RDS runs single-AZ to reduce costs
- Consider using Spot instances for non-production environments
- Set up billing alerts to monitor costs