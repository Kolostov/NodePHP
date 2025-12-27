### `_node_cli_backup` Documentation

**Purpose:**
Creates a backup of the current NodePHP node, either as a ZIP archive (preferred) or a TAR.GZ archive (fallback), excluding certain directories.

---

#### **Function Signature**

```php
_node_cli_backup(bool $tooltip = false, array $argv = []): string
```

---

#### **Parameters**

- `tooltip` (`bool`):
  If `true`, returns a short description of the command without performing a backup.

- `argv` (`array`):
  Optional array of arguments passed via CLI. The first element can specify a custom backup name. Defaults to the current date in `Ymd` format if not provided.

---

#### **Behavior**

1. **Backup Name & Path**
    - Default name: current date (`Ymd`).
    - Custom name: `$argv[0]` if provided.
    - Backup stored in `ROOT_PATH/Backup/` directory.
    - File extension: `.zip` if ZIP extension available, `.tar.gz` otherwise.

2. **ZIP Backup**
    - Checks for availability of `zip` extension or `ZipArchive` class.
    - Skips directories: `Backup`, `Log`, `Deprecated`, `vendor`, `node_modules`.
    - Recursively adds files and directories.
    - Returns summary with path, number of files, and size in MB.

3. **TAR.GZ Fallback**
    - Executed if ZIP not available.
    - Generates a temporary exclude file for skipped directories.
    - Runs system `tar` command with compression.
    - Returns summary including file count, archive size, and warning that ZIP is unavailable.

4. **Error Handling**
    - Returns an error string if:
        - Backup file already exists.
        - Cannot create ZIP or TAR.GZ archive.
        - Required extensions or system tools are missing.

---

#### **Example Usage**

```bash
php node.php backup
php node.php backup MyCustomBackup
```

- First example creates a backup named after the current date.
- Second example creates a backup named `MyCustomBackup`.

---

#### **Return Value**

- On success: String message indicating backup path, number of files, and size.
- On failure: Error string starting with `E:` describing the issue.

---

#### **Related Functions**

- `_node_create_tar_backup(string $backupDir, string $backupName): string`
  Handles TAR.GZ creation when ZIP is unavailable.

- `_node_count_files_in_tar(string $tarFile): int`
  Counts files inside a TAR.GZ archive for reporting.

- `test_node_cli_backup(): int`
  Validates that backups can be created correctly in the environment.

---

#### **Notes**

- Ensures backups do not overwrite existing files.
- Excluded directories are never included in the archive.
- Fully compatible with both phaseless and phased CLI execution modes.
