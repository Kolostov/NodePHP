# Template Directory Structure

## Overview: Presentation Layer Templates

The Template directory contains all presentation templates separated from business logic, following the separation of concerns principle. Templates are organized by type and purpose, supporting different rendering engines and use cases while maintaining consistency with Node.php's pattern-based architecture.

### Template Philosophy

- **Logic-less templates**: Minimal PHP logic in templates
- **Type separation**: Different template types for different contexts
- **Reusability**: Components and partials for DRY templates
- **Framework integration**: Templates work with View patterns and Renderers
- **Engine flexibility**: Support for multiple template syntaxes

## Template Directory Components

### Template/Blade

**Purpose**: Blade-style template syntax support (optional compatibility layer). Provides familiar Blade syntax (`@directives`, `{{ }}`, `{!! !!}`) for developers accustomed to Laravel's templating engine.

**Characteristics**:

- **Syntax Compatibility**: `@if`, `@foreach`, `@include`, `@yield` directives
- **Compilation**: Templates compiled to PHP for performance
- **Optional Feature**: Can be disabled if not needed
- **Framework Bridge**: Integrates with Node.php View/Renderer patterns
- **Component Support**: Blade-style components with slots

**Example Structure**:

```
Template/Blade/
├── layouts/
│   └── app.blade.php
├── components/
│   └── alert.blade.php
└── views/
    └── users/
        └── profile.blade.php
```

### Template/Component

**Purpose**: Self-contained template components that encapsulate both structure and presentation logic for reusable UI elements. Components are the building blocks of template composition.

**Characteristics**:

- **Self-contained**: HTML, CSS, JS concerns in one component
- **Props/Data**: Accept data through defined interfaces
- **Slots/Children**: Support content insertion
- **Framework Integration**: Used by Component View patterns
- **Reusability**: Can be used across multiple views

**Example Components**:

- `alert.php` - Flash messages and notifications
- `card.php` - Content card with header/body/footer
- `form-input.php` - Form field with label and validation
- `pagination.php` - Pagination controls
- `modal.php` - Modal dialog component

### Template/Email

**Purpose**: Email template files specifically designed for HTML email compatibility. These templates consider email client limitations and follow email HTML best practices.

**Characteristics**:

- **Email-safe HTML**: Inline styles, table-based layouts
- **Responsive Design**: Mobile-friendly email templates
- **Plain Text Fallback**: Accompanying plain text versions
- **Framework Integration**: Used by Email Service patterns
- **Variable Injection**: Dynamic content through template variables

**Example Structure**:

```
Template/Email/
├── layouts/
│   └── email.html.php      # Base email layout
├── welcome.html.php        # Welcome email
├── password-reset.html.php # Password reset email
├── invoice.html.php        # Invoice/receipt email
└── (plain text versions)
```

### Template/Layout

**Purpose**: Base template structures that define common page skeletons. Layouts provide the outer shell that views render into, promoting consistent page structure.

**Characteristics**:

- **Page Shell**: HTML doctype, head, header, footer, scripts
- **Yield/Content Areas**: Placeholders for view-specific content
- **Nested Layouts**: Support for layout inheritance
- **Framework Integration**: Used by View Renderer patterns
- **Theme Support**: Different layouts for different themes/contexts

**Common Layouts**:

- `app.php` - Main application layout
- `admin.php` - Admin panel layout
- `auth.php` - Authentication pages layout
- `api.php` - API documentation layout
- `email.php` - Email template base

### Template/Partial

**Purpose**: Reusable template fragments that represent common UI pieces. Partials are smaller than components and typically don't have their own logic or state.

**Characteristics**:

- **Template Fragments**: Small, reusable pieces of HTML
- **Stateless**: No internal state or complex logic
- **Include-based**: Used via `include` or `partial()` helper
- **Context-aware**: Receive data from parent template
- **Framework Integration**: Used across multiple templates

**Example Partials**:

- `header.php` - Page header/navigation
- `footer.php` - Page footer
- `sidebar.php` - Sidebar navigation
- `breadcrumbs.php` - Breadcrumb navigation
- `meta-tags.php` - SEO meta tags

### Template/View

**Purpose**: HTML/XML view templates that represent complete pages or API responses. Views combine layouts, components, and partials to render final output.

**Characteristics**:

- **Complete Pages**: Full HTML pages or API response structures
- **Data Binding**: Receive data from controllers via array
- **Template Inheritance**: Extend layouts, include components/partials
- **Framework Integration**: Rendered by View Renderer patterns
- **Context-specific**: Different views for web, API, mobile

**Example Structure**:

```
Template/View/
├── web/
│   ├── home.php
│   ├── users/
│   │   ├── index.php
│   │   ├── show.php
│   │   └── edit.php
│   └── products/
│       └── catalog.php
└── api/
    └── v1/
        └── users/
            └── index.php  # JSON/XML API view
```

## Template Composition Hierarchy

```
Layout (base structure)
    ↓
View (page-specific template)
    ↓
Components (reusable UI elements)
    ↓
Partials (common fragments)
```

## Framework Integration Patterns

### View Pattern Integration

```php
// Controller uses View pattern
$view = new TemplateView('users/profile.php', ['user' => $user]);
$response = new HttpResponse($view->render());
```

### Renderer Pattern Usage

```php
// Renderer processes template with data
$renderer = new PhpTemplateRenderer();
$html = $renderer->render('Template/View/web/users/profile.php', $data);
```

### Hook-based Customization

```php
// Template behavior extensible via hooks
h('template.before.render', function ($template, $data) {
    // Add global template variables
    $data['current_user'] = $_SESSION['user'] ?? null;
    return $data;
});
```

### Component Pattern Integration

```php
// Component usage in templates
<?php component('alert', ['type' => 'success', 'message' => 'Saved!']) ?>
```

## Template Engine Support

### PHP Native Templates

- **Default**: Plain PHP with `<?php ?>` tags
- **Advantages**: No compilation needed, full PHP power
- **Disciplined Use**: Business logic kept out of templates

### Optional Template Engines

- **Blade Syntax**: Via Template/Blade compatibility layer
- **Twig/Smarty**: Can be integrated if needed
- **Markdown**: For content-heavy templates

### Template Features

- **Escaping**: Auto-escaping for security (`htmlspecialchars`)
- **Inheritance**: Layout extension and section overriding
- **Includes**: Partial and component inclusion
- **Conditionals/Loops**: Basic control structures
- **Translation**: Integrated with Translation patterns

## Development Workflow

1. **Design**: Create layout structure in Template/Layout
2. **Componentize**: Build reusable UI in Template/Component
3. **Fragment**: Extract common parts to Template/Partial
4. **Implement**: Create specific views in Template/View
5. **Specialize**: Email templates in Template/Email
6. **Optional**: Add Blade syntax templates for compatibility

This structure provides a clear, organized approach to templating that supports both simple PHP templates and optional template engine syntax while maintaining separation of concerns and framework integration.
