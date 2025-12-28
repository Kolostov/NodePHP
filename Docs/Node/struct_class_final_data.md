# Final/Data Class Patterns

## Overview: Structured Data Carriers & Specifications

Final data classes in Node.php provide immutable, framework-integrated structures for data representation, validation, transformation, and transport across system boundaries. These patterns address the fundamental challenge of data integrity in distributed systems by providing type-safe, validated, and context-aware data containers that leverage the framework's hook system for extensibility and file-based configuration for validation rules. Unlike domain entities that contain business logic, data classes focus purely on data representation and validation, ensuring clean separation between data transport and business operations.

### Data Flow Philosophy in Node.php

Node.php treats data as a first-class citizen with specific patterns for different data lifecycle stages:

- **Ingestion**: Form and Request patterns validate and sanitize incoming data
- **Transport**: DTO patterns carry data between layers with integrity
- **Specification**: Query patterns define data retrieval requirements
- **Presentation**: Resource patterns transform data for external consumption
- **Persistence**: Implicit through integration with Database patterns

All data patterns integrate with framework utilities: `h()` for validation/transformation hooks, `env()` for validation configuration, `r()` for data flow auditing, and `f()` for validation rule loading.

## Final Data Class Details

### Final/Data/DTO

**Purpose**: Immutable data transfer objects that carry validated data between application layers without behavior. DTOs in Node.php are pure data carriers with framework metadata, automatic validation through hooks, and built-in serialization for crossing process boundaries. They represent the contract between different parts of the system, ensuring data integrity during transport.

**Framework Integration**: DTOs use `h()` for field-level validation hooks, include framework context (NODE_NAME, creation timestamp), support serialization via `json_encode()` for transport, and can be hydrated from various sources (arrays, JSON strings, request data) with automatic validation.

```php
final class UserRegistrationDTO
{
    // Private constructor ensures immutability
    private function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $passwordHash,
        public readonly array $metadata
    ) {}

    // Factory method with framework validation
    public static function create(array $data): self
    {
        // Validate through hook system
        $validated = h('dto.user_registration.validate', $data);

        // Transform with framework context
        $transformed = h('dto.user_registration.transform', $validated);

        return new self(
            $transformed['email'],
            $transformed['username'],
            password_hash($transformed['password'], PASSWORD_DEFAULT),
            [
                'created_at' => time(),
                'node' => NODE_NAME,
                'validation_source' => 'hook_system',
                'request_id' => $data['_request_id'] ?? uniqid('req_', true)
            ]
        );
    }

    // Framework-aware serialization
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'username' => $this->username,
            'metadata' => $this->metadata
        ];
    }
}
```

**Key Characteristics**:

- **Immutable**: Properties are readonly, ensuring data integrity
- **Hook-validated**: Validation through `h()` allows external rule definition
- **Framework-context**: Includes NODE_NAME, timestamps, request context
- **Serializable**: Can cross process/network boundaries
- **Type-safe**: Constructor ensures required data presence

### Final/Data/Form

**Purpose**: Request data structures with built-in validation, error collection, and user-friendly error messages. Forms in Node.php are active validation containers that can load validation rules from files (`f()`), use environment-specific validation levels (`env()`), and provide detailed error feedback. They bridge the gap between raw request data and validated domain data.

**Framework Integration**: Forms load validation rules via `f('Config/Validation/*.json')`, use `h()` for custom validation rules, respect `env('NODE:VALIDATION_STRICT')` settings, and log validation failures via `r()` for auditing.

```php
final class UserRegistrationForm
{
    private array $errors = [];
    private array $validatedData = [];
    private string $formId;

    public function __construct()
    {
        $this->formId = uniqid('form_', true);
    }

    public function validate(array $data): bool
    {
        // Load validation rules from file system
        $rules = $this->loadValidationRules();

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            // Apply each rule through hook system
            foreach ($fieldRules as $rule) {
                $result = h("validation.rule.{$rule}", [
                    'field' => $field,
                    'value' => $value,
                    'form_id' => $this->formId
                ]);

                if ($result['valid'] === false) {
                    $this->errors[$field][] = $result['message'];

                    // Log validation failure
                    r("Form validation failed: {$field}", 'Audit', null, [
                        'form_id' => $this->formId,
                        'field' => $field,
                        'rule' => $rule,
                        'value' => $this->obfuscateSensitive($field, $value)
                    ]);
                }
            }

            // Store validated data if no errors
            if (!isset($this->errors[$field])) {
                $this->validatedData[$field] = h("form.field.transform.{$field}", $value) ?? $value;
            }
        }

        // Post-validation hook for cross-field validation
        if (empty($this->errors)) {
            $crossValidation = h('form.user_registration.validate_cross', $this->validatedData);
            if ($crossValidation['valid'] === false) {
                $this->errors['_form'] = $crossValidation['messages'];
            }
        }

        return empty($this->errors);
    }

    public function getValidatedData(): array
    {
        return $this->validatedData;
    }
}
```

**Key Characteristics**:

- **File-based rules**: Validation rules loaded from JSON/YAML files
- **Hook-extensible**: Custom rules via `h('validation.rule.*')`
- **Error collection**: Detailed field-level error messages
- **Cross-field validation**: Post-validation hooks for complex rules
- **Audit logging**: All validations logged via `r()`

### Final/Data/Query

**Purpose**: Data retrieval specifications that encapsulate filtering, sorting, pagination, and field selection requirements. Queries in Node.php are immutable specifications that can be executed against various data sources (databases, APIs, files) and provide a consistent interface for data retrieval across the application. They separate _what_ data is needed from _how_ it's retrieved.

**Framework Integration**: Queries can be constructed from request parameters via `h()` transformers, include context from `env()` for environment-specific filtering, and are logged via `r()` for query auditing and performance monitoring.

```php
final class UserListQuery
{
    private function __construct(
        public readonly ?array $filters,
        public readonly ?string $sortBy,
        public readonly ?string $sortOrder,
        public readonly ?int $page,
        public readonly ?int $perPage,
        public readonly array $metadata
    ) {}

    // Build from request with framework context
    public static function fromRequest(array $params): self
    {
        // Transform request params through hooks
        $transformed = h('query.user_list.transform', $params);

        // Apply environment-specific defaults
        $defaultPerPage = env('NODE:QUERY_DEFAULT_PER_PAGE', 20);
        $maxPerPage = env('NODE:QUERY_MAX_PER_PAGE', 100);

        return new self(
            $transformed['filters'] ?? null,
            $transformed['sort'] ?? 'created_at',
            $transformed['order'] ?? 'desc',
            max(1, (int)($transformed['page'] ?? 1)),
            min($maxPerPage, (int)($transformed['per_page'] ?? $defaultPerPage)),
            [
                'query_id' => uniqid('query_', true),
                'constructed_at' => time(),
                'node' => NODE_NAME,
                'request_source' => $params['_source'] ?? 'unknown'
            ]
        );
    }

    // Convert to executable specification
    public function toSpecification(): array
    {
        $spec = [
            'filters' => $this->filters,
            'sort' => ['field' => $this->sortBy, 'order' => $this->sortOrder],
            'pagination' => ['page' => $this->page, 'per_page' => $this->perPage]
        ];

        // Add framework context to specification
        return h('query.user_list.finalize', array_merge($spec, [
            'metadata' => $this->metadata
        ])) ?? $spec;
    }
}
```

**Key Characteristics**:

- **Immutable specification**: Once created, query cannot be modified
- **Request-aware**: Can be built from HTTP/CLI request parameters
- **Environment-configurable**: Defaults from `env()` variables
- **Hook-transformable**: Parameters can be transformed via hooks
- **Auditable**: Unique query ID for tracing execution

### Final/Data/Request

**Purpose**: Validated, typed request objects that encapsulate HTTP/CLI input with framework context. Requests in Node.php are more than just parameter bags—they include authentication context, request metadata, validation status, and hooks for request lifecycle events. They serve as the primary input mechanism for controllers and services.

**Framework Integration**: Requests automatically include framework context (NODE_NAME, request ID), use `h()` for input validation and sanitization, integrate with authentication via session hooks, and are logged via `r()` for request auditing.

```php
final class CreateUserRequest
{
    private array $validatedData = [];
    private array $errors = [];
    private string $requestId;
    private ?array $authContext;

    public function __construct(array $input)
    {
        $this->requestId = uniqid('req_', true);

        // Extract authentication context from framework
        $this->authContext = h('request.auth.context', [
            'session' => $_SESSION ?? [],
            'headers' => getallheaders()
        ]);

        // Validate input
        $this->validate($input);

        // Log request creation
        r("Request created: " . static::class, 'Access', null, [
            'request_id' => $this->requestId,
            'node' => NODE_NAME,
            'auth_context' => $this->authContext ? ['user_id' => $this->authContext['user_id']] : null
        ]);
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function getValidated(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->validatedData;
        }
        return $this->validatedData[$key] ?? $default;
    }

    // Framework context accessors
    public function getRequestId(): string { return $this->requestId; }
    public function getAuthContext(): ?array { return $this->authContext; }
    public function getNode(): string { return NODE_NAME; }
}
```

**Key Characteristics**:

- **Framework-context**: Includes request ID, node context, auth info
- **Automatic validation**: Validates on construction via hook system
- **Immutable post-validation**: Data cannot be modified after validation
- **Audit logging**: All requests logged with context
- **Authentication integration**: Works with framework auth hooks

### Final/Data/Resource

**Purpose**: Read-only API resource transformers that convert internal data structures to external representations. Resources in Node.php handle data presentation concerns: formatting, field selection, relationship embedding, and hypermedia links. They separate internal data models from external API contracts, allowing independent evolution of each.

**Framework Integration**: Resources use `h()` for field transformation hooks, respect `env('NODE:API_VERSION')` for version-specific formatting, include hypermedia links based on route hooks, and can be cached using framework caching hooks.

```php
final class UserResource
{
    private array $data;
    private array $links = [];
    private string $resourceId;

    public function __construct(array $userData)
    {
        $this->resourceId = uniqid('res_', true);
        $this->data = $this->transformData($userData);
        $this->links = $this->generateLinks($userData['id']);
    }

    public function toArray(): array
    {
        $response = [
            'data' => $this->data,
            'links' => $this->links,
            'meta' => [
                'resource_id' => $this->resourceId,
                'transformed_at' => time(),
                'node' => NODE_NAME,
                'api_version' => env('NODE:API_VERSION', 'v1')
            ]
        ];

        // Allow post-transformation via hooks
        return h('resource.user.finalize', $response) ?? $response;
    }

    private function transformData(array $userData): array
    {
        // Base transformation
        $transformed = [
            'id' => $userData['id'],
            'type' => 'user',
            'attributes' => [
                'email' => $userData['email'],
                'username' => $userData['username'],
                'created_at' => date('c', $userData['created_at'])
            ]
        ];

        // Apply field-specific transformations via hooks
        foreach ($transformed['attributes'] as $field => $value) {
            $transformed['attributes'][$field] =
                h("resource.user.transform.{$field}", $value) ?? $value;
        }

        return $transformed;
    }
}
```

**Key Characteristics**:

- **Read-only**: Resources are immutable presentation objects
- **Hook-transformable**: Each field can be transformed via hooks
- **Hypermedia-aware**: Includes API links based on route definitions
- **Version-aware**: Respects API version from environment
- **Framework-metadata**: Includes node context, transformation timestamps

## Data Pattern Relationships

```
HTTP Request
    ↓
Request (validates + adds context)
    ↓
Form (field-level validation)
    ↓
DTO (transport between layers)
    ↓
[Business Logic Processing]
    ↓
Resource (presentation for response)
```

## Complementary Patterns in Node.php

**Validation Hook Pattern**: All data classes use `h('validation.*')` for extensible validation rules.

**Configuration Pattern**: Validation rules and transformations loaded via `f()` from files.

**Audit Pattern**: All data transformations and validations logged via `r()`.

**Service Pattern**: Resources often work with service hooks for relationship loading.

**Cache Pattern**: Resources can integrate with caching hooks for performance.

## Framework Integration Summary

Node.php's data patterns provide a comprehensive approach to data integrity:

1. **Validation Hierarchy**: Environment → File → Hook based validation rules
2. **Immutability**: Data cannot be corrupted after validation/construction
3. **Framework Context**: All data includes NODE_NAME, timestamps, request IDs
4. **Hook Extensibility**: Every transformation point extensible via `h()`
5. **Audit Trail**: All data flow logged via `r()` for debugging and compliance
6. **Separation of Concerns**: Clear boundaries between transport, validation, presentation
7. **Environment Awareness**: Data handling varies based on `env()` settings

These patterns ensure that data moving through a Node.php application maintains integrity, carries necessary context, and can be extended or observed at every step through the framework's hook system.
