### `_node_cli_make` Documentation

**Purpose:**
Creates a new NodePHP instance by cloning the Node core repository and a project repository into a predefined directory layout.

---

## Function Signature

```php
_node_cli_make(bool $tooltip = false, array $argv = []): string
```

Supporting helper:

`_node_get_git_urls(): array`

---

## Parameters

### `_node_cli_make`

- `tooltip` (`bool`)
  When enabled, returns a short usage string.

- `argv` (`array`)
    - `argv[0]` — Git repository identifier for the project (e.g. `User/Repo`)
    - `argv[1]` — Target folder name for the new node

---

## Usage

```sh
make <gitrepo> <foldername>
```

**Example:**

```sh
make Kolostov/RouterNode Router
```

---

## Behavior

1. **Validation**
    - Requires exactly two arguments
    - Fails if the target directory already exists

2. **Git URL Resolution**
    - Detects current Node git origin
    - Derives base GitHub URL from Node repository
    - Falls back to `https://github.com/<gitrepo>.git` if needed

3. **Directory Structure Creation**

    ```
    <foldername>/
    ├─ Git/
    │  ├─ Node/
    │  └─ Project/
    ├─ node.php
    ├─ node
    └─ node.json
    ```

4. **Repository Cloning**
    - Clones Node core into `Git/Node`
    - Clones project repository into `Git/Project`

5. **Node Bootstrap**
    - Moves `node.php` to node root
    - Ensures `node` executable symlink exists
    - Runs `php node.php git Node` to set Node mode

6. **Configuration File**
    - Creates `node.json` with:

        ```json
        {
            "name": "<foldername>",
            "run": null,
            "require": []
        }
        ```

7. **Rollback on Failure**
    - Any error triggers full directory cleanup
    - Working directory is restored

---

## Output

- Step-by-step status messages
- Explicit success (`✓`) and notice (`N`) markers
- Clear error messages on failure
- Final instructions for entering and starting the node

---

## Return Value

- Returns formatted CLI output as a string
- Always newline-terminated
- Safe for direct echoing

---

## `_node_get_git_urls`

**Purpose:**
Resolves Git URLs for Node core and project repositories.

### Behavior

- Reads `.git/config` from:
    - Current Node root, or
    - `Git/Node/.git/config`

- Extracts:
    - `node` — full Node repository URL
    - `base` — base GitHub URL inferred from Node repo

### Return Format

```php
[
    "node" => string|null, // Node repository URL
    "base" => string|null  // Base URL for project repos
]
```

---

## Error Conditions

- Missing arguments
- Target directory already exists
- Git clone failure (Node or Project)
- Missing or unmovable `node.php`
- Undetectable Node git origin

All errors abort execution and clean up partial state.

---

## Notes

- Requires `git` to be available in PATH
- Uses shell execution (`exec`)
- Assumes standard NodePHP repository layout
- Does not modify global configuration
- Designed for reproducible Node bootstrapping
