#!/bin/bash

# Start Apache service
systemctl start httpd
systemctl enable httpd

# Restart Apache to ensure environment variables are loaded
systemctl restart httpd

echo "Apache started successfully"