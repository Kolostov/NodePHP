# \_node_execute_run() — Unified Entry Point Resolver

The `_node_execute_run()` function resolves and executes a **configured runtime entry point**.
It is the final dispatcher that turns a string definition into an actual executable target.

This function is intentionally **forgiving**, **dynamic**, and **convention-driven**.

---

## Purpose

- Execute a configured application entry point
- Support multiple invocation styles
- Avoid hard-coding bootstrap logic
- Centralize execution rules in one place
- Fail loudly and log consistently on misconfiguration

---

## Function Signature

```php
_node_execute_run(string $entry): void
```

---

## Accepted Entry Formats

The `$entry` string may describe **one of several executable forms**.

Resolution happens in the order listed below.

---

## 1. Static Method With Arguments

### Format

```php
ClassName::method(arg1, arg2, "arg3")
```

### Behavior

- Parses class name, method name, and arguments
- Arguments are comma-separated
- Quoted arguments are unwrapped
- All arguments are passed as strings
- Executes via `call_user_func_array`

### Requirements

- Class must exist
- Method must exist and be static or callable statically

---

## 2. Static Method Without Arguments

### Format

```php
ClassName::method
```

### Behavior

- Verifies class existence
- Verifies method existence
- Executes via `call_user_func`

### Failure Handling

- Logs missing class
- Logs missing method

---

## 3. Class Entry (Convention-Based)

### Format

```php
ClassName
```

### Behavior

- Instantiates the class
- Attempts execution in this order:

1. `__invoke()`
2. `run()`
3. `execute()`

First matching method is called.

### Failure Handling

- Logs error if no executable method exists

---

## 4. Function Entry

### Format

```php
function_name
```

### Behavior

- Calls the function directly
- No arguments supported

---

## Failure Case

If none of the above resolution paths succeed:

- Logs an `Error` entry
- Sets HTTP response code to `500`
- Terminates execution with a fatal message

---

## Logging

All failures are logged via `r()` with type `Error`.

Examples:

- Missing class
- Missing method
- Non-executable class
- Invalid entry format

---

## Argument Parsing Rules

- Arguments are split by comma
- Whitespace is trimmed
- Quoted strings (`"x"` or `'x'`) are unwrapped
- No type casting is performed
- Everything is passed as a string

---

## Design Characteristics

- Single responsibility
- No dependency injection
- No reflection
- No container
- No configuration branching
- No return value
- One-way execution

---

## Intended Usage

This function is typically invoked once during bootstrap:

```php
_node_execute_run($config["run"]);
```

It defines **what starts the application**, not how the application works.

---

## Non-Goals

- Not a router
- Not a command bus
- Not a task scheduler
- Not sandboxed
- Not reversible

---

## Mental Model

Think of `_node_execute_run()` as:

> `eval`, but disciplined

It converts configuration into execution — nothing more, nothing less.

---
