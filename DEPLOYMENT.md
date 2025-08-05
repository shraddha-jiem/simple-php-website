# Quick Start Guide

This guide helps you quickly deploy the Simple PHP Website infrastructure to AWS.

## Prerequisites

1. AWS CLI configured with appropriate permissions
2. Terraform installed (>= 1.0)
3. Terragrunt installed (>= 0.45)

## 1. Set up AWS Account

Ensure your AWS account has the following services available:
- EC2, VPC, RDS, ALB, Auto Scaling
- IAM roles and policies
- S3 buckets for Terraform state
- DynamoDB for state locking
- CodePipeline, CodeBuild, CodeDeploy
- Secrets Manager
- CodeStarConnection
- SSM

## 2. Create S3 Bucket and DynamoDB Table

Replace `123456789012` with your actual AWS account ID:

```bash
# Create S3 bucket for Terraform state
aws s3 mb s3://simple-php-website-terraform-state-123456789012

# Create DynamoDB table for state locking
aws dynamodb create-table \
  --table-name simple-php-website-terraform-locks \
  --attribute-definitions AttributeName=LockID,AttributeType=S \
  --key-schema AttributeName=LockID,KeyType=HASH \
  --provisioned-throughput ReadCapacityUnits=5,WriteCapacityUnits=5
```

## 3. Deploy Infrastructure

### Deploy Dev Environment

```bash
cd terraform/environments/dev
terragrunt init
terragrunt plan
terragrunt apply
```

### Deploy Stage Environment

```bash
cd terraform/environments/stage
terragrunt init
terragrunt plan
terragrunt apply
```

## 5. Access Your Application

Get the load balancer DNS name:

```bash
cd terraform/environments/dev  # or stage
terragrunt output alb_dns_name
```

Open `http://<alb-dns-name>` in your browser.

Visit `http://<alb-dns-name>?page=status` to see infrastructure status.

## 6. Trigger Deployments

Once infrastructure is deployed, push code to the main branch to trigger automatic deployments via CodePipeline.

## What Gets Created

**Dev Environment:**
- VPC (10.0.0.0/16) with 2 AZs
- 1-2 t2.micro EC2 instances
- db.t3.micro RDS (single AZ)
- Application Load Balancer
- CodePipeline for deployments

**Stage Environment:**
- VPC (10.1.0.0/16) with 2 AZs  
- 1-3 t2.small EC2 instances
- db.t3.small RDS (multi-AZ)
- Application Load Balancer
- CodePipeline for deployments

## Estimated Costs

**Dev:** ~$30-50/month
**Stage:** ~$80-120/month

*Costs may vary based on usage and region*

## Cleanup

To destroy everything:

```bash
cd terraform/environments/dev
terragrunt destroy

cd terraform/environments/stage
terragrunt destroy
```

## Need Help?

See the detailed [terraform/README.md](terraform/README.md) for comprehensive documentation.