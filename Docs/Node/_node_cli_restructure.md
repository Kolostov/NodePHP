### `_node_cli_restructure` Documentation

**Purpose:**
Performs a filesystem cleanup and ensures the project directory structure matches the expected schema.

---

## `_node_cli_restructure`

**Signature:**

```php
_node_cli_restructure(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`):
  If `true`, returns a short usage description:
  `"<void> Deletes empty folders, recreates schema."`
- `argv` (`array`):
  Currently unused; included for CLI consistency.

**Behavior:**

1. **Clean empty directories:**
    - Calls `_node_clean_empty_dirs(ROOT_PATH)`
    - Recursively scans the project root directory and deletes any empty folders.

2. **Recreate project structure:**
    - Calls `_node_structure_deploy()`
    - Ensures the standard project folder/file schema exists.
    - Typically creates missing folders for resources, migrations, public files, etc.

3. **Return Value:**
    - Returns `"Done.\n"` on completion.

---

**Usage Example:**

```bash
php node.php restructure           # Cleans empty folders and deploys standard project structure
```

**Notes:**

- Designed to maintain a clean and predictable project structure.
- Helps avoid clutter from leftover empty folders after deletions or refactors.
- Lightweight and safe; does not delete non-empty directories or files.
