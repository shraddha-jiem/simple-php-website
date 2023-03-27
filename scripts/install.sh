#!/bin/bash

# Install PHP and dependencies
amazon-linux-extras enable php8.0
yum install php php-common php-pear -y
yum install php-{cgi,curl,mbstring,gd,mysqlnd,gettext,json,xml,fpm,intl,zip}
