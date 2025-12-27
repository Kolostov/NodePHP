# r() — Structured Runtime Logger & Return-Passthrough

The `r()` function is the **single, unified logging primitive** of the framework.
It records structured JSON log entries while optionally **passing through a return value**.

It is intentionally **side-effect only** (logging) and **non-intrusive** to control flow.

---

## Purpose

- Centralize all logging
- Enforce structured, machine-readable logs
- Capture execution context automatically
- Avoid breaking runtime flow
- Act as a return-value passthrough

---

## Function Signature

```php
r(string $msg, string $type = "Internal", mixed $return = null, null|array|object $data = null): mixed
```

---

## Core Concepts

### Log Entry

Each call to `r()` appends **one JSON line** to a daily log file.

Logs are:

- newline-delimited
- UTF-8 safe
- human-readable but machine-parsable

---

### Passthrough Return

`r()` always returns the value of `$return`.

This enables patterns like:

```php
return r("failed to persist user", "Error", false, $context);
```

without altering program structure.

---

## Log Types

Log files are grouped by **type**:

- `Internal`
- `Access`
- `Error`
- `Audit`
- `Exception`

Unknown types fall back to `Internal`.

Each type maps to:

```php
LOG_PATH/<Type>/YYYY-MM-DD.log
```

---

## Captured Context

Every log entry includes:

### Always

- timestamp
- log type
- file (relative to ROOT_PATH)
- line number
- calling function
- message
- result
- optional data payload

### Web Only (non-CLI)

- IP address
- HTTP method
- URI
- session ID
- user ID (if authenticated)

No caller needs to provide this manually.

---

## Backtrace Behavior

- Uses `debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)`
- Identifies:
    - the direct caller
    - the source file and line
- Keeps payload small and safe

---

## Data Payload

`$data` may be:

- `null`
- `array`
- `object`

Objects are cast to arrays.

Used for:

- state snapshots
- identifiers
- diagnostics
- structured error context

---

## Error Tolerance

- Never throws
- Never interrupts execution
- Logging failures do not bubble
- Safe inside `catch`, destructors, rollback paths

---

## File Behavior

- Log directories must exist
- Files are appended, never truncated
- No file locking (assumes append-only discipline)

---

## Intended Usage Patterns

### Silent logging

```php
r("cache warmed");
```

### Error reporting with return passthrough

```php
return r("user not found", "Error", null, ["id" => $id]);
```

### Exception context logging

```php
catch (Throwable $e) {
    r($e->getMessage(), "Exception", false, ["exception" => get_class($e)]);
}
```

---

## Design Constraints

- Single function
- No dependencies
- No configuration injection
- No conditionals at call site
- No formatting responsibility

---

## Non-Goals

- Not a PSR-3 logger
- Not a tracing system
- Not a metrics collector
- Not async-safe
- Not buffered

---

## Mental Model

Think of `r()` as:

> printf for structured reality

It observes — it never interferes.

---
