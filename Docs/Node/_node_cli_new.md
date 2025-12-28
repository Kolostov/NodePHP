### `_node_cli_new` Documentation

**Purpose:**
Create new project resources using predefined boilerplates. Supports migrations, public files, and regular resources while automatically preparing documentation stubs.

---

## `_node_cli_new`

**Signature:**

```php
_node_cli_new(bool $tooltip = false, array $argv = []): string
```

---

### Parameters

- **`$tooltip`** (`bool`)
  When `true`, returns a short usage/help string and exits.

- **`$argv`** (`array`)
  CLI arguments in the form:

    ```
    [resource, name]
    ```

---

### Behavior

1. **Tooltip mode**
    - Returns:

        ```
        <resource> <name> Creates new resource from boilerplate.
        ```

2. **Argument validation**
    - Requires both `<resource>` and `<name>`.
    - Missing arguments return an error.

3. **Resource resolution**
    - Iterates through `_node_structure_call()` to match the requested resource.
    - Resolves the target directory and boilerplate type.

4. **Resource handling**
    - **Migrations**
        - Prepends timestamp (`Ymd_His`) to filename.
        - Sanitizes name to alphanumeric + underscores.
        - PHP migrations generate a class with `up()` and `down()` methods.

    - **Public resources**
        - Creates an empty `.php` file (extension auto-added if missing).

    - **Regular resources**
        - Generates content via `_node_generate_boilerplate()`.
        - Automatically appends resource suffix if not already present.
        - Creates a matching documentation file in `/Docs/<Name>.md`.

5. **File creation**
    - Prevents overwriting existing files.
    - Writes file contents and reports size on success.

---

### Error Handling

- **Missing arguments**

    ```
    E: Missing argument(s), call new <resource> <name>
    ```

- **Invalid resource**

    ```
    E: Could not create resource, invalid resource name <resource>
    ```

- **File already exists**

    ```
    E: File <path> already exists.
    ```

- **Documentation creation failure**

    ```
    E: Could not prepare Docs at <path> for new file.
    ```

---

### Return Value

Returns a status message indicating success or failure, including file path and size when applicable.

---

If you want, next we can:

- compress this to **README-sized**
- generate **CLI usage examples**
- or add **behavior tables** (resource → output mapping)
