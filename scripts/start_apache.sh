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

# Update variables_order in php.ini to include environment variables ("E")
if [ -f /etc/php.ini ]; then
    # Check if variables_order is already set to include "E"
    if grep -q "^variables_order = \".*E.*\"" /etc/php.ini; then
        echo "PHP variables_order already includes environment variables"
    else
        # Replace variables_order line with EGPCS
        sed -i 's/^variables_order = ".*"/variables_order = "EGPCS"/' /etc/php.ini
        echo "Updated PHP variables_order in /etc/php.ini to include environment variables"
    fi
else
    # If php.ini doesn't exist, create a custom config file
    echo 'variables_order = "EGPCS"' > /etc/php.d/variables_order.ini
    echo "Created custom PHP configuration with environment variables enabled"
fi

# Restart Apache to ensure environment variables are loaded
systemctl restart httpd

echo "Apache started successfully"