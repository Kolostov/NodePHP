# NodePHP Framework

**Version:** 0.1 pre-Alpha

**Author:** Joonas Kolostov

**BCN Address for donations:** 21TEDpBhfRBigtt2XJ7yBRLrA9CrjMFcAZVEfHcesfK2PuDLE7ZBpU5fNCezqRpKfLJf5dmANoy6uA2bGtZ3uT5fJN67BBd

**PHP:** 8.5+

---

## Introduction

**NodePHP** is a lightweight, modular PHP framework designed for maximum stability, flexibility, and maintainability. It provides a structured yet low-level approach to building robust PHP applications, functioning as a monolith for PHP Node-based programming.

### Key Features:

- Modular file structures and section-based code management
- CLI-first design for developer automation and internal tooling
- Full control over runtime behavior including test harnesses, notifications, and dynamic structure deployment
- Compatibility across multiple PHP versions while maintaining strict typing and modern syntax features
- Git repository management and project structuring

NodePHP is ideal for developers who value **stability, modularity, and full programmatic control** over their codebase.

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

1. **Modularity and Sectioning**
    - Code is split into logical sections using `# section_name begin` / `# section_name end` markers
    - Sections can be automatically wrapped into separate files and included dynamically, keeping `node.php` as a single entry point

2. **CLI-First Tooling**
    - The framework exposes most operations via CLI commands (`node.php <command> [args]`)
    - All repetitive tasks should be handled via CLI commands or internal tests

3. **Internal and External Testing**
    - All test functions start with `test_` and are automatically detected
    - Supports `internal` test runs (NodePHP core functions) and standard test types
    - Provides granular feedback, counts passed/failed tests, and sets appropriate exit codes for CI pipelines

4. **Dynamic Project Structure**
    - Automatically manages project structure through `_node_cli_restructure()`
    - Supports nested folders, empty-folder cleanup, and modular deployment

5. **Stable, Low-Level Design**
    - Minimal reliance on external dependencies
    - Fully typed arguments and return types
    - Emphasis on long-term compatibility with legacy PHP versions while utilizing modern syntax where safe

6. **Programmatic Flexibility**
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
    git clone https://github.com/<your-repo>/NodePHP.git
    cd NodePHP
    ```

2. Make `node.php` executable (optional):

    ```bash
    chmod +x node.php
    ```

3. Ensure the `ROOT_PATH` constant points to your project root in `node.php`

---

## Configuration

### Node Parameters in `_node.json`

Define your node configuration in the `_node.json` file:

```json
{
    "name": "Sample",
    "run": "Function\\Helper\\FunctionName",
    "structure": [
        "Depricated" => "Files that are considered depricated.",
        "Log" => [
            "Internal" => "Application runtime logs",
            "Access" => "HTTP request logs",
            "Error" => "Error and exception logs",
            "Audit" => "Security and audit trails"
        ],
        "Git" => [
            "Node" => "Node.php project repository",
            "Project" => "All excluding the Node.php"
        ]
    ],
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

### 1. Restructure

```bash
php node.php restructure
```

- Deletes empty directories and deploys default project structure

### 2. Search

```bash
php node.php search <query>
```

- Searches across `.php`, `.js`, `.css`, `.html`, `.json`, `.md`, etc
- Excludes `vendor`, `Database`, `Logs`, `Backup`, `Deprecated`

### 3. Serve

```bash
php node.php serve 8000
```

- Starts built-in PHP server at specified port (`8000` default)
- Alternative: `php node serve` for quick startup

### 4. Test

```bash
php node.php test Unit <filter>
php node.php test internal <filter>
```

- Run Unit, Integration, Contract, or E2E tests
- `internal` runs all core NodePHP test functions
- Filters can narrow tests by function or file name

### 5. Wrap

```bash
php node.php wrap open
php node.php wrap close
```

- `open`: splits sections into separate files
- `close`: merges section files back into `node.php`

### 6. Git Management

```bash
php node git <project_name>
```

- Manages git repository links and project structure

---

## Programmatic Patterns

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
php node.php wrap open
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
php node.php test internal helper
```

### Adding New CLI Commands

- Pattern:

```php
function _node_cli_<command>(bool $tooltip = false, array $argv = []): string { ... }
```

- Add command to `node.php`:

```php
$cliCommands = ["restructure", "search", "serve", "test", "wrap", "your_new_command"];
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

## Examples

Start server:

```bash
php node.php serve 8080
```

Search for `backup` references:

```bash
php node.php search backup
```

Run all internal tests:

```bash
php node.php test internal
```

Wrap node.php sections:

```bash
php node.php wrap open
```

---

## License

Creative Commons Attribution 4.0 International (CC BY 4.0) https://creativecommons.org/licenses/by/4.0/

---

## Acknowledgements

NodePHP is built entirely by Joonas Kolostov and inspired by low-level, modular design principles. It reflects a hands-on approach to programmatic PHP development, testing, and automation.
