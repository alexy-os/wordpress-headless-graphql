#!/bin/bash

echo "========================================"
echo "Force stopping all servers"
echo "========================================"

echo "Stopping all nginx processes..."
pkill -f nginx
if [ $? -eq 0 ]; then
    echo "✅ Nginx processes stopped"
else
    echo "⚠️  No nginx processes found or could not stop"
fi

echo "Stopping all php processes..."
pkill -f php
if [ $? -eq 0 ]; then
    echo "✅ PHP processes stopped"
else
    echo "⚠️  No PHP processes found or could not stop"
fi

echo ""
echo "Checking remaining processes..."
if pgrep -f nginx > /dev/null; then
    echo "⚠️  Some nginx processes may still be running"
    pgrep -f nginx
else
    echo "✅ No nginx processes remaining"
fi

if pgrep -f php > /dev/null; then
    echo "⚠️  Some PHP processes may still be running"
    pgrep -f php
else
    echo "✅ No PHP processes remaining"
fi

echo ""
echo "Checking port 80..."
if lsof -i :80 > /dev/null 2>&1; then
    echo "⚠️  Port 80 is still in use"
    lsof -i :80
else
    echo "✅ Port 80 is free"
fi

echo ""
echo "Checking port 9000..."
if lsof -i :9000 > /dev/null 2>&1; then
    echo "⚠️  Port 9000 is still in use"
    lsof -i :9000
else
    echo "✅ Port 9000 is free"
fi

echo ""
echo "========================================"
echo "Cleanup completed"
echo "========================================"
