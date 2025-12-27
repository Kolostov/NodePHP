### `_node_cli_git` Documentation

**Purpose:**
Manages Git repository targeting for a NodePHP project, allowing toggling between the **Node** file (`node.php`) or the entire **Project** as the Git root.

---

#### **Function Signature**

```php
_node_cli_git(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a brief description instead of performing actions.

- `argv` (`array`):
  CLI arguments where:
    - First element is the target mode (`Node` or `Project`)
    - If empty, the function only reports the current Git target.

---

#### **Behavior**

- Checks if `.git` directory exists at `ROOT_PATH`.
- Reads `.gitignore` to determine if Git is currently targeting the Node (`!node.php` present) or the full Project.
- Can toggle Git target:
    - **Node:** Git tracks only `node.php` and ignores everything else.
    - **Project:** Git tracks the entire project, ignoring `node.php` exclusion.
- Moves `.git`, `.gitignore`, and `README.md` files between `Git/Node/` and `Git/Project/` subdirectories to align with the selected target.
- Prevents overwriting files if the source folder already contains content.

---

#### **Return Values**

- Reports the current Git target if no mode is provided.
- Returns status messages describing any file moves and the new Git target.
- Provides warnings if source directories are non-empty and moves are skipped.

---

#### **Example Usage**

```bash
php node.php git          # Shows current Git target
php node.php git Node     # Switch Git to track Node only
php node.php git Project  # Switch Git to track the full project
```

---

#### **Notes**

- Requires a properly initialized Git repository.
- Does not modify tracked content beyond moving the core Git files.
- Designed for NodePHP workflow to easily switch between minimal and full project tracking.
