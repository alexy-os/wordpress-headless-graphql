#!/bin/bash

echo "========================================"
echo "Starting WordPress Headless CMS"
echo "========================================"

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP not found. Please install PHP and add to PATH"
    echo "Download: https://windows.php.net/download"
    exit 1
fi

# Check if WordPress directory exists
if [ ! -d "www" ]; then
    echo "❌ WordPress directory 'www' not found"
    echo "Run: bash .docs/quick-setup.sh"
    exit 1
fi

# Check if wp-config.php exists
if [ ! -f "www/wp-config.php" ]; then
    echo "⚠️  wp-config.php not found. Creating basic config..."
    cat > www/wp-config.php << 'EOF'
<?php
define('DB_NAME', 'wordpress_headless');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
$table_prefix = 'wp_';
define('WP_HOME', 'http://localhost');
define('WP_SITEURL', 'http://localhost');
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}
require_once(ABSPATH . 'wp-settings.php');
EOF
fi

echo "✅ Starting PHP server on port 19000..."
cd www
php -S 127.0.0.1:19000 &
PHP_PID=$!
cd ..

echo "⏳ Waiting for PHP server to start..."
sleep 3

echo "✅ Starting Nginx server..."
cd nginx
./nginx.exe -c "$(pwd)/conf/nginx.conf"
cd ..

echo ""
echo "========================================"
echo "🎉 Servers started successfully!"
echo "========================================"
echo ""
echo "📍 Access your site at:"
echo "   • Homepage:    http://localhost:8080"
echo "   • GraphQL API: http://localhost:8080/graphql"
echo "   • Admin panel: http://localhost:8080/console"
echo ""
echo "🛑 To stop servers:"
echo "   • Nginx: ./nginx/nginx.exe -c nginx/conf/nginx.conf -s stop"
echo "   • PHP:   kill $PHP_PID"
echo ""
echo "📝 Check logs in: nginx/logs/"
echo ""

# Save PHP PID for later stopping
echo $PHP_PID > .php_pid

# Wait for user interrupt
trap "echo '🛑 Stopping servers...'; ./nginx/nginx.exe -c nginx/conf/nginx.conf -s stop; kill $PHP_PID 2>/dev/null; rm -f .php_pid; exit 0" INT TERM

echo "Press Ctrl+C to stop servers..."
wait