#!/bin/bash

# Install PHP and dependencies
amazon-linux-extras enable php8.0
yum clean metadata
yum install php-{pear,cgi,pdo,common,curl,mbstring,gd,mysqlnd,gettext,bcmath,json,xml,fpm,intl,zip} -y
