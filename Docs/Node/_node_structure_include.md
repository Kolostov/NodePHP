# \_node_structure_include() — Include Node Resources

The `_node_structure_include()` function handles the inclusion of PHP files from a defined project node structure. It ensures required nodes are available, includes necessary PHP files, and handles missing nodes or optimizations.

---

## Function Signature

```php
_node_structure_include(array $STRUCTURE, string $PATH, array $NODES): void
```

### Parameters

- `array $STRUCTURE` — The node structure to traverse. Usually the `NODE_STRUCTURE` array.
- `string $PATH` — Base path of the current node for relative resolution.
- `array $NODES` — List of required sub-nodes to include. Each node should be a folder name relative to `$PATH`.

### Return Value

- `void` — This function does not return a value. It includes files and may throw exceptions on missing nodes.

---

## Behavior

1. **Including Requested Nodes:**
    - Iterates over `$NODES`.
    - Resolves each node path relative to `$PATH`.
    - If a node folder exists:
        - Checks for a `node.include.php` file.
            - If missing, attempts to execute the node via `php node.php` to generate it.
        - Includes `node.include.php` if it exists; otherwise includes `node.php`.
    - Throws an `Exception` if the folder does not exist or no executable PHP file is found.

2. **Walking Local Resources:**
    - Defines an internal `$walk` function to traverse directories within `$STRUCTURE`.
    - Excludes paths containing `..` or folders like `Git`, `Test`, `Public`, `Log`, `Deprecated`, `Backup`, `Docs`.
        - Additionally excludes `Migration` in non-CLI environments.
    - For directories, includes all `.php` files found.

3. **Recursive Traversal:**
    - Calls `_node_structure_walk()` with `$walk` to process all local resources under the given structure.

---

## Notes

- **Symbiosis with NODE_STRUCTURE:**
  This function is central to NODE_STRUCTURE handling. CLI commands and resource management rely on it to dynamically include project nodes.

- **Node Generation:**
  If a node is missing `node.include.php`, the function attempts to run `node.php` in the node folder to bootstrap it.

- **Error Handling:**
  Throws exceptions when required nodes or directories are missing, ensuring dependent code cannot silently fail.

- **Exclusions:**
  Certain folders are automatically ignored during inclusion to prevent runtime issues or unwanted code execution.
