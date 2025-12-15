# WordPress Headless Theme with Enhanced Security

A minimal WordPress theme designed to work as a headless CMS with GraphQL support and enhanced security features. This theme serves as a backend for JAMstack applications.

## 🚀 Quick Start with WP-CLI

For fast setup and management, see our [WP-CLI documentation](docs/README.md):

- **[Quick Setup Script](docs/quick-setup.sh)** - Automated installation
- **[WP-CLI Guide](docs/wp-cli-guide.md)** - Complete usage manual
- **[Examples](docs/wp-cli-examples.md)** - Command examples
- **[Checklist](docs/quick-start-checklist.md)** - Step-by-step setup guide

## Features

### Headless Mode
- Disabled frontend rendering
- Optimized for GraphQL content delivery
- Minimal theme structure
- Cleaned up WordPress head and removed unnecessary features

### Security Implementation

#### Admin Protection
- Custom login page through `/console/` endpoint
- Disabled standard wp-login.php
- Protected wp-admin access
- Implemented security headers
- Disabled file editing in admin panel

#### Console Authentication
- Hash-based temporary login links
- Rate limiting protection:
  - 5 attempts per hour per IP
  - 3 attempts per temporary link
  - 30 minutes link expiration
- Brute force protection
- Access logging
- Session management

#### API Security
- Protected REST API endpoints
- GraphQL access control
- Disabled XML-RPC
- Disabled directory browsing
- Protected sensitive files

### GraphQL Integration
- Configured for WPGraphQL with MYGraphQL extension
- Selective field exposure for optimal data transfer
- Custom post type handling with meta fields control
- Structured content delivery with caching
- API endpoint protection

## Installation

1. Clone this repository to your server:
```bash
cd /www
git clone [repository-url] .
```

2. Add the following constants to your wp-config.php:
```php
define('WP_ADMIN_PROTECTION', true);
define('CUSTOM_LOGIN_PATH', 'console');
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', true);
```

3. Add the security rules to your .htaccess:
```apache
# Protect wp-login.php and wp-admin
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^wp-login\.php$ - [R=403,L]
    RewriteCond %{REQUEST_URI} ^/wp-admin
    RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_ [NC]
    RewriteRule ^(.*)$ - [R=403,L]
</IfModule>
```

## Usage

### Accessing Admin Panel
1. Navigate to `/console/`
2. Get a temporary login link
3. Use the link within 30 minutes
4. Login with your WordPress credentials

### GraphQL simple queries
The GraphQL endpoint is available at `/graphql`. Example query:
```graphql
query GetPosts {
  posts {
    nodes {
      id
      title
      content
    }
  }
}
```

### GraphQL Usage with Field Restrictions

The GraphQL endpoint is available at `/graphql`. Example optimized query with controlled field exposure:

```graphql
{
  pages(first: 10) {
    nodes {
      id
      title
      featuredImage {
        node {
          id
          sourceUrl
          altText
        }
      }
      # Only exposed meta fields will be available
      pageFields {
        key
        value
      }
    }
  }
}
```

### Field Control
The MYGraphQL plugin allows you to:
- Explicitly define which meta fields are exposed
- Cache frequently accessed data
- Control featured image exposure
- Implement type-specific field restrictions

### Security Features
- Rate limiting is implemented at both IP and attempt levels
- All login attempts are logged in `/console/access.log`
- Security headers are automatically added to all responses
- Admin area is protected from unauthorized access

## File Structure
```
www/
│── console/
│   └── index.php      # Custom login implementation
├── wp-content/
│   ├── themes/
│   │   └── headless-theme/       # Headless Theme
│   └── plugins/
│       └── mygraphql/            # GraphQL field control plugin
├── wp-config.php
└── .htaccess
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](LICENSE)
