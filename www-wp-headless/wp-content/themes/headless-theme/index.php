<?php

/**
 * Index
 * 
 * @package Headless Theme
 */ 

// Minimal index.php
if (!is_admin()) {
    // Set any needed redirect
    wp_redirect(get_rest_url());
    exit;
} 