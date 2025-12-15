<?php
/**
 * Plugin Name: My GraphQL Plugin
 * Description: Custom GraphQL types, meta fields, and JWT authentication for WPGraphQL
 * Version: 1.1.0
 * Author: My Name
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MYGRAPHQL_VERSION', '1.1.0');
define('MYGRAPHQL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MYGRAPHQL_PLUGIN_URL', plugin_dir_url(__FILE__));

// Class autoloading
spl_autoload_register(function ($class) {
    // Check if the class belongs to our namespace
    if (strpos($class, 'MYGraphQL\\') !== 0) {
        return;
    }
    
    // Convert namespace to file path
    $class_path = str_replace('MYGraphQL\\', '', $class);
    $class_path = str_replace('\\', '/', $class_path);
    $file = __DIR__ . '/core/' . $class_path . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize on 'init' hook
add_action('init', function() {
    MYGraphQL\GraphQLManager::getInstance();
});

// Activation hook - ensure options exist
register_activation_hook(__FILE__, function() {
    // Trigger secret generation on activation
    if (class_exists('MYGraphQL\\Auth\\JWTManager')) {
        MYGraphQL\Auth\JWTManager::getInstance();
    }
});
