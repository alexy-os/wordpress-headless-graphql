<?php
        
/**
 * Admin access
 * 
 * @package Headless Theme
 */

// Admin and login protection
function protect_admin_access() {
    if (!defined('WP_ADMIN_PROTECTION') || !WP_ADMIN_PROTECTION) {
        return;
    }

    $current_url = $_SERVER['REQUEST_URI'];
    
    if (is_user_logged_in()) {
        return;
    }

    // Attempt to get URLs from the configuration file
    $allowed_urls = [];
    $config_file = get_template_directory() . '/admin/config/settings.php';
    if (file_exists($config_file)) {
        $config = include $config_file;
        if (isset($config['allowed_urls']) && is_array($config['allowed_urls'])) {
            $allowed_urls = $config['allowed_urls'];
        }
    }
    
    // If URLs are not found in the config, try to get them from options
    if (empty($allowed_urls)) {
        $allowed_urls = get_option('allowed_urls');
    }
    
    // If URLs are still not found, use default values
    if (empty($allowed_urls)) {
        $allowed_urls = [
            '/wp-admin/admin-ajax.php',
            '/wp-json/',
            '/console/',
            '/graphql'
        ];
    }

    foreach ($allowed_urls as $allowed_url) {
        if (strpos($current_url, $allowed_url) !== false) {
            return;
        }
    }
    
    if (strpos($current_url, '/wp-admin') !== false || 
        strpos($current_url, 'wp-login.php') !== false) {
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('init', 'protect_admin_access', 1);

// Change the login URL
function custom_login_url($login_url) {
    return home_url(CUSTOM_LOGIN_PATH);
}
add_filter('login_url', 'custom_login_url');

// Block the standard wp-login.php
function disable_wp_login() {
    $current_url = $_SERVER['REQUEST_URI'];
    
    if (strpos($current_url, 'wp-login.php') !== false) {
        // Allow only password reset
        if (isset($_GET['action']) && in_array($_GET['action'], ['resetpass', 'rp'])) {
            return;
        }
        
        wp_redirect(home_url('/'));
        exit;
    }
}
add_action('init', 'disable_wp_login');

// Logout handling
function custom_logout_handler() {
    if (isset($_GET['action']) && $_GET['action'] == 'logout') {
        $user = wp_get_current_user();
        
        wp_clear_auth_cookie();
        
        // Clear all cookies
        if (isset($_COOKIE[LOGGED_IN_COOKIE])) {
            unset($_COOKIE[LOGGED_IN_COOKIE]);
            setcookie(LOGGED_IN_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        if (isset($_COOKIE[AUTH_COOKIE])) {
            unset($_COOKIE[AUTH_COOKIE]);
            setcookie(AUTH_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        if (isset($_COOKIE[SECURE_AUTH_COOKIE])) {
            unset($_COOKIE[SECURE_AUTH_COOKIE]);
            setcookie(SECURE_AUTH_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        
        // Destroy sessions
        $sessions = WP_Session_Tokens::get_instance($user->ID);
        $sessions->destroy_all();
        
        do_action('wp_logout', $user->ID);
        
        wp_redirect(home_url());
        exit();
    }
}
add_action('init', 'custom_logout_handler', 1);

// Redefine the logout URL
function custom_logout_url($logout_url, $redirect = '') {
    return add_query_arg('action', 'logout', home_url());
}
add_filter('logout_url', 'custom_logout_url', 10, 2);