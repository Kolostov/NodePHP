### `_node_cli_test` Documentation

**Purpose:**
Runs automated tests for the Node PHP environment. Supports both project-specific tests (Unit, Integration, Contract, E2E) and internal Node test functions.

---

## `_node_cli_test`

**Signature:**

```php
_node_cli_test(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`):
  If `true`, returns a short usage description:
  `"<type|internal> <filter> Use 'internal' for node testing."`

- `argv` (`array`):
  Command-line arguments:
    1. `type` – test type (`Unit`, `Integration`, `Contract`, `E2E`) or `"internal"`.
    2. `filter` – optional filter string to match test file names or internal test functions.

---

### Behavior:

1. **Tooltip Mode:**
    - Returns usage hint if `$tooltip === true`.

2. **Argument Parsing:**
    - Defaults `type` to `"Unit"` and `filter` to empty string.
    - If `$type === "internal"`, delegates to `_node_run_internal_tests($filter)`.

3. **Project Tests:**
    - Validates that `$type` is one of `Unit | Integration | Contract | E2E`.
    - Constructs `$testPath` as `ROOT_PATH . "Test" . D . $type`.
    - Scans `$testPath` for PHP files (`*.php`). Returns an error if none found.

4. **Test Execution:**
    - Iterates each PHP file:
        - Skips if `$filter` does not match the file name.
        - Includes the file.
        - Detects global `test_*` functions and runs them, counting passes and failures.
        - Detects classes ending in `Unit|Integration|Contract|E2E`:
            - Instantiates each class.
            - Runs public methods starting with `test`.
            - Counts passes/failures.
    - Outputs a summary for each test file.

5. **Result Reporting:**
    - Returns a string with:
        - Test name and file.
        - Status per function/method (`OK` or `FAIL` with message).
        - Summary: `Results: X/Y passed, Z failed`.
    - Sets `http_response_code(1)` if any test fails.

---

## `_node_run_internal_tests`

**Purpose:**
Executes Node-internal tests (functions starting with `test_`) optionally filtered by name.

**Parameters:**

- `filter` (`string`): Only run tests whose name contains this substring. If empty, runs all internal tests.

**Behavior:**

- Retrieves all user-defined functions.
- Filters functions starting with `test_`.
- Optionally applies `$filter` to select specific tests.
- Runs each test function:
    - Marks as passed if result is `0`.
    - Marks as failed otherwise or if an exception/error occurs.
- Outputs test results and summary.

**Return Value:**
Returns a formatted string with:

- Per-test pass/fail status
- Summary of total passed/failed tests

---

### Usage Examples

```bash
php node.php test Unit             # Run all Unit tests
php node.php test Integration auth # Run Integration tests matching 'auth'
php node.php test internal cli     # Run internal node tests matching 'cli'
php node.php test internal         # Run all internal tests
```

---

### Notes

- Internal tests use the naming convention `test_<name>`.
- Project tests rely on file and class naming conventions matching test types (`Unit`, `Integration`, `Contract`, `E2E`).
- Outputs are formatted for CLI readability.
- Failures trigger HTTP response code `1` to signal error in automated pipelines.
