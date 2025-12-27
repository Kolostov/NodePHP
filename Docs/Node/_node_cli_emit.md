### `_node_cli_emit` Documentation

**Purpose:**
Outputs different forms of the current NodePHP code or its runtime version for inspection or deployment.

---

#### **Function Signature**

```php
_node_cli_emit(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a brief description of the command instead of executing it.

- `argv` (`array`):
  CLI arguments array:
    - First element specifies the type of code output:
        - `"full"`: Returns the complete source code of the current file.
        - `"runtime"`: Returns a stripped-down runtime version, removing all `_node_` and `test_` functions. Intended for production use without tooling.
        - `"include"`: Returns an include-friendly version of the code.
        - Any other value (or empty): Returns the current file with whitespace cleaned.

---

#### **Behavior**

1. If called with `tooltip = true`, returns a short description:
   `<state> States: full, runtime, include`

2. If no argument is provided and not in tooltip mode, defaults to returning a whitespace-cleaned version of the file.

3. For specific arguments:
    - `"full"` → Returns the raw source of the current file.
    - `"runtime"` → Strips all internal tooling and test code for production deployment.
    - `"include"` → Prepares the code for inclusion in other scripts.
    - Default → Cleans whitespace and returns the file contents.

---

#### **Example Usage**

```bash
php node.php emit full
php node.php emit runtime
php node.php emit include
```

---

#### **Notes**

- The `"runtime"` mode produces a version suitable for running NodePHP applications in a **production environment**, without any developer tools or test scaffolding.
- Useful for generating minimal or deployable versions of NodePHP code.
- Whitespace cleaning helps reduce file size for deployment.
