<?php
namespace MYGraphQL\Types;

use MYGraphQL\AbstractGraphQLType;

class PostType extends AbstractGraphQLType {
    protected string $typeName = 'Post';
    
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'post',
            'graphql_plural_name' => 'posts'
        ];
    }
    
    protected function registerFields(): void {
        register_graphql_field($this->typeName, 'postFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'All custom fields for this post',
            'resolve' => function($post) {
                if (empty($post->ID)) {
                    return [];
                }
                
                $meta = get_post_meta($post->ID);
                $result = [];
                
                foreach ($meta as $key => $values) {
                    if (strpos($key, '_') === 0) {
                        continue;
                    }
                    
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
    }
}
