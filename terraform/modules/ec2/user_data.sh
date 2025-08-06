#!/bin/bash
yum update -y
yum install -y httpd php php-mysqlnd
systemctl start httpd
systemctl enable httpd

# Install CodeDeploy agent
yum install -y ruby wget
cd /home/ec2-user
wget https://aws-codedeploy-us-east-1.s3.us-east-1.amazonaws.com/latest/install
chmod +x ./install
./install auto

# Start CodeDeploy agent
service codedeploy-agent start
chkconfig codedeploy-agent on

# Configure Apache
usermod -a -G apache ec2-user
chown -R ec2-user:apache /var/www
chmod 2775 /var/www
find /var/www -type d -exec chmod 2775 {} \;
find /var/www -type f -exec chmod 0664 {} \;

# Create a simple info page
echo "<?php phpinfo(); ?>" > /var/www/html/info.php

# Set PHP timezone
echo "date.timezone = Asia/Kolkata" >> /etc/php.ini
# Alternative: Create a custom php.ini file in the conf.d directory
echo "date.timezone = Asia/Kolkata" > /etc/php.d/timezone.ini

systemctl restart httpd

# Environment: ${environment}