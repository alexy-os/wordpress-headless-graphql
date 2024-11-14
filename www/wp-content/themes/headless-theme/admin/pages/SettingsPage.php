<?php
namespace HeadlessTheme\Admin\Pages;

use HeadlessTheme\Admin\Interfaces\AdminPageInterface;
use HeadlessTheme\Admin\Traits\ConfigurationTrait;

class SettingsPage implements AdminPageInterface {
    use ConfigurationTrait;
    
    private string $pageSlug = 'headless-settings';
    
    public function __construct() {
        $this->initConfig();
    }
    
    public function init(): void {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    public function addMenuPage(): void {
        add_menu_page(
            $this->getPageTitle(),
            __('Headless Settings', 'headless-theme'),
            'manage_options',
            $this->pageSlug,
            [$this, 'render'],
            'dashicons-admin-generic'
        );
    }
    
    public function registerSettings(): void {
        register_setting($this->pageSlug, 'site_redirect', [
            'type' => 'string',
            'sanitize_callback' => function($value) {
                return empty($value) ? '' : rtrim(esc_url_raw($value), '/');
            }
        ]);
        
        register_setting($this->pageSlug, 'allowed_urls', [
            'type' => 'array',
            'sanitize_callback' => function($urls) {
                if (empty($urls)) {
                    return $this->getDefaultAllowedUrls();
                }
                
                $urls_array = is_array($urls) ? $urls : explode("\n", $urls);
                $sanitized = [];
                
                foreach ($urls_array as $url) {
                    if (!empty($url)) {
                        $sanitized[] = rtrim(esc_url_raw($url), '/') . '/';
                    }
                }
                
                return $sanitized;
            }
        ]);
    }
    
    public function enqueueAssets($hook): void {
        if ("toplevel_page_{$this->pageSlug}" !== $hook) {
            return;
        }

        //wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com', [], null, true);
        
        wp_enqueue_style(
            'headless-admin-style',
            get_template_directory_uri() . '/admin/assets/css/admin-style.css',
            [],
            filemtime(get_template_directory() . '/admin/assets/css/admin-style.css')
        );
    }
    
    public function render(): void {
        $site_redirect = get_option('site_redirect', '');
        $allowed_urls = get_option('allowed_urls', $this->getDefaultAllowedUrls());
        $urls_text = is_array($allowed_urls) ? implode("\n", $allowed_urls) : $allowed_urls;
        
        // Update config only when the page is displayed
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
            $this->updateConfigFromOptions();
        }
        
        ?>
        <div class="wrap">
            <div class="max-w-4xl mx-auto py-8">
                <h1 class="text-3xl font-bold mb-6"><?php echo esc_html($this->getPageTitle()); ?></h1>
                <p class="text-gray-600 mb-8"><?php echo esc_html($this->getPageDescription()); ?></p>
                
                <form method="post" action="options.php" class="bg-white rounded-xl shadow-xl p-6">
                    <?php settings_fields($this->pageSlug); ?>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Site Redirect URL', 'headless-theme'); ?>
                        </label>
                        <input 
                            type="url" 
                            name="site_redirect"
                            value="<?php echo esc_attr($site_redirect); ?>"
                            class="w-full px-3 py-2 !bg-gray-100 border border-gray-300 !rounded-lg"
                            pattern="https://.*"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Allowed URLs', 'headless-theme'); ?>
                        </label>
                        <textarea 
                            name="allowed_urls"
                            rows="4"
                            class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                        ><?php echo esc_textarea($urls_text); ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">
                            <?php _e('Enter one URL per line', 'headless-theme'); ?>
                        </p>
                    </div>
                    
                    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary !rounded-lg !py-1 !px-4" value="Save setting"></p>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function getPageTitle(): string {
        $config = $this->getConfig();
        return $config['pageName'];
    }
    
    public function getPageDescription(): string {
        $config = $this->getConfig();
        return $config['pageDescription'];
    }
    
    public function save(): void {
        // Saving is handled through sanitize_callback in registerSettings()
    }
    
    private function getDefaultAllowedUrls(): array {
        return $this->getDefaultConfig()['allowed_urls'];
    }
    
    private function updateConfigFromOptions(): void {
        $config = [
            'pageName' => __('Settings Page', 'headless-theme'),
            'pageDescription' => __('Configure headless WordPress settings', 'headless-theme'),
            'site_redirect' => get_option('site_redirect', ''),
            'allowed_urls' => get_option('allowed_urls', $this->getDefaultAllowedUrls())
        ];
        
        $this->updateConfig($config);
    }
} 