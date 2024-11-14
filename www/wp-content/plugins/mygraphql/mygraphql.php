<?php
/**
 * Plugin Name: My GraphQL Plugin
 * Description: My GraphQL plugin
 * Version: 1.0.2
 * Author: My Name
 */

if (!defined('ABSPATH')) {
    exit;
}

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

// Initialize
add_action('init', function() {
    MYGraphQL\GraphQLManager::getInstance();
});
