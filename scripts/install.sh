#!/bin/bash

# Install Apache/httpd and start it
yum install -y httpd httpd-tools mod_ssl
systemctl start httpd  

# Install PHP and dependencies
amazon-linux-extras enable php8.0
yum install php php-common php-pear -y
yum install php-{cgi,curl,mbstring,gd,mysqlnd,gettext,json,xml,fpm,intl,zip}
