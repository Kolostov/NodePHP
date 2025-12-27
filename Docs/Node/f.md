# f() — Transactional Filesystem Helper

The `f()` function is a **transaction-aware filesystem utility**.
It wraps common file operations while **recording every mutation** so the entire
filesystem state can be **rolled back atomically** on failure.

This function is intentionally stateful and designed to be used together with
phase-based execution (e.g. `p()`), where **partial side-effects must never leak**.

---

## Purpose

- Locate files across configured root paths
- Perform filesystem mutations safely
- Record every write/delete/copy/move operation
- Roll back all operations in reverse order
- Act as the single choke point for filesystem side-effects

---

## Function Signature

```php
f(string $fn, string $action = "find", ?string $arg = null, bool $critical = true): mixed
```

---

## Parameters

### `$fn`

- Absolute or relative path
- Special control keywords:
    - `"rollback"` — revert all recorded operations
    - `"dump"` — list all recorded filesystem operations

---

### `$action`

Defines the operation to perform.

Supported actions:

- `"find"` (default)
- `"read"`
- `"write"`
- `"delete"`
- `"copy"`
- `"move"`

---

### `$arg`

Optional argument used by certain actions:

- `"write"` → file contents
- `"copy"` → destination path
- `"move"` → destination path

Ignored for `"find"`, `"read"` and `"delete"`.

---

### `$critical`

- `true` → hard failure if file cannot be resolved
- `false` → return `null` instead

---

## Actions

### find

Resolves the real filesystem path.

Search order:

1. Direct path
2. Paths defined in `ROOT_PATHS`

Returns:

- Resolved path
- `null` if not found and `$critical === false`

---

### write

Writes content to a file.

Rollback behavior:

- Restores original content
- Creates file if it did not exist previously

---

### delete

Deletes a file.

Rollback behavior:

- Restores deleted file with original content

---

### copy

Copies a file to a new location.

Rollback behavior:

- Removes copied file

---

### move

Moves a file to a new location.

Rollback behavior:

- Moves file back to original path

---

## Rollback

Calling:

```php
f("rollback");
```

will:

- Execute rollback operations in **reverse order**
- Restore the filesystem to its pre-mutation state
- Clear the internal operation stack

Rollback is **best-effort**:

- Errors are logged
- Execution does not halt mid-rollback

---

## Dumping Recorded Operations

Calling:

```php
f("dump");
```

returns a newline-separated list of affected file paths, in operation order.

Useful for:

- Debugging
- Auditing
- Dry-run inspection

---

## Guarantees

- All filesystem mutations are reversible
- No partial state leaks if rollback is triggered
- Operations are isolated per runtime execution
- Rollback order is deterministic and safe

---

## Design Constraints

- Must be the **only** function performing filesystem writes
- Must never be bypassed by direct `file_*` calls
- Designed for deterministic build / transpilation phases
- Not thread-safe by design (intentionally)

---

## Intended Usage

- Phase-based execution
- Self-modifying code (transpilation)
- Atomic deploy pipelines
- Safe plugin / extension loading
- Transactional configuration mutation

---

## Non-Goals

- Not a virtual filesystem
- Not async-safe
- Not multi-process coordinated
- Not a permission abstraction

---

## Summary

`f()` acts as a **filesystem transaction journal**.

If your code modifies files without going through `f()`,
you have broken atomicity — and rollback is no longer guaranteed.
