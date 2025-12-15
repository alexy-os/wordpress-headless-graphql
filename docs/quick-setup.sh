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
DB_HOST="localhost"
WP_URL="http://localhost/wp-headless"
WP_TITLE="Headless CMS"
WP_ADMIN="admin"
WP_EMAIL="admin@example.com"
WP_PASS="secure_password_2024"

echo "🚀 Starting WordPress Headless CMS Setup..."
echo "=========================================="

# Get the absolute path to WP-CLI
WP_CLI_CMD=""
if command -v wp &> /dev/null; then
    WP_CLI_CMD="wp"
elif command -v wp.bat &> /dev/null; then
    WP_CLI_CMD="wp.bat"
else
    # Find wp-cli.phar relative to script location
    SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

    if [ -f "$PROJECT_ROOT/wp-cli.phar" ]; then
        WP_CLI_CMD="php -d memory_limit=512M $PROJECT_ROOT/wp-cli.phar"
    else
        echo "❌ WP-CLI not found in standard locations."
        echo ""
        echo "📦 Please install WP-CLI first:"
        echo "1. Download: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"
        echo "2. Test: php wp-cli.phar --version"
        echo ""
        echo "Or install globally via Composer:"
        echo "composer global require wp-cli/wp-cli"
        echo ""
        echo "Or install via other methods from: https://wp-cli.org/#installing"
        exit 1
    fi
fi

echo "✅ WP-CLI found: $WP_CLI_CMD"

# Check database connection
echo "🔍 Checking database connection..."
if ! $WP_CLI_CMD db check --path="$WP_DIR" 2>/dev/null; then
    echo "⚠️  Database connection failed. Creating config..."
else
    echo "✅ Database connection OK"
fi

# Change to WordPress directory if not already there
if [ "$(basename $(pwd))" != "$WP_DIR" ]; then
    cd "$WP_DIR" || { echo "❌ Cannot change to $WP_DIR directory"; exit 1; }
fi

# Download WordPress core
echo "📥 Downloading WordPress core..."
$WP_CLI_CMD core download --locale=en_US --force --insecure

# Create wp-config.php (skip if already exists)
echo "⚙️  Checking wp-config.php..."
if [ ! -f "wp-config.php" ]; then
    $WP_CLI_CMD config create \
      --dbname="$DB_NAME" \
      --dbuser="$DB_USER" \
      --dbpass="$DB_PASS" \
      --dbhost="$DB_HOST" \
      --dbprefix=wp_ \
      --force
else
    echo "✅ wp-config.php already exists"
fi

# Install WordPress
echo "🔧 Installing WordPress..."
$WP_CLI_CMD core install \
  --url="$WP_URL" \
  --title="$WP_TITLE" \
  --admin_user="$WP_ADMIN" \
  --admin_password="$WP_PASS" \
  --admin_email="$WP_EMAIL" \
  --skip-email

# Configure for headless mode
echo "🎯 Configuring for headless CMS..."
$WP_CLI_CMD option update blog_public 0
$WP_CLI_CMD option update default_comment_status closed
$WP_CLI_CMD option update default_ping_status closed
$WP_CLI_CMD option update default_pingback_flag 0

# Security settings
$WP_CLI_CMD config set WP_ADMIN_PROTECTION true --type=constant --anchor="/* Custom constants */"
$WP_CLI_CMD config set CUSTOM_LOGIN_PATH console --type=constant
$WP_CLI_CMD config set DISALLOW_FILE_EDIT true --type=constant
$WP_CLI_CMD config set DISALLOW_FILE_MODS true --type=constant

# Disable XML-RPC
$WP_CLI_CMD option update enable_xmlrpc 0

# Set permalink structure
$WP_CLI_CMD rewrite structure '/%postname%/'
$WP_CLI_CMD rewrite flush

# Install essential plugins
echo "📦 Installing essential plugins..."
$WP_CLI_CMD plugin install wp-graphql --activate
$WP_CLI_CMD plugin install advanced-custom-fields --activate

# Activate custom theme
if [ -d "wp-content/themes/headless-theme" ]; then
    echo "🎨 Activating headless theme..."
    $WP_CLI_CMD theme activate headless-theme
fi

# Create basic content structure
echo "📝 Creating basic content structure..."

# Create pages
$WP_CLI_CMD post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish --post_content="<h1>Welcome</h1><p>This is a headless CMS powered by WordPress.</p>"
$WP_CLI_CMD post create --post_type=page --post_title="API" --post_name="api" --post_status=publish --post_content="<h1>GraphQL API</h1><p>API endpoint available at: /graphql</p>"

# Create categories
$WP_CLI_CMD term create category "News" --description="News and updates"
$WP_CLI_CMD term create category "Articles" --description="Useful articles"

# Create menu
$WP_CLI_CMD menu create "Main Menu"
HOME_ID=$($WP_CLI_CMD post list --post_type=page --name=home --format=ids)
API_ID=$($WP_CLI_CMD post list --post_type=page --name=api --format=ids)
$WP_CLI_CMD menu item add-post "main-menu" "$HOME_ID" --title="Home"
$WP_CLI_CMD menu item add-post "main-menu" "$API_ID" --title="API"

# Create sample posts
echo "📄 Creating sample content..."
$WP_CLI_CMD post generate --count=5 --post_type=post --post_status=publish --post_title="Sample Post #%s"
$WP_CLI_CMD post generate --count=3 --post_type=post --post_status=draft --post_title="Draft #%s"

# Generate users
$WP_CLI_CMD user create editor editor@example.com --role=editor --first_name="Editor" --last_name="User"
$WP_CLI_CMD user create author author@example.com --role=author --first_name="Author" --last_name="User"

# Optimize database
echo "🔧 Optimizing database..."
$WP_CLI_CMD db optimize

# Create backup
echo "💾 Creating initial backup..."
$WP_CLI_CMD db export "backup_initial_$(date +%Y%m%d_%H%M%S).sql"

# Verify installation
echo "✅ Verifying installation..."
$WP_CLI_CMD core verify-checksums
$WP_CLI_CMD plugin list
$WP_CLI_CMD theme list

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
echo ""
echo "⚠️  If you encounter database connection issues:"
echo "   1. Make sure MariaDB is running on localhost"
echo "   2. Try changing authentication method in MariaDB:"
echo "      ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';"
echo "      FLUSH PRIVILEGES;"
echo "   3. Or use a different database user/password"
