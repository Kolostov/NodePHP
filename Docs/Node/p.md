# p() — Phase-Orchestrator & Atomic Execution Engine

The `p()` function is the **core orchestrator** of NodePHP. It provides **deterministic, sequential, and transactional phase execution** with **full state isolation**, **context binding**, and **filesystem rollback support**. Each phase can run multiple handlers, either closures or PHP files, in a predictable order.

It is intentionally a **single function**, not a class.

---

## Purpose

- Define and execute ordered runtime phases
- Register multiple handlers per phase
- Execute all phases or up to a target phase
- Guarantee atomic state and filesystem operations
- Support safe re-entry and incremental execution
- Provide detailed introspection and debugging

---

## Function Signature

```php
p(int|string|null $phase = null, object|string|null $action = null): mixed
```

**Parameters:**

- `$phase` (`int|string|null`):
    - Phase index or name
    - Special values: `":order"`, `":index"`, `":cursor"`, `":name"`, `":dump"`
    - `null` → execute all remaining phases

- `$action` (`object|string|null`):
    - Closure/callable handler, or
    - Relative path to PHP file (without extension) to include
    - `null` → only execute phases

**Return Values:**

- `array` → current state after execution
- `bool true` → when a handler is successfully registered
- `bool false` → when no handler is set or action ignored
- Throws `RuntimeException` on failure

---

## Core Concepts

### Phase

- Named execution step
- Predefined order:

```php
["boot", "discover", "transpilate", "resolve", "execute", "mutate", "persist", "finalize"]
```

- Each phase can have **zero or more handlers**
- Handlers are executed **in registration order**
- Phases are **atomic**: success commits state, failure rolls back

---

### Handler

A **handler** can be:

- A `Closure` or callable
- A string representing a PHP file to include

Rules:

- When a handler is a closure, it receives: `fn($name, &$state_copy)`.
- When a handler is a string, the file is included inside a context-bound closure.
- Handlers can return:
    - An associative array → merged into phase state
    - `null` → no change
    - `$this` → ignored for state, available for context binding

- All handlers execute in the context of a **fresh `Context` object**, with `$state` keys accessible as `$this->key`.

---

### State

- `$state` is a key-value array representing runtime state
- Each phase runs on a **copy** of the current state
- If phase succeeds, state is **committed**
- On failure, state is **rolled back** to previous phase
- Phase snapshots are stored in `$backups`

---

### Cursor

- `$cursor` tracks the **last successfully executed phase**
- Prevents re-running completed phases
- Supports **incremental execution**
- `$phase === ":cursor"` or `":index"` returns current cursor
- `$phase === ":name"` returns current phase name

---

## Operating Modes

### 1. Register a handler

```php
p("boot", fn($name, &$copy) => $this);
```

or

```php
p("execute", "relative/path/to/file");
```

Handlers are **queued**, not executed immediately.

---

### 2. Execute all remaining phases

```php
p();
```

Executes phases sequentially from the current cursor.

---

### 3. Execute up to a specific phase

```php
p("persist");
```

Executes all phases **up to and including** `persist`. Already-completed phases are skipped.

---

### 4. Query phase order or internal state

```php
p(":order");  // Returns the array of phase names
p(":dump");   // Returns internal state, backups, cursor, and phases
```

---

## Execution Model

1. Copies the current `$state`
2. Creates a fresh `Context` object
3. Maps `$state` into `$this` for handlers
4. Executes all handlers in order
5. Merges returned arrays into phase state
6. Commits state on success
7. Rolls back state and filesystem via `f("rollback")` on failure
8. Updates cursor to last successful phase

---

## Atomicity & Error Handling

- If any handler **throws an exception**:
    - State is rolled back to the previous phase snapshot
    - Cursor is not advanced
    - Filesystem is reverted via `f("rollback")`
    - Exception is logged via `r()`
    - A `RuntimeException` is thrown

No partial effects survive a failure.

---

## Filesystem Safety

- All file changes must go through `f()`
- Rollbacks are **phase-scoped**
- Inclusion-based handlers (`$action` as string) are executed in a bound `Context`
- Untracked filesystem changes break atomicity guarantees

---

## Handler Return Rules

- `array` → merged into phase state
- `null` → no effect on state
- `$this` → ignored for state, used for context only
- Returning anything else is ignored

---

## Special Modes / Introspection

| `$phase` value | Behavior                                            |
| -------------- | --------------------------------------------------- |
| `":order"`     | Returns ordered phase list                          |
| `":index"`     | Returns numeric index of current phase              |
| `":cursor"`    | Alias for `:index`                                  |
| `":name"`      | Returns current phase name                          |
| `":dump"`      | Returns full phase, state, backups, and cursor dump |

---

## Design Principles

- Single global orchestrator
- Deterministic, sequential execution
- Explicit phase boundaries
- Full atomicity for state and filesystem
- Context-bound handler execution
- Minimal and stable API

---

## Mental Model

Think of `p()` as:

> a transactional, phase-driven execution pipeline
> Each phase either fully happens — or never existed.

Handlers mutate **copies of state**, not the original, ensuring safe retries, rollbacks, and predictable orchestration.
