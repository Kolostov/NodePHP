# Function Patterns in NodePHP Framework

## Overview: Specialized Functional Units

In the NodePHP framework, function patterns are categorized under `Primitive/Function/` as reusable operations by intent and responsibility. They integrate with framework utilities like `r()` for logging operations, `h()` for extensibility (especially in hooks), `f()` for file-related helpers, `p()` for phase-dependent execution (e.g., in commands), and `env()` for configurable behaviors. Each type promotes separation of concerns, with emphasis on purity where possible, supporting domains from CLI to data processing.

### Function Types Overview

| **Function Type** | **Execution Context**    | **Return Type**     | **Side Effects**      | **Primary Use Case**                      |
| ----------------- | ------------------------ | ------------------- | --------------------- | ----------------------------------------- |
| **Command**       | CLI/Shell via `$argv`    | Exit codes          | High (I/O, processes) | System automation with phases             |
| **Helper**        | Global/Any               | Mixed               | Low/None              | Cross-cutting utilities, logged via `r()` |
| **Hook**          | Event-driven via `h()`   | Void/Modified state | Variable              | Plugin/extensibility points               |
| **Predicate**     | Conditional logic        | Boolean             | None                  | Decision making in validators/specs       |
| **Presenter**     | Output formatting        | Formatted strings   | None                  | Display/API output with `h()`             |
| **Template**      | View rendering via `f()` | HTML/Text           | None                  | UI generation in templates                |
| **Transformer**   | Data pipeline            | Transformed data    | None                  | Data processing, pure functions           |
| **Validator**     | Input checking           | Boolean/Error       | None                  | Data validation with `r()` errors         |

## Function Details

### Function/Command

**Purpose**: CLI entry points for system operations. Bridge logic with OS, use `p("execute")` for lifecycle, `r()` for auditing, and `$argv` for inputs.

```php
<?php declare(strict_types=1);

function cli_database_backup($database_name) {
    h('command_pre_backup', $database_name); // Framework hook
    $filename = "backup_" . date('Y-m-d') . ".sql";
    system("mysqldump {$database_name} > {$filename}");
    $success = file_exists($filename);
    r("Database backup: {$database_name}", "Audit", null, ['success' => $success]);
    p('execute'); // Framework phase
    return $success ? 0 : 1;
}
// Usage: php node cli_database_backup production
// Returns: 0 on success, 1 on failure
```

### Function/Helper

**Purpose**: Global, stateless utilities for reusability. Minimal dependencies, with optional `r()` for debugging if `env('APP_DEBUG')`.

```php
<?php declare(strict_types=1);

function array_key_first_or_default($array, $default = null) {
    if (env('APP_DEBUG', false)) {
        r("Helper: array_key_first_or_default", "Internal", null, ['array_size' => count($array)]);
    }
    return empty($array) ? $default : reset($array);
}

function generate_unique_id($prefix = '') {
    $id = $prefix . uniqid() . '_' . mt_rand(1000, 9999);
    h('helper_unique_id', $id); // Framework hook for overrides
    return $id;
}
// Pure, predictable, no side effects
```

### Function/Hook

**Abstract**: Hook functions are specialized callables that register into or execute within the framework's hook system via `h()`. Unlike hook management utilities, these functions represent the actual behaviors that get triggered when hooks fire - they're the concrete implementations that plugins or modules provide, not the registry mechanism itself.
**Purpose**: Concrete implementations that execute on hook triggers. These functions either modify data (filters) or perform side effects (actions) in response to events. They represent plugin behaviors, not the hooking infrastructure.

### Function/Hook Characteristics

| **Aspect**   | **Description**                    | **Key Difference**       |
| ------------ | ---------------------------------- | ------------------------ |
| **Nature**   | Hook implementation functions      | Not hook registry system |
| **Role**     | Behavior providers for hooks       | Not hook dispatchers     |
| **Location** | Executed when hooks fire via `h()` | Registered via `h()`     |
| **Return**   | Modified data or void              | Process hook arguments   |

### Function/Predicate

**Purpose**: Boolean decision functions for conditions. Enable declarative logic, pure, with `h()` for custom predicates.

```php
<?php declare(strict_types=1);

function is_valid_email($email) {
    $valid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    h('predicate_email', [$email, &$valid]); // Framework hook for extensions
    return $valid;
}

function is_business_hours($timestamp = null) {
    $time = $timestamp ?: time();
    $hour = date('H', $time);
    $day = date('N', $time);
    return $day <= 5 && $hour >= 9 && $hour < 17;
}
// Always return true/false, never throw
```

### Function/Presenter

**Purpose**: Format data for outputs like API/headers. Concerned with representation, uses `h()` for formatting hooks.

```php
<?php declare(strict_types=1);

function format_json_response($data, $status = 200) {
    $formatted = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    h('presenter_json', [&$formatted, $status]); // Framework hook
    header('Content-Type: application/json');
    http_response_code($status);
    return $formatted;
}

function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}
// Transform data for human/machine consumption
```

### Function/Template

**Purpose**: View helpers for HTML/text generation. Separate presentation, use `f()` for partials/templates.

```php
<?php declare(strict_types=1);

function render_button($text, $type = 'primary', $attrs = []) {
    $class = "btn btn-{$type}";
    $attr_str = '';
    foreach ($attrs as $k => $v) {
        $attr_str .= " {$k}=\"{$v}\"";
    }
    $html = "<button class=\"{$class}\"{$attr_str}>{$text}</button>";
    h('template_button', [&$html]); // Framework hook
    return $html;
}

function pluralize($count, $singular, $plural = null) {
    if ($plural === null) $plural = $singular . 's';
    return $count == 1 ? $singular : $plural;
}
// HTML-safe, template-focused utilities
```

### Function/Transformer

**Purpose**: Pure converters for data structures. Maintain transparency, no effects, with `h()` for transformation chains.

```php
<?php declare(strict_types=1);

function snake_to_camel($input) {
    $transformed = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    h('transformer_snake_to_camel', [&$transformed]); // Framework hook
    return $transformed;
}

function transform_user_for_api($user) {
    $transformed = [
        'id' => (int)$user['id'],
        'fullName' => $user['first_name'] . ' ' . $user['last_name'],
        'email' => strtolower($user['email']),
        'meta' => json_decode($user['meta'] ?? '{}', true)
    ];
    return $transformed;
}
// Input → Output, always deterministic
```

### Function/Validator

**Purpose**: Validate inputs against rules. Gatekeepers, return results/errors, use `r()` for failures.

```php
<?php declare(strict_types=1);

function validate_password($password) {
    if (strlen($password) < 8) return 'Too short';
    if (!preg_match('/[A-Z]/', $password)) return 'No uppercase';
    if (!preg_match('/[0-9]/', $password)) return 'No number';
    return true; // Validation passed
}

function validate_order($order) {
    $errors = [];
    if (empty($order['items'])) $errors[] = 'No items';
    if ($order['total'] < 0) $errors[] = 'Invalid total';
    if (!empty($errors)) {
        r("Order validation failed", "Error", null, $errors);
    }
    h('validator_order', [&$errors]); // Framework hook
    return empty($errors) ? true : $errors;
}
// Check constraints, return validation result
```

## Complementary Patterns

**Strategy Pattern** uses Predicate functions for evaluation via `p("resolve")`. **Pipeline Pattern** chains Transformer functions for processing. **Template Method Pattern** uses Hook functions for customization with `h()`. **Decorator Pattern** wraps Helper functions with behavior via traits. **Visitor Pattern** may use Presenter functions for formats in `Presentation/Responder`.

## Distinguishing Characteristics

**vs. Methods**: Functions global/stateless; methods in classes like `Final/Behavioral/` with state. **vs. Filters**: Predicate boolean evaluators; filters transform in `Presentation/Filter`. **vs. Callbacks**: Hooks for extensibility via `h()`; callbacks general in listeners. **vs. Formatters**: Presenters output-focused; Transformers structural in pipelines.
