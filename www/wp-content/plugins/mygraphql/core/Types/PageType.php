<?php
namespace MYGraphQL\Types;

use MYGraphQL\AbstractGraphQLType;

class PageType extends AbstractGraphQLType {
    protected string $typeName = 'Page';
    
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'page',
            'graphql_plural_name' => 'pages'
        ];
    }
    
    protected function registerFields(): void {
        register_graphql_field($this->typeName, 'pageFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'All custom fields for this page',
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