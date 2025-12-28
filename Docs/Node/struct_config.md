# Configuration Management Patterns in NodePHP Framework

## Overview: Purpose & Implementation Approaches

In the NodePHP framework, configuration management is handled through the `Config/` structure, separating parameters from logic via `.env` files and `env()` function. This enables environment-specific behavior, integrates with phases like `p("boot")` for loading, and uses `f()` for file-based configs, `r()` for access logging, and `h()` for extension hooks. Each type serves operational domains with externalization, validation via predicates, and controlled access.

### Configuration Types Overview

| **Config Type** | **Primary Purpose**  | **Storage Method**   | **Refresh Rate** | **Access Pattern**                     |
| --------------- | -------------------- | -------------------- | ---------------- | -------------------------------------- |
| **App**         | Application behavior | File/Env via `env()` | Restart          | Read-heavy, hooked via `h()`           |
| **Cache**       | Performance tuning   | File                 | Runtime          | Read/write, with `r()` logging         |
| **Core**        | Framework settings   | File                 | Never            | Read-only, phase-loaded in `p("boot")` |
| **Database**    | Data persistence     | File/Env             | Restart          | Connection-time, validated             |
| **Env**         | Environment vars     | OS/File via `.env`   | Process-start    | Read-only, via `env()`                 |
| **Mail**        | Communication        | File                 | Restart          | Read-only, with hooks                  |
| **Module**      | Feature toggles      | File/DB              | Runtime          | Read/write, dynamic                    |
| **Queue**       | Task processing      | File/Env             | Runtime          | Read/write, queued via `Queue/`        |

## Configuration Type Details

### Config/App

**Purpose**: Controls application-level behavior, routing, middleware, and settings. Loaded during `p("boot")`, uses `env()` for values and `h()` for overrides.

```php
<?php declare(strict_types=1);

final class AppConfig
{
    private $settings = [];

    public function __construct() {
        p('boot'); // Framework phase for loading
        $this->settings = [
            'debug' => env('APP_DEBUG', false),
            'timezone' => env('APP_TIMEZONE', 'UTC'),
            'locale' => env('APP_LOCALE', 'en_US'),
            'maintenance' => env('APP_MAINTENANCE', false),
            'providers' => h('app_providers', []) // Framework hook for extensions
        ];
    }

    public function get($key) {
        $value = $this->settings[$key] ?? null;
        r("AppConfig access: {$key}", "Internal", null, ['value' => $value]);
        return $value;
    }
}
```

### Config/Cache

**Purpose**: Manages caching strategies and drivers. Supports runtime refresh, uses `f()` for file cache paths and `r()` for ops logging.

```php
<?php declare(strict_types=1);

final class CacheConfig
{
    private $drivers = [];

    public function __construct() {
        $this->drivers = [
            'default' => env('CACHE_DRIVER', 'redis'),
            'redis' => ['host' => env('REDIS_HOST', '127.0.0.1'), 'port' => env('REDIS_PORT', 6379)],
            'file' => ['path' => f('Config/Cache/path', 'find') ?? '/tmp/cache'],
            'ttl' => env('CACHE_TTL', 3600)
        ];
    }

    public function driver($name) {
        $driver = $this->drivers[$name] ?? null;
        r("CacheConfig driver access: {$name}", "Internal");
        return $driver;
    }
}
```

### Config/Core

**Purpose**: Defines framework-level constants and paths. Loaded immutably in `p("boot")`, uses `ROOT_PATH` from framework.

```php
<?php declare(strict_types=1);

final class CoreConfig
{
    private $core = [];

    public function __construct() {
        p('boot'); // Framework phase
        $this->core = [
            'version' => env('CORE_VERSION', '1.0.0'),
            'paths' => [ROOT_PATH . 'App', ROOT_PATH . 'Public', ROOT_PATH . 'Storage'],
            'autoload' => h('core_autoload', ['classes', 'helpers']), // Hook for additions
            'base_namespace' => env('CORE_NAMESPACE', 'App\\')
        ];
    }

    public function all() {
        r("CoreConfig all access", "Internal");
        return $this->core;
    }
}
```

### Config/Database

**Purpose**: Configures connections and pools. Validates on load, integrates with `Database/Connection`, uses `env()` for secrets.

```php
<?php declare(strict_types=1);

final class DatabaseConfig
{
    private $connections = [];

    public function __construct() {
        $this->connections = [
            'default' => [
                'driver' => env('DB_DRIVER', 'mysql'),
                'host' => env('DB_HOST', 'localhost'),
                'database' => env('DB_NAME', 'app_db'),
                'charset' => env('DB_CHARSET', 'utf8mb4')
            ]
        ];
        $this->validateConnections();
    }

    private function validateConnections(): void {
        foreach ($this->connections as $name => $conn) {
            if (empty($conn['host'])) {
                throw new RuntimeException("Invalid DB config for {$name}");
            }
        }
        r("DatabaseConfig validated", "Internal");
    }

    public function connection($name = 'default') {
        return $this->connections[$name] ?? null;
    }
}
```

### Config/Env

**Purpose**: Manages environment vars and secrets via `.env`. Directly uses framework's `env()` , with `f()` for loading.

```php
<?php declare(strict_types=1);

final class EnvConfig
{
    public function load($file = '.env') {
        $path = f($file, 'find');
        if ($path) {
            // Framework already loads .env, but hook for custom
            h('env_load', $path);
            r(".env loaded from {$path}", "Internal");
        }
    }

    public function get($key, $default = null) {
        $value = env($key, $default);
        r("EnvConfig get: {$key}", "Internal", null, ['value' => $value]);
        return $value;
    }
}
```

### Config/Mail

**Purpose**: Configures email settings. Uses `env()` for sensitive data, `h()` for driver extensions.

```php
<?php declare(strict_types=1);

final class MailConfig
{
    private $mail = [];

    public function __construct() {
        $this->mail = [
            'driver' => env('MAIL_DRIVER', 'smtp'),
            'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'port' => env('MAIL_PORT', 2525),
            'encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'timeout' => env('MAIL_TIMEOUT', 30)
        ];
        h('mail_config', $this->mail); // Hook for modifications
    }

    public function settings() {
        return $this->mail;
    }
}
```

### Config/Module

**Purpose**: Controls feature flags. Supports runtime toggles, uses `env()` or DB via `f()` for persistence.

```php
<?php declare(strict_types=1);

final class ModuleConfig
{
    private $modules = [];

    public function __construct() {
        $this->modules = [
            'api' => env('MODULE_API', true),
            'admin' => env('MODULE_ADMIN', false),
            'cron' => env('MODULE_CRON', true),
            'export' => env('MODULE_EXPORT', false)
        ];
    }

    public function enabled($module) {
        $enabled = $this->modules[$module] ?? false;
        r("Module check: {$module} - {$enabled}", "Internal");
        return $enabled;
    }
}
```

### Config/Queue

**Purpose**: Manages queue drivers and policies. Runtime configurable, integrates with `Queue/` structure.

```php
<?php declare(strict_types=1);

final class QueueConfig
{
    private $queue = [];

    public function __construct() {
        $this->queue = [
            'default' => env('QUEUE_DRIVER', 'redis'),
            'connections' => [
                'redis' => ['queue' => 'default', 'retry' => env('QUEUE_RETRY', 3)],
                'database' => ['table' => 'jobs', 'timeout' => env('QUEUE_TIMEOUT', 60)]
            ]
        ];
    }

    public function connection($name) {
        return $this->queue['connections'][$name] ?? null;
    }
}
```

## Complementary Patterns

**Factory Pattern** creates config objects via `p("discover")`. **Singleton Pattern** via traits like `Singleton` in `Primitive/Trait/`. **Builder Pattern** for complex configs using `Creational/Builder`. **Adapter Pattern** normalizes sources with `Structural/Adapter`. **Strategy Pattern** switches configs dynamically via `h()`.

## Avoid Mixing With

**Flyweight Pattern** as configs are environment-unique. **Prototype Pattern** since no cloning needed. **Mutable State Patterns** - use immutable post-init, with `r()` for changes. Avoid injecting full configs; use specific values via `env()`.

## Distinguishing Characteristics

**vs. Registry Pattern**: Configs structured/typed; registry global via globals. **vs. Service Locator**: Configs data-only; locator in `Coordination/Provider`. **vs. Feature Toggle**: Config/Module subset for toggles, integrated with `Extension/Plugin`.
