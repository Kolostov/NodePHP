# Interface/Infrastructure Patterns

## Overview: Infrastructure Service Contracts

Infrastructure interfaces in Node.php define contracts for cross-cutting technical concerns that integrate with the framework's phase system, hook architecture, and core utilities. These interfaces abstract platform-specific implementations while leveraging `env()` configuration, `h()` hooks, and `r()` logging for consistent infrastructure service management.

### Infrastructure Interface Integration with Node.php Architecture

| **Interface Type**  | **Primary Phase**        | **Hook Pattern**          | **Framework Utilities**          | **Node Structure Path**                              |
| ------------------- | ------------------------ | ------------------------- | -------------------------------- | ---------------------------------------------------- |
| **Authenticator**   | `boot`, `resolve`        | `h('auth.*', $data)`      | `env()`, `r()`, session state    | `Primitive/Interface/Infrastructure/Authenticator`   |
| **Authorizer**      | `resolve`, `execute`     | `h('authz.*', $context)`  | Phase state in `p()`             | `Primitive/Interface/Infrastructure/Authorizer`      |
| **Bus**             | `execute`, `mutate`      | `h('bus.*', $message)`    | Phase-based message routing      | `Primitive/Interface/Infrastructure/Bus`             |
| **Cache**           | `transpilate`, `resolve` | `h('cache.*', $key)`      | `f()` file operations            | `Primitive/Interface/Infrastructure/Cache`           |
| **Client**          | `execute`, `persist`     | `h('client.*', $request)` | External call logging with `r()` | `Primitive/Interface/Infrastructure/Client`          |
| **EventDispatcher** | `mutate`, `persist`      | `h('event.*', $event)`    | Hook-based event system          | `Primitive/Interface/Infrastructure/EventDispatcher` |
| **Gate**            | `resolve`, `execute`     | `h('gate.*', $decision)`  | Permission validation            | `Primitive/Interface/Infrastructure/Gate`            |
| **Gateway**         | `transpilate`, `execute` | `h('gateway.*', $call)`   | `env()`-based configuration      | `Primitive/Interface/Infrastructure/Gateway`         |
| **Logger**          | All phases               | `h('log.*', $entry)`      | Integrated with `r()` function   | `Primitive/Interface/Infrastructure/Logger`          |
| **Queueable**       | `execute`, `finalize`    | `h('queue.*', $job)`      | Async phase processing           | `Primitive/Interface/Infrastructure/Queueable`       |

## Interface Details Aligned with Node.php Framework

### Interface/Infrastructure/Authenticator

**Framework Context**: Integrates with session management, `env()` credentials, and uses `h()` for authentication lifecycle events.

```php
// Node.php Structure: Primitive/Interface/Infrastructure/Authenticator.php
namespace Primitive\Interface\Infrastructure;

interface Authenticator
{
    /**
     * Authenticate with credentials, using h('auth.attempt') for processing
     * Returns authenticated identity or null
     */
    public function authenticate(array $credentials): ?\stdClass;

    /**
     * Validate existing token with h('auth.validate') hook
     */
    public function validate(string $token): bool;

    /**
     * Logout with h('auth.logout') hook for cleanup
     */
    public function logout(\stdClass $identity): void;
}

// Concrete implementation integrated with Node.php
class NodeAuthenticator implements Primitive\Interface\Infrastructure\Authenticator
{
    public function authenticate(array $credentials): ?\stdClass {
        // Run pre-authentication hook - hooks can modify credentials
        $credentials = h('auth.pre_authenticate', $credentials) ?? $credentials;

        // Use env() for configuration
        $authMethod = env('NODE:AUTH_METHOD', 'session');
        $expectedUser = env('NODE:AUTH_USER', 'admin');
        $expectedPass = env('NODE:AUTH_PASS', 'password');

        $authenticated = false;

        switch ($authMethod) {
            case 'session':
                $authenticated = $this->sessionAuthenticate($credentials, $expectedUser, $expectedPass);
                break;
            case 'basic':
                $authenticated = $this->basicAuthenticate($credentials, $expectedUser, $expectedPass);
                break;
        }

        if ($authenticated) {
            $identity = (object) [
                'id' => 1,
                'username' => $credentials['username'] ?? 'unknown',
                'roles' => ['user'],
                'authenticated_at' => time()
            ];

            // Run post-authentication hook
            $identity = h('auth.authenticated', $identity) ?? $identity;

            // Log authentication
            r("User authenticated: {$identity->username}", "Access", $identity);

            return $identity;
        }

        // Run failed authentication hook
        h('auth.failed', $credentials);

        r("Authentication failed for: {$credentials['username'] ?? 'unknown'}", "Audit", false);
        return null;
    }

    public function validate(string $token): bool {
        // Run validation through hooks
        $isValid = h('auth.validate', $token);

        if ($isValid === null) {
            // No hooks registered, use default validation
            $isValid = !empty($token) && strlen($token) > 10;
        }

        return (bool)$isValid;
    }

    public function logout(\stdClass $identity): void {
        // Run pre-logout hook
        h('auth.pre_logout', $identity);

        // Clear session if active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Run post-logout hook
        h('auth.post_logout', $identity);

        r("User logged out: {$identity->username}", "Access", true);
    }

    private function sessionAuthenticate(array $credentials, string $expectedUser, string $expectedPass): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $valid = ($credentials['username'] ?? '') === $expectedUser &&
                 ($credentials['password'] ?? '') === $expectedPass;

        if ($valid) {
            $_SESSION['authenticated'] = true;
            $_SESSION['user'] = [
                'username' => $credentials['username'],
                'last_login' => time()
            ];
        }

        return $valid;
    }
}

// Usage in boot phase
p('boot', function($phase, $state) {
    // Register authentication hooks
    h('auth.pre_authenticate', function($credentials) {
        // Add additional validation
        if (empty($credentials['username']) || empty($credentials['password'])) {
            throw new \RuntimeException("Missing credentials");
        }
        return $credentials;
    });

    h('auth.authenticated', function($identity) {
        // Add default role if none set
        if (empty($identity->roles)) {
            $identity->roles = ['guest'];
        }
        return $identity;
    });

    // Create authenticator
    $auth = new Primitive\Class\Final\Infrastructure\Authenticator();

    // Auto-authenticate if in CLI mode
    if (PHP_SAPI === 'cli' && env('NODE:CLI_AUTH', false)) {
        $state['cli_user'] = $auth->authenticate([
            'username' => 'cli',
            'password' => 'cli_' . NODE_NAME
        ]);
    }

    return $state;
});
```

### Interface/Infrastructure/Cache

**Framework Context**: Uses `f()` for file operations, `env()` for configuration, and `h()` for cache interception.

```php
namespace Primitive\Interface\Infrastructure;

interface Cache
{
    /**
     * Get cached value with h('cache.get') hook support
     * Returns cached value or default
     */
    public function get(string $key, $default = null);

    /**
     * Set value with h('cache.set') hook for interception
     */
    public function set(string $key, $value, ?int $ttl = null): bool;

    /**
     * Delete with h('cache.delete') hook
     */
    public function delete(string $key): bool;

    /**
     * Clear all cache, triggers h('cache.clear') hook
     */
    public function clear(): bool;
}

class FileCache implements Primitive\Interface\Infrastructure\Cache
{
    private $cacheDir;

    public function __construct() {
        $this->cacheDir = LOG_PATH . 'Cache' . D;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function get(string $key, $default = null) {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';

        // Allow hooks to intercept cache retrieval
        $intercepted = h('cache.get', [
            'key' => $key,
            'file' => $cacheFile,
            'default' => $default
        ]);

        // If hook returns something other than the original array, use it
        if ($intercepted !== null && is_array($intercepted) && isset($intercepted['value'])) {
            return $intercepted['value'];
        }

        if (!file_exists($cacheFile)) {
            return $default;
        }

        $content = f($cacheFile, 'read');
        if ($content === false) {
            return $default;
        }

        $data = unserialize($content);
        if (!is_array($data) || !isset($data['value'], $data['expires'])) {
            f($cacheFile, 'delete', null, false);
            return $default;
        }

        if ($data['expires'] < time()) {
            // Expired - run expiration hook
            h('cache.expired', [
                'key' => $key,
                'data' => $data
            ]);

            f($cacheFile, 'delete', null, false);
            return $default;
        }

        // Allow hooks to modify retrieved value
        $value = h('cache.retrieved', $data['value']) ?? $data['value'];

        r("Cache hit: {$key}", "Internal", $value, [
            'ttl_remaining' => $data['expires'] - time()
        ]);

        return $value;
    }

    public function set(string $key, $value, ?int $ttl = null): bool {
        $ttl = $ttl ?? env('NODE:CACHE_TTL', 3600);
        $cacheFile = $this->cacheDir . md5($key) . '.cache';

        // Allow hooks to modify value before caching
        $value = h('cache.before_set', $value) ?? $value;

        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
            'created' => time(),
            'key' => $key
        ];

        // Hook can prevent caching by returning false
        $shouldCache = h('cache.should_set', true);
        if ($shouldCache === false) {
            r("Cache set prevented by hook: {$key}", "Internal", false);
            return false;
        }

        $result = f($cacheFile, 'write', serialize($data));

        if ($result !== false) {
            // Run post-set hook
            h('cache.after_set', [
                'key' => $key,
                'file' => $cacheFile,
                'data' => $data
            ]);

            r("Cache set: {$key}", "Internal", true, [
                'ttl' => $ttl,
                'size' => strlen(serialize($data))
            ]);
        }

        return $result !== false;
    }

    public function delete(string $key): bool {
        $cacheFile = $this->cacheDir . md5($key) . '.cache';

        // Pre-delete hook
        h('cache.pre_delete', [
            'key' => $key,
            'file' => $cacheFile
        ]);

        $result = f($cacheFile, 'delete', null, false);

        if ($result) {
            // Post-delete hook
            h('cache.post_delete', $key);
            r("Cache deleted: {$key}", "Internal", true);
        }

        return $result;
    }

    public function clear(): bool {
        // Pre-clear hook
        h('cache.pre_clear');

        $files = glob($this->cacheDir . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            if (f($file, 'delete', null, false)) {
                $deleted++;
            }
        }

        // Post-clear hook
        h('cache.post_clear', $deleted);

        r("Cache cleared: {$deleted} files", "Internal", true);

        return $deleted > 0;
    }
}

// Cache usage in phases
p('resolve', function($phase, $state) {
    $cache = new FileCache();

    // Register cache hooks
    h('cache.before_set', function($value) use ($phase) {
        // Add phase context to cached data
        if (is_array($value)) {
            $value['_cached_in_phase'] = $phase;
            $value['_cached_at'] = time();
        }
        return $value;
    });

    h('cache.should_set', function($should) use ($state) {
        // Don't cache if we're in rollback mode
        if (isset($state['_rollback']) && $state['_rollback'] === true) {
            return false;
        }
        return $should;
    });

    // Cache resolved data
    $cacheKey = 'resolved_' . md5(serialize($state));
    $cached = $cache->get($cacheKey);

    if ($cached === null) {
        // Process and cache
        $result = processData($state);
        $cache->set($cacheKey, $result, 300); // 5 minutes
        return ['resolved_data' => $result];
    }

    return ['resolved_data' => $cached, '_from_cache' => true];
});
```

### Interface/Infrastructure/EventDispatcher

**Framework Context**: Tightly integrated with `h()` system where events are hooks with structured data.

```php
namespace Primitive\Interface\Infrastructure;

interface EventDispatcher
{
    /**
     * Dispatch event object through h() system
     * Returns possibly modified event
     */
    public function dispatch(object $event): object;

    /**
     * Add listener by registering to hook
     */
    public function addListener(string $eventClass, callable $listener): void;

    /**
     * Remove listener (requires tracking)
     */
    public function removeListener(string $eventClass, callable $listener): void;
}

class HookEventDispatcher implements Primitive\Interface\Infrastructure\EventDispatcher
{
    private $listeners = [];

    public function dispatch(object $event): object {
        $eventClass = get_class($event);

        // Log event dispatch
        r("Event dispatched: {$eventClass}", "Internal", $event);

        // Dispatch through h() - each listener can modify the event
        $result = h($eventClass, $event) ?? $event;

        // Also run generic event hook
        h('event.dispatched', [
            'event_class' => $eventClass,
            'event' => $event,
            'result' => $result
        ]);

        // Run phase-specific event hooks
        $currentPhase = $GLOBALS['NODE_PHASE'] ?? 'unknown';
        h("event.phase.{$currentPhase}", $event);

        return $result;
    }

    public function addListener(string $eventClass, callable $listener): void {
        // Track listener for removal capability
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $listenerId = spl_object_hash($listener);
        $this->listeners[$eventClass][$listenerId] = $listener;

        // Register with h()
        h($eventClass, function($event) use ($listener) {
            try {
                $result = $listener($event);
                // If listener returns something, use it as modified event
                return $result ?? $event;
            } catch (\Throwable $e) {
                r("Event listener failed: " . $e->getMessage(), "Error", null, [
                    'event' => get_class($event),
                    'exception' => $e
                ]);
                // Return original event to continue chain
                return $event;
            }
        });
    }

    public function removeListener(string $eventClass, callable $listener): void {
        $listenerId = spl_object_hash($listener);

        if (isset($this->listeners[$eventClass][$listenerId])) {
            unset($this->listeners[$eventClass][$listenerId]);

            // Note: h() doesn't support removal, so we'd need to track
            // This is a limitation of the current h() implementation
            r("Listener removed (but still registered in h()): {$eventClass}", "Internal", true);
        }
    }
}

// Event definition examples
class PhaseStartedEvent
{
    public string $phase;
    public array $state;
    public int $timestamp;

    public function __construct(string $phase, array $state) {
        $this->phase = $phase;
        $this->state = $state;
        $this->timestamp = time();
    }
}

class PhaseCompletedEvent
{
    public string $phase;
    public array $resultState;
    public float $duration;

    public function __construct(string $phase, array $resultState, float $duration) {
        $this->phase = $phase;
        $this->resultState = $resultState;
        $this->duration = $duration;
    }
}

// Usage with phase system
p('execute', function($phase, $state) {
    $dispatcher = new HookEventDispatcher();

    // Dispatch phase start event
    $startEvent = new PhaseStartedEvent($phase, $state);
    $dispatcher->dispatch($startEvent);

    // Register event listeners via hooks
    h(PhaseStartedEvent::class, function($event) {
        // Log phase start
        r("Phase started: {$event->phase}", "Internal", null, [
            'state_keys' => array_keys($event->state)
        ]);

        // Can modify event if needed
        $event->timestamp = microtime(true);
        return $event;
    });

    h(PhaseCompletedEvent::class, function($event) {
        // Log phase completion
        r("Phase completed: {$event->phase} in {$event->duration}s", "Internal", null, [
            'result_state_keys' => array_keys($event->resultState)
        ]);

        // Run cleanup hook
        h("phase.{$event->phase}.cleanup");

        return $event;
    });

    // Process phase...
    $result = processPhase($state);

    // Dispatch completion event
    $duration = microtime(true) - ($startEvent->timestamp);
    $completeEvent = new PhaseCompletedEvent($phase, $result, $duration);
    $dispatcher->dispatch($completeEvent);

    return $result;
});
```

### Interface/Infrastructure/Gateway

**Framework Context**: External system integration with `env()` configuration and `h()`-based error handling and transformation.

```php
namespace Primitive\Interface\Infrastructure;

interface Gateway
{
    /**
     * Call external operation with h() hooks for preprocessing
     */
    public function call(string $operation, array $params = []);

    /**
     * Check if operation is supported via configuration
     */
    public function supports(string $operation): bool;
}

class ConfigurableGateway implements Primitive\Interface\Infrastructure\Gateway
{
    public function call(string $operation, array $params = []) {
        // Check if operation is supported
        if (!$this->supports($operation)) {
            throw new \RuntimeException("Gateway operation not supported: {$operation}");
        }

        // Pre-call hook can modify or abort
        $preCallResult = h("gateway.{$operation}.pre_call", $params);

        // If hook returns false, abort
        if ($preCallResult === false) {
            r("Gateway call aborted by hook: {$operation}", "Internal", null, $params);
            return null;
        }

        // If hook returns modified params, use them
        if (is_array($preCallResult)) {
            $params = $preCallResult;
        }

        // Get configuration
        $baseUrl = env("NODE:GATEWAY_{$operation}_URL");
        $timeout = env("NODE:GATEWAY_{$operation}_TIMEOUT", 30);

        if (!$baseUrl) {
            throw new \RuntimeException("Gateway URL not configured for: {$operation}");
        }

        try {
            // Make the call (simplified)
            $result = $this->makeRequest($baseUrl, $params, $timeout);

            // Post-call hook for processing result
            $result = h("gateway.{$operation}.post_call", $result) ?? $result;

            r("Gateway call successful: {$operation}", "Internal", $result, [
                'url' => $baseUrl,
                'params_count' => count($params)
            ]);

            return $result;

        } catch (\Throwable $e) {
            // Error hook for handling failures
            $handled = h("gateway.{$operation}.error", [
                'error' => $e->getMessage(),
                'operation' => $operation,
                'params' => $params
            ]);

            if ($handled === null) {
                // No hook handled it, re-throw
                r("Gateway call failed: {$operation} - " . $e->getMessage(), "Error", null);
                throw $e;
            }

            // Hook returned a fallback value
            return $handled;
        }
    }

    public function supports(string $operation): bool {
        // Check environment configuration
        $url = env("NODE:GATEWAY_{$operation}_URL");

        // Also check with hook
        $hookSupport = h("gateway.supports", [
            'operation' => $operation,
            'has_url' => !empty($url)
        ]);

        return ($hookSupport['has_url'] ?? !empty($url)) && env("NODE:GATEWAY_{$operation}_ENABLED", true);
    }

    private function makeRequest(string $url, array $params, int $timeout) {
        // Simplified HTTP request
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => json_encode($params),
                'timeout' => $timeout
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            throw new \RuntimeException("Gateway request failed to: {$url}");
        }

        return json_decode($response, true) ?? $response;
    }
}

// Gateway usage with hook configuration
p('transpilate', function($phase, $state) {
    $gateway = new ConfigurableGateway();

    // Register transformation hooks
    h('gateway.transform_data.pre_call', function($params) use ($phase) {
        // Add phase context to all gateway calls
        $params['_context'] = [
            'phase' => $phase,
            'node' => NODE_NAME,
            'timestamp' => time()
        ];
        return $params;
    });

    h('gateway.transform_data.post_call', function($result) {
        // Validate response structure
        if (!is_array($result) || !isset($result['status'])) {
            // Add default status
            $result = ['status' => 'processed', 'data' => $result];
        }
        return $result;
    });

    h('gateway.transform_data.error', function($errorInfo) {
        // Provide fallback when gateway fails
        r("Gateway failed, using fallback: " . $errorInfo['error'], "Error", null);
        return ['status' => 'fallback', 'data' => $errorInfo['params']];
    });

    if ($gateway->supports('transform_data')) {
        $transformed = $gateway->call('transform_data', $state);
        return array_merge($state, $transformed);
    }

    return $state;
});
```

### Interface/Infrastructure/Logger

**Framework Context**: Integrated with `r()` function and provides hook points for log processing.

```php
namespace Primitive\Interface\Infrastructure;

interface Logger
{
    /**
     * Log message with level and context
     * Uses h('log.message') hook for processing
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * Convenience methods that delegate to log()
     */
    public function emergency(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
}

class HookableLogger implements Primitive\Interface\Infrastructure\Logger
{
    public function log(string $level, string $message, array $context = []): void {
        // Prepare log entry
        $entry = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime(true),
            'phase' => $GLOBALS['NODE_PHASE'] ?? 'unknown',
            'memory' => memory_get_usage()
        ];

        // Pre-log hook can modify or prevent logging
        $processedEntry = h('log.before', $entry);

        // If hook returns false, skip logging
        if ($processedEntry === false) {
            return;
        }

        // Use processed entry if provided
        if (is_array($processedEntry)) {
            $entry = $processedEntry;
        }

        // Use framework's r() function for actual logging
        r(
            $entry['message'],
            $entry['level'],
            null,
            array_diff_key($entry, ['message' => 1, 'level' => 1])
        );

        // Post-log hook for notification
        h('log.after', $entry);

        // Level-specific hook
        h("log.level.{$level}", $entry);
    }

    public function emergency(string $message, array $context = []): void {
        $this->log('Emergency', $message, $context);
        // Trigger emergency hook for notifications
        h('log.emergency.triggered', ['message' => $message, 'context' => $context]);
    }

    public function error(string $message, array $context = []): void {
        $this->log('Error', $message, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->log('Internal', $message, $context); // Using 'Internal' to match r() types
    }
}

// Logger configuration in boot phase
p('boot', function($phase, $state) {
    $logger = new HookableLogger();

    // Register log processing hooks
    h('log.before', function($entry) {
        // Add node name to all logs
        $entry['node'] = NODE_NAME;

        // Filter sensitive data from context
        if (isset($entry['context']['password'])) {
            $entry['context']['password'] = '***REDACTED***';
        }

        // Don't log debug messages in production
        if (env('NODE:ENVIRONMENT', 'development') === 'production' &&
            $entry['level'] === 'Debug') {
            return false; // Skip logging
        }

        return $entry;
    });

    h('log.after', function($entry) {
        // Send critical errors to external service
        if (in_array($entry['level'], ['Emergency', 'Error'])) {
            // Could integrate with gateway here
            h('log.critical.notify', $entry);
        }

        // Keep track of error count
        if (!isset($GLOBALS['error_count'])) {
            $GLOBALS['error_count'] = 0;
        }
        if ($entry['level'] === 'Error') {
            $GLOBALS['error_count']++;
        }
    });

    // Register the logger in state
    return ['logger' => $logger];
});
```

## Framework Integration Patterns

### Infrastructure Service Registration Pattern

```php
// In boot phase, register all infrastructure services
p('boot', function($phase, $state) {
    $services = [];

    // Initialize based on environment configuration
    $services['cache'] = env('NODE:ENABLE_CACHE', true)
        ? new Primitive\Class\Final\Infrastructure\Cache()
        : null;

    $services['events'] = env('NODE:ENABLE_EVENTS', true)
        ? new HookEventDispatcher()
        : null;

    $services['gateway'] = env('NODE:ENABLE_GATEWAY', false)
        ? new ConfigurableGateway()
        : null;

    // Run service ready hooks
    foreach ($services as $name => $service) {
        if ($service !== null) {
            h("service.{$name}.ready", $service);
        }
    }

    // Log service initialization
    r("Infrastructure services initialized", "Internal", null, [
        'services' => array_keys(array_filter($services))
    ]);

    return array_merge($state, ['infrastructure' => $services]);
});
```

### Phase-Aware Infrastructure Pattern

```php
// Infrastructure services that are phase-aware
class PhaseAwareCache extends FileCache
{
    public function get(string $key, $default = null) {
        $phase = $GLOBALS['NODE_PHASE'] ?? 'unknown';

        // Run phase-specific cache hook
        $phaseResult = h("cache.phase.{$phase}.get", [
            'key' => $key,
            'default' => $default
        ]);

        if ($phaseResult !== null && $phaseResult !== ['key' => $key, 'default' => $default]) {
            return $phaseResult['value'] ?? $default;
        }

        // Add phase context to cache key
        $phaseKey = "{$phase}_{$key}";

        return parent::get($phaseKey, $default);
    }

    public function set(string $key, $value, ?int $ttl = null): bool {
        $phase = $GLOBALS['NODE_PHASE'] ?? 'unknown';

        // Run phase-specific set hook
        $shouldSet = h("cache.phase.{$phase}.should_set", true);
        if ($shouldSet === false) {
            return false;
        }

        // Adjust TTL based on phase
        $phaseTtl = h("cache.phase.{$phase}.ttl", $ttl) ?? $ttl;

        // Use phase-specific key
        $phaseKey = "{$phase}_{$key}";

        return parent::set($phaseKey, $value, $phaseTtl);
    }
}

// Usage in phase-specific context
p('execute', function($phase, $state) {
    $cache = new PhaseAwareCache();

    // Register phase-specific cache rules
    h("cache.phase.execute.should_set", function() use ($state) {
        // Only cache if we have enough memory
        return memory_get_usage() < (env('NODE:CACHE_MEMORY_LIMIT', 134217728)); // 128MB
    });

    h("cache.phase.execute.ttl", function($defaultTtl) {
        // Shorter TTL for execute phase
        return min($defaultTtl ?? 3600, 60); // Max 60 seconds
    });

    // Cache usage
    $result = $cache->get('processed_data');

    if ($result === null) {
        $result = processHeavyOperation($state);
        $cache->set('processed_data', $result, 300);
    }

    return ['executed_data' => $result];
});
```

### Hook-Chained Infrastructure Pattern

```php
// Infrastructure that chains multiple hooks
class HookChainedClient implements Primitive\Interface\Infrastructure\Client
{
    public function request(string $method, string $url, array $options = []) {
        // Chain of hooks for request processing
        $request = ['method' => $method, 'url' => $url, 'options' => $options];

        // 1. Authentication hook
        $request = h('client.request.auth', $request) ?? $request;

        // 2. Headers hook
        $request = h('client.request.headers', $request) ?? $request;

        // 3. Validation hook
        $valid = h('client.request.validate', true);
        if ($valid === false) {
            throw new \RuntimeException("Request validation failed");
        }

        // 4. Pre-flight hook
        h('client.request.pre_flight', $request);

        // Make actual request
        $response = $this->makeHttpRequest($request);

        // 5. Response processing hook
        $response = h('client.request.process_response', $response) ?? $response;

        // 6. Error handling hook (if needed)
        if (isset($response['error'])) {
            $response = h('client.request.handle_error', $response) ?? $response;
        }

        // 7. Logging hook
        h('client.request.log', [
            'request' => $request,
            'response' => $response,
            'timestamp' => time()
        ]);

        return $response;
    }

    private function makeHttpRequest(array $request) {
        // Actual HTTP implementation
        // ...
    }
}
```

This documentation demonstrates how infrastructure interfaces integrate with Node.php's core systems (`h()`, `r()`, `env()`, `p()`, `f()`) while maintaining the framework's architectural patterns and phase-based execution model.
