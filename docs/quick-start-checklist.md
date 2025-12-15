# 🚀 Quick Start Checklist for Headless WordPress

## Prerequisites

### ✅ System Requirements
- [ ] PHP 8.2+ installed and added to PATH: `E:\_PHP\php-8.2.2`
- [ ] WP-CLI installed: `wp --version`
- [ ] MariaDB/MySQL running on `127.0.0.1:3306`
- [ ] Root user has database access

### ✅ Project Ready
- [ ] Repository cloned
- [ ] `www` directory exists
- [ ] Project files in place

---

## WordPress Installation

### Step 1: Quick Setup
```bash
cd www
bash ../docs/quick-setup.sh
```

**Expected Result:**
- WordPress installed
- `wordpress_headless` database created
- GraphQL plugin activated
- Headless theme activated

### Step 2: Installation Verification
```bash
wp core version          # Should show WP version
wp db check             # Should be OK
wp plugin list --status=active  # Show active plugins
```

---

## Configuration

### ✅ Database
- [ ] MariaDB connection works
- [ ] `wordpress_headless` database created
- [ ] WordPress tables created

### ✅ Security
- [ ] WP_ADMIN_PROTECTION = true
- [ ] CUSTOM_LOGIN_PATH = 'console'
- [ ] DISALLOW_FILE_EDIT = true
- [ ] XML-RPC disabled

### ✅ Headless Settings
- [ ] Blog public disabled
- [ ] Comments closed by default
- [ ] Permalink structure configured

---

## Testing

### ✅ Web Interface
- [ ] Homepage: `http://localhost/wp-headless`
- [ ] Console login: `http://localhost/wp-headless/console/`
- [ ] Admin login works

### ✅ GraphQL API
```bash
# Test query
curl -X POST http://localhost/wp-headless/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{posts{nodes{id,title}}}"}'
```

**Expected Response:**
```json
{
  "data": {
    "posts": {
      "nodes": [...]
    }
  }
}
```

### ✅ WP-CLI Commands
- [ ] `wp post list` - shows posts
- [ ] `wp user list` - shows users
- [ ] `wp plugin list` - shows plugins

---

## Content Creation

### Step 1: Basic Pages
```bash
wp post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish
wp post create --post_type=page --post_title="API" --post_name="api" --post_status=publish
```

### Step 2: Categories and Tags
```bash
wp term create category "News"
wp term create category "Articles"
wp term create post_tag "featured"
```

### Step 3: Menu
```bash
wp menu create "Main Menu"
wp menu item add-post "main-menu" 1 --title="Home"
wp menu item add-post "main-menu" 2 --title="API"
```

### Step 4: Sample Content
```bash
wp post generate --count=10 --post_type=post --post_status=publish
wp user generate --count=3
```

---

## Frontend Integration

### ✅ GraphQL Endpoints Ready
- [ ] `/graphql` - main endpoint
- [ ] Introspection works
- [ ] Posts, pages queries work

### ✅ MYGraphQL Plugin
- [ ] Plugin activated
- [ ] Allowed fields configured
- [ ] Caching works

### ✅ CORS and Security
- [ ] CORS headers configured (if needed)
- [ ] API protected from public access
- [ ] Authentication configured

---

## Optimization and Maintenance

### ✅ Performance
```bash
wp db optimize                    # Database optimization
wp transient delete --expired     # Transients cleanup
wp cache flush                    # Cache clearing
```

### ✅ Security
```bash
wp core verify-checksums          # File verification
wp user list --role=administrator # Admin check
wp config set WP_DEBUG false     # Disable debugging
```

### ✅ Backups
```bash
wp db export backup.sql                          # Database backup
tar -czf wp-content-backup.tar.gz wp-content/   # Files backup
```

---

## Monitoring and Support

### ✅ Logging
- [ ] PHP errors logged
- [ ] Console access logged
- [ ] GraphQL requests monitored

### ✅ Automation
```bash
# Add to cron for daily maintenance
0 2 * * * cd /path/to/www && wp transient delete --expired && wp db optimize
```

### ✅ Documentation
- [ ] `docs/wp-cli-guide.md` read
- [ ] `docs/wp-cli-examples.md` studied
- [ ] Useful commands noted

---

## Troubleshooting

### 🔧 Common Issues

#### Problem: "Error establishing database connection"
**Solution:**
```bash
# Check MariaDB
mysql -h 127.0.0.1 -P 3306 -u root -p

# Create database manually
CREATE DATABASE wordpress_headless;
```

#### Problem: "WP-CLI not found"
**Solution:**
```bash
# Download WP-CLI
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar

# Create wp.bat
echo '@ECHO OFF' > E:\_PHP\wp.bat
echo 'php "E:\_PHP\wp-cli.phar" %*' >> E:\_PHP\wp.bat
```

#### Problem: "GraphQL not working"
**Solution:**
```bash
wp plugin activate wp-graphql
wp graphql clear-cache
wp rewrite flush
```

#### Problem: "Cannot login to admin"
**Solution:**
- Use: `http://localhost/wp-headless/console/`
- Check credentials: admin / secure_password_2024

---

## Next Steps

After completing the checklist:

1. **Configure frontend application** to work with GraphQL API
2. **Create custom post types** through ACF
3. **Set up CI/CD** for automated deployment
4. **Add performance monitoring**
5. **Regularly update** WordPress and plugins

### Useful Commands for Daily Work

```bash
# Quick health check
wp core version && wp db check && echo "✅ OK"

# Create new post
wp post create --post_title="New Post" --post_content="Content" --post_status=publish

# Update plugins
wp plugin update --all

# Clear cache
wp cache flush
```

---

## Support

If you encounter problems:
1. Check logs in `wp-content/debug.log`
2. Use diagnostic commands from `docs/wp-cli-examples.md`
3. Refer to `docs/wp-cli-guide.md`
4. Check system requirements

**Happy coding! 🎉**
