### `_node_cli_move` & `_node_cli_move_all` Documentation

**Purpose:**
Move a resource (function, class, enum, interface, or trait) from one namespace/resource folder to another, updating filenames, class/enum names, namespaces, and all relevant `use` statements in the project.

---

## `_node_cli_move`

**Signature:**

```php
_node_cli_move(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`): Return usage/help string if true.
- `argv` (`array`): Arguments `[sourceResource, name, targetResource]`.

**Behavior:**

1. Validate arguments and existence of source and target resources.
2. Locate the file containing the target entity:
    - Global function: searches top-level `function` definitions.
    - Class/enum/interface/trait: searches PHP file for the type declaration.
3. Determine new filename and target namespace.
4. Update content:
    - Rename class/enum/interface/trait as needed.
    - Update namespace to match target resource.
    - Optionally add namespace if missing.
5. Move file to target resource directory.
6. Update all `use` statements and fully qualified class names across project.
7. Return a summary of the move, including namespace changes and number of updated `use` statements.

**Tooltip Output:**
`"<resource> <name> <new_resource> Moves resource."`

---

## `_node_cli_move_all`

**Signature:**

```php
_node_cli_move_all(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`): Return usage/help string if true.
- `argv` (`array`): Arguments `[sourceResource, targetResource]`.

**Behavior:**

1. Validate arguments and existence of source and target resources.
2. Enumerate all PHP files in the source resource folder.
3. For each file:
    - Extract the entity name from the filename.
    - Call `_node_cli_move()` to move and update the file.
4. Collect results and summarize:
    - Number of files successfully moved.
    - Errors encountered per file.
    - Output details for each successfully moved file.

**Tooltip Output:**
`"<resource> <new_resource> Moves ALL resources."`

---

## Notes

- Function files are treated differently from class/enum/interface/trait files: no namespace or name change is applied, only the file is moved.
- Updates all project-wide references to moved classes using `_node_update_use_statements`.
- Handles leaf suffixes in filenames automatically (e.g., `State` → `Type`).
- Fully recursive update of `use` statements and fully-qualified class names ensures consistency.
- Includes a `test_node_cli_move()` function to validate correctness and error handling.
