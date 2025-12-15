<?php
define('DB_NAME', 'wordpress_headless');
define('DB_USER', 'root');
define('DB_PASSWORD', 'DB@!@#');
define('DB_HOST', '127.0.0.1:3306');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
$table_prefix = 'wp_';
define('WP_HOME', 'http://localhost:8080');
define('WP_SITEURL', 'http://localhost:8080');
/* define('WP_ADMIN_PROTECTION', true); */
/* define('DISALLOW_FILE_EDIT', true); */
/* define('DISALLOW_FILE_MODS', true); */
define('CUSTOM_LOGIN_PATH', 'console');
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}
require_once(ABSPATH . 'wp-settings.php');
