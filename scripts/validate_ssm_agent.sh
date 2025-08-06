#!/bin/bash
# Script to check SSM Agent status and connectivity

# Check if instance ID is provided
if [ $# -eq 0 ]; then
  echo "Usage: $0 <instance-id>"
  exit 1
fi

INSTANCE_ID=$1

echo "=== Checking instance status ==="
aws ec2 describe-instances --instance-ids $INSTANCE_ID --query 'Reservations[].Instances[].{State:State.Name,InstanceId:InstanceId}' --output table

echo -e "\n=== Checking SSM instance information ==="
aws ssm describe-instance-information --filters "Key=InstanceIds,Values=$INSTANCE_ID" --query 'InstanceInformationList[].{InstanceId:InstanceId,PingStatus:PingStatus,LastPingDateTime:LastPingDateTime,AgentVersion:AgentVersion}' --output table

echo -e "\n=== If the instance is not listed above, the SSM Agent is not registered or has connectivity issues ==="
echo -e "Try the following commands on the instance to troubleshoot:"
echo -e "  sudo systemctl status amazon-ssm-agent"
echo -e "  sudo systemctl restart amazon-ssm-agent"
echo -e "  sudo tail -f /var/log/amazon/ssm/amazon-ssm-agent.log"
