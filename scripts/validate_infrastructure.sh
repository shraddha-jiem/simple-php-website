#!/bin/bash

# Infrastructure Validation Script
# This script validates the Terraform configuration without actually deploying resources

set -e

echo "🔍 Validating Simple PHP Website Infrastructure..."
echo "================================================="

# Check if required tools are installed
echo "📋 Checking prerequisites..."

if ! command -v terraform &> /dev/null; then
    echo "❌ Terraform is not installed. Please install Terraform >= 1.0"
    exit 1
fi

if ! command -v terragrunt &> /dev/null; then
    echo "⚠️  Terragrunt is not installed. Install it for easier environment management"
    echo "   You can still use plain Terraform, but terragrunt is recommended"
fi

if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI is not installed. Please install and configure AWS CLI"
    exit 1
fi

echo "✅ Prerequisites check passed"

# Validate Terraform syntax
echo ""
echo "🔧 Validating Terraform syntax..."

cd ../terraform/modules

# Initialize without backend for validation
terraform init -backend=false > /dev/null 2>&1

# Validate syntax
if terraform validate; then
    echo "✅ Terraform configuration is valid"
else
    echo "❌ Terraform validation failed"
    exit 1
fi

cd ../../

# Check AWS credentials
echo ""
echo "🔑 Checking AWS credentials..."
if aws sts get-caller-identity > /dev/null 2>&1; then
    echo "✅ AWS credentials are configured"
    ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
    echo "   Account ID: $ACCOUNT_ID"
else
    echo "❌ AWS credentials are not properly configured"
    echo "   Run 'aws configure' to set up your credentials"
    exit 1
fi

# Check for GitHub token (optional for validation)
# echo ""
# echo "🔗 Checking GitHub token..."
# if aws secretsmanager get-secret-value --secret-id "pipeline-github-token" > /dev/null 2>&1; then
#     echo "✅ GitHub token found in Secrets Manager"
# else
#     echo "⚠️  GitHub token not found in Secrets Manager"
#     echo "   Create it with:"
#     echo "   aws secretsmanager create-secret --name 'pipeline-github-token' --secret-string '{\"token\":\"your-token\"}'"
# fi

# Check S3 bucket and DynamoDB table
echo ""
echo "🪣 Checking state backend..."

BUCKET_NAME="iac-trial-simple-php-website-terraform-state-$ACCOUNT_ID"
TABLE_NAME="simple-php-website-terraform-locks"

if aws s3 ls "s3://$BUCKET_NAME" > /dev/null 2>&1; then
    echo "✅ S3 bucket $BUCKET_NAME exists"
else
    echo "⚠️  S3 bucket $BUCKET_NAME does not exist"
    echo "   Create it with: aws s3 mb s3://$BUCKET_NAME"
fi

if aws dynamodb describe-table --table-name "$TABLE_NAME" > /dev/null 2>&1; then
    echo "✅ DynamoDB table $TABLE_NAME exists"
else
    echo "⚠️  DynamoDB table $TABLE_NAME does not exist"
    echo "   Create it with the command in DEPLOYMENT.md"
fi

echo ""
echo "🎯 Validation Summary:"
echo "======================"
echo "✅ Infrastructure code is ready for deployment"
echo "✅ Terraform configuration is valid"
echo "✅ AWS credentials are configured"

#if aws secretsmanager get-secret-value --secret-id "pipeline-github-token" > /dev/null 2>&1 && \
if aws s3 ls "s3://$BUCKET_NAME" > /dev/null 2>&1 && \
   aws dynamodb describe-table --table-name "$TABLE_NAME" > /dev/null 2>&1; then
    echo "✅ All prerequisites met - ready to deploy!"
    echo ""
    echo "🚀 To deploy:"
    echo "   cd terraform/environments/dev"
    echo "   terragrunt apply"
else
    echo "⚠️  Some prerequisites are missing (see warnings above)"
    echo "   Complete the setup steps in DEPLOYMENT.md before deploying"
fi

echo ""
echo "📖 For detailed instructions, see DEPLOYMENT.md"