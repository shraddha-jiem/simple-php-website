#!/bin/bash

# Get database credentials from AWS Secrets Manager
# This script should be run during deployment to set environment variables

REGION="us-east-1"

echo "Reading configuration from Parameter Store..."

# Get configuration from Parameter Store
PROJECT_NAME=$(aws ssm get-parameter --name "/app/config/project_name" --region "$REGION" --query 'Parameter.Value' --output text 2>/dev/null)
ENVIRONMENT=$(aws ssm get-parameter --name "/app/config/environment" --region "$REGION" --query 'Parameter.Value' --output text 2>/dev/null)

if [ -z "$PROJECT_NAME" ] || [ -z "$ENVIRONMENT" ]; then
    echo "Failed to retrieve configuration from Parameter Store"
    exit 1
fi

echo "Retrieved PROJECT_NAME: $PROJECT_NAME"
echo "Retrieved ENVIRONMENT: $ENVIRONMENT"

SECRET_NAME="${PROJECT_NAME}-${ENVIRONMENT}-db-password-4"

if command -v aws &> /dev/null; then
    echo "Getting database credentials from Secrets Manager..."
    
    # Get the secret value
    SECRET_VALUE=$(aws secretsmanager get-secret-value --secret-id "$SECRET_NAME" --region "$REGION" --query SecretString --output text 2>/dev/null)
    
    if [ $? -eq 0 ] && [ -n "$SECRET_VALUE" ]; then
        # Parse JSON and extract values
        DB_USERNAME=$(echo "$SECRET_VALUE" | python3 -c "import sys, json; print(json.load(sys.stdin)['username'])" 2>/dev/null)
        DB_PASSWORD=$(echo "$SECRET_VALUE" | python3 -c "import sys, json; print(json.load(sys.stdin)['password'])" 2>/dev/null)
        
        # Create environment file for Apache
        cat > /etc/environment << EOF
DB_HOST=$DB_HOST
DB_NAME=$DB_NAME
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
DB_PORT=3306
EOF
        
        # Set environment variables for current session
        export DB_HOST="$DB_HOST"
        export DB_NAME="$DB_NAME" 
        export DB_USERNAME="$DB_USERNAME"
        export DB_PASSWORD="$DB_PASSWORD"
        export DB_PORT="3306"
        
        echo "Database environment variables set successfully"
    else
        echo "Failed to retrieve database credentials from Secrets Manager"
        exit 1
    fi
else
    echo "AWS CLI not found, skipping database credential setup"
fi

echo "Environment setup completed"