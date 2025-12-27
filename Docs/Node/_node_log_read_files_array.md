# \_node_log_read_files_array() — Aggregate and Sort Multiple Log Files

The `_node_log_read_files_array()` function reads multiple log files, merges their entries, and sorts them by timestamp in descending order (newest first).

---

## Function Signature

```php
_node_log_read_files_array(array $arrayOfPathsToLogFiles): array
```

### Parameters

| Parameter                 | Type    | Description                               |
| ------------------------- | ------- | ----------------------------------------- |
| `$arrayOfPathsToLogFiles` | `array` | Array of full paths to log files to read. |

### Return Value

- Returns a single array containing all decoded log entries from the given files.
- Returns an empty array if no log files are provided or if all files are empty/nonexistent.

---

## Behavior

1. Checks if the input array is empty; returns an empty array if true.
2. Iterates through each file path and reads logs using `_node_log_read_file()`.
3. Merges all log entries into a single array.
4. Sorts the combined array by the `"timestamp"` field in descending order (newest first).
5. Returns the sorted array.

---

## Notes

- Log entries are expected to have a `"timestamp"` field in a format compatible with `strtotime()`.
- Missing or invalid timestamps default to `"1970-01-01"`.
- Useful for displaying a unified, time-ordered view of logs across multiple sources.
