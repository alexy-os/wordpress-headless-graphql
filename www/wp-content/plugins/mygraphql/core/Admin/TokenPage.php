<?php
namespace MYGraphQL\Admin;

use MYGraphQL\Auth\JWTManager;

/**
 * Admin page for JWT token management
 */
class TokenPage {
    private const MENU_SLUG = 'mygraphql-tokens';
    private const NONCE_ACTION = 'mygraphql_token_action';
    
    private JWTManager $jwtManager;
    
    public function __construct() {
        $this->jwtManager = JWTManager::getInstance();
        $this->init();
    }
    
    private function init(): void {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('admin_init', [$this, 'handleActions']);
    }
    
    public function addMenuPage(): void {
        add_menu_page(
            'GraphQL Tokens',
            'GraphQL Tokens',
            'manage_options',
            self::MENU_SLUG,
            [$this, 'renderPage'],
            'dashicons-admin-network',
            80
        );
    }
    
    public function enqueueAssets(string $hook): void {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }
        
        // Inline styles
        wp_add_inline_style('wp-admin', $this->getStyles());
    }
    
    public function handleActions(): void {
        if (!isset($_POST['mygraphql_action'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_ACTION)) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $action = sanitize_text_field($_POST['mygraphql_action']);
        
        switch ($action) {
            case 'generate_token':
                $this->handleGenerateToken();
                break;
            case 'regenerate_secret':
                $this->handleRegenerateSecret();
                break;
            case 'update_settings':
                $this->handleUpdateSettings();
                break;
        }
    }
    
    private function handleGenerateToken(): void {
        $userId = intval($_POST['user_id'] ?? 0);
        $expiryDays = intval($_POST['expiry_days'] ?? 7);
        
        if (!$userId) {
            $this->redirect('error', 'Please select a user');
            return;
        }
        
        $expirySeconds = $expiryDays * 86400;
        $result = $this->jwtManager->generateToken($userId, $expirySeconds);
        
        if (!$result) {
            $this->redirect('error', 'Failed to generate token. User not found.');
            return;
        }
        
        // Store token temporarily in transient for display
        set_transient('mygraphql_generated_token_' . get_current_user_id(), $result, 300);
        
        $this->redirect('success', 'Token generated successfully!');
    }
    
    private function handleRegenerateSecret(): void {
        $this->jwtManager->regenerateSecret();
        $this->redirect('success', 'Secret regenerated. All existing tokens are now invalid.');
    }
    
    private function handleUpdateSettings(): void {
        $settings = [
            'token_expiry' => intval($_POST['default_expiry'] ?? 7) * 86400,
            'issuer' => sanitize_text_field($_POST['issuer'] ?? get_bloginfo('url')),
        ];
        
        $this->jwtManager->updateSettings($settings);
        $this->redirect('success', 'Settings updated successfully.');
    }
    
    private function redirect(string $type, string $message): void {
        wp_redirect(add_query_arg([
            'page' => self::MENU_SLUG,
            'message' => urlencode($message),
            'type' => $type,
        ], admin_url('admin.php')));
        exit;
    }
    
    public function renderPage(): void {
        $users = get_users(['role__in' => ['administrator', 'editor', 'author']]);
        $settings = $this->jwtManager->getSettings();
        $generatedToken = get_transient('mygraphql_generated_token_' . get_current_user_id());
        
        if ($generatedToken) {
            delete_transient('mygraphql_generated_token_' . get_current_user_id());
        }
        
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
        $messageType = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        ?>
        <div class="wrap mygraphql-tokens">
            <h1>GraphQL JWT Token Manager</h1>
            
            <?php if ($message): ?>
                <div class="notice notice-<?php echo $messageType === 'error' ? 'error' : 'success'; ?> is-dismissible">
                    <p><?php echo esc_html($message); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($generatedToken): ?>
                <div class="mygraphql-token-result">
                    <h2>🎉 Generated Token</h2>
                    <div class="token-info">
                        <table class="form-table">
                            <tr>
                                <th>User</th>
                                <td>
                                    <strong><?php echo esc_html($generatedToken['user_display_name']); ?></strong>
                                    (<?php echo esc_html($generatedToken['user_login']); ?>)
                                    <br>
                                    <small><?php echo esc_html($generatedToken['user_email']); ?></small>
                                </td>
                            </tr>
                            <tr>
                                <th>Roles</th>
                                <td><?php echo esc_html(implode(', ', $generatedToken['user_roles'])); ?></td>
                            </tr>
                            <tr>
                                <th>Issued At</th>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', $generatedToken['issued_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Expires At</th>
                                <td><?php echo esc_html(date('Y-m-d H:i:s', $generatedToken['expires_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Valid For</th>
                                <td><?php echo esc_html($this->formatDuration($generatedToken['expires_in'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="token-display">
                        <label>JWT Token:</label>
                        <div class="token-wrapper">
                            <textarea id="generated-token" readonly rows="4"><?php echo esc_textarea($generatedToken['token']); ?></textarea>
                            <button type="button" class="button button-primary" onclick="copyToken()">
                                📋 Copy Token
                            </button>
                        </div>
                        <p class="description">
                            Use this token in the <code>Authorization</code> header:<br>
                            <code>Authorization: Bearer &lt;token&gt;</code>
                        </p>
                    </div>
                    
                    <div class="token-usage">
                        <h3>Usage Example</h3>
                        <pre><code>// JavaScript fetch example
fetch('<?php echo esc_url(get_site_url()); ?>/graphql', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer <?php echo esc_js(substr($generatedToken['token'], 0, 20)); ?>...'
    },
    body: JSON.stringify({
        query: `mutation {
            updatePost(input: {
                id: "cG9zdDox",
                title: "Updated Title"
            }) {
                post { title }
            }
        }`
    })
});</code></pre>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="mygraphql-sections">
                <!-- Generate Token Section -->
                <div class="mygraphql-section">
                    <h2>Generate New Token</h2>
                    <form method="post" class="token-form">
                        <?php wp_nonce_field(self::NONCE_ACTION); ?>
                        <input type="hidden" name="mygraphql_action" value="generate_token">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="user_id">User</label></th>
                                <td>
                                    <select name="user_id" id="user_id" required>
                                        <option value="">— Select User —</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo esc_attr($user->ID); ?>">
                                                <?php echo esc_html($user->display_name); ?>
                                                (<?php echo esc_html($user->user_login); ?>)
                                                - <?php echo esc_html(implode(', ', $user->roles)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">
                                        Select the user whose permissions the token will inherit.
                                        Only users with Administrator, Editor, or Author roles are shown.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="expiry_days">Token Validity</label></th>
                                <td>
                                    <select name="expiry_days" id="expiry_days">
                                        <option value="1">1 day</option>
                                        <option value="7" selected>7 days</option>
                                        <option value="30">30 days</option>
                                        <option value="90">90 days</option>
                                        <option value="365">1 year</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary button-hero">
                                🔑 Generate Token
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Settings Section -->
                <div class="mygraphql-section">
                    <h2>Settings</h2>
                    <form method="post">
                        <?php wp_nonce_field(self::NONCE_ACTION); ?>
                        <input type="hidden" name="mygraphql_action" value="update_settings">
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="issuer">Token Issuer</label></th>
                                <td>
                                    <input type="text" 
                                           name="issuer" 
                                           id="issuer" 
                                           value="<?php echo esc_attr($settings['issuer']); ?>" 
                                           class="regular-text">
                                    <p class="description">
                                        The issuer claim (iss) in the JWT. Usually your site URL.
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="default_expiry">Default Expiry</label></th>
                                <td>
                                    <input type="number" 
                                           name="default_expiry" 
                                           id="default_expiry" 
                                           value="<?php echo esc_attr($settings['token_expiry'] / 86400); ?>" 
                                           min="1" 
                                           max="365"
                                           class="small-text">
                                    days
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-secondary">
                                Save Settings
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Security Section -->
                <div class="mygraphql-section mygraphql-danger">
                    <h2>⚠️ Security</h2>
                    <form method="post" onsubmit="return confirm('Are you sure? This will invalidate ALL existing tokens!');">
                        <?php wp_nonce_field(self::NONCE_ACTION); ?>
                        <input type="hidden" name="mygraphql_action" value="regenerate_secret">
                        
                        <p>
                            If you suspect your secret key has been compromised, you can regenerate it.
                            <strong>This will immediately invalidate all existing tokens.</strong>
                        </p>
                        
                        <p class="submit">
                            <button type="submit" class="button button-link-delete">
                                🔄 Regenerate Secret Key
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Help Section -->
                <div class="mygraphql-section mygraphql-help">
                    <h2>📚 How It Works</h2>
                    <ol>
                        <li><strong>Generate a token</strong> for a WordPress user with the appropriate permissions.</li>
                        <li><strong>Copy the token</strong> and store it securely in your application's environment variables.</li>
                        <li><strong>Send requests</strong> to the GraphQL endpoint with the <code>Authorization: Bearer &lt;token&gt;</code> header.</li>
                        <li>The token authenticates as the selected user, inheriting their WordPress capabilities.</li>
                    </ol>
                    
                    <h3>Available Mutations</h3>
                    <p>With proper user permissions, you can use WPGraphQL mutations like:</p>
                    <ul>
                        <li><code>createPost</code>, <code>updatePost</code>, <code>deletePost</code></li>
                        <li><code>createPage</code>, <code>updatePage</code>, <code>deletePage</code></li>
                        <li><code>createCategory</code>, <code>updateCategory</code>, <code>deleteCategory</code></li>
                        <li><code>createTag</code>, <code>updateTag</code>, <code>deleteTag</code></li>
                        <li><code>createMediaItem</code>, <code>updateMediaItem</code>, <code>deleteMediaItem</code></li>
                    </ul>
                    
                    <h3>Required Permissions</h3>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Operation</th>
                                <th>Minimum Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Create/Edit own posts</td>
                                <td>Author</td>
                            </tr>
                            <tr>
                                <td>Edit others' posts</td>
                                <td>Editor</td>
                            </tr>
                            <tr>
                                <td>Manage categories/tags</td>
                                <td>Editor</td>
                            </tr>
                            <tr>
                                <td>Manage users, plugins, settings</td>
                                <td>Administrator</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        function copyToken() {
            const textarea = document.getElementById('generated-token');
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            
            navigator.clipboard.writeText(textarea.value).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            }).catch(() => {
                document.execCommand('copy');
                alert('Token copied to clipboard!');
            });
        }
        </script>
        <?php
    }
    
    private function formatDuration(int $seconds): string {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        
        if ($days > 0) {
            return $days . ' day' . ($days > 1 ? 's' : '') . 
                   ($hours > 0 ? ', ' . $hours . ' hour' . ($hours > 1 ? 's' : '') : '');
        }
        
        return $hours . ' hour' . ($hours > 1 ? 's' : '');
    }
    
    private function getStyles(): string {
        return '
            .mygraphql-tokens .mygraphql-sections {
                display: grid;
                gap: 20px;
                margin-top: 20px;
            }
            
            .mygraphql-tokens .mygraphql-section {
                background: #fff;
                padding: 20px 25px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
            }
            
            .mygraphql-tokens .mygraphql-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            
            .mygraphql-tokens .mygraphql-danger {
                border-left: 4px solid #d63638;
            }
            
            .mygraphql-tokens .mygraphql-help {
                background: #f0f6fc;
                border-left: 4px solid #2271b1;
            }
            
            .mygraphql-tokens .mygraphql-help ul,
            .mygraphql-tokens .mygraphql-help ol {
                margin-left: 20px;
            }
            
            .mygraphql-tokens .mygraphql-token-result {
                background: #edfaef;
                border: 1px solid #46b450;
                border-radius: 4px;
                padding: 20px 25px;
                margin-bottom: 20px;
            }
            
            .mygraphql-tokens .mygraphql-token-result h2 {
                margin-top: 0;
                color: #1e7e34;
            }
            
            .mygraphql-tokens .token-display {
                margin: 20px 0;
            }
            
            .mygraphql-tokens .token-display label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
            }
            
            .mygraphql-tokens .token-wrapper {
                display: flex;
                gap: 10px;
                align-items: flex-start;
            }
            
            .mygraphql-tokens .token-wrapper textarea {
                flex: 1;
                font-family: monospace;
                font-size: 12px;
                background: #f6f7f7;
                border: 1px solid #c3c4c7;
                padding: 10px;
                resize: none;
            }
            
            .mygraphql-tokens .token-usage {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #c3ddc6;
            }
            
            .mygraphql-tokens .token-usage pre {
                background: #1e1e1e;
                color: #d4d4d4;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-size: 13px;
            }
            
            .mygraphql-tokens .widefat {
                margin-top: 10px;
            }
        ';
    }
}

