### `_node_cli_dump` Documentation

**Purpose:**
Outputs the detailed structure and value of a global variable for debugging purposes.

---

#### **Function Signature**

```php
_node_cli_dump(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a brief description of the command without performing the dump.

- `argv` (`array`):
  CLI arguments array:
    - First element: variable name to dump, optionally prefixed with `$`.

---

#### **Behavior**

1. **Validation**
    - Returns an error if no variable name is provided.
    - Checks if the specified variable exists in the global scope.
    - Returns an error if the variable does not exist.

2. **Variable Dump**
    - Uses `var_dump()` to display type and value information of the variable.
    - Captures output using `ob_start()`/`ob_get_clean()` and returns it as a string.

---

#### **Example Usage**

```bash
php node.php dump $myVariable
```

- Dumps the content and type of the global variable `$myVariable`.

---

#### **Return Value**

- Success: String containing the `var_dump` output of the variable.
- Failure: Error message starting with `E:` if the variable is not provided or not found.

---

#### **Notes**

- Only works with variables in the `$GLOBALS` scope.
- Useful for quick CLI debugging without modifying code.
- Preserves original formatting of `var_dump()`.
