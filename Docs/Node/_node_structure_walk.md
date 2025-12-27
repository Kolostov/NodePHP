# \_node_structure_walk() — Recursive Node Structure Walker

The `_node_structure_walk()` function traverses a nested node structure array and applies a callback to each node. It is the core utility for iterating over NODE_STRUCTURE and performing operations on nodes or directories.

---

## Function Signature

```php
_node_structure_walk(array $array, callable $callback, string $location = "", string $PATH = ""): array
```

### Parameters

- `array $array` — The nested node structure to walk through. Typically `NODE_STRUCTURE` or a subset.
- `callable $callback` — A function to execute for each node. Receives two parameters:
    - `string $path` — Full resolved path of the current node.
    - `mixed $val` — Value or sub-array associated with the node.
- `string $location` — Internal parameter used to track the relative location within the traversal. Default is empty string.
- `string $PATH` — Base path for resolving absolute node paths. Default is empty string.

### Return Value

- `array` — Returns an array of callback results, with empty entries filtered out.

---

## Behavior

1. **Iterate Nodes:**
    - Loops through each key-value pair in `$array`.
    - Skips numeric keys (used for lists) to avoid duplicate processing.

2. **Resolve Path:**
    - Constructs the full path for the node using `$PATH` and `$location`.

3. **Callback Execution:**
    - Executes the provided `$callback` on the resolved path and node value.
    - Stores the callback result in the return array.

4. **Recursive Traversal:**
    - If the node value is an array, recursively walks through the sub-structure:
        - Converts sequential lists (`array_is_list`) into associative arrays using `array_flip`.
        - Updates `$location` to reflect current traversal depth.
    - Aggregates results from recursive calls.

5. **Filtering Results:**
    - Filters out empty values from the final result array before returning.

---

## Notes

- This function is foundational for NODE_STRUCTURE operations, powering:
    - `_node_structure_call()`
    - `_node_structure_deploy()`
    - `_node_structure_include()`

- Callback functions can perform any operation, such as:
    - Including PHP files
    - Collecting paths
    - Generating boilerplate
    - Cleaning directories

- Recursive handling ensures all nested nodes are processed in a depth-first manner.
