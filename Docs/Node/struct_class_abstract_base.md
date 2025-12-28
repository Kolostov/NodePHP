# Abstract/Base Class Patterns in NodePHP Framework

## Overview: Foundation Abstract Classes

In the NodePHP framework, abstract base classes are located under `Primitive/Class/Abstract/Base/` and provide skeletal implementations for core components. They leverage framework utilities like `r()` for logging, `h()` for hooks, `f()` for file operations, and `p()` for phase management. These classes enforce consistent patterns, integrate with the phase system (e.g., boot, execute), and support extension via hooks and template methods. They reduce duplication while aligning with the framework's structure defined in `node.json`.

### Abstract Base Class Types Overview

| **Base Class** | **Domain Responsibility**            | **Abstraction Level**   | **Template Methods**           | **Extension Points**                                      |
| -------------- | ------------------------------------ | ----------------------- | ------------------------------ | --------------------------------------------------------- |
| **Command**    | Command execution in CLI or phases   | High (orchestration)    | `execute()`, `validate()`      | `handle()`, `authorize()`, `h("command_pre_execute")`     |
| **Controller** | Request handling in web/API contexts | Medium (routing)        | `handle()`, `respond()`        | `index()`, `store()`, `h("controller_pre_handle")`        |
| **Entity**     | Domain object with business rules    | High (business rules)   | `validate()`, `equals()`       | Domain-specific logic, `h("entity_pre_validate")`         |
| **Model**      | Data representation and persistence  | Medium (persistence)    | `save()`, `delete()`, `find()` | Accessors, mutators, integration with Database/Connection |
| **Repository** | Data persistence abstraction         | High (CRUD abstraction) | `find()`, `save()`, `delete()` | Query building, caching, `h("repository_pre_query")`      |
| **Service**    | Business operations coordination     | Medium (coordination)   | `execute()`, `validate()`      | Business rule application, transaction hooks              |

## Abstract Base Class Details

### Abstract/Base/Command

**Purpose**: Provides skeletal structure for command pattern implementations, integrated with NodePHP's CLI and phase system (e.g., via `p("execute")`). Uses `r()` for logging and `h()` for extension points. Enforces execution flow while allowing subclasses to implement behavior, with support for environment variables via `env()`.

```php
<?php declare(strict_types=1);

abstract class Command
{
    protected $parameters = [];
    protected $executed = false;

    public function __construct(array $parameters = []) {
        $this->parameters = $parameters;
    }

    // Template method defining command execution flow
    final public function execute() {
        h('command_pre_execute', $this); // Framework hook
        if (!$this->authorize()) {
            throw new RuntimeException("Command not authorized"); // Use RuntimeException as per framework
        }
        if (!$this->validate()) {
            throw new RuntimeException("Command validation failed");
        }
        $this->logBefore();
        $result = $this->handle();
        $this->logAfter($result);
        $this->executed = true;
        h('command_post_execute', $result); // Framework hook
        return $result;
    }

    final public function isExecuted(): bool {
        return $this->executed;
    }

    // Hook methods for subclasses to implement
    abstract protected function handle();

    protected function authorize(): bool {
        return true; // Default: authorized, override with env('APP_AUTH_ENABLED')
    }

    protected function validate(): bool {
        return true; // Default: valid
    }

    protected function logBefore(): void {
        r("Starting command: " . static::class, "Internal");
    }

    protected function logAfter($result): void {
        r("Command completed: " . static::class, "Internal", null, ['result' => $result]);
    }

    public function getParameters(): array {
        return $this->parameters;
    }
}

// Concrete implementation example (place in Primitive/Class/Final/Behavioral/Command/)
class CreateUserCommand extends Command
{
    protected function handle() {
        // Business logic for creating user
        $name = $this->parameters['name'];
        $email = $this->parameters['email'];
        // Simulate user creation, integrate with framework file ops
        $userId = uniqid();
        f('users.json', 'write', json_encode(['id' => $userId, 'name' => $name, 'email' => $email]) . "\n", true); // Append to log-like file
        r("Created user {$name} with ID {$userId}", "Audit");
        return ['id' => $userId, 'name' => $name, 'email' => $email];
    }

    protected function validate(): bool {
        return !empty($this->parameters['name'])
            && !empty($this->parameters['email'])
            && filter_var($this->parameters['email'], FILTER_VALIDATE_EMAIL);
    }
}
```

### Abstract/Base/Controller

**Purpose**: Foundation for request handlers, integrated with NodePHP's superglobals and phase system. Uses `r()` for logging and `h()` for middleware-like extensions. Supports web/console routing aligned with `Public/Entry` and `Console/Kernel`.

```php
<?php declare(strict_types=1);

abstract class Controller
{
    protected $request;
    protected $response = [];

    public function __construct($request = null) {
        $this->request = $request ?? $_SERVER; // Framework integration with superglobals
    }

    // Main entry point - template method
    final public function handle(): array {
        h('controller_pre_handle', $this); // Framework hook
        $method = strtolower($this->request['REQUEST_METHOD'] ?? 'get');
        $action = $this->request['REQUEST_URI'] ?? 'index'; // Simplified for framework
        // Route to appropriate method
        if (method_exists($this, $method . ucfirst($action))) {
            $methodName = $method . ucfirst($action);
        } elseif (method_exists($this, $action)) {
            $methodName = $action;
        } else {
            throw new RuntimeException("Action {$action} not found");
        }
        // Execute with middleware-style hooks
        $this->before();
        $this->$methodName();
        $this->after();
        $response = $this->buildResponse();
        h('controller_post_handle', $response); // Framework hook
        return $response;
    }

    // Hook methods
    protected function before(): void {
        // Authentication, authorization, input sanitization
        if (env('APP_DEBUG', false)) {
            r("Controller before hook", "Internal");
        }
    }

    protected function after(): void {
        // Logging, cleanup, response transformation
        r("Controller after hook", "Internal");
    }

    // Default CRUD actions (can be overridden)
    protected function index(): void {
        $this->response = ['message' => 'Index action not implemented'];
    }

    protected function getShow(): void {
        $this->response = ['message' => 'Show action not implemented'];
    }

    protected function postStore(): void {
        $this->response = ['message' => 'Store action not implemented'];
    }

    protected function putUpdate(): void {
        $this->response = ['message' => 'Update action not implemented'];
    }

    protected function deleteDestroy(): void {
        $this->response = ['message' => 'Destroy action not implemented'];
    }

    // Helper methods
    protected function json($data, $status = 200): void {
        $this->response = [
            'status' => $status,
            'data' => $data,
            'content_type' => 'application/json'
        ];
    }

    protected function redirect($url, $status = 302): void {
        $this->response = [
            'redirect' => $url,
            'status' => $status
        ];
    }

    protected function view($template, $data = []): void {
        $templatePath = f("Template/View/{$template}.php", 'find'); // Framework file find
        if ($templatePath) {
            $this->response = [
                'view' => $templatePath,
                'data' => $data,
                'content_type' => 'text/html'
            ];
        } else {
            throw new RuntimeException("View {$template} not found");
        }
    }

    private function buildResponse(): array {
        return array_merge([
            'timestamp' => time(),
            'controller' => static::class
        ], $this->response);
    }
}
```

### Abstract/Base/Entity

**Purpose**: Base for domain entities, with integration to NodePHP's event system via `h()` and logging via `r()`. Supports domain events and lifecycle phases (e.g., `p("persist")`).

```php
<?php declare(strict_types=1);

abstract class Entity
{
    protected $id = null;
    protected $domainEvents = [];
    protected $errors = [];

    // Identity and equality
    abstract public function getId();

    public function equals($other): bool {
        if (!$other instanceof static) {
            return false;
        }
        return $this->getId() === $other->getId() && $this->getId() !== null;
    }

    // Validation
    abstract public function validate(): bool;

    protected function addError($field, $message): void {
        $this->errors[] = ['field' => $field, 'message' => $message];
        r("Entity validation error: {$message}", "Error", null, ['field' => $field]);
    }

    // Domain events
    protected function recordEvent($event): void {
        $this->domainEvents[] = $event;
        h('entity_record_event', $event); // Framework hook
    }

    public function releaseEvents(): array {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    // Lifecycle hooks integrated with phases
    public function prePersist(): void {
        p('persist'); // Trigger framework phase if needed
    }

    public function postPersist(): void {}

    public function preUpdate(): void {}

    public function postUpdate(): void {}

    public function preRemove(): void {}

    public function postRemove(): void {}

    // Business rule methods
    protected function businessRule($condition, $message): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    // Magic methods for property access
    public function __get($name) {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new InvalidArgumentException("Property {$name} does not exist");
    }

    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            throw new InvalidArgumentException("Property {$name} does not exist");
        }
    }

    public function __isset($name): bool {
        return property_exists($this, $name);
    }
}
```

### Abstract/Base/Model

**Purpose**: Foundation for data models, bridging to NodePHP's Database structures (e.g., Flat/JSON). Uses `f()` for persistence in file-based DBs and `r()` for auditing.

```php
<?php declare(strict_types=1);

abstract class Model
{
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    // Attribute management
    public function fill(array $attributes): void {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function getAttribute($key) {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute($key, $value): void {
        // Call mutator if exists
        $mutator = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->$mutator($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function toArray(): array {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            // Call accessor if exists
            $accessor = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            if (method_exists($this, $accessor)) {
                $array[$key] = $this->$accessor($value);
            } else {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    // Persistence operations (framework-integrated, e.g., file-based)
    public function save(): bool {
        if ($this->exists) {
            return $this->performUpdate();
        }
        return $this->performInsert();
    }

    public function delete(): bool {
        if (!$this->exists) {
            return false;
        }
        return $this->performDelete();
    }

    public static function find($id): ?static {
        // Framework file lookup example
        $data = json_decode(f('Database/Flat/JSON/models.json', 'read'), true) ?? [];
        $found = array_filter($data, fn($item) => $item['id'] == $id);
        if ($found) {
            $model = new static();
            $model->fill(reset($found));
            $model->exists = true;
            return $model;
        }
        return null;
    }

    public static function all(): array {
        // Framework query logic
        return [];
    }

    // Template methods for subclasses
    abstract protected function performInsert(): bool;

    abstract protected function performUpdate(): bool;

    abstract protected function performDelete(): bool;

    // Dirty tracking
    public function isDirty(): bool {
        return $this->attributes !== $this->original;
    }

    public function getDirty(): array {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    // Magic methods
    public function __get($name) {
        return $this->getAttribute($name);
    }

    public function __set($name, $value) {
        $this->setAttribute($name, $value);
    }
}
```

### Abstract/Base/Repository

**Purpose**: Skeletal for repositories, integrating with NodePHP's Database/Connection and Flat storage. Uses `h()` for query hooks and `r()` for audit logging.

```php
<?php declare(strict_types=1);

abstract class Repository
{
    protected $entityClass;
    protected $queryBuilder;

    abstract protected function createQueryBuilder();

    abstract protected function executeQuery($query, $params = []);

    // Template method for find
    final public function find($id): ?object {
        h('repository_pre_query', ['type' => 'find', 'id' => $id]); // Framework hook
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName())
            ->where('id = :id');
        $result = $this->executeQuery($query, ['id' => $id]);
        if (empty($result)) {
            return null;
        }
        return $this->hydrate($result[0]);
    }

    final public function findAll(array $criteria = [], array $orderBy = [], $limit = null, $offset = null): array {
        $qb = $this->createQueryBuilder()
            ->select('*')
            ->from($this->getTableName());
        foreach ($criteria as $field => $value) {
            $qb->andWhere("{$field} = :{$field}");
        }
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        $results = $this->executeQuery($qb, $criteria);
        return array_map([$this, 'hydrate'], $results);
    }

    final public function save($entity): bool {
        if ($this->hasIdentity($entity)) {
            return $this->update($entity);
        }
        return $this->insert($entity);
    }

    final public function delete($entity): bool {
        $id = $this->getIdentity($entity);
        if ($id === null) {
            return false;
        }
        $query = $this->createQueryBuilder()
            ->delete($this->getTableName())
            ->where('id = :id');
        return $this->executeQuery($query, ['id' => $id]) !== false;
    }

    final public function count(array $criteria = []): int {
        $qb = $this->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($this->getTableName());
        foreach ($criteria as $field => $value) {
            $qb->andWhere("{$field} = :{$field}");
        }
        $result = $this->executeQuery($qb, $criteria);
        return (int) ($result[0]['COUNT(*)'] ?? 0);
    }

    // Hook methods for subclasses
    abstract protected function getTableName(): string;

    abstract protected function hydrate(array $data): object;

    abstract protected function extract($entity): array;

    abstract protected function getIdentity($entity);

    abstract protected function hasIdentity($entity): bool;

    // Concrete operations (e.g., file-based for Flat DB)
    protected function insert($entity): bool {
        $data = $this->extract($entity);
        unset($data['id']);
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);
        $query = $this->createQueryBuilder()
            ->insert($this->getTableName())
            ->values(array_combine($fields, $placeholders));
        $success = $this->executeQuery($query, $data) !== false;
        if ($success) {
            r("Inserted entity into {$this->getTableName()}", "Audit");
        }
        return $success;
    }

    protected function update($entity): bool {
        $data = $this->extract($entity);
        $id = $this->getIdentity($entity);
        unset($data['id']);
        $fields = array_keys($data);
        $qb = $this->createQueryBuilder()
            ->update($this->getTableName());
        foreach ($fields as $field) {
            $qb->set($field, ":{$field}");
        }
        $qb->where('id = :id');
        $data['id'] = $id;
        $success = $this->executeQuery($qb, $data) !== false;
        if ($success) {
            r("Updated entity in {$this->getTableName()}", "Audit");
        }
        return $success;
    }
}
```

### Abstract/Base/Service

**Purpose**: Foundation for services, with transaction management via phases and hooks. Uses `r()` for logging, `h()` for coordination, and `env()` for config.

```php
<?php declare(strict_types=1);

abstract class Service
{
    protected $errors = [];
    protected $transactionActive = false;

    // Main execution template method
    final public function execute(array $input = []) {
        $this->errors = [];
        try {
            $this->beginTransaction();
            $this->validateInput($input);
            if ($this->hasErrors()) {
                throw new RuntimeException("Input validation failed");
            }
            $result = $this->perform($input);
            $this->validateOutput($result);
            if ($this->hasErrors()) {
                throw new RuntimeException("Output validation failed");
            }
            $this->commitTransaction();
            return $result;
        } catch (Exception $e) {
            $this->rollbackTransaction();
            $this->errors[] = $e->getMessage();
            r("Service execution failed: " . $e->getMessage(), "Exception", null, ['input' => $input]);
            throw $e;
        }
    }

    // Business operation - must be implemented by subclasses
    abstract protected function perform(array $input);

    // Validation hooks (can be overridden)
    protected function validateInput(array $input): void {
        // Default: no validation
    }

    protected function validateOutput($output): void {
        // Default: no validation
    }

    // Error handling
    protected function addError($message): void {
        $this->errors[] = $message;
    }

    public function hasErrors(): bool {
        return !empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    // Transaction management (hook methods, integrated with phases)
    protected function beginTransaction(): void {
        $this->transactionActive = true;
        p('mutate'); // Framework phase for changes
        r("Transaction started", "Internal");
    }

    protected function commitTransaction(): void {
        if ($this->transactionActive) {
            p('persist'); // Framework phase for persistence
            r("Transaction committed", "Internal");
            $this->transactionActive = false;
        }
    }

    protected function rollbackTransaction(): void {
        if ($this->transactionActive) {
            f('rollback'); // Framework rollback
            r("Transaction rolled back", "Internal");
            $this->transactionActive = false;
        }
    }

    // Utility methods
    protected function ensureCondition($condition, $message): void {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function log($message, $level = 'info'): void {
        r($message, strtoupper($level));
    }

    // Service coordination
    protected function callService(Service $service, array $input) {
        h('service_pre_call', ['service' => get_class($service), 'input' => $input]);
        return $service->execute($input);
    }
}
```

## Complementary Patterns in NodePHP

**Template Method Pattern**: Used in base classes for skeletons, aligned with phase system (`p()`). **Factory Method**: In abstract classes for creation, often hooked via `h()`. **Composite Pattern**: Controllers/commands form hierarchies with `Extension/Hook`. **Strategy Pattern**: Services use strategies, configurable via `env()`. **Chain of Responsibility**: Controllers chain via `h()` and middleware in `Presentation/Middleware`.

## Distinguishing Characteristics

**vs. Interface**: Abstract bases provide implementation; interfaces (in `Primitive/Interface/`) are contracts only. **vs. Trait**: Bases establish hierarchy; traits (in `Primitive/Trait/`) enable reuse. **vs. Concrete Class**: Abstracts can't instantiate; concretes in `Primitive/Class/Final/`. **vs. Utility Class**: Bases for extension; utilities in `Primitive/Function/Helper`. **vs. Service Layer**: Base service coordinates; layer in `Infrastructure/Service` with phase integration.
