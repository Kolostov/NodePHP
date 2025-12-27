### `_node_cli_env` Documentation

**Purpose:**
Manages environment variables stored in a `.env` file at the project root. Supports listing, setting, and retrieving key-value pairs.

---

#### **Function Signature**

```php
_node_cli_env(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a brief description instead of performing actions.

- `argv` (`array`):
  CLI arguments where:
    - First element is the action (`list`, `set`, `get`)
    - Second element depends on the action:
        - For `set`: `KEY = VALUE`
        - For `get`: `KEY`

---

#### **Actions**

1. **list** (default)
   Lists all environment variables in the `.env` file, ignoring comment lines starting with `#`.

2. **set**
   Sets or updates an environment variable.
    - Usage: `env set KEY = VALUE`
    - Adds the key if it does not exist, or updates the existing value.

3. **get**
   Retrieves the value of a specific environment variable.
    - Usage: `env get KEY`
    - Returns an error if the key does not exist.

---

#### **Behavior**

- Automatically creates `.env` if missing, initializing with a comment header.
- Lines are written back to the file after modification using `f()` helper.
- Whitespace around keys and values is trimmed.
- Provides clear error messages for invalid usage or missing keys.

---

#### **Example Usage**

```bash
php node.php env list
php node.php env set API_KEY = 12345
php node.php env get API_KEY
```

---

#### **Notes**

- All environment variables are stored in a simple `KEY=VALUE` format.
- Comments (`#`) are ignored during listing and retrieval.
- This CLI tool allows quick local configuration without editing files manually.
