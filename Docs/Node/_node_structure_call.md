# \_node_structure_call() — Build and Cache Node Structure for Resource Management

The `_node_structure_call()` function is central to the NODE*STRUCTURE system and serves as the backbone for CLI commands, resource discovery, and file management. All `cli*\*` operations and resource-handling mechanisms rely on this function to provide a preprocessed and cached map of the framework’s file and directory hierarchy.

---

## Function Signature

```php
_node_structure_call(): array
```

### Parameters

- None.

### Return Value

- Returns an **array** representing the processed NODE_STRUCTURE.
- Each entry in the array contains:
    1. A unique call key derived from the path components.
    2. The full path of the resource.
    3. The associated value from `NODE_STRUCTURE` (metadata or configuration).

- Returns a **cached version** if the structure was already generated in the current runtime.

---

## Behavior

1. **Static Caching:**
   Uses static variables `$calls` and `$structure` to store previously processed calls and the generated structure. This ensures the function only walks the structure once per runtime.

2. **Walking NODE_STRUCTURE:**
   Invokes `_node_structure_walk()` on the global `NODE_STRUCTURE`, passing a callback that:
    - Skips directories (only processes leaf resources).
    - Builds a **unique call key** from the path segments to avoid duplicate entries.
    - Returns an array of `[call_key, full_path, value]` for each resource.

3. **Call Key Generation:**
    - Explodes the path into segments.
    - Iteratively takes slices of the path from the leaf upward until it finds a unique combination.
    - This ensures each resource has a unique, deterministic call key for access by CLI commands and internal resource functions.

4. **Caching and Return:**
    - The fully processed structure is cached in `$structure` for subsequent calls.
    - Returns the cached structure immediately if already generated.

---

## Notes and Philosophy

- **Core of NODE_STRUCTURE:**
  This function is the fundamental bridge between the physical file/resource layout and all framework-level abstractions, including CLI utilities, automated loading, and resource management.

- **Symbiosis with Other Functions:**
  Functions like `cli_*`, `_node_generate_boilerplate()`, and resource loaders rely entirely on `_node_structure_call()` to resolve paths, generate unique call keys, and ensure a consistent framework state.

- **Efficiency:**
  By caching the structure statically, it avoids repeated filesystem scans and reduces overhead during runtime operations.

- **Uniqueness Guarantee:**
  The call key algorithm ensures that every leaf resource gets a distinct identifier, even if paths share common segments, enabling safe referencing in CLI commands and other runtime logic.
