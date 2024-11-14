<?php
namespace MYGraphQL\Interfaces;

class WithMetaFields {
    public static function register(): void {
        try {
            register_graphql_object_type('MediaItem', [
                'fields' => [
                    'id' => ['type' => 'ID'],
                    'sourceUrl' => ['type' => 'String'],
                    'altText' => ['type' => 'String'],
                    'caption' => ['type' => 'String']
                ]
            ]);

            if (!register_graphql_interface_type('WithMetaFields', [
                'fields' => [
                    'metaFields' => [
                        'type' => ['list_of' => 'PostMetaField'],
                        'description' => 'Custom fields for this content'
                    ],
                    'featuredImage' => [
                        'type' => 'MediaItem',
                        'description' => 'Featured image for the object'
                    ]
                ],
                'resolveType' => function($post) {
                    try {
                        if (empty($post->post_type)) {
                            return null;
                        }
                        return \WPGraphQL\TypeRegistry::get_type(ucfirst($post->post_type));
                    } catch (\Exception $e) {
                        return null;
                    }
                }
            ])) {
                error_log('Failed to register WithMetaFields interface');
            }
        } catch (\Exception $e) {
            error_log('Error registering WithMetaFields interface: ' . $e->getMessage());
        }
    }
}