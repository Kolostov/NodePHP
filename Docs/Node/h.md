# h() — Hook Registration and Execution

The `h()` function provides a simple **hook system** for registering callable hooks, executing them, and applying them as filters. It supports both **action hooks** (run without arguments) and **filter hooks** (modify a passed value).

---

## Function Signature

```php
h(?string $name, mixed $arg = null): mixed
```

### Parameters

| Parameter | Type      | Description                                                                                                                                 |
| --------- | --------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| `$name`   | `?string` | Name of the hook. If `null`, returns counts of all registered hooks.                                                                        |
| `$arg`    | `mixed`   | Either a callable (to register a hook) or a value to be processed by registered hooks. If `null`, executes hooks without modifying a value. |

### Return Value

- `true` when registering a callable hook.
- `null` when running hooks that do not return a value.
- Filtered value if hooks modify the argument.
- Original argument if no hooks are registered for the given name.
- Array of hook counts if `$name` is `null`.

---

## Behavior

### Registering a Hook

```php
h("my_hook", function($value) {
    return $value . " modified";
});
```

- Registers a callable to a specific hook name.
- Returns `true` on success.

### Running Action Hooks

```php
h("init");
```

- Executes all callables registered to the `"init"` hook.
- Does not modify any value.
- Exceptions thrown inside hooks are caught and logged via `r()`.

### Running Filter Hooks

```php
$result = h("filter_text", "original value");
```

- Passes `$arg` through all registered hook callables.
- Each callable can modify and return the value.
- The final modified value is returned.
- Exceptions in any callable are caught and logged, but the current value is preserved.

### Getting Hook Counts

```php
$counts = h(null);
```

- Returns an array of hook names with the number of registered callables for each.

---

## Notes

- Provides a **centralized hook registry** using a static variable.
- Exceptions inside hooks are logged but do not halt execution.
- Can be used for extending behavior in a modular fashion without modifying core code.
