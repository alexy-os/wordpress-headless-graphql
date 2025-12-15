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
        if ($attempts >= 50) { // 5 attempts per hour
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
        <style>*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/* ! tailwindcss v3.4.17 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.mb-8{margin-bottom:2rem}.mt-2{margin-top:0.5rem}.flex{display:flex}.h-full{height:100%}.min-h-screen{min-height:100vh}.w-full{width:100%}.max-w-md{max-width:28rem}.items-center{align-items:center}.justify-center{justify-content:center}.space-y-6 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1.5rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1.5rem * var(--tw-space-y-reverse))}.space-y-8 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(2rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(2rem * var(--tw-space-y-reverse))}.rounded-lg{border-radius:0.5rem}.rounded-md{border-radius:0.375rem}.border{border-width:1px}.border-gray-200{--tw-border-opacity:1;border-color:rgb(229 231 235 / var(--tw-border-opacity, 1))}.border-transparent{border-color:transparent}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-gradient-to-br{background-image:linear-gradient(to bottom right, var(--tw-gradient-stops))}.bg-gradient-to-r{background-image:linear-gradient(to right, var(--tw-gradient-stops))}.from-blue-500{--tw-gradient-from:#3b82f6 var(--tw-gradient-from-position);--tw-gradient-to:rgb(59 130 246 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-gray-50{--tw-gradient-from:#f9fafb var(--tw-gradient-from-position);--tw-gradient-to:rgb(249 250 251 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.to-blue-600{--tw-gradient-to:#2563eb var(--tw-gradient-to-position)}.to-gray-100{--tw-gradient-to:#f3f4f6 var(--tw-gradient-to-position)}.p-8{padding:2rem}.px-4{padding-left:1rem;padding-right:1rem}.py-12{padding-top:3rem;padding-bottom:3rem}.py-2{padding-top:0.5rem;padding-bottom:0.5rem}.text-center{text-align:center}.text-2xl{font-size:1.5rem;line-height:2rem}.text-sm{font-size:0.875rem;line-height:1.25rem}.font-medium{font-weight:500}.font-semibold{font-weight:600}.text-gray-600{--tw-text-opacity:1;color:rgb(75 85 99 / var(--tw-text-opacity, 1))}.text-gray-900{--tw-text-opacity:1;color:rgb(17 24 39 / var(--tw-text-opacity, 1))}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.shadow-lg{--tw-shadow:0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 2px 0 rgb(0 0 0 / 0.05);--tw-shadow-colored:0 1px 2px 0 var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.hover\:from-blue-600:hover{--tw-gradient-from:#2563eb var(--tw-gradient-from-position);--tw-gradient-to:rgb(37 99 235 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.hover\:to-blue-700:hover{--tw-gradient-to:#1d4ed8 var(--tw-gradient-to-position)}.hover\:text-gray-900:hover{--tw-text-opacity:1;color:rgb(17 24 39 / var(--tw-text-opacity, 1))}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus\:ring-2:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.focus\:ring-blue-500:focus{--tw-ring-opacity:1;--tw-ring-color:rgb(59 130 246 / var(--tw-ring-opacity, 1))}.focus\:ring-offset-2:focus{--tw-ring-offset-width:2px}.dark\:border-gray-700:is(.dark *){--tw-border-opacity:1;border-color:rgb(55 65 81 / var(--tw-border-opacity, 1))}.dark\:bg-gray-800:is(.dark *){--tw-bg-opacity:1;background-color:rgb(31 41 55 / var(--tw-bg-opacity, 1))}.dark\:from-gray-900:is(.dark *){--tw-gradient-from:#111827 var(--tw-gradient-from-position);--tw-gradient-to:rgb(17 24 39 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.dark\:to-gray-800:is(.dark *){--tw-gradient-to:#1f2937 var(--tw-gradient-to-position)}.dark\:text-gray-100:is(.dark *){--tw-text-opacity:1;color:rgb(243 244 246 / var(--tw-text-opacity, 1))}.dark\:text-gray-400:is(.dark *){--tw-text-opacity:1;color:rgb(156 163 175 / var(--tw-text-opacity, 1))}.dark\:hover\:text-gray-100:hover:is(.dark *){--tw-text-opacity:1;color:rgb(243 244 246 / var(--tw-text-opacity, 1))}@media (min-width: 640px){.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}}@media (min-width: 1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}</style>
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
        function initTheme() {
            // Check for saved theme preference or default to system preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', initTheme);
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
    <style>*, ::before, ::after{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }::backdrop{--tw-border-spacing-x:0;--tw-border-spacing-y:0;--tw-translate-x:0;--tw-translate-y:0;--tw-rotate:0;--tw-skew-x:0;--tw-skew-y:0;--tw-scale-x:1;--tw-scale-y:1;--tw-pan-x: ;--tw-pan-y: ;--tw-pinch-zoom: ;--tw-scroll-snap-strictness:proximity;--tw-gradient-from-position: ;--tw-gradient-via-position: ;--tw-gradient-to-position: ;--tw-ordinal: ;--tw-slashed-zero: ;--tw-numeric-figure: ;--tw-numeric-spacing: ;--tw-numeric-fraction: ;--tw-ring-inset: ;--tw-ring-offset-width:0px;--tw-ring-offset-color:#fff;--tw-ring-color:rgb(59 130 246 / 0.5);--tw-ring-offset-shadow:0 0 #0000;--tw-ring-shadow:0 0 #0000;--tw-shadow:0 0 #0000;--tw-shadow-colored:0 0 #0000;--tw-blur: ;--tw-brightness: ;--tw-contrast: ;--tw-grayscale: ;--tw-hue-rotate: ;--tw-invert: ;--tw-saturate: ;--tw-sepia: ;--tw-drop-shadow: ;--tw-backdrop-blur: ;--tw-backdrop-brightness: ;--tw-backdrop-contrast: ;--tw-backdrop-grayscale: ;--tw-backdrop-hue-rotate: ;--tw-backdrop-invert: ;--tw-backdrop-opacity: ;--tw-backdrop-saturate: ;--tw-backdrop-sepia: ;--tw-contain-size: ;--tw-contain-layout: ;--tw-contain-paint: ;--tw-contain-style: }/* ! tailwindcss v3.4.17 | MIT License | https://tailwindcss.com */*,::after,::before{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}::after,::before{--tw-content:''}:host,html{line-height:1.5;-webkit-text-size-adjust:100%;-moz-tab-size:4;tab-size:4;font-family:ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";font-feature-settings:normal;font-variation-settings:normal;-webkit-tap-highlight-color:transparent}body{margin:0;line-height:inherit}hr{height:0;color:inherit;border-top-width:1px}abbr:where([title]){-webkit-text-decoration:underline dotted;text-decoration:underline dotted}h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}a{color:inherit;text-decoration:inherit}b,strong{font-weight:bolder}code,kbd,pre,samp{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;font-feature-settings:normal;font-variation-settings:normal;font-size:1em}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}table{text-indent:0;border-color:inherit;border-collapse:collapse}button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}button,select{text-transform:none}button,input:where([type=button]),input:where([type=reset]),input:where([type=submit]){-webkit-appearance:button;background-color:transparent;background-image:none}:-moz-focusring{outline:auto}:-moz-ui-invalid{box-shadow:none}progress{vertical-align:baseline}::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}::-webkit-search-decoration{-webkit-appearance:none}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}summary{display:list-item}blockquote,dd,dl,figure,h1,h2,h3,h4,h5,h6,hr,p,pre{margin:0}fieldset{margin:0;padding:0}legend{padding:0}menu,ol,ul{list-style:none;margin:0;padding:0}dialog{padding:0}textarea{resize:vertical}input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}[role=button],button{cursor:pointer}:disabled{cursor:default}audio,canvas,embed,iframe,img,object,svg,video{display:block;vertical-align:middle}img,video{max-width:100%;height:auto}[hidden]:where(:not([hidden=until-found])){display:none}.mb-4{margin-bottom:1rem}.mb-8{margin-bottom:2rem}.mt-1{margin-top:0.25rem}.mt-2{margin-top:0.5rem}.block{display:block}.flex{display:flex}.h-full{height:100%}.min-h-full{min-height:100%}.w-full{width:100%}.max-w-md{max-width:28rem}.appearance-none{-webkit-appearance:none;appearance:none}.items-center{align-items:center}.justify-center{justify-content:center}.space-y-6 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(1.5rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(1.5rem * var(--tw-space-y-reverse))}.space-y-8 > :not([hidden]) ~ :not([hidden]){--tw-space-y-reverse:0;margin-top:calc(2rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(2rem * var(--tw-space-y-reverse))}.rounded-lg{border-radius:0.5rem}.rounded-md{border-radius:0.375rem}.border{border-width:1px}.border-gray-200{--tw-border-opacity:1;border-color:rgb(229 231 235 / var(--tw-border-opacity, 1))}.border-gray-300{--tw-border-opacity:1;border-color:rgb(209 213 219 / var(--tw-border-opacity, 1))}.border-transparent{border-color:transparent}.bg-white{--tw-bg-opacity:1;background-color:rgb(255 255 255 / var(--tw-bg-opacity, 1))}.bg-gradient-to-br{background-image:linear-gradient(to bottom right, var(--tw-gradient-stops))}.bg-gradient-to-r{background-image:linear-gradient(to right, var(--tw-gradient-stops))}.from-gray-50{--tw-gradient-from:#f9fafb var(--tw-gradient-from-position);--tw-gradient-to:rgb(249 250 251 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.from-blue-500{--tw-gradient-from:#3b82f6 var(--tw-gradient-from-position);--tw-gradient-to:rgb(59 130 246 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.to-gray-100{--tw-gradient-to:#f3f4f6 var(--tw-gradient-to-position)}.to-blue-600{--tw-gradient-to:#2563eb var(--tw-gradient-to-position)}.p-8{padding:2rem}.px-3{padding-left:0.75rem;padding-right:0.75rem}.px-4{padding-left:1rem;padding-right:1rem}.py-12{padding-top:3rem;padding-bottom:3rem}.py-2{padding-top:0.5rem;padding-bottom:0.5rem}.text-center{text-align:center}.text-2xl{font-size:1.5rem;line-height:2rem}.text-sm{font-size:0.875rem;line-height:1.25rem}.font-medium{font-weight:500}.font-semibold{font-weight:600}.text-gray-600{--tw-text-opacity:1;color:rgb(75 85 99 / var(--tw-text-opacity, 1))}.text-gray-700{--tw-text-opacity:1;color:rgb(55 65 81 / var(--tw-text-opacity, 1))}.text-gray-900{--tw-text-opacity:1;color:rgb(17 24 39 / var(--tw-text-opacity, 1))}.text-white{--tw-text-opacity:1;color:rgb(255 255 255 / var(--tw-text-opacity, 1))}.shadow-lg{--tw-shadow:0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);--tw-shadow-colored:0 10px 15px -3px var(--tw-shadow-color), 0 4px 6px -4px var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.shadow-sm{--tw-shadow:0 1px 2px 0 rgb(0 0 0 / 0.05);--tw-shadow-colored:0 1px 2px 0 var(--tw-shadow-color);box-shadow:var(--tw-ring-offset-shadow, 0 0 #0000), var(--tw-ring-shadow, 0 0 #0000), var(--tw-shadow)}.hover\:from-blue-600:hover{--tw-gradient-from:#2563eb var(--tw-gradient-from-position);--tw-gradient-to:rgb(37 99 235 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.hover\:to-blue-700:hover{--tw-gradient-to:#1d4ed8 var(--tw-gradient-to-position)}.hover\:text-gray-900:hover{--tw-text-opacity:1;color:rgb(17 24 39 / var(--tw-text-opacity, 1))}.focus\:border-transparent:focus{border-color:transparent}.focus\:outline-none:focus{outline:2px solid transparent;outline-offset:2px}.focus\:ring-2:focus{--tw-ring-offset-shadow:var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);--tw-ring-shadow:var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);box-shadow:var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000)}.focus\:ring-blue-500:focus{--tw-ring-opacity:1;--tw-ring-color:rgb(59 130 246 / var(--tw-ring-opacity, 1))}.focus\:ring-offset-2:focus{--tw-ring-offset-width:2px}.dark\:border-gray-600:is(.dark *){--tw-border-opacity:1;border-color:rgb(75 85 99 / var(--tw-border-opacity, 1))}.dark\:border-gray-700:is(.dark *){--tw-border-opacity:1;border-color:rgb(55 65 81 / var(--tw-border-opacity, 1))}.dark\:bg-gray-700:is(.dark *){--tw-bg-opacity:1;background-color:rgb(55 65 81 / var(--tw-bg-opacity, 1))}.dark\:bg-gray-800:is(.dark *){--tw-bg-opacity:1;background-color:rgb(31 41 55 / var(--tw-bg-opacity, 1))}.dark\:from-gray-900:is(.dark *){--tw-gradient-from:#111827 var(--tw-gradient-from-position);--tw-gradient-to:rgb(17 24 39 / 0) var(--tw-gradient-to-position);--tw-gradient-stops:var(--tw-gradient-from), var(--tw-gradient-to)}.dark\:to-gray-800:is(.dark *){--tw-gradient-to:#1f2937 var(--tw-gradient-to-position)}.dark\:text-gray-100:is(.dark *){--tw-text-opacity:1;color:rgb(243 244 246 / var(--tw-text-opacity, 1))}.dark\:text-gray-300:is(.dark *){--tw-text-opacity:1;color:rgb(209 213 219 / var(--tw-text-opacity, 1))}.dark\:text-gray-400:is(.dark *){--tw-text-opacity:1;color:rgb(156 163 175 / var(--tw-text-opacity, 1))}.dark\:hover\:text-gray-100:hover:is(.dark *){--tw-text-opacity:1;color:rgb(243 244 246 / var(--tw-text-opacity, 1))}@media (min-width: 640px){.sm\:px-6{padding-left:1.5rem;padding-right:1.5rem}}@media (min-width: 1024px){.lg\:px-8{padding-left:2rem;padding-right:2rem}}</style>
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
        function initTheme() {
            // Check for saved theme preference or default to system preference
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark')
                localStorage.theme = 'light'
            } else {
                document.documentElement.classList.add('dark')
                localStorage.theme = 'dark'
            }
        }

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', initTheme);
    </script>
</body>
</html>
