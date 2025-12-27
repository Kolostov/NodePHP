### `_node_migrate_up` Documentation

**Purpose:**
Applies pending migrations, updating the migration tracking state accordingly. Supports both PHP and SQL migrations and can target a specific migration or apply all pending ones.

---

## Function Signature

```php
_node_migrate_up(array $tracking, string $trackingFile, string $migrationPath, string $target): string
```

---

## Parameters

- `tracking` (`array`)
  Current migration state loaded from `.migrations.json`, grouped by type:
    - `SQL`
    - `PHP`

- `trackingFile` (`string`)
  Absolute path to the `.migrations.json` file used for persistence.

- `migrationPath` (`string`)
  Absolute path to the `Migration/` directory.

- `target` (`string`)
  Optional migration name to apply.
    - Empty string → apply all pending migrations
    - Non-empty → apply only the specified migration

---

## Behavior

1. Iterates over migration types in order:
    - `SQL`
    - `PHP`

2. For each type:
    - Lists all migration files sorted by filename (chronological)
    - Skips migrations already recorded as applied in `$tracking[type]`

3. For each migration:
    - Skips if it does not match `target` when specified
    - Executes migration logic:
        - **PHP migrations:**
            - Includes migration file
            - Resolves class name from filename
            - Instantiates class and calls `up()` if method exists
        - **SQL migrations:**
            - Finds corresponding `.sql` file
            - Emits placeholder for SQL execution (manual execution expected)

4. Updates `$tracking` by adding successfully applied migrations

5. Writes updated `$tracking` to `.migrations.json` if any migrations were applied

---

## Targeted Application

- If `target` is specified:
    - Only migrations matching the target filename are applied
    - Other pending migrations are skipped

---

## Output

- Emits human-readable migration progress
- Lists all applied migrations on success
- Reports failures per migration if exceptions occur
- Indicates when no new migrations are available

---

## Failure Handling

- Missing migration files are silently skipped
- Missing classes or `up()` methods do not stop execution
- Exceptions inside migration logic:
    - Do not abort the entire process
    - Are reported individually per migration

---

## Notes

- Migration order is deterministic due to filename sorting
- Designed to be idempotent against already-applied migrations
- SQL execution is intentionally left decoupled
- Compatible with NodePHP phase orchestration
