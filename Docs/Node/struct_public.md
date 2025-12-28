# Public Directory Structure

## Overview: Publicly Accessible Web Resources

The Public directory contains all files that are directly accessible via web requests, serving as the document root for the web server. This separation ensures that only intentionally exposed files are publicly accessible while application code remains protected. The structure follows security best practices with clear boundaries between executable PHP files and static assets.

### Security Philosophy

- **Only Entry PHP files** in the root Public directory
- **All static assets** organized by type with appropriate caching headers
- **No application logic** in publicly accessible locations
- **Clear separation** between user uploads and application assets

## Public Directory Components

### Public/Entry

**Purpose**: Front controller entry points that bootstrap the application and handle all web requests. These PHP files are the only executable files in the public directory, implementing the Front Controller pattern.

**File Structure**:

```
Public/
├── index.php          # Primary web application entry point
├── api.php            # API-specific entry point (optional)
├── .htaccess          # Apache configuration (security, routing, headers)
└── (other entry points as needed)
```

**Key Characteristics**:

- **Front Controllers**: All requests routed through these files
- **Minimal Logic**: Only bootstrap and dispatch, no business logic
- **Security Configuration**: `.htaccess` restricts access to sensitive files
- **Environment Detection**: Different entry points for different contexts (web vs API)

### Public/Static/Asset

**Purpose**: Static frontend assets organized by type for efficient delivery and caching. These files are served directly by the web server with appropriate cache headers.

**Subdirectories**:

- **CSS**: Stylesheets (compiled CSS, Sass/Less output)
- **FONT**: Web fonts (WOFF, WOFF2, TTF, EOT)
- **IMG**: Images (optimized PNG, JPG, SVG, WebP)
- **JS**: JavaScript files (compiled bundles, vendor libraries)

**Key Characteristics**:

- **Type-Specific Organization**: Clear separation by MIME type
- **Cache Optimization**: Aggressive caching for static assets
- **Versioning Support**: File naming for cache busting (e.g., `app-{hash}.css`)
- **Build Integration**: Output from frontend build processes

### Public/Static/Build

**Purpose**: Build outputs and compiled assets from build processes. This directory contains the final production-ready assets after processing.

**Contents**:

- Compiled CSS/JS bundles
- Optimized images
- Asset manifests
- Source maps (for debugging)

**Key Characteristics**:

- **Production Assets**: Optimized for performance
- **Build Artifacts**: Output from Webpack, Vite, Gulp, etc.
- **Version Controlled**: Build outputs typically NOT in version control
- **Cache Headers**: Long-term caching enabled

### Public/Static/Media

**Purpose**: Dynamically generated or user-uploaded media files with public access.

**Subdirectories**:

- **Cache**: Publicly cacheable generated files (thumbnails, resized images, PDF exports)
- **Upload**: User-uploaded content (avatars, documents, media files)

**Key Characteristics**:

- **Dynamic Content**: Files generated at runtime
- **Access Control**: May have permission checks via PHP
- **Cleanup Policies**: Old files automatically removed
- **Security Scanning**: Uploaded files validated and scanned

### Public/Static/Meta

**Purpose**: Webmaster and browser metadata files for SEO, security, and discovery.

**Files**:

- `robots.txt`: Search engine directives
- `security.txt`: Security contact information (RFC 9116)
- `humans.txt`: Credit to humans behind the website
- `manifest.json`: Web app manifest (PWA)
- `favicon.ico`, `apple-touch-icon.png`: Browser icons
- `sitemap.xml`: URL structure for search engines (may be generated)

**Key Characteristics**:

- **Standardized Formats**: Follow established web standards
- **SEO Optimization**: Helps search engine indexing
- **Security Disclosure**: Clear security contact information
- **Browser Integration**: PWA and browser metadata

### Public/Static/Theme

**Purpose**: Theme-specific static assets that can be swapped when changing visual themes.

**Contents**:

- Theme-specific CSS overrides
- Theme images and icons
- Theme JavaScript enhancements
- Font files specific to theme

**Key Characteristics**:

- **Theme Isolation**: Assets specific to visual theme
- **Hot-Swappable**: Can change without rebuilding
- **Cascading Overrides**: Theme assets override base assets
- **Namespace Protection**: Prevents theme asset collisions

## Security Considerations

### File Permissions

- **Public/**: `755` directories, `644` files
- **Uploads/**: `755` directories, `644` files (execution disabled via `.htaccess`)
- **Entry PHP**: `644` (executable by web server only)

### Access Restrictions

- **.htaccess rules** prevent directory listing
- **PHP execution disabled** in asset directories
- **Direct file access blocked** to sensitive file types
- **Upload directory isolated** with execution prevention

### Content Delivery

- **Static assets**: Served directly by web server (nginx/Apache)
- **Dynamic content**: Routed through PHP entry points
- **Cache headers**: Appropriate caching based on content type
- **CDN integration**: Assets can be served via CDN

## Framework Integration

### Path Resolution

```php
// Framework utilities resolve public paths
$cssPath = f('Public/Static/Asset/CSS/app.css', 'find');
```

### Asset Management

- **Version hashing** for cache busting via build process
- **Asset manifest** for referencing hashed filenames
- **Theme switching** via configuration or user preference

### Security Integration

- **Upload validation** via framework validation patterns
- **Media access control** via PHP before serving
- **Cache headers** set via framework response patterns

## Deployment Considerations

1. **Public directory** is web server document root
2. **Static assets** can be deployed to CDN
3. **Upload directory** may need separate storage (S3, etc.)
4. **Build directory** regenerated on each deployment
5. **Entry points** remain minimal and stable

This structure ensures clean separation between public-facing assets and protected application code while supporting modern web development practices and security requirements.
