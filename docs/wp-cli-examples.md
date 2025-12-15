# WP-CLI Command Examples for Headless WordPress

## Table of Contents
- [Quick Commands](#quick-commands)
- [Content Management](#content-management)
- [Working with GraphQL](#working-with-graphql)
- [Security](#security)
- [Optimization](#optimization)
- [Monitoring](#monitoring)

---

## Quick Commands

### Status Check
```bash
# Check WordPress version
wp core version

# Check database connection
wp db check

# Show site information
wp option get siteurl
wp option get home
wp option get blogname

# Check active plugins
wp plugin list --status=active

# Check active theme
wp theme list --status=active
```

### Backup Creation
```bash
# Database backup
wp db export backup_$(date +%Y%m%d).sql

# Files backup (excluding uploads for speed)
tar -czf wp-content-backup.tar.gz --exclude='wp-content/uploads' wp-content/

# Full backup
wp db export full-backup.sql && tar -czf full-backup.tar.gz wp-content/ full-backup.sql
```

---

## Content Management

### Content Creation
```bash
# Create page
wp post create --post_type=page --post_title="About Company" --post_content="<p>About our company</p>" --post_status=publish

# Create post with custom fields
wp post create --post_title="News" --post_content="News content" --post_status=publish --post_category="news"

# Generate test content
wp post generate --count=20 --post_type=post --post_status=publish
wp user generate --count=5
wp comment generate --count=50 --post_id=1

# Create menu
wp menu create "Main Navigation"
wp menu item add-post "main-navigation" 1 --title="Home"
wp menu item add-custom "main-navigation" "About" "/contact"
```

### Search and Filtering
```bash
# Find posts by title
wp post list --search="wordpress"

# Show latest 10 posts
wp post list --posts_per_page=10 --orderby=date --order=desc

# Find posts by author
wp post list --author=admin

# Show drafts
wp post list --post_status=draft

# Find pages
wp post list --post_type=page
```

### Bulk Operations
```bash
# Change all drafts to published
wp post update $(wp post list --post_status=draft --format=ids) --post_status=publish

# Add category to all posts without category
wp post term add $(wp post list --category=0 --format=ids) category "uncategorized"

# Delete all spam comments
wp comment delete $(wp comment list --status=spam --format=ids)

# Change author for all posts
wp post update $(wp post list --author=olduser --format=ids) --post_author=newuser
```

### Working with Meta Fields
```bash
# Add meta field to post
wp post meta set 123 hero_title "Main Title"
wp post meta set 123 hero_subtitle "Subtitle"

# Get meta field value
wp post meta get 123 hero_title

# Delete meta field
wp post meta delete 123 hero_title

# Show all meta fields of post
wp post meta list 123
```

---

## Working with GraphQL

### Managing WPGraphQL
```bash
# Check GraphQL status
wp graphql status

# Clear GraphQL cache
wp graphql clear-cache

# Show available types
wp post-type list --graphql-enabled=true

# Show available taxonomies
wp taxonomy list --graphql-enabled=true
```

### GraphQL Testing
```bash
# Simple posts query
wp graphql query '{posts{nodes{id,title,content}}}'

# Query with variables
wp graphql query 'query GetPost($id: ID!) {post(id: $id){title,content}}' --variables='{"id": "1"}'

# Query pages
wp graphql query '{pages{nodes{id,title,slug}}}'

# Query with filters
wp graphql query '{posts(where: {status: PUBLISH}){nodes{id,title}}}'
```

### Managing MYGraphQL Plugin
```bash
# Configure allowed fields
wp option update mygraphql_allowed_fields '["title","content","excerpt","featuredImage"]'

# Configure caching
wp option update mygraphql_cache_timeout 3600

# Check settings
wp option get mygraphql_allowed_fields
```

---

## Security

### Security Audit
```bash
# Verify core file checksums
wp core verify-checksums

# Check for vulnerabilities (requires plugin)
wp vuln status

# Check user permissions
wp cap list admin
```

### User Management
```bash
# Create user with limited access
wp user create api_user api@example.com --role=editor --first_name="API" --last_name="User"

# Change password
wp user update admin --user_pass="new_strong_password"

# Deactivate user
wp user update olduser --display_name="Deactivated User"

# Check recent logins
wp user list --orderby=last_login --order=desc
```

### Spam Protection
```bash
# Delete all spam comments
wp comment delete $(wp comment list --status=spam --format=ids)

# Close comments for old posts
wp post update $(wp post list --before="6 months ago" --format=ids) --comment_status=closed

# Check suspicious users
wp user list --who=suspicious
```

---

## Optimization

### Database Cleanup
```bash
# Delete post revisions (older than 30 days)
wp post delete $(wp post list --post_type=revision --date_before="30 days ago" --format=ids)

# Delete auto-drafts
wp post delete $(wp post list --post_status=auto-draft --format=ids)

# Clear expired transients
wp transient delete --expired

# Optimize tables
wp db query "REPAIR TABLE $(wp db tables | tr '\n' ',')"
```

### Caching
```bash
# Clear cache (if caching plugin is installed)
wp cache flush

# Clear transients
wp transient delete --all

# Rebuild indexes
wp db query "REPAIR TABLE $(wp db tables | tr '\n' ',')"
```

### Performance
```bash
# Check database size
wp db size

# Find heaviest tables
wp db size --tables

# Check slow queries
wp db query "SHOW PROCESSLIST"

# Optimize images (requires plugin)
wp media regenerate --only-missing
```

---

## Monitoring

### Logs and Debugging
```bash
# Enable debugging
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG_LOG true --raw

# View logs
tail -f wp-content/debug.log

# Check PHP errors
wp eval 'error_reporting(E_ALL); ini_set("display_errors", 1);'
```

### Statistics
```bash
# Content statistics
wp post list --format=count
wp user list --format=count
wp comment list --format=count

# Statistics by post type
wp post-type list --format=json | jq '.[] | select(.name != "revision") | "\(.name): \(.count)"'

# Check updates
wp plugin list --update=available
wp theme list --update=available
wp core check-update
```

### System Information
```bash
# WordPress information
wp core version
wp option get db_version

# Server information
wp eval 'phpinfo(INFO_GENERAL);'

# Check system requirements
wp doctor check
```

---

## Automation

### Cron Tasks
```bash
# Add to crontab for daily maintenance
# 0 2 * * * cd /path/to/wordpress && wp transient delete --expired && wp db optimize

# Weekly cleanup
# 0 3 * * 1 cd /path/to/wordpress && wp db optimize && wp post delete $(wp post list --post_type=revision --date_before="90 days ago" --format=ids)
```

### Maintenance Scripts
```bash
#!/bin/bash
# maintenance.sh

echo "Starting maintenance..."

# Cleanup
wp transient delete --expired
wp post delete $(wp post list --post_status=auto-draft --format=ids)

# Optimization
wp db optimize
wp cache flush

# Backup
wp db export "backup_$(date +%Y%m%d).sql"

echo "Maintenance completed!"
```

### CI/CD Integration
```yaml
# .github/workflows/deploy.yml
name: Deploy WordPress
on: [push]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install WP-CLI
        run: curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
      - name: Deploy
        run: php wp-cli.phar core update && php wp-cli.phar plugin update --all
```

---

## Useful One-liners

```bash
# Search and replace URL in content
wp search-replace 'old-site.com' 'new-site.com'

# Bulk email change for users
wp user update $(wp user list --format=ids) --user_email=newdomain.com

# Import users from CSV
while IFS=, read -r user email; do wp user create "$user" "$email" --role=subscriber; done < users.csv

# Export content to JSON
wp post list --format=json --fields=ID,title,content > posts.json

# Import users from JSON
wp user import users.json

# Quick health check
wp core version && wp db check && wp plugin list --status=active --format=count && echo "✅ Site is healthy"
```
