# Final/Infrastructure Class Patterns

## Overview: Technical Foundation Implementation

Final infrastructure classes in Node.php provide concrete, non-extendable implementations for cross-cutting technical concerns and external system integrations. These patterns handle the "how" of system operation—communication, data mapping, error handling, and service provisioning—while maintaining tight integration with the framework's logging (`r()`), phase (`p()`), and hook (`h()`) systems. Infrastructure patterns are the technical enablers that support domain operations without polluting business logic with technical details.

### Infrastructure Design Principles in Node.php

Infrastructure components in Node.php adhere to specific design principles:

- **Framework Context Propagation**: All operations include NODE_NAME and execution context
- **Structured Logging Integration**: Comprehensive audit trails via `r()` with appropriate log types
- **Phase-Aware Execution**: Respect `p()` phase boundaries for stateful operations
- **Hook-Driven Extensibility**: Configuration and behavior customizable via `h()`
- **File-Based Configuration**: Settings loaded via `f()` from external files
- **Error Resilience**: Built-in retry with appropriate error logging

These patterns bridge Node.php's internal architecture with external systems while maintaining framework consistency.

## Final Infrastructure Class Details

### Final/Infrastructure/Client

**Purpose**: Concrete outbound HTTP/API integration with framework context propagation, automatic retry logic, and structured logging. Clients handle communication with external services while maintaining Node.php's execution context through headers and correlation IDs.

**Framework Integration**: Clients propagate NODE_NAME and request IDs via headers, log all requests/responses via `r()` with appropriate types (Infrastructure for normal operations, Error for failures), and can integrate with `p()` phases for request lifecycle management. They use `h()` hooks for request/response transformation.

```php
final class HttpClient
{
    private string $clientId;

    public function request(string $method, string $url, array $options = []): array
    {
        $requestId = uniqid('req_', true);
        $startTime = microtime(true);

        // Add framework context to headers
        $headers = array_merge(
            $options['headers'] ?? [],
            [
                'X-Node-Name' => NODE_NAME,
                'X-Request-ID' => $requestId,
                'X-Client-ID' => $this->clientId
            ]
        );

        // Pre-request transformation via hooks
        $transformed = h('http.client.request.transform', [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'request_id' => $requestId
        ]);

        try {
            // Execute request with retry logic
            $response = $this->executeWithRetry(
                $transformed['method'] ?? $method,
                $transformed['url'] ?? $url,
                array_merge($options, ['headers' => $transformed['headers'] ?? $headers])
            );

            $duration = microtime(true) - $startTime;

            // Log successful request
            return r("HTTP request completed", 'Infrastructure', $response, [
                'request_id' => $requestId,
                'method' => $method,
                'url' => $url,
                'status' => $response['status'],
                'duration' => $duration,
                'client_id' => $this->clientId
            ]);

        } catch (Throwable $e) {
            // Log failed request
            return r("HTTP request failed", 'Error', null, [
                'request_id' => $requestId,
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
                'client_id' => $this->clientId,
                'attempts' => $this->retryCount
            ]);
        }
    }
}
```

**Key Characteristics**:

- **Context Propagation**: Includes NODE_NAME and request IDs in external calls
- **Structured Logging**: All requests logged via `r()` with appropriate types
- **Retry Logic**: Built-in retry with exponential backoff
- **Hook Transformation**: Request/response processing via `h()` hooks
- **Error Resilience**: Graceful degradation with detailed error logging

### Final/Infrastructure/Console

**Purpose**: CLI console implementation that integrates with Node.php's phase system for command execution. The console provides structured command handling with automatic help generation, argument parsing, and execution within appropriate `p()` phases.

**Framework Integration**: Console commands execute within specific phases (`p('execute', ...)`), use `r()` for command execution logging (Internal type for normal execution, Error for failures), and can integrate with `h()` for command lifecycle hooks.

```php
final class Console
{
    public function executeCommand(string $command, array $args = []): int
    {
        $commandId = uniqid('cmd_', true);

        // Log command start
        r("Console command starting", 'Internal', null, [
            'command_id' => $commandId,
            'command' => $command,
            'args' => $args,
            'node' => NODE_NAME
        ]);

        try {
            // Execute within appropriate phase
            $result = p('execute', function ($phase, $state) use ($command, $args, $commandId) {
                $state['command_execution'] = [
                    'command_id' => $commandId,
                    'phase' => $phase,
                    'start_time' => microtime(true)
                ];

                // Find and execute command handler via hooks
                $handler = h("console.command.{$command}", $args);
                if (!$handler) {
                    throw new CommandNotFoundException("Command not found: {$command}");
                }

                $executionResult = $handler($args);

                $state['command_execution']['result'] = $executionResult;
                $state['command_execution']['success'] = true;
                return $state;
            });

            $exitCode = $result['command_execution']['result'] ?? 0;

            // Log successful completion
            return r("Console command completed", 'Internal', $exitCode, [
                'command_id' => $commandId,
                'command' => $command,
                'exit_code' => $exitCode,
                'duration' => microtime(true) -
                    ($result['command_execution']['start_time'] ?? microtime(true))
            ]);

        } catch (Throwable $e) {
            // Log command failure
            return r("Console command failed", 'Error', 1, [
                'command_id' => $commandId,
                'command' => $command,
                'error' => $e->getMessage(),
                'exception' => get_class($e)
            ]);
        }
    }
}
```

**Key Characteristics**:

- **Phase-Aware Execution**: Commands run within `p()` phase system
- **Structured Logging**: Command lifecycle logged via `r()`
- **Hook-Based Command Discovery**: Commands registered via `h()` system
- **Error Handling**: Exit codes and structured error logging
- **Help Generation**: Automatic help for registered commands

### Final/Infrastructure/Exception

**Purpose**: Base application exception with framework context integration. These exceptions include Node.php execution context (phase, node name, request ID) automatically and integrate with `r()` for structured error logging.

**Framework Integration**: Exceptions capture current framework context on instantiation and can be logged via `r()` with Exception type. They integrate with the phase system to include current phase in error context.

```php
final class InfrastructureException extends Exception
{
    private array $context;

    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        // Capture framework context at exception creation
        $this->context = [
            'node' => NODE_NAME,
            'phase' => p(':name'),
            'timestamp' => time(),
            'request_id' => $_SERVER['HTTP_X_REQUEST_ID'] ?? uniqid('req_', true)
        ];

        parent::__construct($message, $code, $previous);

        // Auto-log exception creation
        r($message, 'Exception', null, array_merge($this->context, [
            'exception_class' => static::class,
            'code' => $code
        ]));
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function __toString(): string
    {
        return json_encode([
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'trace' => $this->getTrace()
        ], JSON_PRETTY_PRINT);
    }
}
```

**Key Characteristics**:

- **Context-Aware**: Includes framework execution context
- **Auto-Logging**: Automatically logged via `r()` on creation
- **Structured Output**: JSON representation for machine processing
- **Phase Integration**: Captures current phase in error context
- **Request Tracing**: Includes request ID for distributed tracing

### Final/Infrastructure/Gateway

**Purpose**: Concrete external system boundary with circuit breaking, rate limiting, and framework context propagation. Gateways manage communication with external services (payment processors, messaging systems, etc.) while maintaining Node.php's operational patterns.

**Framework Integration**: Gateways use `h()` for request/response transformation, `r()` for operation logging with appropriate types, and can integrate with `p()` phases for transaction management. They propagate NODE_NAME and correlation IDs to external systems.

```php
final class PaymentGateway
{
    public function charge(array $paymentData): array
    {
        $transactionId = uniqid('txn_', true);

        // Log charge attempt
        r("Payment charge initiated", 'Audit', null, [
            'transaction_id' => $transactionId,
            'gateway' => static::class,
            'amount' => $paymentData['amount'],
            'node' => NODE_NAME
        ]);

        try {
            // Transform request via hooks
            $transformedData = h('gateway.payment.charge.transform', array_merge(
                $paymentData,
                ['transaction_id' => $transactionId, 'node' => NODE_NAME]
            ));

            // Execute with circuit breaker
            $result = $this->circuitBreaker->execute(function () use ($transformedData) {
                return $this->executeCharge($transformedData);
            });

            // Log successful charge
            return r("Payment charge successful", 'Audit', $result, [
                'transaction_id' => $transactionId,
                'gateway' => static::class,
                'amount' => $paymentData['amount'],
                'external_id' => $result['id'] ?? null
            ]);

        } catch (Throwable $e) {
            // Log failed charge
            return r("Payment charge failed", 'Error', null, [
                'transaction_id' => $transactionId,
                'gateway' => static::class,
                'amount' => $paymentData['amount'],
                'error' => $e->getMessage(),
                'circuit_state' => $this->circuitBreaker->getState()
            ]);
        }
    }
}
```

**Key Characteristics**:

- **Circuit Breaking**: Prevents cascade failures
- **Context Propagation**: Includes Node.php context in external calls
- **Structured Logging**: All operations logged via `r()` with Audit/Error types
- **Hook Transformation**: Request/response processing via `h()`
- **Rate Limiting**: Prevents overloading external systems

### Final/Infrastructure/Library

**Purpose**: Reusable library class with framework integration for common utilities. Libraries provide shared functionality (validation, formatting, calculations) while integrating with Node.php's logging and hook systems.

**Framework Integration**: Libraries can use `r()` for debug logging (Internal type), integrate with `h()` for behavior customization, and respect framework configuration via `env()`.

```php
final class ValidationLibrary
{
    public static function validateEmail(string $email): bool
    {
        $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

        // Debug logging for validation failures
        if (!$isValid) {
            r("Email validation failed", 'Internal', false, [
                'email' => $email,
                'validator' => static::class,
                'node' => NODE_NAME
            ]);
        }

        // Allow custom validation via hooks
        $hookResult = h('library.validation.email', $email);
        if ($hookResult !== null) {
            return $hookResult === true;
        }

        return $isValid;
    }

    public static function formatCurrency(float $amount, string $currency = null): string
    {
        $currency = $currency ?? env('NODE:DEFAULT_CURRENCY', 'USD');

        // Format using framework configuration
        $formatted = number_format($amount, 2, '.', ',');

        // Allow formatting customization via hooks
        return h('library.format.currency', [
            'amount' => $amount,
            'currency' => $currency,
            'formatted' => $formatted
        ])['formatted'] ?? "{$currency} {$formatted}";
    }
}
```

**Key Characteristics**:

- **Stateless Operations**: Pure functions or static methods
- **Debug Logging**: Optional logging via `r()` for debugging
- **Hook Extensibility**: Behavior customizable via `h()` hooks
- **Framework Configuration**: Uses `env()` for settings
- **Reusable Utilities**: Shared across application components

### Final/Infrastructure/Mapper

**Purpose**: Infrastructure data mapper that transforms between persistence format and domain/data objects (DTOs). Mappers handle the technical concern of data transformation while maintaining framework context.

**Framework Integration**: Mappers use `h()` for field-level transformation hooks, `r()` for mapping operation logging, and can integrate with `p()` phases for batch transformations.

```php
final class UserMapper
{
    public static function toPersistence(User $user): array
    {
        $mappingId = uniqid('map_', true);

        // Base mapping
        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getUsername(),
            'created_at' => $user->getCreatedAt()->getTimestamp(),
            '_mapped_at' => time(),
            '_node' => NODE_NAME,
            '_mapping_id' => $mappingId
        ];

        // Field-level transformation via hooks
        foreach ($data as $field => $value) {
            $transformed = h("mapper.user.persistence.{$field}", $value);
            if ($transformed !== null) {
                $data[$field] = $transformed;
            }
        }

        // Log mapping operation
        r("User mapped to persistence", 'Internal', $data, [
            'mapping_id' => $mappingId,
            'user_id' => $user->getId(),
            'fields_mapped' => count($data)
        ]);

        return $data;
    }

    public static function fromPersistence(array $data): User
    {
        $mappingId = uniqid('map_', true);

        // Field-level transformation via hooks
        foreach ($data as $field => $value) {
            $transformed = h("mapper.user.domain.{$field}", $value);
            if ($transformed !== null) {
                $data[$field] = $transformed;
            }
        }

        // Create user object
        $user = new User(
            $data['id'],
            $data['email'],
            $data['username'],
            new DateTimeImmutable('@' . $data['created_at'])
        );

        // Log mapping operation
        r("User mapped from persistence", 'Internal', $user->getId(), [
            'mapping_id' => $mappingId,
            'user_id' => $user->getId(),
            'source_data_keys' => array_keys($data)
        ]);

        return $user;
    }
}
```

**Key Characteristics**:

- **Bidirectional Mapping**: To/from persistence formats
- **Hook-Based Transformation**: Field-level transformation via `h()`
- **Context Preservation**: Includes framework metadata in mapped data
- **Structured Logging**: All mappings logged via `r()`
- **Type Safety**: Ensures data integrity during transformation

### Final/Infrastructure/Service

**Purpose**: Stateless application service that provides technical capabilities to other parts of the system. Infrastructure services handle concerns like caching, messaging, or file operations while integrating with Node.php's framework systems.

**Framework Integration**: Services use `h()` for configuration and behavior hooks, `r()` for operation logging with appropriate types, and can integrate with `p()` phases for transactional operations.

```php
final class CacheService
{
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->buildKey($key);

        try {
            $value = $this->driver->get($cacheKey);

            if ($value !== null) {
                // Log cache hit
                r("Cache hit", 'Internal', $value, [
                    'key' => $key,
                    'cache_key' => $cacheKey,
                    'node' => NODE_NAME
                ]);
                return $value;
            }

            // Log cache miss
            r("Cache miss", 'Internal', $default, [
                'key' => $key,
                'cache_key' => $cacheKey,
                'node' => NODE_NAME
            ]);
            return $default;

        } catch (Throwable $e) {
            // Log cache error
            return r("Cache error", 'Error', $default, [
                'key' => $key,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'node' => NODE_NAME
            ]);
        }
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $cacheKey = $this->buildKey($key);

        // Transform value via hooks
        $transformedValue = h('cache.service.set.transform', [
            'key' => $key,
            'value' => $value,
            'cache_key' => $cacheKey
        ])['value'] ?? $value;

        try {
            $result = $this->driver->set($cacheKey, $transformedValue, $ttl);

            // Log cache set
            r("Cache set", 'Internal', $result, [
                'key' => $key,
                'cache_key' => $cacheKey,
                'ttl' => $ttl,
                'value_type' => gettype($value),
                'node' => NODE_NAME
            ]);

            return $result;

        } catch (Throwable $e) {
            // Log cache set error
            return r("Cache set failed", 'Error', false, [
                'key' => $key,
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'node' => NODE_NAME
            ]);
        }
    }

    private function buildKey(string $key): string
    {
        // Include node context in cache keys for multi-node environments
        return NODE_NAME . ':' . $key;
    }
}
```

**Key Characteristics**:

- **Stateless Operations**: No internal state between calls
- **Comprehensive Logging**: All operations logged via `r()` with appropriate types
- **Hook Integration**: Behavior customizable via `h()` hooks
- **Error Resilience**: Graceful degradation with error logging
- **Framework Context**: Includes NODE_NAME in operations and cache keys

## Infrastructure Pattern Relationships

```
External Systems
    ↓
Client/Gateway (communication, circuit breaking)
    ↓
Mapper (data transformation)
    ↓
Library/Service (business logic support)
    ↓
Domain Layer
```

## Complementary Patterns in Node.php

**Hook Pattern (`h()`)**: Infrastructure components use hooks for extensibility and transformation.

**Logging Pattern (`r()`)**: All infrastructure operations logged with appropriate types.

**Phase Pattern (`p()`)**: Stateful operations respect phase boundaries.

**Configuration Pattern**: Settings loaded via `env()` and `f()`.

**Exception Pattern**: Framework-aware exceptions for error handling.

## Framework Integration Summary

Node.php's infrastructure patterns provide technical capabilities while maintaining framework consistency:

1. **Context Propagation**: NODE_NAME and request IDs included in all external communications
2. **Structured Logging**: All operations logged via `r()` with appropriate log types
3. **Phase Awareness**: Stateful operations respect `p()` phase boundaries
4. **Hook Extensibility**: Behavior customizable via `h()` system
5. **Error Resilience**: Built-in retry and circuit breaking with proper error logging
6. **Configuration Integration**: Settings from `env()` and `f()` files
7. **Stateless Design**: Services designed for reuse across requests

This integration ensures that infrastructure components in Node.php are not just technical utilities but are fully aware of and integrated with the framework's execution model, logging system, and configuration approach.
