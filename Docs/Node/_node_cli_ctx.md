### `_node_cli_ctx` Documentation

**Purpose:**
Displays the complete context of a specified class or function, including its source code and all related dependencies, with optional ranking information. This is useful for understanding relationships and usage in the NodePHP framework.

---

#### **Function Signature**

```php
_node_cli_ctx(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a short description of the command without performing context analysis.

- `argv` (`array`):
  CLI arguments array. The first element must be the name of a class or function to inspect.

---

#### **Behavior**

1. **Validation**
    - Returns an error if no target class or function is provided.

2. **Target Discovery**
    - Recursively searches project directories for PHP files containing the target.
    - Skips directories: `vendor`, `Database`, `Logs`, `Backup`, `Deprecated`.
    - Supports:
        - Functions (including standalone global functions)
        - Classes (including full class body extraction)

3. **Dependency Analysis**
    - Parses the PHP source of the target using `token_get_all`.
    - Detects:
        - Classes referenced via `extends` or `new` statements
        - Function calls within the target
    - Excludes common PHP language keywords and internal framework helpers.

4. **Related Code Extraction**
    - Locates full definitions of external classes and functions used by the target.
    - Excludes trivial CLI-related helpers and test scaffolding.
    - Displays related code below the main target for context.

5. **Ranking Analysis**
    - Calls `_node_cli_rank()` to provide ranking or importance of the file within the project.
    - Optional: could include resource listings from `_node_cli_list()`.

6. **Output Formatting**
    - Displays:
        - Relative file path of the target
        - Optional description from node structure
        - Full source of target
        - Related dependencies’ code
        - Ranking analysis
    - Each section clearly separated with headers for readability.

---

#### **Example Usage**

```bash
php node.php ctx SomeClass
php node.php ctx someFunction
```

- First example outputs the full source and dependencies for `SomeClass`.
- Second example outputs context for `someFunction`.

---

#### **Return Value**

- On success: Formatted string containing target source, related code, and ranking information.
- On failure: Error string starting with `E:`.

---

#### **Notes**

- Excluded directories prevent vendor or generated code from polluting the context.
- Both class and function dependencies are fully resolved.
- Designed to aid deep inspection and debugging in the NodePHP framework.
