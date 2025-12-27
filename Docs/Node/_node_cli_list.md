### `_node_cli_list` Documentation

**Purpose:**
Lists registered NodePHP resources and enumerates physical files belonging to a specific resource type.

This command is used to inspect resource availability and filesystem-backed resource contents.

---

#### **Function Signature**

```php
_node_cli_list(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`)
  When enabled, returns a short CLI help description.

- `argv` (`array`)
    - `argv[0]` — resource name to list (optional).

---

#### **Behavior Overview**

The command operates in two modes depending on whether a resource name is supplied.

---

### **1. Resource Index Mode (no argument)**

When no resource name is provided:

- Iterates over all registered resources from `_node_structure_call()`
- Displays:
    - Resource call name
    - Human-readable description
    - Relative filesystem path

**Output includes:**

- A list of all available resource types
- A usage hint for listing a specific resource

This mode is intended for discovery and orientation.

---

### **2. Resource Listing Mode (`list <resource>`)**

When a valid resource name is supplied:

- Locates the matching resource definition
- Scans the resource directory for files
- For each file found, outputs:
    - Last modification timestamp
    - Full file path
    - File size (formatted, bytes)

**If files are found:**

- Returns a detailed listing with total count

**If the resource directory exists but contains no files:**

- Returns a clear “no resources found” message

---

### **Error Handling**

- Invalid resource name:
    - Returns an error indicating the resource does not exist
- Missing or empty resource directory:
    - Reported explicitly

---

### **Return Value**

- Always returns a human-readable string suitable for CLI output
- Output is newline-terminated and ready for direct echo/print

---

### **Notes**

- Resource resolution is based entirely on `_node_structure_call()` metadata
- Filesystem access uses direct directory globbing
- Designed for transparency and auditability rather than abstraction
- Complements:
    - `_node_cli_like` (symbol discovery)
    - `_node_cli_emit` (code/state extraction)

---

### **Typical Use Cases**

- Verifying deployed resources
- Inspecting runtime asset state
- Debugging missing or outdated resource files
- Quick filesystem audits without leaving the NodePHP CLI
