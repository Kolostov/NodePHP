# Interface/Presentation Patterns

## Overview: Presentation Layer Contracts

Presentation interfaces in Node.php define contracts for handling user interactions, formatting responses, and rendering outputs across the framework's execution phases and delivery mechanisms. These interfaces separate presentation concerns from business logic while integrating with the framework's `p()` phase system, `h()` hooks, and `r()` logging to enable consistent interaction patterns across HTTP, CLI, and API channels. Unlike traditional presentation layers that focus solely on HTTP responses, Node.php's presentation interfaces are phase-aware and work within the framework's stateful execution model, allowing presentation logic to participate in and influence phase transitions.

### Presentation Interface Integration with Node.php Architecture

| **Interface Type** | **Primary Phase**     | **State Interaction**            | **Hook Integration**          | **Framework Utilities**          |
| ------------------ | --------------------- | -------------------------------- | ----------------------------- | -------------------------------- |
| **Controller**     | `execute`, `mutate`   | Receives/modifies phase state    | `h('controller.*', $state)`   | `env()` routing, `r()` logging   |
| **Endpoint**       | `execute`, `persist`  | Processes state for output       | `h('endpoint.*', $payload)`   | `env()` configuration validation |
| **Middleware**     | `resolve`, `execute`  | Filters/modifies phase state     | `h('middleware.*', $pipe)`    | Phase pipeline integration       |
| **Responder**      | `persist`, `finalize` | Formats final state output       | `h('responder.*', $response)` | Channel-specific formatting      |
| **View**           | `mutate`, `persist`   | Transforms state to presentation | `h('view.*', $data)`          | Template/rendering integration   |

## Interface Details and Framework Integration

### Interface/Presentation/Controller

**Purpose**: Defines the contract for orchestrating request handling within Node.php's phase system. Controllers receive phase state, coordinate business logic through hooks and phase transitions, and determine appropriate responses that may influence subsequent phases. Unlike traditional MVC controllers, Node.php controllers are phase-aware and can participate in the framework's stateful execution flow, making decisions based on the current phase context and accumulated state.

**Framework Integration**: Controllers integrate with the `p()` system to receive execution context, use `h()` hooks for extensibility, and leverage `env()` for configuration-based routing decisions. They serve as the primary orchestrators between user interactions and the framework's phase-based execution model.

```php
namespace Primitive\Interface\Presentation;

interface Controller
{
    /**
     * Handles request by processing phase state and returning modified state
     * Uses h('controller.handle') hooks for extensibility
     */
    public function handle(array $state): array;

    /**
     * Determines if controller supports current phase/request context
     * Checks via env() configuration and phase state
     */
    public function supports(string $phase, array $state): bool;
}
```

**Example Implementation**:

```php
class PhaseController implements Primitive\Interface\Presentation\Controller
{
    public function handle(array $state): array {
        // Use hook to pre-process state
        $state = h('controller.pre_handle', $state) ?? $state;

        // Determine action based on phase and state
        $action = $state['action'] ?? $this->determineAction($state);

        // Execute action through hook system
        $result = h("controller.action.{$action}", $state) ?? $state;

        // Log controller execution
        r("Controller handled action: {$action}", "Internal", $result);

        return $result;
    }

    public function supports(string $phase, array $state): bool {
        // Check environment configuration
        $enabledControllers = env('NODE:ENABLED_CONTROLLERS', []);
        $controllerName = get_class($this);

        // Use hook for dynamic support checking
        $supported = h('controller.supports', [
            'controller' => $controllerName,
            'phase' => $phase,
            'state' => $state,
            'enabled' => in_array($controllerName, $enabledControllers)
        ]);

        return $supported['enabled'] ?? false;
    }
}
```

**Interaction with Other Patterns**: Controllers work with `Middleware` for request preprocessing, delegate to `Responder` for output formatting, and often trigger `Endpoint` execution for API operations. They coordinate between the framework's phase system and domain logic, serving as the primary bridge between presentation concerns and business operations.

### Interface/Presentation/Endpoint

**Purpose**: Defines a contract for public API callable methods that directly expose functionality with strict input/output contracts. Endpoints in Node.php are phase-aware service boundaries that process structured payloads from the phase state and return results that can influence subsequent phase execution. They provide clean, focused interfaces for external consumers while maintaining integration with the framework's hook system for validation, transformation, and error handling.

**Framework Integration**: Endpoints leverage `h()` hooks for input validation and output processing, use `env()` for configuration-based behavior, and integrate with the `r()` logging system for audit trails. They represent the framework's service boundary where external input meets internal processing logic.

```php
namespace Primitive\Interface\Presentation;

interface Endpoint
{
    /**
     * Invokes endpoint with phase state, returning modified state
     * Uses h('endpoint.invoke') hooks for processing
     */
    public function __invoke(array $state): array;

    /**
     * Returns HTTP method for routing (if applicable)
     * Uses env() for method configuration
     */
    public function getMethod(): string;

    /**
     * Returns endpoint path/identifier
     * Configurable via env() with phase context
     */
    public function getPath(): string;
}
```

**Example Implementation**:

```php
class DataEndpoint implements Primitive\Interface\Presentation\Endpoint
{
    public function __invoke(array $state): array {
        // Validate input via hook chain
        $validated = h('endpoint.validate', $state);
        if ($validated === false) {
            throw new \RuntimeException("Endpoint validation failed");
        }

        // Process through business logic hooks
        $result = h('endpoint.process', $state) ?? $state;

        // Format response via responder hooks
        $response = h('endpoint.format', $result);

        // Log endpoint execution
        r("Endpoint executed: " . $this->getPath(), "Access", $response);

        return array_merge($state, ['endpoint_response' => $response]);
    }

    public function getMethod(): string {
        return env('NODE:ENDPOINT_METHOD', 'POST');
    }

    public function getPath(): string {
        return env('NODE:ENDPOINT_PATH', '/api/data');
    }
}
```

**Interaction with Other Patterns**: Endpoints are often called by `Controller` implementations, use `Middleware` for cross-cutting concerns like authentication, and delegate to `Responder` for final output formatting. They serve as the entry point for API interactions while maintaining integration with the framework's phase-based execution model.

### Interface/Presentation/Middleware

**Purpose**: Defines a contract for request/response processing pipeline components that operate within Node.php's phase execution flow. Middleware implementations intercept phase state, apply transformations or validations, and pass control to the next handler in the phase pipeline. Unlike traditional HTTP middleware, Node.php middleware is phase-aware and can operate on the framework's execution state rather than just HTTP requests and responses.

**Framework Integration**: Middleware integrates with the `p()` phase system to intercept state transitions, uses `h()` hooks for composable behavior, and leverages `r()` logging for execution tracing. It represents the framework's primary mechanism for cross-cutting concern implementation across phase boundaries.

```php
namespace Primitive\Interface\Presentation;

interface Middleware
{
    /**
     * Processes phase state, optionally calling next middleware
     * Uses h('middleware.process') hooks for composition
     */
    public function process(array $state, callable $next): array;
}
```

**Example Implementation**:

```php
class PhaseMiddleware implements Primitive\Interface\Presentation\Middleware
{
    public function process(array $state, callable $next): array {
        // Pre-processing hook
        $state = h('middleware.pre_process', $state) ?? $state;

        // Apply middleware logic based on phase
        $phase = $state['_phase'] ?? 'unknown';
        $state = $this->applyPhaseLogic($state, $phase);

        // Call next middleware/phase handler
        $result = $next($state);

        // Post-processing hook
        $result = h('middleware.post_process', $result) ?? $result;

        // Log middleware execution
        r("Middleware processed phase: {$phase}", "Internal", $result);

        return $result;
    }
}
```

**Interaction with Other Patterns**: Middleware chains are orchestrated by the framework's phase system, process state before it reaches `Controller` or `Endpoint` implementations, and can modify responses before they reach `Responder` components. They provide the architectural foundation for implementing cross-cutting concerns like authentication, logging, and validation across the entire execution flow.

### Interface/Presentation/Responder

**Purpose**: Defines a contract for formatting responses according to specific output requirements across different channels (HTTP, CLI, API). Responders in Node.php transform phase state into channel-specific output formats while maintaining integration with the framework's hook system for output customization. They separate data generation from presentation formatting, allowing the same phase state to be rendered differently based on execution context.

**Framework Integration**: Responders use `h()` hooks for output transformation, leverage `env()` for format configuration, and integrate with `r()` logging for response auditing. They represent the final stage of the phase execution pipeline where internal state becomes external output.

```php
namespace Primitive\Interface\Presentation;

interface Responder
{
    /**
     * Formats phase state into channel-specific response
     * Uses h('responder.format') hooks for customization
     */
    public function respond(array $state): mixed;

    /**
     * Handles errors by formatting error state appropriately
     * Uses h('responder.error') hooks for error customization
     */
    public function error(array $state): mixed;
}
```

**Example Implementation**:

```php
class PhaseResponder implements Primitive\Interface\Presentation\Responder
{
    public function respond(array $state): mixed {
        // Determine output format from environment or state
        $format = $state['_format'] ?? env('NODE:RESPONSE_FORMAT', 'json');

        // Use hook to select formatter
        $formatter = h('responder.select_formatter', $format) ?? $format;

        // Format state through hook chain
        $output = h("responder.format.{$formatter}", $state);

        // Log response generation
        r("Responder formatted output as: {$formatter}", "Internal", [
            'state_keys' => array_keys($state),
            'output_size' => strlen(serialize($output))
        ]);

        return $output;
    }

    public function error(array $state): mixed {
        // Format error through hook chain
        $errorOutput = h('responder.format_error', $state) ?? [
            'error' => $state['_error'] ?? 'Unknown error',
            'phase' => $state['_phase'] ?? 'unknown',
            'timestamp' => time()
        ];

        // Log error response
        r("Responder formatted error", "Error", $errorOutput);

        return $errorOutput;
    }
}
```

**Interaction with Other Patterns**: Responders are called by `Controller` and `Endpoint` implementations to format their outputs, can be configured by `Middleware` based on execution context, and work with `View` components for complex rendering scenarios. They serve as the final transformation point where internal execution state becomes externally consumable output.

### Interface/Presentation/View

**Purpose**: Defines a contract for renderable content that transforms data into presentation formats within Node.php's phase context. Views work with phase state to produce rendered output while maintaining integration with the framework's hook system for template selection, data transformation, and rendering customization. They support multiple rendering strategies and output formats while remaining phase-aware.

**Framework Integration**: Views use `h()` hooks for template resolution and data transformation, leverage `env()` for rendering configuration, and integrate with `r()` logging for rendering diagnostics. They represent the framework's primary abstraction for content generation across different output formats.

```php
namespace Primitive\Interface\Presentation;

interface View
{
    /**
     * Renders phase state into presentation format
     * Uses h('view.render') hooks for rendering pipeline
     */
    public function render(array $state): string;

    /**
     * Returns content type for output headers
     * Configurable via env() with state context
     */
    public function getContentType(): string;
}
```

**Example Implementation**:

```php
class HookableView implements Primitive\Interface\Presentation\View
{
    public function render(array $state): string {
        // Determine template from state or environment
        $template = $state['_template'] ?? env('NODE:DEFAULT_TEMPLATE', 'default');

        // Pre-render hook for data preparation
        $renderData = h('view.pre_render', $state) ?? $state;

        // Template-specific rendering hook
        $content = h("view.template.{$template}", $renderData);

        if ($content === null) {
            // Fallback to default rendering
            $content = $this->defaultRender($renderData);
        }

        // Post-render hook for content transformation
        $content = h('view.post_render', $content) ?? $content;

        // Log view rendering
        r("View rendered template: {$template}", "Internal", [
            'content_type' => $this->getContentType(),
            'content_length' => strlen($content)
        ]);

        return $content;
    }

    public function getContentType(): string {
        return env('NODE:VIEW_CONTENT_TYPE', 'text/html');
    }
}
```

**Interaction with Other Patterns**: Views are typically invoked by `Responder` implementations for content generation, can be configured by `Middleware` based on execution context, and work with `Controller` logic to determine appropriate rendering strategies. They provide the framework's primary mechanism for separating presentation logic from data processing.

## Framework-Specific Integration Patterns

### Phase-Aware Presentation Flow

In Node.php, presentation interfaces participate in a phase-aware execution flow where:

1. **Phase State as Context**: All presentation interfaces receive and return phase state arrays, allowing them to participate in the framework's stateful execution model.

2. **Hook-Based Extension**: Each interface leverages the `h()` hook system for extensibility, allowing behavior customization without interface modification.

3. **Environment Configuration**: Behavior is driven by `env()` configuration, enabling runtime adaptation without code changes.

4. **Integrated Logging**: All operations use `r()` for consistent logging across presentation layers.

### Presentation Layer and Phase Transitions

Presentation interfaces interact with the framework's phase system in specific ways:

- **Controllers** typically operate in `execute` and `mutate` phases, orchestrating business logic transitions.
- **Middleware** operates across phase boundaries, particularly in `resolve` and `execute` phases.
- **Responders** and **Views** work in `persist` and `finalize` phases, transforming state into final output.
- **Endpoints** can operate in any phase but typically focus on `execute` and `persist` for API interactions.

### Hook Integration Strategy

Each presentation interface uses hooks strategically:

- **Pre/Post Hooks**: Most interfaces support pre- and post-execution hooks for behavior injection.
- **Validation Hooks**: Input validation uses dedicated hook chains.
- **Formatting Hooks**: Output formatting leverages format-specific hook registrations.
- **Error Hooks**: Error handling uses specialized error processing hooks.

### Configuration-Driven Presentation

Presentation behavior in Node.php is heavily configuration-driven:

- **Environment Variables**: `env()` calls determine routing, formatting, and rendering strategies.
- **Phase Context**: Current phase and accumulated state influence presentation decisions.
- **Hook Registrations**: Registered hooks provide runtime behavior customization.
- **Channel Detection**: Execution context (HTTP vs CLI) determines appropriate presentation strategies.

This architecture allows Node.php's presentation layer to be both flexible and consistent, adapting to different delivery channels while maintaining integration with the framework's core execution model.
