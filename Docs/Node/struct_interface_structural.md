# Interface/Structural Patterns

## Overview: Structural Composition Contracts

Structural interfaces in Node.php define contracts for object composition, interface adaptation, and access control mechanisms that integrate with the framework's phase system and state management. These interfaces enable flexible object relationships within the framework's execution context, allowing components to work together in various configurations while maintaining defined interaction patterns that respect phase boundaries, hook integration, and environment configuration.

Unlike traditional structural patterns that focus purely on object relationships, Node.php's structural interfaces are phase-aware and designed to operate within the framework's stateful execution model. They provide mechanisms for adapting between different execution contexts, composing phase-aware components, extending behavior through hooks, controlling access to phase state, and abstracting persistence operations—all while maintaining integration with the framework's core utilities like `env()`, `h()`, and `r()`.

### Structural Interface Integration with Node.php Architecture

| **Interface Type** | **Primary Phase**        | **State Interaction**                  | **Hook Integration**       | **Framework Role**                    |
| ------------------ | ------------------------ | -------------------------------------- | -------------------------- | ------------------------------------- |
| **Adapter**        | `transpilate`, `resolve` | Translates between phase state formats | `h('adapter.*', $state)`   | Interface compatibility across phases |
| **Composite**      | `resolve`, `mutate`      | Manages hierarchical phase state       | `h('composite.*', $tree)`  | Tree-structured state organization    |
| **Decorator**      | `execute`, `mutate`      | Wraps and extends phase handlers       | `h('decorator.*', $chain)` | Phase behavior extension              |
| **Proxy**          | `resolve`, `execute`     | Controls access to phase resources     | `h('proxy.*', $access)`    | Phase resource access control         |
| **Repository**     | `persist`, `finalize`    | Abstracts phase state persistence      | `h('repository.*', $data)` | Phase state storage abstraction       |

## Interface Details and Framework Integration

### Interface/Structural/Adapter

**Purpose**: Defines a contract for translating between incompatible interfaces within Node.php's phase execution context. Adapters in the framework specialize in converting between different state representations, hook signatures, or phase execution patterns, enabling seamless integration between components that were not originally designed to work together. They serve as interface translators that respect phase boundaries and hook integration points.

**Framework Integration**: Adapters integrate with the `p()` phase system to receive and transform state between phases, use `h()` hooks for transformation logic, and leverage `env()` configuration to determine adaptation strategies. They are particularly useful for integrating legacy components, third-party libraries, or different execution patterns into the framework's cohesive phase-based architecture.

```php
namespace Primitive\Interface\Structural;

interface Adapter
{
    /**
     * Adapts input data or state between different formats/interfaces
     * Uses h('adapter.transform') hooks for flexible transformation
     */
    public function adapt(array $input): array;

    /**
     * Returns target interface/format identifier
     * Configurable via env() with phase context
     */
    public function getTarget(): string;

    /**
     * Checks if adapter can handle current phase/state combination
     * Uses h('adapter.supports') for dynamic capability checking
     */
    public function supports(string $phase, array $state): bool;
}
```

**Example Implementation**:

```php
class PhaseStateAdapter implements \Primitive\Interface\Structural\Adapter
{
    public function adapt(array $input): array {
        // Use hook to pre-process adaptation
        $input = h('adapter.pre_adapt', $input) ?? $input;

        // Determine adaptation strategy from environment
        $strategy = env('NODE:ADAPTER_STRATEGY', 'default');

        // Apply strategy through hook system
        $output = h("adapter.strategy.{$strategy}", $input) ?? $input;

        // Post-adaptation hook for validation
        $output = h('adapter.post_adapt', $output) ?? $output;

        // Log adaptation operation
        r("Adapter transformed state", "Internal", [
            'input_keys' => array_keys($input),
            'output_keys' => array_keys($output),
            'strategy' => $strategy
        ]);

        return $output;
    }

    public function getTarget(): string {
        return env('NODE:ADAPTER_TARGET', 'phase_state');
    }

    public function supports(string $phase, array $state): bool {
        // Check environment configuration
        $enabledAdapters = env('NODE:ENABLED_ADAPTERS', []);
        $adapterClass = get_class($this);

        // Dynamic support checking through hooks
        $supported = h('adapter.supports', [
            'adapter' => $adapterClass,
            'phase' => $phase,
            'state' => $state,
            'enabled' => in_array($adapterClass, $enabledAdapters)
        ]);

        return $supported['enabled'] ?? false;
    }
}
```

**Interaction with Other Patterns**: Adapters work closely with `Decorator` patterns to wrap and transform component behavior, integrate with `Proxy` patterns to provide interface translation for protected resources, and often collaborate with `Repository` patterns to adapt between different persistence formats. They serve as the framework's primary mechanism for interface compatibility across different execution contexts and phase boundaries.

### Interface/Structural/Composite

**Purpose**: Defines a contract for tree-like hierarchical structures where individual components and composite containers can be treated uniformly within Node.php's phase execution model. Composites in the framework manage hierarchical state organization, allowing complex phase state trees to be manipulated as single units while maintaining phase awareness and hook integration at each level of the hierarchy.

**Framework Integration**: Composites integrate with the `p()` phase system to manage hierarchical state transitions, use `h()` hooks for tree operations, and leverage `r()` logging for hierarchy diagnostics. They provide the architectural foundation for organizing complex phase states into manageable tree structures that can be processed recursively during phase execution.

```php
namespace Primitive\Interface\Structural;

interface Composite
{
    /**
     * Adds component to composite structure
     * Uses h('composite.add') hooks for validation
     */
    public function add(Composite $component): void;

    /**
     * Removes component from composite structure
     * Uses h('composite.remove') hooks for cleanup
     */
    public function remove(Composite $component): void;

    /**
     * Returns child components as array
     * Configurable via env() for depth limiting
     */
    public function getChildren(): array;

    /**
     * Executes operation on composite structure
     * Uses h('composite.operation') hooks for processing
     */
    public function operate(array $state): array;
}
```

**Example Implementation**:

```php
class PhaseComposite implements \Primitive\Interface\Structural\Composite
{
    private $name;
    private $children = [];

    public function operate(array $state): array {
        // Pre-operation hook
        $state = h('composite.pre_operate', $state) ?? $state;

        // Apply operation to all children recursively
        foreach ($this->children as $child) {
            $state = $child->operate($state);

            // Child operation hook
            $state = h('composite.child_operated', $state) ?? $state;
        }

        // Post-operation hook
        $state = h('composite.post_operate', $state) ?? $state;

        // Log composite operation
        r("Composite operated on children", "Internal", [
            'composite' => $this->name,
            'child_count' => count($this->children),
            'state_keys' => array_keys($state)
        ]);

        return $state;
    }

    public function add(Composite $component): void {
        // Validation hook can reject addition
        $allowed = h('composite.validate_add', [
            'parent' => $this,
            'child' => $component
        ]);

        if ($allowed !== false) {
            $this->children[] = $component;
            h('composite.added', ['parent' => $this, 'child' => $component]);
        }
    }

    public function getChildren(): array {
        // Hook can filter or transform children list
        return h('composite.get_children', $this->children) ?? $this->children;
    }
}
```

**Interaction with Other Patterns**: Composites often work with `Iterator` patterns for traversal, integrate with `Visitor` patterns for operations on tree structures, and collaborate with `Decorator` patterns to add behavior to entire hierarchies. They provide the structural foundation for organizing complex phase state into manageable tree structures that can be processed during phase execution.

### Interface/Structural/Decorator

**Purpose**: Defines a contract for dynamically adding behavior to objects without affecting other instances of the same class within Node.php's phase execution context. Decorators in the framework wrap phase handlers, middleware, or other components to add cross-cutting concerns like logging, caching, validation, or transformation while maintaining phase awareness and hook integration.

**Framework Integration**: Decorators integrate deeply with the `h()` hook system, using hooks for behavior composition and extension points. They work within the `p()` phase system to wrap phase handlers, leverage `env()` configuration for decorator chaining strategies, and use `r()` logging for decorator execution tracing. They represent the framework's primary mechanism for aspect-oriented programming within the phase execution model.

```php
namespace Primitive\Interface\Structural;

interface Decorator
{
    /**
     * Wraps and extends component behavior
     * Uses h('decorator.wrap') hooks for composition
     */
    public function decorate(callable $component): callable;

    /**
     * Applies decoration to specific execution
     * Uses h('decorator.apply') hooks for behavior injection
     */
    public function apply(array $state): array;
}
```

**Example Implementation**:

```php
class PhaseDecorator implements \Primitive\Interface\Structural\Decorator
{
    private $wrapped;

    public function decorate(callable $component): callable {
        // Store original component
        $this->wrapped = $component;

        // Return decorated version
        return function(array $state) use ($component) {
            // Pre-execution hook
            $state = h('decorator.pre_execute', $state) ?? $state;

            // Apply decoration logic
            $state = $this->apply($state);

            // Execute wrapped component
            $result = $component($state);

            // Post-execution hook
            $result = h('decorator.post_execute', $result) ?? $result;

            // Log decoration
            r("Decorator applied to component", "Internal", [
                'decorator' => get_class($this),
                'result_keys' => array_keys($result)
            ]);

            return $result;
        };
    }

    public function apply(array $state): array {
        // Default implementation - override in concrete decorators
        return $state;
    }
}

// Concrete decorator example
class LoggingDecorator extends PhaseDecorator
{
    public function apply(array $state): array {
        // Add logging to state
        if (!isset($state['_logs'])) {
            $state['_logs'] = [];
        }

        $state['_logs'][] = [
            'decorator' => get_class($this),
            'timestamp' => microtime(true),
            'phase' => $state['_phase'] ?? 'unknown'
        ];

        return $state;
    }
}
```

**Interaction with Other Patterns**: Decorators form chains with other `Decorator` instances to create layered behavior, work with `Proxy` patterns to add behavior to protected resources, integrate with `Adapter` patterns to add transformation capabilities, and often wrap `Controller` or `Endpoint` implementations in the presentation layer. They provide the framework's primary mechanism for adding cross-cutting concerns to phase execution.

### Interface/Structural/Proxy

**Purpose**: Defines a contract for surrogate objects that control access to other objects within Node.php's phase execution context. Proxies in the framework manage access to phase resources, implement lazy loading of phase dependencies, provide security checks on state access, or add monitoring capabilities—all while maintaining phase awareness and hook integration for access control decisions.

**Framework Integration**: Proxies integrate with the `p()` phase system to control resource access during specific phases, use `h()` hooks for access control decisions and monitoring, leverage `env()` configuration for proxy behavior settings, and utilize `r()` logging for access auditing. They serve as the framework's primary mechanism for implementing the principle of least privilege within phase execution.

```php
namespace Primitive\Interface\Structural;

interface Proxy
{
    /**
     * Controls access to proxied resource/operation
     * Uses h('proxy.access') hooks for authorization
     */
    public function access(string $operation, array $context = []);

    /**
     * Returns the real subject if access is granted
     * Uses h('proxy.reveal') hooks for conditional exposure
     */
    public function getSubject();

    /**
     * Validates if proxy can handle operation in current context
     * Configurable via env() with phase-based rules
     */
    public function canProxy(string $operation, array $state): bool;
}
```

**Example Implementation**:

```php
class PhaseProxy implements \Primitive\Interface\Structural\Proxy
{
    private $subject;
    private $accessRules = [];

    public function access(string $operation, array $context = []): mixed {
        // Check access via hook system
        $allowed = h('proxy.check_access', [
            'operation' => $operation,
            'context' => $context,
            'proxy' => $this
        ]);

        if ($allowed === false) {
            r("Proxy denied access to: {$operation}", "Audit", false, $context);
            throw new \RuntimeException("Access denied to: {$operation}");
        }

        // Pre-access hook
        h('proxy.pre_access', ['operation' => $operation, 'context' => $context]);

        // Execute operation on subject
        $result = $this->executeOperation($operation, $context);

        // Post-access hook
        h('proxy.post_access', [
            'operation' => $operation,
            'result' => $result,
            'context' => $context
        ]);

        // Log successful access
        r("Proxy granted access to: {$operation}", "Audit", true, [
            'context_keys' => array_keys($context)
        ]);

        return $result;
    }

    public function canProxy(string $operation, array $state): bool {
        // Check environment configuration
        $proxyEnabled = env('NODE:PROXY_ENABLED', true);

        // Check phase-based rules
        $phase = $state['_phase'] ?? 'unknown';
        $phaseRules = env("NODE:PROXY_PHASE_RULES_{$phase}", []);

        // Hook for dynamic capability checking
        $capable = h('proxy.capable', [
            'operation' => $operation,
            'phase' => $phase,
            'state' => $state,
            'enabled' => $proxyEnabled && in_array($operation, $phaseRules)
        ]);

        return $capable['enabled'] ?? false;
    }
}
```

**Interaction with Other Patterns**: Proxies often wrap `Repository` implementations to control data access, protect `Controller` or `Endpoint` executions with security checks, work with `Decorator` patterns to add monitoring to proxied operations, and integrate with `Adapter` patterns to provide controlled access to adapted interfaces. They implement the framework's security and access control layer within the phase execution model.

### Interface/Structural/Repository

**Purpose**: Defines a contract for abstracting data persistence operations within Node.php's phase execution context. Repositories in the framework provide collection-like interfaces for accessing and manipulating phase state while hiding the implementation details of storage mechanisms. They integrate with the framework's persistence phases (`persist`, `finalize`) to manage state storage and retrieval in a phase-aware manner.

**Framework Integration**: Repositories integrate with the `p()` phase system for state persistence operations, use `h()` hooks for query transformation and result processing, leverage `env()` configuration for storage backend selection, and utilize `r()` logging for persistence auditing. They serve as the framework's primary abstraction layer between phase state and persistent storage.

```php
namespace Primitive\Interface\Structural;

interface Repository
{
    /**
     * Finds entity by identifier within phase context
     * Uses h('repository.find') hooks for query enhancement
     */
    public function find($id, array $context = []);

    /**
     * Finds all entities matching criteria within phase context
     * Uses h('repository.find_all') hooks for criteria processing
     */
    public function findAll(array $criteria = [], array $context = []);

    /**
     * Saves entity with phase context for audit/versioning
     * Uses h('repository.save') hooks for validation/transformation
     */
    public function save($entity, array $context = []);

    /**
     * Deletes entity with phase context for audit
     * Uses h('repository.delete') hooks for cascade/prevention
     */
    public function delete($entity, array $context = []): bool;

    /**
     * Counts entities matching criteria within phase context
     * Configurable via env() for performance optimization
     */
    public function count(array $criteria = [], array $context = []): int;
}
```

**Example Implementation**:

```php
class PhaseRepository implements \Primitive\Interface\Structural\Repository
{
    public function find($id, array $context = []) {
        // Pre-find hook for query modification
        $query = h('repository.pre_find', [
            'id' => $id,
            'context' => $context,
            'repository' => $this
        ]);

        // Execute find with phase context
        $entity = $this->executeFind($query['id'] ?? $id, $context);

        // Post-find hook for result processing
        $entity = h('repository.post_find', $entity) ?? $entity;

        // Log find operation
        r("Repository found entity: {$id}", "Internal", [
            'context' => $context,
            'entity_keys' => array_keys((array)$entity)
        ]);

        return $entity;
    }

    public function save($entity, array $context = []) {
        // Pre-save validation hook
        $valid = h('repository.validate_save', [
            'entity' => $entity,
            'context' => $context
        ]);

        if ($valid === false) {
            r("Repository save validation failed", "Error", false, [
                'entity' => $entity,
                'context' => $context
            ]);
            throw new \RuntimeException("Save validation failed");
        }

        // Add phase context to entity
        if (is_array($entity)) {
            $entity['_saved_in_phase'] = $context['_phase'] ?? 'unknown';
            $entity['_saved_at'] = time();
        }

        // Execute save with context
        $result = $this->executeSave($entity, $context);

        // Post-save hook for side effects
        h('repository.post_save', [
            'entity' => $entity,
            'result' => $result,
            'context' => $context
        ]);

        return $result;
    }
}
```

**Interaction with Other Patterns**: Repositories often work with `Proxy` patterns for access-controlled data operations, integrate with `Adapter` patterns for multiple storage backend support, collaborate with `Composite` patterns for hierarchical data structures, and are frequently called by `Controller` and `Endpoint` implementations in the presentation layer. They provide the framework's persistence abstraction layer that separates storage concerns from phase execution logic.

## Framework-Specific Integration Patterns

### Phase-Aware Structural Composition

Structural patterns in Node.php operate within a phase-aware context where:

1. **Phase State as Primary Data**: All structural operations work with phase state arrays rather than isolated objects, enabling coordinated behavior across the execution pipeline.

2. **Hook-Based Composition**: Structural composition uses `h()` hooks for dynamic behavior injection, allowing runtime reconfiguration of structural relationships.

3. **Environment-Driven Configuration**: Structural behavior is configured via `env()` variables, enabling environment-specific structural adaptations.

4. **Integrated State Management**: Structural patterns participate in the framework's state management system, with operations logged via `r()` and state transitions managed through `p()`.

### Structural Patterns and Phase Lifecycle

Different structural interfaces specialize in different phase interactions:

- **Adapters** excel in `transpilate` and `resolve` phases where interface translation is most needed.
- **Composites** work well in `resolve` and `mutate` phases for organizing complex state hierarchies.
- **Decorators** are most active in `execute` and `mutate` phases where behavior extension occurs.
- **Proxies** operate across all phases but are particularly important in `resolve` and `execute` for access control.
- **Repositories** specialize in `persist` and `finalize` phases for state storage operations.

### Hook Integration Strategy for Structural Patterns

Each structural pattern leverages hooks in specific ways:

- **Validation Hooks**: Used by all patterns for operation validation within phase context.
- **Transformation Hooks**: Particularly important for Adapters and Decorators.
- **Access Control Hooks**: Central to Proxy implementations.
- **Persistence Hooks**: Key for Repository operations.
- **Composition Hooks**: Essential for Composite structure management.

### Configuration-Driven Structural Behavior

Structural behavior in Node.php is heavily influenced by configuration:

- **Environment Variables**: `env()` calls determine structural strategies, access rules, and persistence configurations.
- **Phase Context**: Current phase and accumulated state influence structural decisions and operations.
- **Hook Registrations**: Registered hooks provide runtime structural adaptation capabilities.
- **Execution Mode**: Structural behavior may differ between HTTP, CLI, and API execution contexts.

This architecture allows Node.php's structural patterns to provide flexible, phase-aware object composition and relationship management while maintaining tight integration with the framework's core execution model and utilities.
