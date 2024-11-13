<?php

/** 
 * GraphQL post types
 * 
 * Correct way to disable post types in GraphQL
 * 
 * @package Headless Theme
 */ 

// Add filter to register post type args
add_filter('register_post_type_args', function($args, $post_type) {
    // Check the post type
    switch ($post_type) {
        case 'post':
            // For posts, we keep access
            $args['show_in_graphql'] = true;
            $args['graphql_single_name'] = 'post';
            $args['graphql_plural_name'] = 'posts';
            break;
            
        default:
            // For all other types, we disable
            $args['show_in_graphql'] = false;
            break;
    }
    
    return $args;
}, 10, 2);

/**
 * Admin access
 * 
 * Restrict access to the admin area and redirect to the REST API.
 * 
 * @package Headless Theme
 */

// Admin access
require_once get_template_directory() . '/admin-access.php';

// Safety functions
require_once get_template_directory() . '/safety-functions.php';