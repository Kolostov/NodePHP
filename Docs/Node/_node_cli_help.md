### `_node_cli_help` Documentation

**Purpose:**
Provides a CLI help overview of all available NodePHP commands and related core utility functions.

---

#### **Function Signature**

```php
_node_cli_help(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a brief description of the command instead of the full listing.

- `argv` (`array`):
  Optional argument controlling detailed output:
    - If provided, displays extended function documentation for internal NodePHP utilities.

---

#### **Behavior**

- Scans all user-defined functions for names prefixed with `_node_cli_`.
- Extracts command names by removing the `_node_cli_` prefix.
- Collects tooltips from each command by invoking it with `tooltip = true`.
- Arranges commands into two columns for concise CLI display.
- Provides optional extended descriptions for core functions if any CLI argument is supplied:
    - **f()**: File path resolution and mutation tracker.
    - **r()**: Result logging function with type tagging.
    - **p()**: Atomic phase orchestration with rollback support.
    - **h()**: Hook registration and execution.

---

#### **Return Values**

- Formatted CLI-friendly string containing:
    - List of available commands with tooltips.
    - Optional detailed documentation for core NodePHP utility functions (`f`, `r`, `p`, `h`) if `argv` is set.

---

#### **Example Usage**

```bash
php node.php help          # Displays all commands with basic descriptions
php node.php help yes      # Displays all commands with extended internal function documentation
```

---

#### **Notes**

- Automatically adapts to all user-defined `_node_cli_` commands, making it future-proof for extensions.
- Extended help (`argv` non-empty) also shows available actions, types, and phases for core functions.
- Designed to provide both concise CLI reference and deeper internal documentation for developers.
