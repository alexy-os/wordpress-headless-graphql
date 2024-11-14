<?php
define('COOKIE_DOMAIN', false);

require_once('../wp-load.php');

// Function to generate a unique hash
function generate_login_hash() {
    $unique = uniqid('login_', true);
    $hash = wp_hash($unique . time() . $_SERVER['REMOTE_ADDR']);
    return substr($hash, 0, 32); // Take the first 32 characters for brevity
}

// Logging access attempts
function log_access_attempt($type, $hash = '') {
    $log_entry = date('Y-m-d H:i:s') . " | " . 
                 $_SERVER['REMOTE_ADDR'] . " | " . 
                 $type . " | " . 
                 $hash . "\n";
    
    file_put_contents(__DIR__ . '/access.log', $log_entry, FILE_APPEND);
}

// IP rate limit check function
function check_ip_rate_limit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_name = 'ip_attempts_' . md5($ip);
    $attempts = get_transient($transient_name);
    
    if ($attempts === false) {
        set_transient($transient_name, 1, HOUR_IN_SECONDS);
    } else {
        if ($attempts >= 5) { // 5 attempts per hour
            log_access_attempt('rate_limit_exceeded', '');
            wp_die('Too many login attempts. Please try again later.', 'Rate Limit Exceeded', ['response' => 429]);
        }
        set_transient($transient_name, $attempts + 1, HOUR_IN_SECONDS);
    }
}

// Check for a valid hash
if (!isset($_GET['login'])) {

    // Check the IP rate limit
    check_ip_rate_limit();

    // Clear all authentication cookies when generating a new hash
    wp_clear_auth_cookie();
    if (isset($_COOKIE[LOGGED_IN_COOKIE])) {
        unset($_COOKIE[LOGGED_IN_COOKIE]);
        setcookie(LOGGED_IN_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
    if (isset($_COOKIE[AUTH_COOKIE])) {
        unset($_COOKIE[AUTH_COOKIE]);
        setcookie(AUTH_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
    if (isset($_COOKIE[SECURE_AUTH_COOKIE])) {
        unset($_COOKIE[SECURE_AUTH_COOKIE]);
        setcookie(SECURE_AUTH_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
    }

    // Generate a new hash
    $hash = generate_login_hash();
    
    // Save the hash in a transient with attempt limit
    set_transient('login_hash_' . $hash, [
        'attempts' => 4,
        'ip' => $_SERVER['REMOTE_ADDR']
    ], 30 * MINUTE_IN_SECONDS);

    log_access_attempt('hash_generated', $hash);
    
    // Show the temporary link
    $login_url = add_query_arg('login', $hash, home_url('/console'));
    
    // New Tailwind styled page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Console Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            border: "hsl(var(--border))",
                            input: "hsl(var(--input))",
                            background: "hsl(var(--background))",
                        }
                    }
                }
            }

            // Dark theme check
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            }
        </script>
    </head>
    <body class="h-full bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
        <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md w-full space-y-8">
                <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Console Access</h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Generate temporary login link</p>
                    </div>
                    
                    <div class="space-y-6">
                        <button 
                            onclick="window.location.href='<?php echo esc_url($login_url); ?>'"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white 
                            bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 
                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Get Login Link
                        </button>
                        
                        <div class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Link will be valid for 30 minutes with 3 login attempts
                        </div>
                    </div>
                </div>
                <!--   Theme switcher -->
                <div class="text-center">
                    <button 
                        onclick="toggleTheme()"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        Toggle theme
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
    </script>
    </body>
    </html>
    <?php
    exit;
}

// Check the hash and attempts
$hash = sanitize_text_field($_GET['login']);
$hash_data = get_transient('login_hash_' . $hash);

if (!$hash_data || $hash_data['ip'] !== $_SERVER['REMOTE_ADDR']) {
    log_access_attempt('invalid_hash', $hash);
    wp_die('Invalid or expired login link.');
}

if ($hash_data['attempts'] <= 0) {
    delete_transient('login_hash_' . $hash);
    log_access_attempt('attempts_exceeded', $hash);
    wp_die('Login attempts exceeded.');
}

// Decrease the number of attempts
$hash_data['attempts']--;
set_transient('login_hash_' . $hash, $hash_data, 30 * MINUTE_IN_SECONDS);

// If this is a POST request, log the login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_access_attempt('login_attempt', $hash);
}

// If the user is already logged in
if (is_user_logged_in()) {
    wp_redirect(admin_url());
    exit;
}

if (isset($_POST['login-form'])) {
    // Nonce verification
    if (!wp_verify_nonce($_POST['security_login'], 'wp_login_form')) {
        wp_die('Security check failed');
    }

    $username = sanitize_user($_POST['log']);
    $password = $_POST['pwd'];
    
    // Get the user
    $user = get_user_by('login', $username);
    
    if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
        // First, clear all old cookies
        wp_clear_auth_cookie();
        
        // Correct password, authenticate
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, false);
        
        // Update session cookies
        if (session_id()) {
            session_regenerate_id(true);
        }
        
        // Log successful login
        log_access_attempt('login_success', $hash);
        
        // Delete the used hash
        delete_transient('login_hash_' . $hash);
        
        // Important: add nocache headers
        nocache_headers();
        
        // Redirect to admin with forced session closure
        wp_safe_redirect(admin_url());
        session_write_close();
        exit();
    } else {
        // Incorrect credentials
        $failed_attempts = get_transient('failed_login_' . $_SERVER['REMOTE_ADDR']);
        set_transient(
            'failed_login_' . $_SERVER['REMOTE_ADDR'], 
            ($failed_attempts ? $failed_attempts + 1 : 1), 
            HOUR_IN_SECONDS
        );
        log_access_attempt('login_failed', $hash);
    }
}
?><!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console</title>
    <script src="https://cdn.tailwindcss.com?3.5"></script>
    <script>
        // Dark theme configuration
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        border: "hsl(var(--border))",
                        input: "hsl(var(--input))",
                        background: "hsl(var(--background))",
                    }
                }
            }
        }

        // Dark theme check
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
</head>
<body class="h-full bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Login form -->
            <div class="bg-white dark:bg-gray-800 p-8 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Console</h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Enter your credentials</p>
                </div>

<form class="space-y-6" method="post">
    <?php wp_nonce_field('wp_login_form', 'security_login'); ?>
    <input type="hidden" name="login-form" value="1">
    <input type="hidden" name="testcookie" value="1" />
    <input type="hidden" name="interim-login" value="0" />
    
    <div>
        <label for="user_login" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Username
        </label>
        <div class="mt-1">
            <input 
                id="user_login" 
                name="log" 
                type="text" 
                autocomplete="username"
                required 
                class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm 
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                dark:bg-gray-700 dark:text-gray-100"
            >
        </div>
    </div>

    <div>
        <label for="user_pass" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            Password
        </label>
        <div class="mt-1">
            <input 
                id="user_pass" 
                name="pwd" 
                type="password" 
                autocomplete="current-password"
                required 
                class="appearance-none block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm 
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                dark:bg-gray-700 dark:text-gray-100"
            >
        </div>
    </div>

    <div>
        <button 
            type="submit"
            name="wp-submit"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white 
            bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 
            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
            Sign in
        </button>
    </div>
<div class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">
                    Remaining attempts: <?php echo $hash_data['attempts'] + 1; ?>
                </div>      
            </form>
            </div>

            <!--   Theme switcher -->
            <div class="text-center">
                <button 
                    onclick="toggleTheme()"
                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100"
                >
                    Toggle theme
                </button>
            </div>
        </div>
    </div>

    <script>
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }
    </script>
</body>
</html>
