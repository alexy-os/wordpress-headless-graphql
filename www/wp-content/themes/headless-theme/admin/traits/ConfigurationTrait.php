<?php
namespace HeadlessTheme\Admin\Traits;

trait ConfigurationTrait {
    private string $configPath;
    private array $defaultConfig = [
        'pageName' => 'Settings Page',
        'pageDescription' => 'Configure headless WordPress settings',
        'site_redirect' => '',
        'allowed_urls' => [
            '/wp-admin/admin-ajax.php',
            '/wp-json/',
            '/console/',
            '/graphql'
        ]
    ];
    
    protected function initConfig(): void {
        $this->configPath = get_template_directory() . '/admin/config/settings.php';
        
        if (!file_exists($this->configPath)) {
            $this->createDefaultConfig();
        }
    }
    
    protected function updateConfig(array $data): bool {
        $configDir = dirname($this->configPath);
        if (!file_exists($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $data = array_merge($this->getDefaultConfig(), $data);
        
        $config = "<?php\nreturn " . var_export($data, true) . ";\n";
        return file_put_contents($this->configPath, $config) !== false;
    }
    
    protected function getConfig(): array {
        if (file_exists($this->configPath)) {
            $config = include $this->configPath;
            if (is_array($config)) {
                return array_merge($this->getDefaultConfig(), $config);
            }
        }
        return $this->createDefaultConfig();
    }
    
    protected function getDefaultConfig(): array {
        return array_map(function($value) {
            return is_string($value) ? __($value, 'headless-theme') : $value;
        }, $this->defaultConfig);
    }
    
    private function createDefaultConfig(): array {
        $config = $this->getDefaultConfig();
        $this->updateConfig($config);
        return $config;
    }
}