### `_node_cli_deprecate` Documentation

**Purpose:**
Creates a timestamped copy of a specified resource file in the `Deprecated` directory. This allows safely marking code as deprecated without deleting the original file.

---

#### **Function Signature**

```php
_node_cli_deprecate(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a short description of the command without performing the deprecation action.

- `argv` (`array`):
  CLI arguments array:
    - First element: resource type (e.g., node name, module name).
    - Second element: name of the file or class to deprecate.

---

#### **Behavior**

1. **Validation**
    - Returns an error if required arguments (`resource` and `name`) are missing.
    - Checks whether the specified resource exists in the node structure.

2. **File Discovery**
    - Searches for the file using patterns:
        - Exact match: `resource/name.php`
        - Partial match: `resource/name.*.php` or `resource/*name*.php`
    - Returns an error if no matching file is found.

3. **Deprecated Copy Creation**
    - Determines the target path under `ROOT_PATH/Deprecated/`.
    - Creates the directory if it does not exist.
    - Copies the original file with a timestamp appended to its name:
      `<original_name>_<YYYYMMDD_HHMMSS>.php`

4. **Output**
    - On success: `Deprecated: <original_file> → <deprecated_file> (<size> bytes)`
    - On failure: descriptive error message starting with `E:`.

---

#### **Example Usage**

```bash
php node.php deprecate State SomeName
```

- Copies `State/SomeName.php` to `Deprecated/State/SomeName_20251227_153000.php`.

---

#### **Return Value**

- Success: Information about the deprecated copy including size in bytes.
- Failure: Error message detailing the reason (missing arguments, invalid resource, or copy failure).

---

#### **Notes**

- Ensures original files remain intact.
- Organizes deprecated files with timestamped names to prevent overwriting.
- Works recursively across the node structure as defined by `_node_structure_call()`.
- Intended for CLI use within NodePHP framework.
