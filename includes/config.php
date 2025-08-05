<?php

/**
 * Used to store website configuration information.
 *
 * @var string or null
 */
function config($key = '')
{
    $config = [
        'name' => 'Simple PHP Website',
        'site_url' => '',
        'pretty_uri' => false,
        'nav_menu' => [
            '' => 'Home',
            'about-us' => 'About Us',
            'products' => 'Products',
            'contact' => 'Contact',
            'status' => 'Status',
        ],
        'template_path' => 'template',
        'content_path' => 'content',
        'version' => 'v3.1',
        // Database configuration (will be set via environment variables)
        'database' => [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'name' => getenv('DB_NAME') ?: 'simpleapp',
            'username' => getenv('DB_USERNAME') ?: 'admin',
            'password' => getenv('DB_PASSWORD') ?: '',
            'port' => getenv('DB_PORT') ?: '3306',
        ],
    ];

    return isset($config[$key]) ? $config[$key] : null;
}