### `_node_cli_search` Documentation

**Purpose:**
Searches for a query string across all project code files, returning matching lines with context.

---

## `_node_cli_search`

**Signature:**

```php
_node_cli_search(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`):
  If `true`, returns a short usage description:
  `"<query...> Search across all code files"`

- `argv` (`array`):
  Command-line arguments; expected to contain the search query as one or more strings.

---

**Behavior:**

1. **Check for query:**
    - If no query is provided in `$argv`, returns:
      `"E: Provide search query\n"`

2. **Prepare search:**
    - Joins `$argv` into a single string `$searchQuery`.
    - Defines file extensions to search:
      `php, js, css, html, scss, json, xml, yml, yaml, md, txt`
    - Excludes directories:
      `vendor, Database, Logs, Backup, Deprecated`

3. **Search process:**
    - Iterates through project structure from `ROOT_PATH` and `_node_structure_call()`.
    - Skips any paths that contain excluded directories.
    - For each allowed extension, scans all matching files using `glob()`.
    - Reads file lines; skips files that cannot be read.
    - Performs case-insensitive search using `stripos()`.
    - For matches:
        - Adds the file path and line number of the first match.
        - Displays the matched line:
            - If line length > 114, shows a 100-character snippet centered around the match.
            - Otherwise, prints the full line with a `>  ` prefix.
        - Separates matches in a file with a newline.

4. **Return Value:**
    - If matches are found: returns formatted matches including file, line, and snippets.
    - If no matches: returns `"No matches found for: {$searchQuery}\n"`

---

**Usage Example:**

```bash
php node.php search "capture_state"     # Search for 'capture_state' across project
php node.php search "TODO fix this"     # Search for multi-word queries
```

**Notes:**

- Efficiently avoids searching in large or irrelevant directories like `vendor` or `Logs`.
- Snippet display ensures readability for long lines.
- Can be adapted to add more extensions or exclude additional folders.
