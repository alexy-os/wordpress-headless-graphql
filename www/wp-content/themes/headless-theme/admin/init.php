<?php
namespace HeadlessTheme\Admin;

use HeadlessTheme\Admin\Pages\SettingsPage;

class AdminInit {
    public function __construct() {
        $this->loadDependencies();
        $this->initPages();
    }
    
    private function loadDependencies(): void {
        require_once get_template_directory() . '/admin/interfaces/AdminPageInterface.php';
        require_once get_template_directory() . '/admin/traits/ConfigurationTrait.php';
        require_once get_template_directory() . '/admin/pages/SettingsPage.php';
    }
    
    private function initPages(): void {
        $settingsPage = new SettingsPage();
        $settingsPage->init();
    }
} 