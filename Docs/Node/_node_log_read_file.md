# \_node_log_read_file() — Read and Decode Log File

The `_node_log_read_file()` function reads a log file line by line and decodes each line as JSON. It is used to process structured log entries created by the framework.

---

## Function Signature

```php
_node_log_read_file(string $path): array
```

### Parameters

| Parameter | Type     | Description                        |
| --------- | -------- | ---------------------------------- |
| `$path`   | `string` | Full path to the log file to read. |

### Return Value

- Returns an array of decoded log entries.
- Returns an empty array if the file does not exist or contains no valid JSON entries.

---

## Behavior

1. Checks if the given file exists. Returns an empty array if it does not.
2. Reads the file line by line, ignoring empty lines.
3. Attempts to decode each line as JSON.
4. If a line contains valid JSON, it is added to the result array.
5. If JSON decoding fails or throws an exception, the error is logged via `r()`.

---

## Notes

- Each log line is expected to be a single JSON object.
- Exceptions during parsing are caught, and the function continues processing subsequent lines.
- Ideal for retrieving structured logs for further analysis or display.
