# Database System Patterns in NodePHP Framework

## Overview: Persistence Layer Architecture

In the NodePHP framework, database patterns are structured under `Database/` and provide approaches to persistence, from connections to abstractions. Each component integrates with framework utilities like `f()` for schema files, `r()` for query logging, `h()` for lifecycle hooks (e.g., pre-migrate), `p()` for phases (e.g., persist for seeds, migrate for schema changes), and `env()` for connection configs. This handles data lifecycle, supporting both relational and flat file storage (e.g., Flat/JSON), with validation and generation.

### Database Components Overview

| **Component**              | **Layer**            | **Primary Responsibility**                     | **Volatility** | **Pattern Family** |
| -------------------------- | -------------------- | ---------------------------------------------- | -------------- | ------------------ |
| **Connection**             | Infrastructure       | Connection pooling & configuration via `env()` | Low            | Structural         |
| **Factory**                | Data Generation      | Model instantiation for testing with hooks     | High           | Creational         |
| **Fixture**                | Testing              | Predefined test datasets, loaded via `f()`     | Never          | Creational         |
| **Migration/Programmatic** | Schema Evolution     | Class-based schema changes in phases           | Low            | Behavioral         |
| **Migration/Raw**          | Schema Evolution     | Direct SQL migrations with logging             | Low            | Behavioral         |
| **Procedure**              | Database Logic       | Server-side business logic definitions         | Low            | Behavioral         |
| **Schema**                 | Structure Definition | DDL and table definitions via files            | Very Low       | Structural         |
| **Seed**                   | Data Population      | Initial data insertion in persist phase        | Once           | Behavioral         |
| **Trigger**                | Event Handling       | Automatic data reactions with hooks            | Low            | Behavioral         |
| **View**                   | Data Abstraction     | Virtual table definitions                      | Low            | Structural         |

## Component Details

### Database/Connection

**Purpose**: Manages connections and pools. Uses `env()` for configs, supports flat file via Flat/Storage, with `r()` for connection logging.

```php
<?php declare(strict_types=1);

final class DatabaseConnection
{
    private $pdo;
    private $config;

    public function __construct() {
        $this->config = [
            'host' => env('DB_HOST', 'localhost'),
            'database' => env('DB_NAME', 'app_db'),
            'username' => env('DB_USER'),
            'password' => env('DB_PASS')
        ];
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']}";
        $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password']);
        r("Database connected: {$this->config['database']}", "Internal");
        h('db_connection_init', $this); // Framework hook
    }

    public function query($sql) {
        r("Query: {$sql}", "Internal");
        return $this->pdo->query($sql);
    }
}
```

### Database/Factory

**Purpose**: Generates model instances for testing/seeding. Uses hooks for customization, integrates with Faker if extended via `Extension/Library`.

```php
<?php declare(strict_types=1);

final class UserFactory
{
    public static function make() {
        h('factory_pre_make', 'User'); // Framework hook
        return [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'active' => true
        ];
    }

    public static function create() {
        $data = self::make();
        // Insert into database, e.g., via connection
        r("User created via factory", "Internal", null, $data);
        p('persist'); // Framework phase
        return $data;
    }
}
```

### Database/Fixture

**Purpose**: Provides predefined datasets for testing. Loaded via `f()` from Fixture files, ensures predictable states.

```php
<?php declare(strict_types=1);

final class UserFixture
{
    public static function load() {
        $path = f('Database/Fixture/users.json', 'find');
        if ($path) {
            $data = json_decode(f($path, 'read'), true);
            r("Fixture loaded: users", "Internal");
            return $data ?? [
                ['id' => 1, 'name' => 'Admin'],
                ['id' => 2, 'name' => 'Editor'],
                ['id' => 3, 'name' => 'Viewer']
            ];
        }
        throw new RuntimeException("Fixture not found");
    }
}
```

### Database/Migration/Programmatic

**Purpose**: Programmatic schema changes. Executed in `p("migrate")` phase, with `h()` for pre/post migration hooks.

```php
<?php declare(strict_types=1);

final class CreateUsersTable
{
    public function up() {
        h('migration_pre_up', 'CreateUsersTable'); // Framework hook
        // Schema builder calls
        r("Creating users table", "Internal");
        p('migrate'); // Framework phase
    }

    public function down() {
        r("Dropping users table", "Internal");
        p('migrate');
    }
}
```

### Database/Migration/Raw

**Purpose**: Direct SQL migrations for complexity. Uses `r()` for SQL logging, executed in phases.

```php
<?php declare(strict_types=1);

final class AddEmailColumn
{
    public function up() {
        $sql = "ALTER TABLE users ADD email VARCHAR(255)";
        r("Raw migration up: {$sql}", "Internal");
        return $sql;
    }

    public function down() {
        $sql = "ALTER TABLE users DROP COLUMN email";
        r("Raw migration down: {$sql}", "Internal");
        return $sql;
    }
}
```

### Database/Procedure

**Purpose**: Stored procedures for server-side logic. Defined as SQL, with `h()` for procedure hooks.

```php
<?php declare(strict_types=1);

final class UserProcedures
{
    public static function createUserSQL() {
        $sql = "CREATE PROCEDURE create_user(IN name VARCHAR(100))
                BEGIN
                    INSERT INTO users (name) VALUES (name);
                END";
        r("Procedure defined: create_user", "Internal");
        h('procedure_define', $sql); // Framework hook
        return $sql;
    }
}
```

### Database/Schema

**Purpose**: Defines structure via DDL. Stored in Schema files via `f()`, loaded in boot phase.

```php
<?php declare(strict_types=1);

final class UserSchema
{
    public static function definition() {
        $def = [
            'table' => 'users',
            'columns' => [
                'id' => 'INT PRIMARY KEY',
                'name' => 'VARCHAR(100)'
            ]
        ];
        r("Schema definition: users", "Internal", null, $def);
        return $def;
    }
}
```

### Database/Seed

**Purpose**: Populates with initial data. Runs in `p("persist")`, uses factories or fixtures.

```php
<?php declare(strict_types=1);

final class DatabaseSeeder
{
    public function run() {
        // Insert initial data
        r("Seeding users table", "Internal");
        // e.g., UsersTableSeeder::run();
        p('persist'); // Framework phase
    }
}
```

### Database/Trigger

**Purpose**: Triggers for automatic reactions. Defined as SQL, with logging and hooks.

```php
<?php declare(strict_types=1);

final class AuditTrigger
{
    public static function createSQL() {
        $sql = "CREATE TRIGGER audit_user_changes
                AFTER UPDATE ON users
                FOR EACH ROW
                BEGIN
                    INSERT INTO audit_log (user_id, action)
                    VALUES (OLD.id, 'updated');
                END";
        r("Trigger defined: audit_user_changes", "Internal");
        h('trigger_define', $sql); // Framework hook
        return $sql;
    }
}
```

### Database/View

**Purpose**: Virtual tables for queries. Defined as SQL, integrated with schema.

```php
<?php declare(strict_types=1);

final class ActiveUsersView
{
    public static function definition() {
        $sql = "CREATE VIEW active_users AS
                SELECT * FROM users WHERE active = 1";
        r("View defined: active_users", "Internal");
        return $sql;
    }
}
```

## Complementary Patterns

**Repository Pattern** abstracts access in `Domain/Repository`, above these. **Data Mapper Pattern** translates objects/database via `Infrastructure/Mapper`. **Unit of Work Pattern** manages transactions with `p("persist")`. **Identity Map Pattern** prevents duplicates in caches. **Active Record Pattern** builds on models in `Domain/Model`.

## Distinguishing Characteristics

**vs. ORM Patterns**: Infrastructure components; ORM via models/repositories. **vs. CQRS**: Components handle storage; CQRS separates read/write in services. **vs. Data Access Object**: DAO higher-level over primitives, aligned with gateways.
