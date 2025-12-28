# Abstract/Infrastructure Class Patterns in NodePHP Framework

## Overview: Infrastructure Foundation Classes

In the NodePHP framework, abstract infrastructure classes are located under `Primitive/Class/Abstract/Infrastructure/` and provide skeletal implementations for technical concerns. They integrate with framework utilities like `r()` for logging, `h()` for hooks, `f()` for file operations (e.g., in flat DBs), `p()` for phase management (e.g., persist phase), and `env()` for configuration. These classes abstract platform details, align with structures like `Database/Connection` and `Config/Cache`, and support extensions via hooks.

### Abstract Infrastructure Class Types Overview

| **Base Class** | **Technical Domain** | **Integration Type**               | **State Management** | **Extension Hooks**                                       |
| -------------- | -------------------- | ---------------------------------- | -------------------- | --------------------------------------------------------- |
| **Cache**      | Data caching         | Storage systems (e.g., file-based) | Ephemeral            | Serialization, invalidation, `h("cache_pre_set")`         |
| **Database**   | Data persistence     | Database systems (e.g., Flat/JSON) | Transactional        | Connection, query building, `h("database_pre_query")`     |
| **DTO**        | Data transfer        | API boundaries                     | Immutable            | Validation, transformation, `h("dto_pre_validate")`       |
| **Migration**  | Schema evolution     | Version control                    | Sequential           | Up/down operations, integration with `Database/Migration` |
| **Transport**  | Communication        | Network protocols                  | Connection state     | Message encoding/decoding, `h("transport_pre_send")`      |

## Abstract Infrastructure Class Details

### Abstract/Infrastructure/Cache

**Purpose**: Provides foundation for caching with operations, TTL, and namespace support. Abstracts backends (e.g., file-based via `f()`), integrates with `Config/Cache` via `env()`, and logs via `r()`.

```php
<?php declare(strict_types=1);

abstract class Cache
{
    protected $prefix = '';
    protected $defaultTtl = 3600;
    protected $hitCount = 0;
    protected $missCount = 0;

    // Core operations - must be implemented by concrete classes
    abstract public function get($key, $default = null);
    abstract public function set($key, $value, $ttl = null): bool;
    abstract public function delete($key): bool;
    abstract public function clear(): bool;
    abstract public function has($key): bool;

    // Template method for get-or-compute pattern
    final public function remember($key, $ttl, callable $callback) {
        h('cache_pre_get', ['key' => $key]); // Framework hook
        $value = $this->get($key);
        if ($value !== null) {
            $this->hitCount++;
            r("Cache hit: {$key}", "Internal");
            return $value;
        }
        $this->missCount++;
        r("Cache miss: {$key}", "Internal");
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    final public function rememberForever($key, callable $callback) {
        return $this->remember($key, null, $callback);
    }

    // Multiple operations
    final public function getMultiple(array $keys, $default = null): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    final public function setMultiple(array $values, $ttl = null): bool {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    // Key management
    final protected function buildKey($key): string {
        return $this->prefix . $key;
    }

    final public function setPrefix($prefix): void {
        $this->prefix = $prefix;
    }

    // TTL handling
    final protected function computeTtl($ttl): ?int {
        if ($ttl === null) {
            return null; // Forever
        }
        if (is_int($ttl)) {
            return $ttl;
        }
        return $this->defaultTtl;
    }

    // Statistics
    final public function getStats(): array {
        return [
            'hits' => $this->hitCount,
            'misses' => $this->missCount,
            'hit_rate' => $this->hitCount + $this->missCount > 0
                ? $this->hitCount / ($this->hitCount + $this->missCount)
                : 0
        ];
    }

    // Serialization hooks
    protected function serialize($value): string {
        return serialize($value);
    }

    protected function unserialize($data) {
        return unserialize($data);
    }

    // Invalidation strategies
    public function invalidate($pattern): bool {
        // Default implementation - override for pattern-based invalidation
        return false;
    }
}
```

### Abstract/Infrastructure/Database

**Purpose**: Foundation for database integrations, managing connections and transactions. Supports flat file DBs via `f()`, queries logged with `r()`, and phases like `p("persist")`.

```php
<?php declare(strict_types=1);

abstract class Database
{
    protected $connection = null;
    protected $inTransaction = false;
    protected $queryLog = [];
    protected $config = [];

    // Connection management
    abstract protected function connect(): void;
    abstract protected function disconnect(): void;

    // Query execution
    abstract public function query($sql, $params = []);
    abstract public function execute($sql, $params = []): int;

    // Transaction template methods
    final public function beginTransaction(): bool {
        if ($this->inTransaction) {
            throw new RuntimeException("Already in transaction");
        }
        $this->logQuery("BEGIN TRANSACTION");
        $this->inTransaction = $this->performBeginTransaction();
        p('mutate'); // Framework phase
        return $this->inTransaction;
    }

    final public function commit(): bool {
        if (!$this->inTransaction) {
            throw new RuntimeException("Not in transaction");
        }
        $this->logQuery("COMMIT");
        $success = $this->performCommit();
        $this->inTransaction = false;
        p('persist'); // Framework phase
        return $success;
    }

    final public function rollback(): bool {
        if (!$this->inTransaction) {
            throw new RuntimeException("Not in transaction");
        }
        $this->logQuery("ROLLBACK");
        $success = $this->performRollback();
        $this->inTransaction = false;
        f('rollback'); // Framework rollback
        return $success;
    }

    // Transaction hooks for concrete implementations
    abstract protected function performBeginTransaction(): bool;
    abstract protected function performCommit(): bool;
    abstract protected function performRollback(): bool;

    // Template method for transactional operations
    final public function transaction(callable $callback) {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            r("Transaction failed: " . $e->getMessage(), "Exception");
            throw $e;
        }
    }

    // Query building support
    final public function table($tableName): QueryBuilder {
        return new QueryBuilder($this, $tableName);
    }

    // Prepared statement execution
    final public function select($sql, $params = []): array {
        h('database_pre_query', ['sql' => $sql]); // Framework hook
        return $this->query($sql, $params);
    }

    final public function insert($table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        return $this->execute($sql, array_values($data));
    }

    final public function update($table, array $data, $where, $whereParams = []): int {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "{$column} = ?";
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE {$where}";
        return $this->execute($sql, array_merge(array_values($data), $whereParams));
    }

    final public function delete($table, $where, $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->execute($sql, $params);
    }

    // Logging
    protected function logQuery($sql, $params = []): void {
        $this->queryLog[] = [
            'sql' => $sql,
            'params' => $params,
            'time' => microtime(true)
        ];
        r("Query executed: {$sql}", "Internal", null, ['params' => $params]);
    }

    public function getQueryLog(): array {
        return $this->queryLog;
    }

    public function flushQueryLog(): void {
        $this->queryLog = [];
    }

    // Connection state
    final public function isConnected(): bool {
        return $this->connection !== null;
    }

    final public function inTransaction(): bool {
        return $this->inTransaction;
    }

    // Lifecycle management
    public function __destruct() {
        if ($this->inTransaction) {
            $this->rollback();
        }
        $this->disconnect();
    }
}
```

### Abstract/Infrastructure/DTO

**Purpose**: Base for Data Transfer Objects, with validation and serialization. Uses `h()` for hooks, `env()` for config, and ensures immutability aligned with domain transfers.

```php
<?php declare(strict_types=1);

abstract class DTO
{
    protected $data = [];
    protected $validated = false;

    // Constructor template method
    final public function __construct(array $data = []) {
        $this->initialize();
        $this->hydrate($data);
        $this->validate(); // Auto-validate on construction
    }

    // Initialization hook
    protected function initialize(): void {
        // Set default values or configuration from env()
    }

    // Hydration with validation
    final protected function hydrate(array $data): void {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) || $this->allowDynamicProperties()) {
                $this->data[$key] = $this->transformIncomingValue($key, $value);
            }
        }
    }

    // Validation template method
    final public function validate(): bool {
        h('dto_pre_validate', $this); // Framework hook
        $this->validated = false;
        // Run custom validation rules
        $this->validateData();
        // Run field-specific validators
        foreach ($this->data as $key => $value) {
            $this->validateField($key, $value);
        }
        $this->validated = true;
        r("DTO validated: " . static::class, "Internal");
        return true;
    }

    // Validation hooks
    protected function validateData(): void {
        // Override for cross-field validation
    }

    protected function validateField($field, $value): void {
        // Override for field-specific validation
    }

    // Value transformation hooks
    protected function transformIncomingValue($key, $value) {
        return $value;
    }

    protected function transformOutgoingValue($key, $value) {
        return $value;
    }

    // Serialization
    final public function toArray(): array {
        $result = [];
        foreach ($this->data as $key => $value) {
            $result[$key] = $this->transformOutgoingValue($key, $value);
        }
        return $result;
    }

    final public function toJson(): string {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    // Factory method pattern
    final public static function create(array $data): static {
        return new static($data);
    }

    final public static function fromJson(string $json): static {
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON: " . json_last_error_msg());
        }
        return new static($data);
    }

    // Immutability support
    final public function with($key, $value): static {
        $clone = clone $this;
        $clone->data[$key] = $clone->transformIncomingValue($key, $value);
        $clone->validated = false; // Reset validation state
        return $clone;
    }

    // Data access with validation enforcement
    final public function get($key, $default = null) {
        if (!$this->validated) {
            throw new RuntimeException("DTO must be validated before access");
        }
        if (array_key_exists($key, $this->data)) {
            return $this->transformOutgoingValue($key, $this->data[$key]);
        }
        return $default;
    }

    // Magic accessors
    final public function __get($key) {
        return $this->get($key);
    }

    final public function __isset($key): bool {
        return array_key_exists($key, $this->data);
    }

    // Configuration
    protected function allowDynamicProperties(): bool {
        return env('DTO_ALLOW_DYNAMIC', false);
    }

    // Clone handling
    public function __clone() {
        $this->validated = false;
    }
}
```

### Abstract/Infrastructure/Migration

**Purpose**: Base for schema migrations, with version tracking and rollback. Integrates with `Database/Migration`, uses `f()` for schema files, and logs via `r()`.

```php
<?php declare(strict_types=1);

abstract class Migration
{
    protected $connection;
    protected $version;
    protected $description;
    protected $dependencies = [];

    public function __construct($connection) {
        $this->connection = $connection;
        $this->initialize();
    }

    // Initialization hook
    protected function initialize(): void {
        $this->version = $this->getVersion();
        $this->description = $this->getDescription();
        $this->dependencies = $this->getDependencies();
    }

    // Template method for migration execution
    final public function migrate(): bool {
        $this->beforeUp();
        try {
            $this->up();
            $this->afterUp();
            return true;
        } catch (Exception $e) {
            $this->onUpError($e);
            return false;
        }
    }

    final public function rollback(): bool {
        $this->beforeDown();
        try {
            $this->down();
            $this->afterDown();
            return true;
        } catch (Exception $e) {
            $this->onDownError($e);
            return false;
        }
    }

    // Migration operations - must be implemented
    abstract protected function up(): void;
    abstract protected function down(): void;

    // Metadata - must be implemented
    abstract protected function getVersion(): string;
    abstract protected function getDescription(): string;

    // Optional hooks
    protected function getDependencies(): array {
        return [];
    }

    protected function beforeUp(): void {
        r("Starting migration: {$this->description}", "Internal");
    }

    protected function afterUp(): void {
        r("Completed migration: {$this->description}", "Internal");
    }

    protected function beforeDown(): void {
        r("Rolling back migration: {$this->description}", "Internal");
    }

    protected function afterDown(): void {
        r("Rollback completed: {$this->description}", "Internal");
    }

    protected function onUpError(Exception $e): void {
        r("Migration failed: " . $e->getMessage(), "Exception");
    }

    protected function onDownError(Exception $e): void {
        r("Rollback failed: " . $e->getMessage(), "Exception");
    }

    // Helper methods for common operations
    final protected function createTable($tableName, callable $blueprint): void {
        $builder = new TableBuilder($tableName);
        $blueprint($builder);
        $sql = $builder->buildCreateSql();
        $this->connection->execute($sql);
    }

    final protected function dropTable($tableName): void {
        $sql = "DROP TABLE IF EXISTS {$tableName}";
        $this->connection->execute($sql);
    }

    final protected function addColumn($tableName, $columnName, $definition): void {
        $sql = "ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}";
        $this->connection->execute($sql);
    }

    final protected function dropColumn($tableName, $columnName): void {
        $sql = "ALTER TABLE {$tableName} DROP COLUMN {$columnName}";
        $this->connection->execute($sql);
    }

    final protected function addIndex($tableName, $indexName, $columns): void {
        $columnList = is_array($columns) ? implode(', ', $columns) : $columns;
        $sql = "CREATE INDEX {$indexName} ON {$tableName} ({$columnList})";
        $this->connection->execute($sql);
    }

    final protected function dropIndex($tableName, $indexName): void {
        $sql = "DROP INDEX {$indexName} ON {$tableName}";
        $this->connection->execute($sql);
    }

    // Metadata access
    final public function getMigrationInfo(): array {
        return [
            'version' => $this->version,
            'description' => $this->description,
            'dependencies' => $this->dependencies,
            'class' => static::class
        ];
    }

    final public function hasDependency($version): bool {
        return in_array($version, $this->dependencies);
    }
}
```

### Abstract/Infrastructure/Transport

**Purpose**: Foundation for transports, managing connections and messages. Uses `h()` for hooks, `r()` for errors, and `env()` for options like timeouts.

```php
<?php declare(strict_types=1);

abstract class Transport
{
    protected $connected = false;
    protected $options = [];
    protected $lastError = null;

    // Connection lifecycle template methods
    final public function connect(): bool {
        if ($this->connected) {
            return true;
        }
        $this->beforeConnect();
        try {
            $this->connected = $this->performConnect();
            if ($this->connected) {
                $this->afterConnect();
            }
            return $this->connected;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->onConnectError($e);
            return false;
        }
    }

    final public function disconnect(): bool {
        if (!$this->connected) {
            return true;
        }
        $this->beforeDisconnect();
        try {
            $success = $this->performDisconnect();
            if ($success) {
                $this->connected = false;
                $this->afterDisconnect();
            }
            return $success;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            $this->onDisconnectError($e);
            return false;
        }
    }

    // Message sending template method
    final public function send($message, array $options = []) {
        if (!$this->connected) {
            throw new RuntimeException("Not connected");
        }
        $mergedOptions = array_merge($this->options, $options);
        $encodedMessage = $this->encodeMessage($message, $mergedOptions);
        h('transport_pre_send', ['message' => $encodedMessage]); // Framework hook
        $this->beforeSend($encodedMessage, $mergedOptions);
        try {
            $result = $this->performSend($encodedMessage, $mergedOptions);
            $this->afterSend($encodedMessage, $result, $mergedOptions);
            return $this->decodeResponse($result, $mergedOptions);
        } catch (Exception $e) {
            $this->onSendError($e, $encodedMessage, $mergedOptions);
            throw $e;
        }
    }

    // Message receiving template method
    final public function receive(array $options = []) {
        if (!$this->connected) {
            throw new RuntimeException("Not connected");
        }
        $mergedOptions = array_merge($this->options, $options);
        $this->beforeReceive($mergedOptions);
        try {
            $rawResponse = $this->performReceive($mergedOptions);
            $this->afterReceive($rawResponse, $mergedOptions);
            return $this->decodeResponse($rawResponse, $mergedOptions);
        } catch (Exception $e) {
            $this->onReceiveError($e, $mergedOptions);
            throw $e;
        }
    }

    // Core operations - must be implemented
    abstract protected function performConnect(): bool;
    abstract protected function performDisconnect(): bool;
    abstract protected function performSend($encodedMessage, array $options);
    abstract protected function performReceive(array $options);

    // Encoding/decoding hooks
    protected function encodeMessage($message, array $options) {
        return $message; // Default: no encoding
    }

    protected function decodeResponse($response, array $options) {
        return $response; // Default: no decoding
    }

    // Lifecycle hooks
    protected function beforeConnect(): void {}
    protected function afterConnect(): void {}
    protected function onConnectError(Exception $e): void {
        r("Connect error: " . $e->getMessage(), "Exception");
    }
    protected function beforeDisconnect(): void {}
    protected function afterDisconnect(): void {}
    protected function onDisconnectError(Exception $e): void {
        r("Disconnect error: " . $e->getMessage(), "Exception");
    }
    protected function beforeSend($encodedMessage, array $options): void {}
    protected function afterSend($encodedMessage, $result, array $options): void {}
    protected function onSendError(Exception $e, $encodedMessage, array $options): void {
        r("Send error: " . $e->getMessage(), "Exception", null, ['message' => $encodedMessage]);
    }
    protected function beforeReceive(array $options): void {}
    protected function afterReceive($response, array $options): void {}
    protected function onReceiveError(Exception $e, array $options): void {
        r("Receive error: " . $e->getMessage(), "Exception");
    }

    // Connection state
    final public function isConnected(): bool {
        return $this->connected;
    }

    final public function getLastError(): ?string {
        return $this->lastError;
    }

    // Configuration
    final public function setOption($key, $value): void {
        $this->options[$key] = $value;
    }

    final public function getOption($key, $default = null) {
        return $this->options[$key] ?? env("TRANSPORT_{$key}", $default);
    }

    // Batch operations
    final public function sendBatch(array $messages, array $options = []): array {
        $results = [];
        foreach ($messages as $index => $message) {
            $results[$index] = $this->send($message, $options);
        }
        return $results;
    }

    // Health check
    final public function ping(): bool {
        try {
            $this->connect();
            return $this->performPing();
        } catch (Exception $e) {
            return false;
        }
    }

    protected function performPing(): bool {
        // Default implementation - override for protocol-specific ping
        return $this->connected;
    }

    // Cleanup
    public function __destruct() {
        $this->disconnect();
    }
}
```

## Complementary Patterns in NodePHP

**Bridge Pattern**: Transport bridges abstraction and implementation, aligned with `Infrastructure/Gateway`. **Adapter Pattern**: Database/Cache adapt backends via `Extension/Library`. **Template Method**: Used heavily, integrated with `p()` phases. **Strategy Pattern**: Encoding strategies in Transport, configurable via `env()`. **Factory Method**: In DTO for creation, hooked via `h()`.

## Distinguishing Characteristics

**vs. Service Classes**: Infrastructure handles technical concerns; services (in `Primitive/Class/Final/Infrastructure/Service`) handle business logic. **vs. Concrete Implementations**: These are abstract; concretes in `Primitive/Class/Final/Infrastructure/`. **vs. Interfaces**: Abstracts provide implementation; interfaces in `Primitive/Interface/Infrastructure/` define contracts. **vs. Traits**: Establish hierarchies; traits (in `Primitive/Trait/`) provide reuse. **vs. Utility Classes**: Manage state; utilities in `Primitive/Function/Helper` are stateless.
