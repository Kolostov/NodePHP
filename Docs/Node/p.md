# p() — Phase Orchestrator & Atomic Execution Engine

The `p()` function is the **core execution primitive** of the framework.
It provides **ordered, transactional, restartable phase execution** with
full **state isolation** and **filesystem rollback support**.

It is intentionally implemented as **a single function**, not a class.

---

## Purpose

- Define ordered execution phases
- Attach multiple handlers per phase
- Execute phases sequentially or up to a target phase
- Guarantee atomicity of state and filesystem
- Allow safe re-entry and partial execution
- Provide deterministic orchestration of boot → runtime → persistence

---

## Function Signature

```php
p(int|string|null $phase = null, object|string|null $action = null): bool|array
```

---

## Core Concepts

### Phase

A **phase** is a named execution step in a fixed order:

```php
["boot", "discover", "transpilate", "resolve", "execute", "mutate", "persist", "finalize"]
```

Each phase may contain **zero or more handlers**.

---

### Handler

A **handler** is either:

- a Closure / callable
- a PHP file (included once)

Handlers are executed **in registration order**.

---

### State

- `$state` is a key-value array
- Each phase runs on a **copy** of the state
- State is only committed if the phase succeeds
- On failure, state is reverted automatically

---

### Cursor

- `$cursor` tracks the last successfully executed phase
- Prevents re-running completed phases
- Enables incremental execution (resume semantics)

---

## Operating Modes

### 1. Register a handler

```php
p("boot", fn($name, &$copy) => $this);
```

or

```php
p("discover", "path/to/file");
```

Handlers are **queued**, not executed immediately.

---

### 2. Execute all remaining phases

```php
p();
```

Executes from the current cursor forward.

---

### 3. Execute up to a specific phase

```php
p("persist");
```

Executes all phases **up to and including** `persist`.

Already-executed phases are skipped.

---

### 4. Query phase order

```php
p("order");
```

Returns the ordered list of phase names.

---

### 5. Debug internal state

```php
p("dump");
```

Returns:

- registered phase handlers
- current state
- backups per phase
- cursor position

---

## Handler Execution Model

Each phase:

1. Copies current `$state`
2. Creates a fresh `Context` object
3. Injects state values as `$this->key`
4. Executes handlers in order
5. Merges returned state (if any)
6. Commits on success
7. Rolls back on failure

---

## Handler Return Rules

Handlers may:

- `return $this;`
- `return null;`
- return an associative array

Behavior:

- `null` → no state change
- array → merged into phase state
- `$this` → ignored for state, used for context only

Despite documentation guidance, **returning `$this` is not enforced**;
state mutation is controlled purely by returned arrays.

---

## Atomicity Guarantees

If **any handler throws**:

- All filesystem changes are reverted via `f("rollback")`
- State is restored to the previous phase snapshot
- Cursor is not advanced
- Exception is logged via `r()`
- A RuntimeException is thrown

No partial effects survive.

---

## Filesystem Safety

`p()` assumes:

- **All filesystem mutations go through `f()`**
- `f()` maintains an internal rollback stack
- Rollback is phase-scoped and deterministic

If filesystem changes bypass `f()`, atomicity is broken.

---

## Inclusion-Based Handlers

When `$action` is a string:

```php
p("execute", "relative/path");
```

Behavior:

- `.php` is appended
- File is resolved via `f(..., "find")`
- File is included once
- Must execute within the phase context
- May mutate `$this` or return state

---

## Design Constraints

- Single global orchestrator
- No classes, no inheritance
- Deterministic execution order
- Explicit side-effect boundaries
- Zero magic autoloading inside phases

---

## Intended Usage Pattern

1. Bootstrap runtime (autoloaders, config)
2. Register phase handlers
3. Call `p()` or `p(targetPhase)`
4. Entry-point logic runs _inside phases_
5. Persistence only happens in `persist`

Classes **should not call `p()` internally**
They should assume the phase system already ran.

---

## Non-Goals

- Not an event dispatcher
- Not a hook system
- Not async-safe
- Not a dependency container

---

## Mental Model

Think of `p()` as:

> a transactional compiler pipeline for your entire runtime

Each phase either **fully happens** — or **never existed**.

---
