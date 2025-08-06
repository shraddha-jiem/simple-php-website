# EC2 SSM Connectivity Fix

This fix addresses the "SSM Agent is not online" error when trying to connect to EC2 instances via AWS Systems Manager Session Manager.

## Changes Implemented

1. **Added SSM Session Manager IAM Permissions**
   - Added required `ssmmessages:*` permissions to the EC2 instance IAM role
   - These permissions are essential for establishing Session Manager connections

2. **Explicit SSM Agent Installation**
   - Added explicit installation and start commands for the SSM agent in EC2 user_data script
   - Ensures the SSM agent is properly installed and running on instance startup

3. **Added Validation Tools**
   - Created `validate_ssm_agent.sh` script to help diagnose SSM connectivity issues
   - Updated `validate_infrastructure.sh` to check for SSM configuration

4. **Updated Documentation**
   - Added SSM Session Manager setup instructions to DEPLOYMENT.md
   - Added troubleshooting section for SSM connectivity issues

## How to Apply the Fix

1. Apply the Terraform changes:
```bash
cd terraform/environments/dev
terragrunt plan  # Review the changes
terragrunt apply # Apply the changes
```

2. For existing instances, you'll need to:
   - Terminate and let Auto Scaling recreate them with the new configuration, OR
   - Manually install and start the SSM agent:
     ```
     sudo yum install -y amazon-ssm-agent
     sudo systemctl enable amazon-ssm-agent
     sudo systemctl start amazon-ssm-agent
     ```

3. Verify connectivity:
```bash
./scripts/validate_ssm_agent.sh <your-instance-id>
```

## Testing the Connection

After applying the changes, test the SSM connection:

```bash
# List available instances
aws ec2 describe-instances --filters "Name=tag:Environment,Values=dev" \
  --query "Reservations[*].Instances[*].[InstanceId,State.Name,Tags[?Key=='Name'].Value|[0]]" \
  --output table

# Connect to an instance using Session Manager
aws ssm start-session --target i-1234567890abcdef0
```

The connection should now succeed and provide a shell session on the EC2 instance.
