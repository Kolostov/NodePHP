### `_node_cli_log` Documentation

**Purpose:**
Provides a unified CLI interface for inspecting, reading, clearing, and tailing NodePHP log files.

This command acts as a dispatcher that routes log-related actions to specialized handlers.

---

## Function Signatures

```php
_node_cli_log(bool $tooltip = false, array $argv = []): string
```

Supporting handlers:

- `_node_list_logs(array $options): string`
- `_node_show_logs(array $options): string`
- `_node_clear_logs(array $options): string`
- `_node_tail_logs(array $options): string`

---

## Parameters

### `_node_cli_log`

- `tooltip` (`bool`)
  When enabled, returns a short usage hint.

- `argv` (`array`)
    - `argv[0]` — action name (`list`, `show`, `clear`, `tail`)
    - Remaining values are passed as options to the selected action

---

## Actions Overview

### `log list`

**Description:**
Lists all available log files known to the system.

**Details shown per log:**

- Log type
- Full path
- File size (KB)
- Last modified timestamp

**Summary footer:**

- Total number of log files
- Combined size in MB

If no log files exist, this is explicitly reported.

---

### `log show <file|type> [limit]`

**Description:**
Displays parsed log entries from one or more log files.

**Matching rules (in order):**

1. Exact match by log type
2. Exact match by full file path
3. Partial match against file path

**Behavior:**

- Default entry limit: `50`
- Aggregates entries across all matched files
- Shows newest entries first
- Truncates long messages for readability
- Pretty-prints structured data (up to 3 lines)

**Special case:**

- `system` logs cannot be shown and must be tailed instead

---

### `log clear <file|type|all>`

**Description:**
Clears log file contents without deleting the files.

**Modes:**

- `all` — clears all writable log files
- `<type>` — clears all logs of the given type
- `<file>` — clears a specific log file

**Output includes:**

- Number of cleared files
- Amount of disk space freed

If no logs can be cleared, permission or path issues are reported.

---

### `log tail <file> [lines]`

**Description:**
Displays the last N lines of a log file using the system tail command.

**Defaults:**

- Lines: `10`

**Requirements:**

- File must exist
- Intended for live inspection and system-level logs

**Notes:**

- Uses shell execution
- Returns raw tail output

---

## Error Handling

- Unknown action → explicit error with valid actions listed
- Missing required arguments → usage hint + context-aware listing
- Non-existent files → clear error message
- Permission issues → reported during clear operations

---

## Return Value

- Always returns formatted CLI-safe text
- Suitable for direct output
- All outputs are newline-terminated

---

## Design Notes

- Log discovery is centralized via `_node_get_all_log_files`
- Parsing is handled through `_node_log_read_files_array`
- Actions are intentionally explicit to avoid accidental destructive operations
- `clear` truncates files instead of deleting them
- `tail` is separated from `show` to preserve privilege boundaries

---

## Typical Use Cases

- Inspecting recent runtime or application errors
- Auditing log growth and disk usage
- Cleaning up accumulated logs in long-running nodes
- Live monitoring of production behavior
