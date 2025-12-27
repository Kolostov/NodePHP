# \_node_get_all_log_files() — Aggregated Log File Retriever

The `_node_get_all_log_files()` function collects **all relevant log files** from both the internal Node framework logging structure and common system locations, returning detailed metadata for each file.

---

## Function Signature

```php
_node_get_all_log_files(): array
```

### Return Value

```php
array
```

Each element of the array is an associative array with the following structure:

| Key        | Type     | Description                                                                                     |
| ---------- | -------- | ----------------------------------------------------------------------------------------------- |
| `type`     | `string` | Type of log. Either internal (`Internal`, `Access`, `Error`, `Audit`, `Exception`) or `system`. |
| `path`     | `string` | Full filesystem path to the log file.                                                           |
| `size`     | `int`    | File size in bytes.                                                                             |
| `modified` | `int`    | Last modification timestamp (Unix epoch).                                                       |

---

## Behavior

### Internal Logs

- Scans internal Node framework log directories based on `LOG_PATH` constant:
    - `Internal`
    - `Access`
    - `Error`
    - `Audit`
    - `Exception`

- Uses `glob()` to locate all `*.log` files.
- Collects file size and modification timestamp for each file.

### System Logs

- Checks common system log locations for **Apache**, **Nginx**, and general system logs:
    - Apache: `/var/log/apache2/access.log`, `/var/log/apache2/error.log`, `/var/log/httpd/access_log`, `/var/log/httpd/error_log`
    - Nginx: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
    - System: `/var/log/syslog`, `/var/log/messages`

- Only includes files that exist and are readable.

---

## Example Usage

```php
$logs = _node_get_all_log_files();

foreach ($logs as $log) {
    echo "{$log['type']}: {$log['path']} ({$log['size']} bytes, modified " . date('Y-m-d H:i:s', $log['modified']) . ")\n";
}
```

---

## Notes

- Ensures a **unified view** of both internal and system logs.
- Returns **metadata**, not file content.
- Can be used for automated log monitoring, reporting, or archival scripts.
