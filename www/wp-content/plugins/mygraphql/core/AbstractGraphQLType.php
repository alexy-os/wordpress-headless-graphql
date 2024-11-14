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
        if (empty($post->ID)) {
            error_log("Post ID is empty in resolveMetaFields");
            return [];
        }

        error_log("Resolving meta fields for post ID: " . $post->ID);
        $meta = get_post_meta($post->ID);
        error_log("Raw meta data: " . print_r($meta, true));
        
        $result = [];
        
        foreach ($meta as $key => $values) {
            error_log("Processing meta key: {$key}");
            if ($this->shouldSkipMetaField($key)) {
                error_log("Skipping meta key: {$key}");
                continue;
            }
            
            foreach ($values as $value) {
                $result[] = [
                    'key' => $key,
                    'value' => maybe_unserialize($value)
                ];
            }
        }
        
        error_log("Final result: " . print_r($result, true));
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
           /* 'title' => [
                'type' => 'String',
                'description' => 'The title of the object',
                'resolve' => function($post) {
                    return get_the_title($post->ID);
                }
            ],*/
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