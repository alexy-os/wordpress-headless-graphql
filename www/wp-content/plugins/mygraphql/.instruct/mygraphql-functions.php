<?php
/**
 * The MYGraphQL plugin implements these functions in an OOP style
 */
/* // Add filter to register post type args
add_filter('register_post_type_args', function($args, $post_type) {
    // Check if args is an array
    if (!is_array($args)) {
        return $args;
    }

    // Enable GraphQL for posts and pages only
    switch ($post_type) {
        case 'post':
            $args['show_in_graphql'] = true;
            $args['graphql_single_name'] = 'post';
            $args['graphql_plural_name'] = 'posts';
            break;
            
        case 'page':
            $args['show_in_graphql'] = true;
            $args['graphql_single_name'] = 'page';
            $args['graphql_plural_name'] = 'pages';
            break;
            
        default:
            // Disable GraphQL for all other post types
            $args['show_in_graphql'] = false;
            break;
    }
    
    return $args;
}, 10, 2);

// Register post meta fields to be available in GraphQL
register_graphql_object_type('PostMetaField', [
    'fields' => [
        'key' => ['type' => 'String'],
        'value' => ['type' => 'String']
    ]
]);

// Add meta fields to both posts and pages
add_action('graphql_register_types', function() {
    // Register field for pages
    register_graphql_field('Page', 'pageFields', [
        'type' => ['list_of' => 'PostMetaField'],
        'description' => 'All custom fields for this page',
        'resolve' => function($post) {
            $meta = get_post_meta($post->ID);
            $result = [];
            
            foreach ($meta as $key => $values) {
                // Skip WordPress internal fields
                if (strpos($key, '_') === 0) continue;
                
                foreach ($values as $value) {
                    $result[] = [
                        'key' => $key,
                        'value' => maybe_unserialize($value)
                    ];
                }
            }
            
            return $result;
        }
    ]);

    // Register same field for posts
    register_graphql_field('Post', 'postFields', [
        'type' => ['list_of' => 'PostMetaField'],
        'description' => 'All custom fields for this post',
        'resolve' => function($post) {
            $meta = get_post_meta($post->ID);
            $result = [];
            
            foreach ($meta as $key => $values) {
                // Skip WordPress internal fields
                if (strpos($key, '_') === 0) continue;
                
                foreach ($values as $value) {
                    $result[] = [
                        'key' => $key,
                        'value' => maybe_unserialize($value)
                    ];
                }
            }
            
            return $result;
        }
    ]);
});

// Images

// Add theme support for post thumbnails and GraphQL fields
add_action('after_setup_theme', function() {
    // Enable featured images support
    add_theme_support('post-thumbnails');
});

// Register custom fields interface and GraphQL fields
register_graphql_object_type('PostMetaField', [
    'fields' => [
        'key' => ['type' => 'String'],
        'value' => ['type' => 'String']
    ]
]);

add_action('graphql_register_types', function() {
    // Register fields for both posts and pages
    $post_types = ['Post', 'Page'];
    
    foreach ($post_types as $type) {
        // Add meta fields
        register_graphql_field($type, 'pageFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'All custom fields',
            'resolve' => function($post) {
                $meta = get_post_meta($post->ID);
                $result = [];
                
                foreach ($meta as $key => $values) {
                    if (strpos($key, '_') === 0) continue;
                    
                    foreach ($values as $value) {
                        $result[] = [
                            'key' => $key,
                            'value' => maybe_unserialize($value)
                        ];
                    }
                }
                
                return $result;
            }
        ]);

        // Add featured image URL field
        register_graphql_field($type, 'featuredImageUrl', [
            'type' => 'String',
            'description' => 'URL of the featured image',
            'resolve' => function($post) {
                $image_id = get_post_thumbnail_id($post->ID);
                return $image_id ? wp_get_attachment_image_url($image_id, 'full') : null;
            }
        ]);
    }
});*/