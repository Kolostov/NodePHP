### `_node_cli_new` Documentation

**Purpose:**
Create a new resource file from a boilerplate template. Supports migrations, public files, and regular resources such as enums, classes, or states.

---

## `_node_cli_new`

**Signature:**

```php
_node_cli_new(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`): Return usage/help string if true.
- `argv` (`array`): Arguments `[resource, name]`.

**Behavior:**

1. If `tooltip` is true, returns usage string:
   `"<resource> <name> Creates new resource from boilerplate."`
2. Validates arguments: must include resource type and name.
3. Finds resource path from `_node_structure_call()`.
4. Based on resource type:
    - **Migration:**
        - Generates timestamped PHP migration file.
        - Adds `up()` and `down()` methods if PHP migration.
    - **Public files:**
        - Creates empty `.php` file in `Public` directory.
    - **Regular resources:**
        - Uses `_node_generate_boilerplate()` to generate content.
        - Determines filename, adding the resource suffix if missing.
5. Checks if file already exists; returns error if so.
6. Writes file to target location and returns success message with file size.

**Error Handling:**

- Missing arguments → `"E: Missing argument(s), call new <resource> <name>"`
- Invalid resource name → `"E: Could not create resource, invalid resource name <name>"`
- File already exists → `"E: File <filename> already exists"`

---
