### `_node_cli_info` Documentation

**Purpose:**
Displays general system information about the current NodePHP runtime or retrieves documentation from project and Node documentation directories.

---

#### **Function Signature**

```php
_node_cli_info(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`)
  When `true`, returns a short usage description for CLI help output.

- `argv` (`array`)
  Optional arguments:
    - No arguments → show runtime/system information.
    - One argument → treated as a documentation lookup key.

---

#### **Behavior**

##### **Tooltip Mode**

- Returns a concise usage hint describing available modes:
    - void
    - resource
    - func
    - class

---

##### **Documentation Lookup Mode** (`argv[0]` provided)

- Argument is normalized to lowercase.
- Searches for Markdown documentation files in:
    - `Docs/Node/`
    - `Docs/`
- Lookup order:
    1. Exact filename match: `<arg>.md`
    2. Full-text search across all `.md` files in documentation paths
- If exact match is found:
    - File contents are returned directly.
- If no exact match:
    - Performs a ranked full-text search.
    - Returns match counts per file and total hits.

---

##### **System Information Mode** (no arguments)

Outputs a summary including:

- Node name
- Root path
- PHP version and SAPI
- Number of structure categories
- Number of loaded classes
- Number of detected log files

---

#### **Return Value**

- Always returns a formatted string suitable for CLI output.
- Content depends on selected mode:
    - Tooltip
    - Documentation content
    - Search results
    - Runtime/system overview

---

#### **Notes**

- Documentation lookup is case-insensitive.
- Node-internal documentation (`Docs/Node`) has priority over project-level documentation.
- Full-text search results are ranked by occurrence count.
- Uses the shared `f()` utility for safe file access.
- Intended as both a discovery tool and a lightweight documentation browser within NodePHP.
