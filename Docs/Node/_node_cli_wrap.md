### `_node_cli_wrap` Documentation

**Purpose:**
Handles wrapping and unwrapping of `node.php` into separate modular files per section. This allows maintaining each logical section in its own file while keeping a single entry point.

---

## `_node_cli_wrap`

**Signature:**

```php
_node_cli_wrap(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`): If `true`, returns a short description:
  `"<open|close> Wraps/unwraps node.php into separate files."`
- `argv` (`array`): Command-line arguments:
    1. `action` – `"open"` to split node.php into section files, `"close"` to merge section files back into node.php.

**Behavior:**

- Uses `match` statement to delegate:
    - `"open"` → `_node_wrap_open()`
    - `"close"` → `_node_wrap_close()`
    - Any other → returns usage error

---

## `_node_wrap_open`

**Purpose:**
Extracts sections from `node.php` and writes each to a separate `node.<section>.php` file. Replaces the original section in `node.php` with an `include_once` statement.

**Steps:**

1. Loads `node.php` content.
2. Calls `_node_extract_sections()` to identify sections by `# <name> begin` / `# <name> end` markers.
3. For each section:
    - Creates a new `node.<fullSectionName>.php` file.
    - Writes the section content with `<?php declare(strict_types=1);` prepended.
    - Replaces original content in `node.php` with an `include_once` statement.
4. Returns a summary: `"✓ Wrapped X sections"` or `"✓ node.php is already wrapped"` if no new sections found.

---

## `_node_wrap_close`

**Purpose:**
Reverts the wrapping process: reads included section files and restores their content inline in `node.php`.

**Steps:**

1. Loads `node.php` content.
2. Calls `_node_find_wrapped_sections()` to locate `include_once` lines within section markers.
3. For each section:
    - Reads content of `node.<fullSectionName>.php`.
    - Recursively closes any nested wrapped sections.
    - Removes `<?php declare(strict_types=1);` line.
    - Indents content according to section's original marker.
    - Replaces the include line with full section content.
    - Deletes the separate section file.
4. Returns a summary: `"✓ Unwrapped X sections"` or `"✓ node.php is already unwrapped"` if no include lines found.

---

## Section Parsing Helpers

### `_node_extract_sections`

- Recursively parses content for `# <name> begin` / `# <name> end` blocks.
- Returns array of sections:
    ```php
    [
      "name" => "sectionName",
      "fullName" => "parent.sectionName",
      "indent" => string,      // original line indentation
      "start" => int,          // line index of begin marker
      "end" => int,            // line index of end marker
      "content" => string,     // section content
    ]
    ```

### `_node_find_wrapped_sections`

- Identifies sections already wrapped (i.e., replaced with `include_once "node.<fullName>.php";`).
- Returns array of wrapped sections with same structure as `_node_extract_sections` but without `content`.

### `_node_process_sections_open`

- Saves each extracted section to its own file.
- Replaces section in `node.php` with an `include_once` reference.

### `_node_process_sections_close`

- Reads each section file, restores its content inline, and removes the separate file.
- Handles nested wrapped sections recursively.

---

## Indentation Helpers

### `_node_remove_relative_indentation`

- Removes base indentation from each line in section content.

### `_node_add_relative_indentation`

- Adds base indentation to each line of section content.

---

### Usage Examples

```bash
php node.php wrap open   # Split node.php into modular section files
php node.php wrap close  # Merge section files back into node.php
php node.php wrap        # Shows usage error
```

---

### Notes

- Section markers must follow strict format: `# <name> begin` and `# <name> end`.
- Nested sections are supported and recursively processed.
- Indentation is preserved for readability.
- Designed for development workflow: modularity while keeping single entry point.
