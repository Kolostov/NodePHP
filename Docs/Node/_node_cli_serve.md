### `_node_cli_serve` Documentation

**Purpose:**
Starts a PHP built-in web server for the current node project, targeting the project's public entry point.

---

## `_node_cli_serve`

**Signature:**

```php
_node_cli_serve(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`):
  If `true`, returns a short usage description:
  `"<port> Starts PHP built-in web server for current node."`

- `argv` (`array`):
  Command-line arguments; expected to contain the port number as the first argument.
  Defaults to `8000` if not provided.

---

**Behavior:**

1. **Determine port:**
    - Uses `$argv[0]` if provided, otherwise defaults to `"8000"`.
    - Uses `localhost` as host.
    - Sets the document root to `ROOT_PATH . "Public" . D . "Entry"`.

2. **Check port availability:**
    - Attempts to open a socket to the specified host and port using `fsockopen()`.
    - If the port is already in use, returns an error:
      `"E: Port {$port} is already in use.\n"`

3. **Prepare server command:**
    - Generates the PHP built-in server command:
        ```php
        php -S localhost:<port> -t <documentRoot>
        ```
    - Escapes the document root for safety with `escapeshellarg()`.

4. **Output information:**
    - Returns a formatted message including:
        - URL to access the server (`http://localhost:<port>/`)
        - Document root path
        - Instructions to stop (`Ctrl+C`)
        - The exact command to run manually

---

**Return Value:**
Returns a string containing either an error message (if port is in use) or instructions for starting the development server.

---

**Usage Example:**

```bash
php node.php serve              # Starts server on default port 8000
php node.php serve 8080         # Starts server on port 8080
```

**Notes:**

- The server does **not automatically run**; it only provides the command to execute manually.
- Useful for local development of PHP projects without installing external web servers.
- Port availability check prevents conflicts with other running services.
