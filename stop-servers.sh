#!/bin/bash

echo "========================================"
echo "Stopping WordPress Headless CMS"
echo "========================================"

echo "🛑 Stopping Nginx server..."
./nginx/nginx.exe -c nginx/conf/nginx.conf -s stop

echo "🛑 Stopping PHP server..."
if [ -f ".php_pid" ]; then
    PHP_PID=$(cat .php_pid)
    kill $PHP_PID 2>/dev/null && echo "✅ PHP server stopped" || echo "⚠️  PHP server was not running"
    rm -f .php_pid
else
    # Try to kill any php processes on port 19000
    PHP_PIDS=$(lsof -ti:19000 2>/dev/null || netstat -ano 2>/dev/null | grep :19000 | grep LISTENING | awk '{print $5}' | head -1)
    if [ ! -z "$PHP_PIDS" ]; then
        kill $PHP_PIDS 2>/dev/null && echo "✅ PHP server stopped" || echo "⚠️  Could not stop PHP server"
    else
        echo "⚠️  PHP server not found on port 19000"
    fi
fi

echo "✅ All servers stopped"
echo ""
echo "💡 To restart: bash start-servers.sh"
echo ""

# Clean up any remaining processes
pkill -f "php -S 127.0.0.1:19000" 2>/dev/null || true