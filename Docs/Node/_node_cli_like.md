### `_node_cli_like` Documentation

**Purpose:**
Performs a broad, case-insensitive search across the NodePHP runtime to find resources, symbols, and documentation that _look like_ a given term.

This command is intended for discovery, exploration, and orientation within large NodePHP-based systems.

---

#### **Function Signature**

```php
_node_cli_like(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`)
  When `true`, returns a short usage description for CLI help.

- `argv` (`array`)
    - `argv[0]` — search term (required).

---

#### **Search Scope**

When a search term is provided, the function scans the following domains:

---

##### **1. Node Resources**

- Uses `_node_structure_call()` output.
- Matches against:
    - Resource call names
    - Relative resource paths
- Output format:
    - `[resource] <call> path - description`

---

##### **2. Functions**

- Searches all defined PHP functions:
    - User-defined
    - Internal (built-in)
- Output format:
    - `[function:user] function_name()`
    - `[function:internal] function_name()`

---

##### **3. Classes**

- Searches all declared classes.
- Detects and annotates:
    - Internal vs user-defined
    - `abstract`
    - `final`
- Output format:
    - `[class:user] [abstract|final] ClassName`

---

##### **4. Interfaces**

- Searches all declared interfaces.
- Output format:
    - `[interface:user] InterfaceName`

---

##### **5. Traits**

- Searches all declared traits.
- Output format:
    - `[trait:user] TraitName`

---

##### **6. Constants**

- Searches all defined constants across all scopes.
- Includes a lightweight value preview:
    - Scalar → string value
    - Non-scalar → type name
- Output format:
    - `[constant:scope] NAME = "value"`

---

##### **7. Documentation**

- Delegates to `_node_search_docs()`.
- Searches Markdown documentation content.
- Output format:
    - `[documentation:path] N mention(s)`

---

#### **Deduplication**

- All results are de-duplicated internally using a type-prefixed key.
- Ensures the same symbol is never reported more than once, even if found via multiple paths.

---

#### **Return Value**

- On success:
    - Returns a formatted list of matches with total count.
- If no matches are found:
    - Returns a clear “no matches” message.
- If no search term is provided:
    - Returns an error message with correct usage.

---

#### **Error Conditions**

- Missing search term:
    - `E: Missing search term, call like <term>`

---

#### **Notes**

- Matching is case-insensitive.
- Designed as a _horizontal_ search tool (breadth-first visibility), not deep analysis.
- Complements:
    - `_node_cli_ctx` (deep context inspection)
    - `_node_cli_info` (documentation lookup)
- Especially useful for:
    - Large codebases
    - Unknown APIs
    - Refactoring and dependency discovery
