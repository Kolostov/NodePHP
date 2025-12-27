# \_node_structure_deploy() — Deploy Directory Structure

The `_node_structure_deploy()` function ensures that all directories defined in a given `NODE_STRUCTURE` array exist on the filesystem. It creates missing directories recursively according to the project’s structure.

---

## Function Signature

```php
_node_structure_deploy(?array $NODE_STRUCTURE = null): void
```

### Parameters

- `?array $NODE_STRUCTURE` — Optional array defining the directory structure to deploy.
  If `null`, the function uses the global `NODE_STRUCTURE` constant.

### Return Value

- `void` — This function does not return a value. Its purpose is to create directories on the filesystem.

---

## Behavior

1. **Directory Deployment:**
    - Traverses the structure using `_node_structure_walk()`.
    - For each path in the structure:
        - Checks if the directory exists.
        - If not, creates the directory using `mkdir()` with permissions `0777` and recursive creation enabled.

2. **Default Behavior:**
    - If no `$NODE_STRUCTURE` is passed, it uses the global `NODE_STRUCTURE`.

3. **Main Entry Point:**
    - After defining the function, the snippet calls `_node_structure_deploy($NODE_STRUCTURE);` to deploy the main project structure at the entry point.

---

## Notes

- **Permissions:**
  Creates directories with `0777` permissions, allowing full read/write/execute access. Adjust if stricter permissions are required.

- **Recursion:**
  Ensures nested directories are automatically created.

- **Use Case:**
  Useful for bootstrapping projects, frameworks, or applications where a defined folder structure is required before further operations.
