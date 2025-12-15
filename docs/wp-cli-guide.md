# WP-CLI Guide: WordPress Headless CMS Setup & Management

## Table of Contents
1. [Introduction to WP-CLI](#introduction-to-wp-cli)
2. [Installing WordPress with WP-CLI](#installing-wordpress-with-wp-cli)
3. [Headless CMS Configuration](#headless-cms-configuration)
4. [Working with MariaDB Database](#working-with-mariadb-database)
5. [Popular Usage Scenarios](#popular-usage-scenarios)
6. [Content Management](#content-management)
7. [Security & Optimization](#security--optimization)
8. [GraphQL Integration](#graphql-integration)
9. [Automation & Scripting](#automation--scripting)

---

## Introduction to WP-CLI

WP-CLI is a powerful command-line tool for managing WordPress installations without using the web interface. It allows you to:

- Install and configure WordPress
- Manage users, posts, pages
- Work with plugins and themes
- Execute bulk operations
- Perform maintenance and security checks
- Create backups and migrations

### Advantages for Headless CMS
- Fast clean installation setup
- Programmatic content management
- Deployment automation
- Development and testing
- CI/CD integration

---

## Installing WordPress with WP-CLI

### 1. Download WordPress Core

```bash
# Download the latest WordPress version
wp core download --locale=en_US

# Or download a specific version
wp core download --version=6.4.3 --locale=en_US
```

### 2. Create wp-config.php

```bash
# Generate wp-config.php with database settings
wp config create \
  --dbname=wordpress_headless \
  --dbuser=root \
  --dbpass="" \
  --dbhost=127.0.0.1:3306 \
  --dbprefix=wp_ \
  --locale=en_US
```

### 3. Install WordPress

```bash
# Install WordPress with basic settings
wp core install \
  --url="http://localhost/wp-headless" \
  --title="Headless CMS" \
  --admin_user="admin" \
  --admin_password="strong_password_123" \
  --admin_email="admin@example.com" \
  --skip-email
```

### 4. Verify Installation

```bash
# Check installation status
wp core version
wp db check
wp core verify-checksums
```

---

## Headless CMS Configuration

### Basic Headless Mode Settings

```bash
# Disable standard WordPress features
wp option update blog_public 0
wp option update default_pingback_flag 0
wp option update default_ping_status 0
wp option update default_comment_status closed

# Disable REST API for public access (if not needed)
wp option update rest_api_enabled 0

# Set permalink structure for API
wp rewrite structure '/%postname%/'
wp rewrite flush
```

### Security Settings

```bash
# Add security constants to wp-config.php
wp config set WP_ADMIN_PROTECTION true --type=constant
wp config set CUSTOM_LOGIN_PATH console --type=constant
wp config set DISALLOW_FILE_EDIT true --type=constant
wp config set DISALLOW_FILE_MODS true --type=constant

# Disable XML-RPC
wp option update enable_xmlrpc 0

# Set proper file upload limits
wp option update fileupload_maxk 10
wp option update upload_max_filesize 10
```

---

## Working with MariaDB Database

### Check Database Connection

```bash
# Verify database connection
wp db check

# Show database information
wp db size

# Optimize database
wp db optimize

# Create database backup
wp db export backup.sql
```

### Manage Tables

```bash
# Show all tables
wp db tables

# Show table structure
wp db describe wp_posts

# Execute custom SQL query
wp db query "SELECT COUNT(*) FROM wp_posts WHERE post_status = 'publish'"
```

### Migration and Recovery

```bash
# Import database from file
wp db import backup.sql

# Search and replace in database (be careful!)
wp search-replace 'old-domain.com' 'new-domain.com'
```

---

## Popular Usage Scenarios

### 1. User Management

```bash
# Create user
wp user create john john@example.com --role=editor --first_name="John" --last_name="Doe"

# Change user role
wp user set-role admin editor

# Reset password
wp user reset-password admin

# List all users
wp user list

# Delete user
wp user delete olduser --reassign=admin
```

### 2. Content Management

```bash
# Create post
wp post create --post_title="Hello World" --post_content="Welcome to headless CMS" --post_status=publish

# Import content from CSV
wp post generate --count=50 --post_type=post --post_status=publish
wp user generate --count=10
wp term generate --count=20 --taxonomy=category
```

### 3. Plugin Management

```bash
# Install and activate plugin
wp plugin install wp-graphql --activate
wp plugin install advanced-custom-fields --activate

# Deactivate plugins
wp plugin deactivate hello-dolly

# Update plugins
wp plugin update --all

# Delete plugins
wp plugin delete hello-dolly
```

### 4. Theme Management

```bash
# Install theme
wp theme install twentytwentythree --activate

# Activate theme
wp theme activate headless-theme

# Delete unused themes
wp theme delete twentytwentyone
```

### 5. Menu Management

```bash
# Create menu
wp menu create "Main Navigation"

# Add items to menu
wp menu item add-post main-navigation 1
wp menu item add-custom main-navigation "About" "http://example.com/about"

# Assign menu to location
wp menu location assign main-navigation primary
```

### 6. Media Management

```bash
# Import media file
wp media import /path/to/image.jpg --title="Sample Image"

# Regenerate thumbnails
wp media regenerate

# List all media files
wp media list
```

---

## Content Management

### Working with Posts and Pages

```bash
# Create page
wp post create --post_type=page --post_title="About Us" --post_content="<p>About our company</p>" --post_status=publish

# Update post
wp post update 123 --post_title="New Title"

# Delete posts
wp post delete 123 --force

# Search posts
wp post list --post_type=post --posts_per_page=10
wp post list --search="wordpress"
```

### Working with Categories and Tags

```bash
# Create category
wp term create category "News" --description="Latest news"

# Create tag
wp term create post_tag "featured"

# Assign category to post
wp post term set 123 category news

# List all categories
wp term list category
```

### Custom Fields (ACF)

```bash
# Install ACF if not installed
wp plugin install advanced-custom-fields --activate

# Add value to custom field
wp post meta set 123 hero_image "image.jpg"
wp post meta set 123 subtitle "Welcome to our site"
```

---

## Security & Optimization

### Security Audit

```bash
# Verify core file checksums
wp core verify-checksums

# Check for vulnerabilities (requires plugin)
wp vuln status

# Check user permissions
wp cap list admin
```

### Performance Optimization

```bash
# Clear cache (if caching plugin is installed)
wp cache flush

# Optimize database
wp db optimize

# Delete post revisions
wp post delete $(wp post list --post_type=revision --format=ids)

# Delete spam comments
wp comment delete $(wp comment list --status=spam --format=ids)
```

### Backup Creation

```bash
# Create full backup
wp db export database.sql
tar -czf wp-content.tar.gz wp-content/

# Create backup with date
DATE=$(date +%Y%m%d_%H%M%S)
wp db export "backup_db_$DATE.sql"
tar -czf "backup_files_$DATE.tar.gz" wp-content/
```

---

## GraphQL Integration

### Setting up WPGraphQL

```bash
# Install WPGraphQL
wp plugin install wp-graphql --activate

# Check GraphQL status
wp graphql status

# Clear GraphQL cache
wp graphql clear-cache
```

### Working with MYGraphQL Plugin

```bash
# Activate plugin
wp plugin activate mygraphql

# Configure allowed fields
wp option update mygraphql_allowed_fields '["title","content","excerpt"]'

# Manage content types in GraphQL
wp post-type list --graphql-enabled=true
```

### Testing GraphQL API

```bash
# Simple query through WP-CLI (requires wp-graphql-cli)
wp graphql query '{posts{nodes{id,title}}}'

# Or through curl
curl -X POST http://localhost/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{posts{nodes{id,title}}}"}'
```

---

## Automation & Scripting

### Create Installation Script

Create file `setup.sh`:

```bash
#!/bin/bash

# Configuration
DB_NAME="wordpress_headless"
DB_USER="root"
DB_PASS=""
DB_HOST="127.0.0.1:3306"
WP_URL="http://localhost/wp-headless"
WP_TITLE="Headless CMS"
WP_ADMIN="admin"
WP_EMAIL="admin@example.com"
WP_PASS="strong_password_123"

# Install WordPress
wp core download --locale=en_US

wp config create \
  --dbname=$DB_NAME \
  --dbuser=$DB_USER \
  --dbpass=$DB_PASS \
  --dbhost=$DB_HOST \
  --dbprefix=wp_

wp core install \
  --url=$WP_URL \
  --title="$WP_TITLE" \
  --admin_user=$WP_ADMIN \
  --admin_password=$WP_PASS \
  --admin_email=$WP_EMAIL \
  --skip-email

# Configure for headless
wp option update blog_public 0
wp option update default_comment_status closed
wp rewrite structure '/%postname%/'
wp rewrite flush

# Install essential plugins
wp plugin install wp-graphql --activate
wp plugin install advanced-custom-fields --activate

echo "WordPress headless CMS installed!"
```

### Content Deployment Script

```bash
#!/bin/bash

# Create basic pages
wp post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish
wp post create --post_type=page --post_title="About" --post_name="about" --post_status=publish
wp post create --post_type=page --post_title="Contact" --post_name="contact" --post_status=publish

# Create menu
wp menu create "Main Menu"
wp menu item add-post main-menu $(wp post list --post_type=page --name=home --format=ids)
wp menu item add-post main-menu $(wp post list --post_type=page --name=about --format=ids)
wp menu item add-post main-menu $(wp post list --post_type=page --name=contact --format=ids)

# Create categories
wp term create category "News" --description="Company news"
wp term create category "Articles" --description="Useful articles"

echo "Basic content created!"
```

### Cron Tasks for Maintenance

```bash
# Add to crontab for weekly maintenance
# 0 2 * * 1 wp db optimize && wp cache flush && wp transient delete --expired
```

---

## Summary

WP-CLI is an indispensable tool for developing and managing WordPress headless CMS. It allows you to:

1. **Quickly install** clean WordPress instances
2. **Automate** routine tasks
3. **Scale** content management
4. **Integrate** with CI/CD pipelines
5. **Ensure** security and performance

### Recommended Workflow:

1. Use installation scripts for consistency
2. Regularly create database and file backups
3. Monitor performance and security
4. Automate updates and maintenance

For additional information, visit [wp-cli.org](https://wp-cli.org/) and [developer.wordpress.org/cli](https://developer.wordpress.org/cli/commands/).
