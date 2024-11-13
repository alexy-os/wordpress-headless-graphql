# WordPress Headless Theme with Enhanced Security

A minimal WordPress theme designed to work as a headless CMS with GraphQL support and enhanced security features. This theme serves as a backend for JAMstack applications.

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
- Configured for WPGraphQL
- Custom post type handling
- Structured content delivery
- API endpoint protection

## Installation

1. Clone this repository to your WordPress themes directory:
```bash
cd wp-content/themes
git clone [repository-url] headless-theme
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

### GraphQL Queries
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

### Security Features
- Rate limiting is implemented at both IP and attempt levels
- All login attempts are logged in `/console/access.log`
- Security headers are automatically added to all responses
- Admin area is protected from unauthorized access

## File Structure
```
headless-theme/
├── console/
│   └── index.php          # Custom login implementation
├── admin-access.php       # Admin protection logic
├── functions.php          # Theme functionality
├── safety-functions.php   # Security implementations
├── index.php             # Minimal frontend
└── style.css             # Theme declaration
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](LICENSE)