<?php
/**
 * Custom constants
 * 
 * Just add these constants to the wp-config.php file.
 * 
 * @package Headless Theme
 */ 

// Admin protection 
define('WP_ADMIN_PROTECTION', true);

// Custom login path
define('CUSTOM_LOGIN_PATH', 'console');

/**
 * Disable file editing
 * 
 * If you need additional protection for the admin files, then uncomment the following lines.
 * 
 * Be careful. You will not be able to manage themes and plugins from the admin panel.
 * @package Headless Theme
 */

// Disable file editing
//define('DISALLOW_FILE_EDIT', true);
//define('DISALLOW_FILE_MODS', true);