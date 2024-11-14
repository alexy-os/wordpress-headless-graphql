<?php

/**
 * Index
 * 
 * @package Headless Theme
 */ 

if (!is_admin()) {
    $redirect_url = '';
    
    // Attempt to get the URL from the configuration file
    $config_file = get_template_directory() . '/admin/config/settings.php';
    if (file_exists($config_file)) {
        $config = include $config_file;
        if (isset($config['site_redirect']) && !empty($config['site_redirect'])) {
            $redirect_url = $config['site_redirect'];
        }
    }
    
    // If the URL is not found in the config, try to get it from options
    if (empty($redirect_url)) {
        $redirect_url = get_option('site_redirect');
    }
    
    // If the URL is still not found, use site_url()
    if (empty($redirect_url)) {
        $redirect_url = site_url();
    }
    
    wp_redirect($redirect_url);
    exit;
} 