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