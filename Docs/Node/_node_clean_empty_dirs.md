# \_node_clean_empty_dirs() — Recursively Remove Empty Directories

The `_node_clean_empty_dirs()` function recursively traverses a directory and deletes any subdirectories that are empty. This is useful for cleaning up leftover folders after file operations, deployments, or resource generation.

---

## Function Signature

```php
_node_clean_empty_dirs(string $dir): bool
```

### Parameters

- `string $dir` — The path of the directory to check and clean.

### Return Value

- `true` if the directory and all its subdirectories were empty and successfully removed.
- `false` if the directory is not empty or could not be removed.

---

## Behavior

1. **Directory Validation:**
   Checks if the provided path is a valid directory. Returns `false` immediately if it is not.

2. **Recursive Traversal:**
    - Scans the directory contents using `scandir()`.
    - Ignores the special entries `"."` and `".."`.
    - For each item:
        - If it is a directory, recursively calls `_node_clean_empty_dirs()` on it.
        - If it is a file, marks the current directory as non-empty.

3. **Removal Logic:**
    - After processing all items, if the directory is empty and is not the `ROOT_PATH`, removes the directory with `rmdir()` and returns `true`.
    - Otherwise, returns `false`.

---

## Notes

- **Preserves Root:**
  The function never deletes the `ROOT_PATH` directory itself, even if empty.

- **Use Case:**
  Ideal for maintaining a clean filesystem structure in projects, especially after automated generation, deletion, or migrations.

- **Recursion:**
  Efficiently handles nested directories, ensuring all empty branches are cleaned up.
