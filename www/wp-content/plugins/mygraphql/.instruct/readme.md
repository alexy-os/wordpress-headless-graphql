# MYGraphQL Plugin Documentation

## Overview
MYGraphQL is an Object-Oriented WordPress plugin that extends WPGraphQL functionality by providing a structured way to expose post types, meta fields, and featured images through GraphQL.

## Core Components

### 1. Abstract Base Class
The `AbstractGraphQLType` class serves as the foundation for all GraphQL types:

```1:126:core/AbstractGraphQLType.php
<?php
namespace MYGraphQL;

abstract class AbstractGraphQLType {
    protected array $config;
    protected string $typeName;
    protected array $metaFields = [];
    
    abstract protected function getTypeConfig(): array;
    abstract protected function registerFields(): void;
    
    public function __construct(array $config = []) {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->init();
    }
    
    protected function getDefaultConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => '',
            'graphql_plural_name' => '',
            'allowed_meta_fields' => [],
            'interfaces' => ['WithMetaFields'],
            'cache_ttl' => 3600
        ];
    }
    
    protected function init(): void {
        add_filter('register_post_type_args', [$this, 'modifyPostTypeArgs'], 10, 2);
        add_action('graphql_register_types', [$this, 'registerGraphQLType']);
    }
    
    protected function resolveMetaFields($post): array {
        $cache_key = "graphql_meta_{$this->typeName}_{$post->ID}";
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $meta = get_post_meta($post->ID);
        $result = [];
        
        foreach ($meta as $key => $values) {
            if ($this->shouldSkipMetaField($key)) {
                continue;
            }
            
            foreach ($values as $value) {
                $result[] = [
                    'key' => $key,
                    'value' => maybe_unserialize($value)
                ];
            }
        }
        
        wp_cache_set($cache_key, $result, '', $this->config['cache_ttl']);
        return $result;
    }
    
    protected function shouldSkipMetaField(string $key): bool {
        if (strpos($key, '_') === 0) {
            return true;
        }
        
        if (!empty($this->config['allowed_meta_fields'])) {
            return !in_array($key, $this->config['allowed_meta_fields']);
        }
        
        return false;
    }
    
    public function modifyPostTypeArgs(array $args, string $post_type): array {
        if ($post_type === strtolower($this->typeName)) {
            $args['show_in_graphql'] = $this->config['show_in_graphql'];
            $args['graphql_single_name'] = $this->config['graphql_single_name'];
            $args['graphql_plural_name'] = $this->config['graphql_plural_name'];
        }
        return $args;
    }
    
    public function registerGraphQLType(): void {
        register_graphql_object_type($this->typeName, [
            'description' => "Type for {$this->typeName}",
            'interfaces' => $this->config['interfaces'],
            'fields' => $this->getFields()
        ]);
        
        $this->registerFields();
    }
    
    protected function getFields(): array {
        return [
            'id' => [
                'type' => 'ID',
                'description' => 'The ID of the object'
            ],
            'title' => [
                'type' => 'String',
                'description' => 'The title of the object',
                'resolve' => function($post) {
                    return get_the_title($post->ID);
                }
            ],
            'featuredImage' => [
                'type' => 'MediaItem',
                'description' => 'Featured image for the object',
                'resolve' => function($post) {
                    try {
                        if (empty($post->ID)) {
                            return null;
                        }
                        $image_id = get_post_thumbnail_id($post->ID);
                        if (!$image_id) {
                            return null;
                        }
                        return \WPGraphQL\Data\DataSource::resolve_post_object($image_id, 'attachment');
                    } catch (\Exception $e) {
                        error_log('Error resolving featured image: ' . $e->getMessage());
                        return null;
                    }
                }
            ]
        ];
    }
}
```


Key features:
- Configurable caching for meta fields
- Automatic registration of GraphQL types and fields
- Built-in featured image support
- Meta fields filtering system

### 2. Type Management
The `GraphQLManager` implements a singleton pattern for managing GraphQL types:

```1:71:core/GraphQLManager.php
<?php
namespace MYGraphQL;

class GraphQLManager {
    private array $types = [];
    private static ?self $instance = null;
    
    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->registerMetaFieldType();
        $this->registerTypes();
        $this->registerInterfaces();
    }
    
    private function registerTypes(): void {
        try {
            $config = $this->getTypesConfig();
            
            foreach ($config as $typeClass => $typeConfig) {
                if ($this->shouldRegisterType($typeConfig)) {
                    try {
                        $this->types[] = new $typeClass($typeConfig);
                    } catch (\Exception $e) {
                        error_log("Failed to register type {$typeClass}: " . $e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('Error in registerTypes: ' . $e->getMessage());
        }
    }
    
    private function getTypesConfig(): array {
        // It can be loaded from a configuration file or database
        return [
            Types\PostType::class => [
                'enabled' => true,
            ],
            Types\PageType::class => [
                'enabled' => true,
            ],
        ];
    }
    
    private function shouldRegisterType(array $config): bool {
        return $config['enabled'] ?? false;
    }
    
    private function registerInterfaces(): void {
        add_action('graphql_register_types', function() {
            Interfaces\WithMetaFields::register();
        });
    }
    
    private function registerMetaFieldType(): void {
        add_action('graphql_register_types', function() {
            register_graphql_object_type('PostMetaField', [
                'fields' => [
                    'key' => ['type' => 'String'],
                    'value' => ['type' => 'String']
                ]
            ]);
        });
    }
}
```


### 3. Interface Implementation
The `WithMetaFields` interface provides a consistent structure for types with meta fields and featured images:

```1:44:core/Interfaces/WithMetaFields.php
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
```


## Usage

### 1. Creating Custom Types
Extend `AbstractGraphQLType` to create new types:

```php
class CustomType extends AbstractGraphQLType {
    protected string $typeName = 'CustomType';
    
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'customType',
            'graphql_plural_name' => 'customTypes',
            'allowed_meta_fields' => ['field1', 'field2'],
            'cache_ttl' => 3600
        ];
    }
    
    protected function registerFields(): void {
        register_graphql_field($this->typeName, 'customFields', [
            'type' => ['list_of' => 'PostMetaField'],
            'description' => 'Custom fields',
            'resolve' => [$this, 'resolveMetaFields']
        ]);
    }
}
```

### 2. GraphQL Queries
Example queries for retrieving data:

```graphql
{
  pages(first: 10) {
    nodes {
      id
      title
      featuredImage {
        node {
          id
          sourceUrl
          altText
          caption
        }
      }
      pageFields {
        key
        value
      }
    }
  }
}
```

### 3. All query pages and posts

```graphql
{
  pages(first: 10) {
    nodes {
      id
      pageId
      date
      title
      content
      featuredImage {
        node {
          id
          sourceUrl
          altText
          caption
        }
      }
      pageFields {
        key
        value
      }
    }
  }
  posts(first: 10) {
    nodes {
      id
      postId
      date
      title
      content
      excerpt
      categories {
        nodes {
          id
          categoryId
          name
        }
      }
      tags {
        nodes {
          id
          tagId
          name
        }
      }
      featuredImage {
        node {
          id
          sourceUrl
          altText
          caption
        }
      }
      postFields {
        key
        value
      }
    }
  }
}
```

## Configuration

### 1. Type Configuration
Configure types in `GraphQLManager::getTypesConfig()`:

```39:49:core/GraphQLManager.php
    private function getTypesConfig(): array {
        // It can be loaded from a configuration file or database
        return [
            Types\PostType::class => [
                'enabled' => true,
            ],
            Types\PageType::class => [
                'enabled' => true,
            ],
        ];
    }
```


### 2. Meta Fields
Control meta field exposure through the `allowed_meta_fields` configuration:

```9:17:core/Types/PostType.php
    protected function getTypeConfig(): array {
        return [
            'show_in_graphql' => true,
            'graphql_single_name' => 'post',
            'graphql_plural_name' => 'posts',
            'allowed_meta_fields' => ['author_bio', 'featured_video'],
            'cache_ttl' => 1800
        ];
    }
```


## Features

1. **Caching System**
   - Built-in caching for meta fields
   - Configurable TTL per type
   - Automatic cache invalidation

2. **Meta Fields Management**
   - Filtered exposure of meta fields
   - Serialization handling
   - Protected fields handling

3. **Featured Images**
   - Full integration with WordPress media system
   - Support for alt text and captions
   - Null safety handling

4. **Type Registration**
   - Automatic registration of types and fields
   - Interface implementation
   - Error handling and logging

## Best Practices

1. **Type Definition**
   - Use meaningful type names
   - Implement proper field resolvers
   - Configure appropriate cache TTLs

2. **Meta Fields**
   - Explicitly define allowed meta fields
   - Handle serialized data appropriately
   - Implement field-specific resolvers when needed

3. **Error Handling**
   - Implement try-catch blocks in resolvers
   - Log errors appropriately
   - Provide fallback values

## Security Considerations

1. **Meta Fields**
   - Only expose necessary meta fields
   - Filter sensitive data
   - Validate meta field values

2. **Caching**
   - Set appropriate cache TTLs
   - Clear cache on relevant updates
   - Handle user-specific data appropriately