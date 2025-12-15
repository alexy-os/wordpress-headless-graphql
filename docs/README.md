# WP-CLI Documentation for Headless WordPress

## About the Project

This documentation set is designed for managing WordPress as a headless CMS using WP-CLI. The project includes:

- **Quick WordPress installation** with headless configuration
- **Content management** through command line
- **GraphQL API integration**
- **Routine task automation**
- **Security and performance optimization**

## Documentation Files

### 📚 Main Files

| File | Description |
|------|----------|
| [`wp-cli-guide.md`](wp-cli-guide.md) | Complete guide for installing and using WP-CLI with headless WordPress |
| [`quick-setup.sh`](quick-setup.sh) | Automated installation script |
| [`wp-cli-examples.md`](wp-cli-examples.md) | Command examples for various scenarios |
| [`quick-start-checklist.md`](quick-start-checklist.md) | Step-by-step setup checklist |

### 🚀 Quick Start

#### 1. Install WordPress with WP-CLI

```bash
cd www
bash ../docs/quick-setup.sh
```

#### 2. Verify Installation

```bash
wp core version
wp db check
wp plugin list --status=active
```

#### 3. Test GraphQL

```bash
# Test query
curl -X POST http://localhost/wp-headless/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{posts{nodes{id,title}}}"}'
```

## Project Structure

```
wp-headless/
├── docs/                    # English WP-CLI Documentation
│   ├── README.md           # This file
│   ├── wp-cli-guide.md     # Main guide
│   ├── quick-setup.sh      # Setup script
│   └── wp-cli-examples.md  # Command examples
├── .docs/                  # Russian Documentation (internal)
├── www/                    # WordPress Installation
│   ├── wp-config.php       # Configuration
│   ├── wp-content/         # Content
│   │   ├── plugins/        # Plugins
│   │   └── themes/         # Themes
│   └── console/            # Custom login console
└── README.md              # Main project README
```

## Database Configuration

The project is configured to work with MariaDB:

- **Host**: `127.0.0.1:3306`
- **User**: `root`
- **Password**: empty (default)
- **Database**: `wordpress_headless`

### Changing Database Settings

If you need to modify database settings, edit the `quick-setup.sh` file:

```bash
# Change these variables
DB_NAME="your_database"
DB_USER="your_user"
DB_PASS="your_password"
DB_HOST="127.0.0.1:3306"
```

## Security

The project includes multiple security layers:

### 🔒 Security Features

- **Protected admin access**: `/console/` instead of standard `/wp-login.php`
- **Login attempt limits**: 3 attempts per temporary link
- **Rate limiting**: 5 attempts per hour per IP
- **File editing disabled**: `DISALLOW_FILE_EDIT = true`
- **XML-RPC disabled**: Prevents attack vectors
- **Comments closed**: by default

### 🛡️ Additional Protections

```bash
# Enable additional protections
wp config set FORCE_SSL_ADMIN true --type=constant
wp config set WP_DEBUG false --raw
wp config set WP_DEBUG_LOG false --raw
```

## GraphQL Integration

### Installed Plugins

- **WPGraphQL**: Main GraphQL provider
- **MYGraphQL**: Custom plugin for field control

### Example Queries

```graphql
# Get posts
{
  posts {
    nodes {
      id
      title
      content
    }
  }
}

# Get pages with meta fields
{
  pages {
    nodes {
      id
      title
      pageFields {
        key
        value
      }
    }
  }
}
```

## Automation

### Cron Tasks

Recommended cron tasks for maintenance:

```bash
# Daily at 2:00 AM
0 2 * * * cd /path/to/www && wp transient delete --expired

# Weekly on Mondays at 3:00 AM
0 3 * * 1 cd /path/to/www && wp db optimize && wp post delete $(wp post list --post_type=revision --date_before="90 days ago" --format=ids)
```

### Scripts

Use the provided scripts for automation:

```bash
# Full maintenance
bash docs/maintenance.sh

# Backup
bash docs/backup.sh

# Update
bash docs/update.sh
```

## Development and Testing

### Creating Test Content

```bash
# Generate test data
wp post generate --count=50 --post_type=post --post_status=publish
wp user generate --count=10
wp term generate --count=20 --taxonomy=category
```

### Debugging

```bash
# Enable debugging
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG_LOG true --raw

# View logs
tail -f wp-content/debug.log
```

## Useful Links

- [WP-CLI Handbook](https://developer.wordpress.org/cli/commands/)
- [WPGraphQL Documentation](https://www.wpgraphql.com/docs/)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WordPress Security Best Practices](https://wordpress.org/support/article/hardening-wordpress/)

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

This project is licensed under the MIT License. See the LICENSE file in the project root.
