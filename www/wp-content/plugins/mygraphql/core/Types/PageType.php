<?php
namespace MYGraphQL\Types;

use MYGraphQL\AbstractGraphQLType;

class PageType extends AbstractGraphQLType {
    protected string $typeName = 'Page';
    
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'page',
            'graphql_plural_name' => 'pages',
            'allowed_meta_fields' => ['page_template', 'custom_header'],
            'cache_ttl' => 3600
        ];
    }
    
    protected function registerFields(): void {
        register_graphql_field($this->typeName, 'pageFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'All custom fields for this page',
            'resolve' => [$this, 'resolveMetaFields']
        ]);
    }
    
    protected function resolveCustomField($page) {
        return 'custom page value';
    }
}