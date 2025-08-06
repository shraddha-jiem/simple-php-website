#!/bin/bash

# Start Apache service
systemctl start httpd
systemctl enable httpd

# Source environment variables if config exists
if [ -f /var/www/env/db_config.sh ]; then
    source /var/www/env/db_config.sh
    echo "Database environment variables loaded"
else
    echo "Warning: Database configuration file not found"
fi

# Set up PHP timezone to prevent warnings (Asia timezone)
echo 'date.timezone = "Asia/Kolkata"' > /etc/php.d/timezone.ini

# Restart Apache to ensure environment variables are loaded
systemctl restart httpd

echo "Apache started successfully"