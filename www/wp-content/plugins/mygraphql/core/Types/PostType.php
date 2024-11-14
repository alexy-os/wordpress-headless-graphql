<?php
namespace MYGraphQL\Types;

use MYGraphQL\AbstractGraphQLType;

class PostType extends AbstractGraphQLType {
    protected string $typeName = 'Post';
    
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'post',
            'graphql_plural_name' => 'posts',
            'allowed_meta_fields' => ['author_bio', 'featured_video'],
            'cache_ttl' => 1800
        ];
    }
    
    protected function registerFields(): void {
        register_graphql_field($this->typeName, 'postFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'All custom fields for this post',
            'resolve' => [$this, 'resolveMetaFields']
        ]);
    }
    
    protected function resolveCustomField($post) {
        return 'custom value';
    }
}
