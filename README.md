# NodePHP

**Version:** 0.1 pre-Alpha<br/>
**Author:** Joonas Kolostov<br/>
**PHP:** 8.5+<br/>
**BCN Address for donations:** 21TEDpBhfRBigtt2XJ7yBRLrA9CrjMFcAZVEfHcesfK2PuDLE7ZBpU5fNCezqRpKfLJf5dmANoy6uA2bGtZ3uT5fJN67BBd<br/>

---

## Introduction

**NodePHP** is a lightweight, modular PHP framework designed for maximum modularity, flexibility, and maintainability. It provides a structured yet low-level approach to building robust PHP applications, functioning as a monolith for PHP Node-based programming.

### Key Features:

- Modular file structures and section-based code management
- CLI-first design for developer automation and internal tooling
- Full control over runtime behavior including test harnesses, notifications, and dynamic structure deployment
- Compatibility across multiple PHP versions while maintaining strict typing and modern syntax features
- Git repository management and project structuring

NodePHP is ideal for developers who value **minimalism, modularity, and full programmatic control** over their codebase.

---

## Quick Start

### Create New Node from Repository

```bash
git clone https://<URL>/Kolostov/NodePHP.git .
```

### Link Existing Git Repo as New Node Project

```bash
php node git Project
git clone https://<URL>/Kolostov/EmptyRepo.git Git/Project
php node git Project
```

### Run Main Application

```bash
php node serve
```

---

## Core Principles

1. **Stable, Low-Level Design**
    - Minimal reliance on external dependencies
    - Fully typed arguments and return types
    - Emphasis on long-term compatibility with legacy PHP versions while utilizing modern syntax where safe

2. **Programmatic Flexibility**
    - Sections, tests, and CLI commands are programmatically extensible
    - Notifications, backup, and automated scripts can be integrated seamlessly
    - Includes utilities for code search, automated server serving, and section wrapping

---

## Getting Started

### Requirements

- PHP 8.5+
- Composer optional for external libraries (NodePHP itself is dependency-free)
- Command-line access

### Installation

1. Clone the repository:

    ```bash
    git clone https://<URL>/Kolostov/NodePHP.git .

    # Move Node files to Git/Node
    php node git Project
    git clone https://github.com/<repo>/<project>.git Git/Project

    # Pull Project files to ROOT_PATH
    php node git Project
    ```

---

## Configuration

### Node Parameters in `_node.json`

Define your node configuration in the `_node.json` file:

```json
{
    "name": "Sample",
    "run": "Function\\Helper\\FunctionName",
    "structure": {
        "Depricated": "Files that are considered depricated.",
        "Log": {
            "Internal": "Application runtime logs",
            "Access": "HTTP request logs",
            "Error": "Error and exception logs",
            "Audit": "Security and audit trails"
        },
        "Git": {
            "Node": "Node.php project repository",
            "Project": "All excluding the Node.php"
        }
    },
    "require": []
}
```

### Editor Configuration (Zed)

For optimal formatting in Zed editor:

```json
{
    "format_on_save": "on",
    "preferred_line_length": 120,
    "soft_wrap": "preferred_line_length"
}
```

---

## Folder Structure

Example project structure after `restructure`:

```
/NodePHP
├── node.php            # Entry point
├── node.section1.php   # Auto-wrapped sections
├── Public/
│   └── Entry/          # Document root for built-in server
├── Test/
│   ├── Unit/
│   ├── Integration/
│   ├── Contract/
│   └── E2E/
├── Logs/
├── Database/
├── Backup/
├── Deprecated/
└── Git/
    ├── Node/           # Node.php project repository
    └── Project/        # All excluding the Node.php
```

- Sections are dynamically created using `wrap open`
- The `Public/Entry` folder serves as the document root for PHP built-in server (`serve <port>`)

---

## CLI Commands Overview

### 1. Like

```bash
php node like <resource name>
```

- Searches the entire codebase for units of resource. Lists available options.

### 2. Search

```bash
php node search <query>
```

- Searches for given keyword within files and lists snippets of matched strings.

- Searches across `.php`, `.js`, `.css`, `.html`, `.json`, `.md`, etc
- Excludes `vendor`, `Database`, `Logs`, `Backup`, `Deprecated`

### 3. Serve

```bash
php node serve 8000
```

- Constructs built-in PHP server igntion command at specified port (`8000` default)

### 4. Test

```bash
php node test Unit <filter>
php node test internal
```

- Run Unit, Integration, Contract, or E2E tests
- `internal` runs all core NodePHP test functions
- Filters can narrow tests by function or file name

### 5. Wrap

```bash
php node wrap open
php node wrap close
```

- `open`: splits sections into separate files
- `close`: merges section files back into `node.php`

### 6. Git Management

```bash
php node git <Project\|Node>
```

- Manages focus on git repository

---

## Contributing to NodePHP

### Wrapping Sections

- Use `# section_name begin` / `# section_name end` markers
- Example:

```php
# utilities begin
function helper(): string {
    return "This is a helper.";
}
# utilities end
```

- Wrap into `node.utilities.php`:

```bash
php node wrap open
```

- NodePHP now includes the section via:

```php
include_once "{$LOCAL_PATH}node.utilities.php";
```

### Running Tests Programmatically

- Define functions like:

```php
function test_helper(): int {
    return helper() === "This is a helper." ? 0 : 1;
}
```

- Run:

```bash
php node test internal helper
```

### Adding New CLI Commands

- Pattern:

```php
function _node_cli_<command>(bool $tooltip = false, array $argv = []): string { ... }
```

---

## Known Issues & TODO

- `_node_cli_make Git/Node/$symlink` does not get removed after cloning from repo
- This has to be excluded from checks if folder is empty or deleted after moving/making new symlink

---

## Philosophy & Best Practices

- **Stability over speed**: prefer predictable, version-compatible PHP code
- **Explicit over implicit**: section markers, typed arguments, clear CLI behaviors
- **Programmatic modularity**: CLI + sections + tests form a dynamic, self-documenting codebase
- **Automation first**: all repetitive tasks should be handled via CLI commands or internal tests
- **Lightweight and portable**: framework does not require complex dependencies or installation

---

## License

Creative Commons Attribution 4.0 International (CC BY 4.0) https://creativecommons.org/licenses/by/4.0/

---

## Acknowledgements

NodePHP is built entirely by Joonas Kolostov and inspired by low-level, modular design principles. It reflects a hands-on approach to programmatic PHP development, testing, and automation.
