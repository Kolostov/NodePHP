### `_node_migrate_down` Documentation

**Purpose:**
Rolls back previously applied migrations, updating the migration tracking state accordingly. Supports both PHP and SQL migrations and can target a specific migration or roll back the latest applied ones.

---

## Function Signature

```php
_node_migrate_down(array $tracking, string $trackingFile, string $migrationPath, string $target): string
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
  Optional migration name to roll back.
    - Empty string → rollback latest migrations
    - Non-empty → rollback only the specified migration

---

## Behavior

1. Iterates over migration types in fixed order:
    - `SQL`
    - `PHP`

2. For each type:
    - Uses **reverse order** of applied migrations (LIFO)
    - Ensures correct rollback sequencing

3. For each applied migration:
    - Skips non-matching entries if `target` is set
    - Resolves migration file path
    - Executes rollback logic
    - Removes migration from tracking state

4. Writes updated tracking state back to disk if any rollback occurred.

---

## PHP Migration Rollback

- Includes the migration file
- Resolves class name from filename
- If class exists and defines `down()`:
    - Calls `down()` method
- Exceptions are caught and logged per migration

---

## SQL Migration Rollback

- Resolves corresponding `.down.sql` file
- If present:
    - Marks rollback point
    - Execution placeholder is emitted
- Actual SQL execution is intentionally externalized

---

## Targeted Rollback

- When `target` is provided:
    - Stops after rolling back the matching migration
    - Does not continue further down the stack

---

## Tracking Update Rules

- Successfully rolled back migrations are:
    - Removed from `$tracking[type]`
    - Re-indexed to preserve array order
- `.migrations.json` is rewritten only if changes occurred

---

## Output

- Emits human-readable rollback progress
- Lists all rolled back migrations on success
- Emits failure messages per migration if exceptions occur
- Emits a no-op message if nothing was rolled back

---

## Failure Handling

- Missing migration files are silently skipped
- Missing classes or methods do not hard-fail execution
- Exceptions inside rollback logic:
    - Do not abort the entire process
    - Are reported per migration

---

## Notes

- Rollback order is deterministic and safe
- Designed to be idempotent against missing files
- Compatible with NodePHP phase orchestration
- SQL execution is intentionally decoupled from runtime
