<?php

/**
 * Safety functions
 * 
 * @package Headless Theme
 */ 

// Add security headers
function add_security_headers() {
    if (!is_admin()) {
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Only for HTTPS
        if (is_ssl()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Block embedding the site in frames
        header('Content-Security-Policy: frame-ancestors \'self\'');
    }
}
add_action('send_headers', 'add_security_headers');

/* // Basic setup for headless mode
function headless_theme_setup() {
    // Minimal function support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    
    // Disable unnecessary theme functions
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');
    remove_action('wp_head', 'feed_links', 2);
    remove_action('wp_head', 'feed_links_extra', 3);
    
    // Disable XML-RPC
    add_filter('xmlrpc_enabled', '__return_false');
    
    // Disable theme editor and customizer
    remove_action('admin_menu', '_add_themes_utility_last', 101);
    add_action('admin_init', function() {
        remove_submenu_page('themes.php', 'themes.php');
    }, 999);
}
add_action('after_setup_theme', 'headless_theme_setup');

// Clean up admin menu
function cleanup_admin_menu() {
    remove_menu_page('edit-comments.php');
    remove_menu_page('tools.php');
    remove_submenu_page('themes.php', 'themes.php');
    remove_submenu_page('themes.php', 'theme-editor.php');
    remove_submenu_page('themes.php', 'customize.php');
}
add_action('admin_menu', 'cleanup_admin_menu', 999);

// Disable frontend if not admin or not a GraphQL request
function disable_frontend() {
    if (!is_admin() && !defined('GRAPHQL_REQUEST')) {
        wp_redirect(get_rest_url());
        exit;
    }
}
add_action('template_redirect', 'disable_frontend');

// Add GraphQL support
add_action('init', function() {
    if (class_exists('WPGraphQL')) {
        // Add custom types for GraphQL if needed
    }
}); 

// Disable REST API
// Disable REST API completely
function disable_rest_api() {
    // Allow access only to admin
    if (!is_admin()) {
        // Remove all REST routes
        remove_action('init', 'rest_api_init');
        remove_action('parse_request', 'rest_api_loaded');
        remove_action('auth_cookie_valid', 'rest_cookie_collect_status');
        
        // Disable REST API filters
        remove_filter('rest_authentication_errors', 'rest_cookie_check_errors', 100);
        
        // Remove REST API links from head
        remove_action('wp_head', 'rest_output_link_wp_head', 10);
        remove_action('template_redirect', 'rest_output_link_header', 11);
        
        // Remove REST API from headers
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
    }
}
add_action('init', 'disable_rest_api', 0);

// Block all requests to REST API
function block_rest_api_requests() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-json/') !== false) {
        wp_die('REST API is disabled.', 'REST API Disabled', ['response' => 403]);
    }
}
add_action('init', 'block_rest_api_requests', 0);

// Disable REST API links
function remove_rest_api_links() {
    remove_action('wp_head', 'rest_output_link_wp_head', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
    remove_action('template_redirect', 'rest_output_link_header', 11);
}
add_action('after_setup_theme', 'remove_rest_api_links');

// Disable oembed
function disable_oembed() {
    remove_action('rest_api_init', 'wp_oembed_register_route');
    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'disable_oembed', 9999);

// Return 403 for all REST API requests
function disable_rest_api_endpoints($access) {
    if (!is_admin() && !current_user_can('manage_options')) {
        return new WP_Error('rest_disabled', 'REST API is disabled.', array('status' => 403));
    }
    return $access;
}
add_filter('rest_authentication_errors', 'disable_rest_api_endpoints');
*/