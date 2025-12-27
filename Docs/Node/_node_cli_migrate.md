### `_node_cli_migrate` Documentation

**Purpose:**
Provides a lightweight migration system for NodePHP projects, supporting both PHP-based and SQL-based migrations with state tracking.

---

## Function Signature

```php
_node_cli_migrate(bool $tooltip = false, array $argv = []): string
```

Supporting helpers (external / included):

- `_node_migrate_up(...)`
- `_node_migrate_down(...)`
- `_node_create_migration(string $migrationPath, string $name): string`

---

## Parameters

### `_node_cli_migrate`

- `tooltip` (`bool`)
  When enabled, returns a short usage description.

- `argv` (`array`)
    - `argv[0]` — action (`status`, `up`, `down`, `create`)
    - `argv[1]` — target migration name (optional, action-dependent)

---

## Usage

```sh
migrate [action] [target]
```

### Actions

| Action   | Description                        |
| -------- | ---------------------------------- |
| `status` | Show applied vs pending migrations |
| `up`     | Apply migrations                   |
| `down`   | Roll back migrations               |
| `create` | Create a new migration scaffold    |

---

## Directory Layout

Migrations are expected under:

```
Migration/
├─ PHP/
│  └─ *.php
└─ SQL/
   ├─ *.php
   ├─ *.sql
   └─ *.down.sql
```

Migration state is tracked in:

```
.migrations.json
```

---

## Migration Tracking

- `.migrations.json` is auto-created if missing
- Structure:

    ```json
    {
        "SQL": [],
        "PHP": []
    }
    ```

- Each applied migration is recorded by filename (without extension)

---

## Action Details

### `status`

Displays migration state per type:

- **APPLIED** — recorded in `.migrations.json`
- **PENDING** — present on disk but not applied

Output is grouped by `SQL` and `PHP`.

---

### `up`

Applies pending migrations.

- Delegates execution to `_node_migrate_up`
- Updates `.migrations.json`
- Supports optional target name to limit execution

---

### `down`

Rolls back applied migrations.

- Delegates execution to `_node_migrate_down`
- Updates `.migrations.json`
- Supports optional target name

---

### `create`

Creates a new migration scaffold.

```sh
migrate create <name>
```

#### Behavior

1. Generates timestamped migration name:

    ```
    YYYYMMDD_HHMMSS_<safe_name>
    ```

2. Prompts for migration type:

    ```
    Migration type (SQL/PHP) [PHP]:
    ```

3. Creates appropriate files and directories

---

## PHP Migration Structure

Generated file:

```php
<?php declare(strict_types=1);

class MigrationName
{
    public function up(): void
    {
        // Migration logic
    }

    public function down(): void
    {
        // Rollback logic
    }
}
```

Location:

```
Migration/PHP/<timestamp>_<name>.php
```

---

## SQL Migration Structure

Generated files:

```
Migration/SQL/<timestamp>_<name>.sql
Migration/SQL/<timestamp>_<name>.down.sql
Migration/SQL/<timestamp>_<name>.php
```

- `.sql` — up migration
- `.down.sql` — rollback SQL
- `.php` — wrapper class referencing SQL files

---

## Error Conditions

- Missing `Migration/` directory
- Invalid action
- Missing migration name on `create`
- Invalid migration type
- Malformed or unreadable tracking file

All errors return descriptive CLI-safe messages.

---

## Notes

- Designed for deterministic, file-based migrations
- No external database abstraction required
- SQL and PHP migrations coexist safely
- Execution order is filename-based (timestamp-driven)
- Compatible with transactional execution via Node phases
