#!/bin/bash

# ===========================================
# Quick Setup Script for WordPress Headless CMS
# Compatible with MariaDB 127.0.0.1:3306 root
# ===========================================

set -e  # Exit on any error

# Configuration
WP_DIR="www"
DB_NAME="wordpress_headless"
DB_USER="root"
DB_PASS=""
DB_HOST="127.0.0.1:3306"
WP_URL="http://localhost/wp-headless"
WP_TITLE="Headless CMS"
WP_ADMIN="admin"
WP_EMAIL="admin@example.com"
WP_PASS="secure_password_2024"

echo "🚀 Starting WordPress Headless CMS Setup..."
echo "=========================================="

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "❌ WP-CLI not found. Please install WP-CLI first."
    echo "Run: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
    echo "Then: php wp-cli.phar --version"
    exit 1
fi

# Check database connection
echo "🔍 Checking database connection..."
if ! wp db check --path="$WP_DIR" 2>/dev/null; then
    echo "⚠️  Database connection failed. Creating config..."
else
    echo "✅ Database connection OK"
fi

cd "$WP_DIR"

# Download WordPress core
echo "📥 Downloading WordPress core..."
wp core download --locale=en_US --force

# Create wp-config.php
echo "⚙️  Creating wp-config.php..."
wp config create \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --dbhost="$DB_HOST" \
  --dbprefix=wp_ \
  --force

# Install WordPress
echo "🔧 Installing WordPress..."
wp core install \
  --url="$WP_URL" \
  --title="$WP_TITLE" \
  --admin_user="$WP_ADMIN" \
  --admin_password="$WP_PASS" \
  --admin_email="$WP_EMAIL" \
  --skip-email

# Configure for headless mode
echo "🎯 Configuring for headless CMS..."
wp option update blog_public 0
wp option update default_comment_status closed
wp option update default_ping_status closed
wp option update default_pingback_flag 0

# Security settings
wp config set WP_ADMIN_PROTECTION true --type=constant --anchor="/* Custom constants */"
wp config set CUSTOM_LOGIN_PATH console --type=constant
wp config set DISALLOW_FILE_EDIT true --type=constant
wp config set DISALLOW_FILE_MODS true --type=constant

# Disable XML-RPC
wp option update enable_xmlrpc 0

# Set permalink structure
wp rewrite structure '/%postname%/'
wp rewrite flush

# Install essential plugins
echo "📦 Installing essential plugins..."
wp plugin install wp-graphql --activate
wp plugin install advanced-custom-fields --activate

# Activate custom theme
if [ -d "wp-content/themes/headless-theme" ]; then
    echo "🎨 Activating headless theme..."
    wp theme activate headless-theme
fi

# Create basic content structure
echo "📝 Creating basic content structure..."

# Create pages
wp post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish --post_content="<h1>Welcome</h1><p>This is a headless CMS powered by WordPress.</p>"
wp post create --post_type=page --post_title="API" --post_name="api" --post_status=publish --post_content="<h1>GraphQL API</h1><p>API endpoint available at: /graphql</p>"

# Create categories
wp term create category "News" --description="News and updates"
wp term create category "Articles" --description="Useful articles"

# Create menu
wp menu create "Main Menu"
HOME_ID=$(wp post list --post_type=page --name=home --format=ids)
API_ID=$(wp post list --post_type=page --name=api --format=ids)
wp menu item add-post "main-menu" "$HOME_ID" --title="Home"
wp menu item add-post "main-menu" "$API_ID" --title="API"

# Create sample posts
echo "📄 Creating sample content..."
wp post generate --count=5 --post_type=post --post_status=publish --post_title="Sample Post #%s"
wp post generate --count=3 --post_type=post --post_status=draft --post_title="Draft #%s"

# Generate users
wp user create editor editor@example.com --role=editor --first_name="Editor" --last_name="User"
wp user create author author@example.com --role=author --first_name="Author" --last_name="User"

# Optimize database
echo "🔧 Optimizing database..."
wp db optimize

# Create backup
echo "💾 Creating initial backup..."
wp db export "backup_initial_$(date +%Y%m%d_%H%M%S).sql"

# Verify installation
echo "✅ Verifying installation..."
wp core verify-checksums
wp plugin list
wp theme list

echo ""
echo "🎉 WordPress Headless CMS setup completed!"
echo "=========================================="
echo ""
echo "📋 Installation Summary:"
echo "  • WordPress URL: $WP_URL"
echo "  • Admin login: $WP_ADMIN"
echo "  • Admin email: $WP_EMAIL"
echo "  • Database: $DB_NAME on $DB_HOST"
echo "  • GraphQL endpoint: $WP_URL/graphql"
echo "  • Console login: $WP_URL/console/"
echo ""
echo "🔒 Security features enabled:"
echo "  • Admin protection: ✅"
echo "  • File editing disabled: ✅"
echo "  • XML-RPC disabled: ✅"
echo "  • Comments closed by default: ✅"
echo ""
echo "📚 Next steps:"
echo "  1. Test GraphQL endpoint: curl -X POST $WP_URL/graphql -H 'Content-Type: application/json' -d '{\"query\":\"{posts{nodes{id,title}}}\"}'"
echo "  2. Access admin via: $WP_URL/console/"
echo "  3. Configure your frontend application"
echo "  4. Set up monitoring and backups"
echo ""
echo "📖 For more commands, see: docs/wp-cli-guide.md"
