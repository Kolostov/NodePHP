# Final/Coordination Class Patterns

## Overview: Centralized Flow Control Architecture

Final coordination classes constitute the central nervous system of Node.php applications, providing immutable, non-extendable orchestrators that manage the flow of execution, events, and data between components. Unlike behavioral patterns that focus on object interactions or creational patterns that handle instantiation, coordination patterns specifically address how different parts of the system communicate and synchronize. These classes leverage Node.php's core utilities (`h()`, `p()`, `f()`, `r()`, `env()`) to create a predictable, hook-driven coordination layer that maintains the framework's philosophical emphasis on file-based configuration and phase-based execution.

### Philosophical Foundation

In Node.php, coordination is fundamentally decentralized yet predictable. The framework favors hook-based communication (`h()`) over direct method calls, file-based configuration (`f()`) over runtime registration, and phase-based execution (`p()`) over ad-hoc lifecycle management. Final coordination classes embody these principles by providing concrete implementations that cannot be modified through inheritance, ensuring consistent behavior across the application while still allowing extensive customization through hooks and configuration files.

### Coordination Hierarchy

```
Event System (h()-based)
├── Event Objects (immutable data carriers)
└── EventDispatcher (hook wrapper)

Phase System (p()-based)
├── Kernel (orchestrator)
├── Pipeline (stage processor)
└── Job (background task)

Routing System (file-based)
├── Router (matcher)
├── Routes (definitions)
└── Provider (service registration)

Mediation Layer
└── Mediator (component coordinator)
```

## Final Coordination Class Details

### Final/Coordination/Event

**Purpose**: Immutable value objects representing significant occurrences within the application domain. Events serve as the primary data carriers in Node.php's event-driven architecture, flowing through the hook system (`h()`) to enable loose coupling between producers and consumers. Each event is a self-contained snapshot of a domain occurrence, complete with all necessary context and framework metadata.

**Framework Integration Strategy**: Events are designed to be published via `h($eventName, $event)` and consumed by any registered hook. They include framework metadata (NODE_NAME, timestamps, source identifiers) to facilitate tracing across distributed executions. The immutable nature ensures event data cannot be corrupted during transmission through hook chains.

```php
final class UserRegisteredEvent
{
    // Event data with framework context
    private array $metadata = [
        'node' => NODE_NAME,
        'timestamp' => null,
        'source' => 'user_system'
    ];

    public function __construct(string $userId, string $email)
    {
        $this->metadata['timestamp'] = time();
        $this->metadata['trace_id'] = uniqid('trace_', true);
    }

    public function dispatch(): void
    {
        // Primary integration point with Node.php hook system
        $result = h('user.registered', $this);

        // Framework logging for audit trail
        r("Event dispatched: user.registered", 'Audit', null, [
            'user_id' => $this->userId,
            'trace_id' => $this->metadata['trace_id']
        ]);
    }
}
```

**Design Considerations**:

- Immutability prevents side effects during hook chain execution
- Framework metadata enables cross-cutting concerns (logging, tracing, monitoring)
- Serialization support allows events to cross process boundaries
- Type safety through final class prevents unexpected subclass behavior

### Final/Coordination/EventDispatcher

**Purpose**: Concrete facade over Node.php's hook system that adds structure, error handling, and observability to event publishing. While `h()` provides the fundamental hook mechanism, EventDispatcher adds production-ready features: timing metrics, error recovery strategies, batch processing, and event history tracking. It represents the "professional" interface to the hook system for complex applications.

**Framework Integration Strategy**: This class doesn't replace `h()` but rather augments it. All events still flow through the core hook system, but EventDispatcher wraps each `h()` call with additional functionality. It leverages `r()` for structured logging, respects `env()` configuration for behavior tuning, and can trigger phase transitions via `p()` for event-driven workflow orchestration.

```php
final class EventDispatcher
{
    public function dispatch(string $eventName, $payload, array $options = []): mixed
    {
        $start = microtime(true);

        // Pre-dispatch hooks allow for payload transformation
        $payload = h("event.before.{$eventName}", $payload) ?? $payload;

        try {
            // Core dispatch through Node.php hook system
            $result = h($eventName, $payload);

            // Post-dispatch hooks for result processing
            $result = h("event.after.{$eventName}", $result) ?? $result;

            // Framework logging with performance metrics
            r("Event completed: {$eventName}", 'Internal', null, [
                'duration' => microtime(true) - $start,
                'success' => true,
                'node' => NODE_NAME
            ]);

            return $result;

        } catch (\Throwable $e) {
            // Error handling through framework's error hooks
            h('event.error', $e, $eventName, $payload);

            // Recovery strategy based on environment configuration
            return $this->handleError($e, $eventName, $payload, $options);
        }
    }
}
```

**Design Considerations**:

- Wrapper pattern over core `h()` maintains compatibility
- Error recovery strategies configurable via `env()` variables
- Performance metrics integrated with framework logging (`r()`)
- Batch processing optimizes multiple event dispatches

### Final/Coordination/Job

**Purpose**: Serializable units of work designed for deferred or asynchronous execution within Node.php's phase-based architecture. Jobs encapsulate all necessary context to perform a task outside the main request/response cycle, with built-in support for retries, failure handling, and progress tracking. They represent the framework's approach to background processing.

**Framework Integration Strategy**: Jobs execute within dedicated phases (`p('jobs.process', $job)`), allowing them to integrate with the framework's lifecycle management. They leverage `f()` for persistence (serialization to disk), `r()` for execution logging, and `h()` for job lifecycle events. The phase-based execution ensures jobs respect the same error handling and cleanup procedures as other framework operations.

```php
final class ProcessUploadJob
{
    public function handle(): void
    {
        // Execute within a dedicated job processing phase
        p('jobs.process', function ($phaseName, $state) {
            $state['job_id'] = $this->id;
            $state['start_time'] = microtime(true);

            // Job logic with framework resource access
            $filePath = f("Storage/Uploads/{$this->filename}", 'find');
            $content = f($filePath, 'read');

            // Process with error handling
            $result = $this->processContent($content);

            // Update state for phase system
            $state['result'] = $result;
            $state['success'] = true;

            return $state;
        });

        // Job completion hook for downstream processing
        h('job.completed', [
            'job_id' => $this->id,
            'type' => 'upload_processing',
            'node' => NODE_NAME
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        // Framework error logging with job context
        r("Job failed: {$this->id}", 'Exception', null, [
            'exception' => get_class($exception),
            'job_type' => static::class,
            'attempt' => $this->attempts
        ]);

        // Configurable retry logic
        if ($this->attempts < env('NODE:JOB_MAX_RETRIES', 3)) {
            h('job.retry', $this);
        } else {
            h('job.failed_permanently', $this);
        }
    }
}
```

**Design Considerations**:

- Serializable design allows jobs to cross process boundaries
- Phase-based execution ensures consistent lifecycle management
- Built-in retry logic with exponential backoff
- Integration with framework file system via `f()`

### Final/Coordination/Kernel

**Purpose**: The central orchestrator of Node.php's phase-based execution model. The kernel manages the complete lifecycle of a request (or CLI command), executing phases in the configured order, handling errors at phase boundaries, and providing hooks for phase lifecycle events. It represents the framework's approach to structured, predictable execution flow.

**Framework Integration Strategy**: The kernel directly interacts with `p()` to drive phase execution, uses `h()` to expose kernel events, leverages `r()` for phase transition logging, and reads configuration via `env()` and `f()`. It embodies the framework's philosophy of file-based configuration and hook-driven extensibility.

```php
final class ApplicationKernel
{
    public function run(array $context = []): array
    {
        // Initialize with framework context
        $state = array_merge($context, [
            'kernel_start' => microtime(true),
            'node' => NODE_NAME,
            'phase_order' => p('order')
        ]);

        // Phase execution loop
        foreach (p('order') as $phase) {
            $phaseStart = microtime(true);

            // Pre-phase hooks
            h("kernel.phase.before.{$phase}", $state);

            try {
                // Execute phase through framework's phase system
                $state = p($phase) ?? $state;

                // Post-phase hooks
                h("kernel.phase.after.{$phase}", $state);

                // Framework logging
                r("Phase completed: {$phase}", 'Internal', null, [
                    'duration' => microtime(true) - $phaseStart,
                    'node' => NODE_NAME
                ]);

            } catch (\Throwable $e) {
                // Phase error handling
                $this->handlePhaseError($e, $phase, $state);

                // Continue or abort based on configuration
                if (env('NODE:KERNEL_ABORT_ON_PHASE_ERROR', true)) {
                    break;
                }
            }
        }

        // Finalize with total execution metrics
        $totalDuration = microtime(true) - $state['kernel_start'];
        r("Kernel execution completed", 'Internal', null, [
            'total_duration' => $totalDuration,
            'phases_executed' => count(p('order')),
            'node' => NODE_NAME
        ]);

        return $state;
    }
}
```

**Design Considerations**:

- Predictable phase execution order
- Configurable error handling strategies
- Performance metrics at phase and kernel level
- Extensible through phase and kernel hooks

### Final/Coordination/Mediator

**Purpose**: Central coordinator for complex interactions between components that should not have direct dependencies on each other. The mediator implements the mediator pattern using Node.php's hook system as the communication channel, reducing coupling while maintaining visibility into component interactions. It's particularly useful for coordinating multi-step processes that involve several independent components.

**Framework Integration Strategy**: The mediator uses `h()` for all communication, creating a dedicated namespace for mediated interactions (`h('mediator.component.action', $data)`). It leverages `r()` to log mediation decisions and outcomes, and can trigger `p()` phase transitions when coordinating multi-phase workflows. Configuration is driven by `env()` variables and external definition files loaded via `f()`.

```php
final class OrderFulfillmentMediator
{
    public function fulfillOrder(string $orderId): array
    {
        // Mediation context with framework metadata
        $context = [
            'mediation_id' => uniqid('mediate_', true),
            'order_id' => $orderId,
            'start_time' => microtime(true),
            'node' => NODE_NAME
        ];

        // Step 1: Validate order through inventory hook
        $inventoryResult = h('mediator.order.validate_inventory', [
            'order_id' => $orderId,
            'context' => $context
        ]);

        // Step 2: Process payment through payment hook
        $paymentResult = h('mediator.order.process_payment', [
            'order_id' => $orderId,
            'context' => $context,
            'inventory_ok' => $inventoryResult['success'] ?? false
        ]);

        // Step 3: Schedule shipping through shipping hook
        $shippingResult = h('mediator.order.schedule_shipping', [
            'order_id' => $orderId,
            'context' => $context,
            'payment_processed' => $paymentResult['success'] ?? false
        ]);

        // Mediation logging for audit trail
        r("Order fulfillment mediated", 'Audit', null, [
            'order_id' => $orderId,
            'mediation_id' => $context['mediation_id'],
            'duration' => microtime(true) - $context['start_time'],
            'results' => [
                'inventory' => $inventoryResult['success'] ?? false,
                'payment' => $paymentResult['success'] ?? false,
                'shipping' => $shippingResult['success'] ?? false
            ]
        ]);

        // Return consolidated results
        return [
            'mediated' => true,
            'order_id' => $orderId,
            'success' => ($inventoryResult['success'] ?? false)
                      && ($paymentResult['success'] ?? false)
                      && ($shippingResult['success'] ?? false),
            'context' => $context
        ];
    }
}
```

**Design Considerations**:

- Dedicated hook namespace prevents collision with other systems
- Transactional semantics for multi-step processes
- Comprehensive logging for debugging complex interactions
- Timeout and retry logic for unreliable components

### Final/Coordination/Pipeline

**Purpose**: Sequential processor that applies a series of operations to a payload, with each operation potentially transforming the payload or producing side effects. Built on the same conceptual model as Node.php's phase system, pipelines provide a generalized mechanism for data processing workflows that can be configured externally and extended through hooks.

**Framework Integration Strategy**: Pipelines use a similar execution model to `p()` but are designed for data transformation rather than application lifecycle management. They leverage `h()` for stage implementation, `r()` for stage transition logging, and configuration loaded via `f()`. The pipeline pattern is particularly useful for request processing, data validation chains, and content transformation workflows.

```php
final class RequestProcessingPipeline
{
    public function process(array $request): array
    {
        $pipelineId = uniqid('pipe_', true);
        $payload = $request;

        // Load pipeline stages from configuration
        $stages = $this->loadStages();

        foreach ($stages as $stageName) {
            $stageStart = microtime(true);

            // Pre-stage hook for payload inspection/modification
            $payload = h("pipeline.before.{$stageName}", $payload) ?? $payload;

            try {
                // Execute stage through dedicated hook
                $payload = h("pipeline.stage.{$stageName}", $payload);

                // Post-stage hook for result processing
                $payload = h("pipeline.after.{$stageName}", $payload) ?? $payload;

                // Stage completion logging
                r("Pipeline stage completed: {$stageName}", 'Internal', null, [
                    'pipeline_id' => $pipelineId,
                    'duration' => microtime(true) - $stageStart,
                    'stage' => $stageName,
                    'node' => NODE_NAME
                ]);

            } catch (\Throwable $e) {
                // Stage error handling
                $payload = $this->handleStageError($e, $stageName, $payload);

                // Continue or abort based on pipeline configuration
                if ($this->shouldAbortOnError($stageName)) {
                    break;
                }
            }
        }

        // Pipeline completion logging
        r("Pipeline execution completed", 'Internal', null, [
            'pipeline_id' => $pipelineId,
            'total_stages' => count($stages),
            'node' => NODE_NAME
        ]);

        return $payload;
    }

    private function loadStages(): array
    {
        // Load pipeline configuration from file system
        $configFile = f("Config/Pipeline/request.json", 'find');
        if ($configFile) {
            $config = json_decode(f($configFile, 'read'), true);
            return $config['stages'] ?? ['validate', 'authenticate', 'authorize', 'process'];
        }

        // Fallback to environment configuration
        return explode(',', env('NODE:PIPELINE_STAGES', 'validate,authenticate,authorize,process'));
    }
}
```

**Design Considerations**:

- External configuration via files or environment variables
- Consistent error handling across all stages
- Performance metrics per stage
- Hot-reload capability for stage configuration

### Final/Coordination/Provider

**Purpose**: Service bootstrapper that registers and configures services with the framework, making them available through the hook system. Providers are the primary mechanism for service dependency management in Node.php, offering a structured way to initialize services during specific phases and expose them through consistent hooks.

**Framework Integration Strategy**: Providers execute during the `p('boot')` phase (or other configured phases), registering services via `h('service.{name}', $serviceInstance)`. They read configuration via `env()` and `f()`, log initialization via `r()`, and can declare dependencies on other services through the hook system.

```php
final class DatabaseServiceProvider
{
    public function register(): void
    {
        // Service registration through framework hook system
        h('service.database', function () {
            $config = [
                'host' => env('NODE:DB_HOST', 'localhost'),
                'database' => env('NODE:DB_DATABASE', 'app'),
                'username' => env('NODE:DB_USERNAME', 'root'),
                'password' => env('NODE:DB_PASSWORD', ''),
                'node' => NODE_NAME
            ];

            // Create database connection
            $connection = new DatabaseConnection($config);

            // Register connection events
            h('database.connected', function () use ($connection) {
                r("Database connected successfully", 'Internal', null, [
                    'database' => $config['database'],
                    'node' => NODE_NAME
                ]);
            });

            return $connection;
        });

        // Provider registration logging
        r("Database service provider registered", 'Internal', null, [
            'provider' => static::class,
            'node' => NODE_NAME
        ]);
    }

    public function boot(): void
    {
        // Optional boot logic executed after all providers are registered
        $database = h('service.database');

        // Register database-related hooks
        h('query.executing', function ($query) use ($database) {
            // Query logging or modification
            r("Query executing", 'Internal', null, [
                'query' => $query,
                'node' => NODE_NAME
            ]);

            return $query;
        });
    }
}
```

**Design Considerations**:

- Lazy initialization through factory hooks
- Phase-aware registration and booting
- Dependency declaration through hook dependencies
- Configuration via environment and file system

### Final/Coordination/Router

**Purpose**: Request matcher that maps incoming requests (HTTP or CLI) to appropriate handlers based on predefined patterns. The router integrates with Node.php's file-based configuration philosophy, loading route definitions from files and executing matched routes within the framework's phase system.

**Framework Integration Strategy**: Routes are loaded via `f()` from configuration files, matched against incoming requests, and executed within a dedicated `p('route.execute', $route)` phase. The router uses `h()` for route events (matching, execution, completion) and `r()` for route execution logging.

```php
final class HttpRouter
{
    public function dispatch(string $method, string $uri): mixed
    {
        $routeId = uniqid('route_', true);

        // Load routes from file system
        $routes = $this->loadRoutes();

        // Find matching route
        $route = $this->matchRoute($method, $uri, $routes);

        if (!$route) {
            // No route found - trigger 404 hooks
            return h('route.not_found', [
                'method' => $method,
                'uri' => $uri,
                'route_id' => $routeId,
                'node' => NODE_NAME
            ]);
        }

        // Pre-route execution hooks
        h('route.before.execute', [
            'route' => $route,
            'method' => $method,
            'uri' => $uri,
            'route_id' => $routeId
        ]);

        // Route execution within framework phase system
        $result = p('route.execute', function ($phaseName, $state) use ($route, $method, $uri, $routeId) {
            $state['route_execution'] = [
                'route_id' => $routeId,
                'handler' => $route['handler'],
                'parameters' => $route['parameters'],
                'start_time' => microtime(true),
                'node' => NODE_NAME
            ];

            // Execute route handler
            $handlerResult = $this->executeHandler($route['handler'], $route['parameters']);

            $state['route_execution']['result'] = $handlerResult;
            $state['route_execution']['success'] = true;
            $state['route_execution']['duration'] = microtime(true) -
                $state['route_execution']['start_time'];

            return $state;
        });

        // Post-route execution hooks
        h('route.after.execute', [
            'route' => $route,
            'result' => $result,
            'route_id' => $routeId,
            'node' => NODE_NAME
        ]);

        // Route execution logging
        r("Route dispatched", 'Access', null, [
            'method' => $method,
            'uri' => $uri,
            'route_id' => $routeId,
            'handler' => $route['handler'],
            'duration' => $result['route_execution']['duration'] ?? 0,
            'node' => NODE_NAME
        ]);

        return $result['route_execution']['result'] ?? null;
    }

    private function loadRoutes(): array
    {
        // Load routes from file system using framework file utilities
        $routeFiles = [
            f("Routes/Web/routes.json", 'find'),
            f("Routes/Api/routes.json", 'find'),
            f("Routes/Console/routes.json", 'find')
        ];

        $routes = [];
        foreach (array_filter($routeFiles) as $file) {
            $fileRoutes = json_decode(f($file, 'read'), true);
            $routes = array_merge($routes, $fileRoutes['routes'] ?? []);
        }

        return $routes;
    }
}
```

**Design Considerations**:

- File-based route configuration
- Phase-based route execution
- Comprehensive logging for all route dispatches
- Hook-based extensibility for route matching and execution

### Final/Coordination/Routes

**Purpose**: File-based route definition containers that separate route configuration from routing logic. Routes are defined in JSON (or other structured) files and loaded by the router, enabling hot-reloading of route definitions without code changes. This aligns with Node.php's philosophy of externalizing configuration.

**Framework Integration Strategy**: Route files are loaded via `f()` during router initialization, parsed according to their format (JSON, YAML, PHP array), and transformed into route objects. The route definitions can include hook names for handlers, allowing route execution to integrate seamlessly with the framework's hook system.

```php
// Example: Routes/Web/routes.json
{
    "routes": [
        {
            "method": "GET",
            "pattern": "/users",
            "handler": "web.users.index",
            "name": "users.index",
            "middleware": ["web.auth", "web.admin"]
        },
        {
            "method": "POST",
            "pattern": "/users",
            "handler": "web.users.store",
            "name": "users.store",
            "validation": "UserCreateRequest"
        }
    ]
}

// Route execution integrates with hook system
// GET /users triggers: h('web.users.index', $requestData)
// POST /users triggers: h('web.users.store', $requestData)
```

**Design Considerations**:

- Multiple format support (JSON, YAML, PHP arrays)
- Hot-reload capability
- Integration with framework validation and middleware systems
- Namespacing to prevent route collisions

## Complementary Patterns

**Hook Pattern**: All coordination classes fundamentally rely on Node.php's `h()` hook system for communication and extensibility.

**Phase Pattern**: Coordination classes like Kernel, Job, and Pipeline are built on the `p()` phase execution model.

**File-based Configuration**: Router, Routes, and Provider patterns leverage `f()` for external configuration management.

**Immutable Data**: Event pattern uses immutability to ensure data integrity across hook chains.

**Facade Pattern**: EventDispatcher acts as a facade over the raw `h()` hook system.

**Strategy Pattern**: Mediator and Pipeline can use different strategies for error handling and stage execution based on configuration.

## Distinguishing Characteristics

**vs. Behavioral Patterns**: Coordination patterns manage flow between components; behavioral patterns define object interactions.

**vs. Infrastructure Patterns**: Coordination patterns orchestrate application flow; infrastructure patterns provide technical capabilities.

**vs. Presentation Patterns**: Coordination patterns handle request/event flow; presentation patterns format output.

**vs. Service Locator**: Provider pattern registers services; service locator finds already-registered services.

**vs. Message Bus**: EventDispatcher is hook-based and synchronous; message buses are typically asynchronous and queue-based.

**vs. Workflow Engine**: Pipeline pattern is simpler and phase-based; workflow engines have complex state management and branching logic.

## Framework Integration Summary

Node.php's coordination patterns are deeply integrated with its core architecture:

1. **Hook-driven Communication**: All coordination uses `h()` for loose coupling
2. **Phase-based Execution**: Complex coordination uses `p()` for structured lifecycle management
3. **File-based Configuration**: Routes, pipelines, and providers load from external files via `f()`
4. **Structured Logging**: All coordination activities are logged via `r()` with consistent metadata
5. **Environment-aware**: Behavior is configurable via `env()` variables
6. **Immutable Core**: Final classes ensure consistent, predictable coordination behavior
