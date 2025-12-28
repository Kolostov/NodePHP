# Trait Patterns

## Overview: Horizontal Behavior Reuse

Trait patterns in Node.php provide horizontal reuse mechanisms for sharing behavior across class hierarchies within the framework's phase-aware architecture. Unlike traditional PHP traits that operate in isolation, Node.php traits are designed to integrate with the framework's execution model, leveraging `env()` configuration, `h()` hooks, and `r()` logging to provide phase-aware behavior composition. These patterns enable classes to opt into specific framework capabilities while maintaining consistency with Node.php's state management and phase execution flow.

Traits in Node.php serve as behavioral building blocks that can be mixed into classes participating in the framework's phase system, allowing for consistent implementation of cross-cutting concerns while preserving the ability to work within specific phase contexts and hook integration points.

### Trait Integration with Node.php Architecture

| **Trait Type**    | **Primary Phase**    | **State Interaction**          | **Hook Integration**         | **Framework Role**             |
| ----------------- | -------------------- | ------------------------------ | ---------------------------- | ------------------------------ |
| **Capability**    | `execute`, `mutate`  | Adds phase-aware capabilities  | `h('capability.*', $state)`  | Opt-in feature injection       |
| **Concern**       | `resolve`, `persist` | Implements cross-cutting logic | `h('concern.*', $operation)` | Shared implementation patterns |
| **Mixin**         | All phases           | Provides stateless utilities   | `h('mixin.*', $input)`       | Pure helper logic composition  |
| **Singleton**     | `boot`, `resolve`    | Manages phase-aware instances  | `h('singleton.*', $access)`  | Phase-scoped single instance   |
| **SoftDeletes**   | `mutate`, `persist`  | Adds deletion state tracking   | `h('softdelete.*', $action)` | Phase-aware soft deletion      |
| **Timestampable** | All phases           | Adds phase timing metadata     | `h('timestamp.*', $event)`   | Phase execution timing         |

## Trait Details and Framework Integration

### Trait/Capability

**Purpose**: Adds optional, self-contained capabilities to classes that can "opt-in" to specific behaviors within Node.php's phase execution context. Capability traits represent features that classes can selectively include to gain phase-aware functionality like logging, caching, or notification systems that integrate with the framework's state management and hook architecture.

**Framework Integration**: Capability traits use `h()` hooks for behavior extension points, leverage `env()` configuration for capability settings, and integrate with `r()` logging for capability-specific diagnostics. They allow classes to selectively participate in the framework's cross-cutting concerns while maintaining phase awareness.

```php
namespace Primitive\Trait;

trait Loggable
{
    private array $phaseLogs = [];

    /**
     * Logs message with phase context using framework's r() function
     * Respects env('NODE:LOGGING_LEVEL') configuration
     */
    public function logPhase(string $message, string $level = 'Internal'): void {
        $phase = $this->getCurrentPhase();
        $timestamp = microtime(true);

        // Use hook for log enhancement
        $logEntry = h('capability.log.pre', [
            'message' => $message,
            'level' => $level,
            'phase' => $phase,
            'timestamp' => $timestamp,
            'object' => $this
        ]);

        // Store in trait state
        $this->phaseLogs[] = $logEntry ?? [
            'phase' => $phase,
            'message' => $message,
            'level' => $level,
            'timestamp' => $timestamp
        ];

        // Also use framework logging if level allows
        $minLevel = env('NODE:LOGGING_LEVEL', 'Internal');
        if ($this->shouldLog($level, $minLevel)) {
            r($message, $level, null, [
                'phase' => $phase,
                'object_class' => get_class($this)
            ]);
        }

        // Post-log hook
        h('capability.log.post', $this->phaseLogs);
    }

    /**
     * Retrieves logs filtered by current phase context
     * Uses h('capability.log.retrieve') for filtering
     */
    public function getPhaseLogs(?string $phase = null): array {
        $filtered = $phase
            ? array_filter($this->phaseLogs, fn($log) => $log['phase'] === $phase)
            : $this->phaseLogs;

        return h('capability.log.retrieve', $filtered) ?? $filtered;
    }

    private function getCurrentPhase(): string {
        return $GLOBALS['NODE_PHASE'] ?? 'unknown';
    }

    private function shouldLog(string $level, string $minLevel): bool {
        $levels = ['Debug', 'Internal', 'Access', 'Error', 'Emergency'];
        $levelIndex = array_search($level, $levels);
        $minIndex = array_search($minLevel, $levels);

        return $levelIndex !== false && $minIndex !== false && $levelIndex >= $minIndex;
    }
}

// Usage in phase-aware class
class PhaseService {
    use Primitive\Trait\Loggable;

    public function execute(array $state): array {
        $this->logPhase("Service execution started", "Internal");

        // Process state...
        $result = h('service.process', $state) ?? $state;

        $this->logPhase("Service execution completed", "Internal");
        return $result;
    }
}
```

**Interaction with Other Patterns**: Capability traits often work with `Decorator` patterns to add layered behavior, integrate with `Proxy` patterns for access-controlled capabilities, and are used by `Controller` and `Endpoint` implementations in the presentation layer to add consistent cross-cutting functionality.

### Trait/Concern

**Purpose**: Provides shared implementation for cross-cutting concerns that multiple classes need identically within Node.php's phase execution model. Concern traits handle common responsibilities like validation, authorization, or state transformation that cut across domain boundaries while maintaining phase awareness and hook integration.

**Framework Integration**: Concern traits integrate with the `p()` phase system for state validation, use `h()` hooks for concern-specific behavior customization, leverage `env()` configuration for concern settings, and utilize `r()` logging for concern execution tracing.

```php
namespace Primitive\Trait;

trait PhaseValidatable
{
    /**
     * Validates state within current phase context
     * Uses h('concern.validate') hooks for rule processing
     */
    public function validatePhaseState(array $state): bool {
        // Get validation rules from environment or class
        $rules = $this->getValidationRules();

        // Pre-validation hook
        $validationContext = h('concern.validate.pre', [
            'state' => $state,
            'rules' => $rules,
            'phase' => $this->getCurrentPhase()
        ]);

        $errors = [];

        foreach ($validationContext['rules'] as $field => $rule) {
            $valid = $this->validateField(
                $validationContext['state'][$field] ?? null,
                $rule,
                $field
            );

            if (!$valid) {
                $errors[$field] = "Validation failed: {$field} against {$rule}";
            }
        }

        // Post-validation hook can modify errors
        $errors = h('concern.validate.post', $errors) ?? $errors;

        if (!empty($errors)) {
            r("Phase validation failed", "Internal", false, [
                'errors' => $errors,
                'phase' => $validationContext['phase'],
                'state_keys' => array_keys($validationContext['state'])
            ]);

            // Store errors for retrieval
            $this->lastValidationErrors = $errors;
            return false;
        }

        return true;
    }

    /**
     * Retrieves validation rules based on phase and environment
     */
    private function getValidationRules(): array {
        $phase = $this->getCurrentPhase();
        $envRules = env("NODE:VALIDATION_RULES_{$phase}", []);

        // Hook for dynamic rule generation
        $rules = h('concern.rules.get', [
            'phase' => $phase,
            'env_rules' => $envRules,
            'class' => get_class($this)
        ]);

        return $rules['rules'] ?? $envRules;
    }
}
```

**Interaction with Other Patterns**: Concern traits are used extensively by `Controller` and `Endpoint` implementations for input validation, work with `Repository` patterns for data integrity concerns, and integrate with `Middleware` patterns for request validation in the execution pipeline.

### Trait/Mixin

**Purpose**: Provides pure, stateless helper logic without identity or dependencies that can be mixed into any phase-aware class. Mixin traits in Node.php contain reusable algorithms and utilities that integrate with the framework's execution context, providing phase-aware helper functions for common operations like state transformation, string manipulation, or mathematical calculations.

**Framework Integration**: Mixin traits leverage `h()` hooks for utility customization, use `env()` configuration for behavior settings, and integrate with `r()` logging for diagnostic operations when configured for verbose mode.

```php
namespace Primitive\Trait;

trait PhaseUtilities
{
    /**
     * Transforms state keys based on phase naming conventions
     * Uses h('mixin.transform.keys') for custom transformations
     */
    public function transformStateKeys(array $state, string $convention = 'snake'): array {
        $phase = $this->getCurrentPhase();
        $transformed = [];

        foreach ($state as $key => $value) {
            $newKey = match($convention) {
                'snake' => $this->toSnakeCase($key),
                'camel' => $this->toCamelCase($key),
                'phase_prefixed' => "{$phase}_{$key}",
                default => $key
            };

            // Hook for key transformation
            $newKey = h('mixin.transform.key', [
                'original' => $key,
                'transformed' => $newKey,
                'convention' => $convention,
                'phase' => $phase
            ])['transformed'] ?? $newKey;

            $transformed[$newKey] = $value;
        }

        return $transformed;
    }

    /**
     * Merges multiple phase states with conflict resolution
     * Uses h('mixin.merge.conflict') for conflict handling
     */
    public function mergePhaseStates(array ...$states): array {
        $merged = [];

        foreach ($states as $state) {
            foreach ($state as $key => $value) {
                if (array_key_exists($key, $merged)) {
                    // Conflict resolution hook
                    $resolved = h('mixin.merge.conflict', [
                        'key' => $key,
                        'existing' => $merged[$key],
                        'new' => $value,
                        'states_count' => count($states)
                    ]);

                    $merged[$key] = $resolved['value'] ?? $value; // Default to new value
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }
}
```

**Interaction with Other Patterns**: Mixin traits are used by virtually all patterns in the framework for utility operations, particularly by `Adapter` patterns for data transformation, `Composite` patterns for tree operations, and `Decorator` patterns for state manipulation utilities.

### Trait/Singleton

**Purpose**: Implements the singleton pattern with phase awareness, ensuring a class has only one instance per phase context and providing controlled access points that respect phase boundaries. Singleton traits in Node.php manage instance lifecycle with phase sensitivity, allowing different instances or configurations per execution phase.

**Framework Integration**: Singleton traits integrate with the `p()` phase system for instance management, use `h()` hooks for instance creation and destruction events, leverage `env()` configuration for singleton behavior settings, and utilize `r()` logging for instance lifecycle tracking.

```php
namespace Primitive\Trait;

trait PhaseSingleton
{
    private static array $phaseInstances = [];

    /**
     * Retrieves singleton instance for current phase
     * Uses h('singleton.get') hooks for instance customization
     */
    public static function getPhaseInstance() {
        $phase = self::getCurrentPhase();
        $className = static::class;

        if (!isset(self::$phaseInstances[$phase][$className])) {
            // Pre-creation hook
            $creationContext = h('singleton.create.pre', [
                'phase' => $phase,
                'class' => $className
            ]);

            // Create instance
            self::$phaseInstances[$phase][$className] = new static();

            // Post-creation hook
            h('singleton.create.post', [
                'instance' => self::$phaseInstances[$phase][$className],
                'phase' => $phase,
                'class' => $className
            ]);

            r("Singleton created for phase: {$phase}", "Internal", null, [
                'class' => $className
            ]);
        }

        return self::$phaseInstances[$phase][$className];
    }

    /**
     * Clears singleton instance for specific phase
     * Uses h('singleton.clear') hooks for cleanup
     */
    public static function clearPhaseInstance(?string $phase = null): void {
        $phase = $phase ?? self::getCurrentPhase();
        $className = static::class;

        if (isset(self::$phaseInstances[$phase][$className])) {
            // Pre-clear hook
            h('singleton.clear.pre', [
                'instance' => self::$phaseInstances[$phase][$className],
                'phase' => $phase,
                'class' => $className
            ]);

            unset(self::$phaseInstances[$phase][$className]);

            // Post-clear hook
            h('singleton.clear.post', [
                'phase' => $phase,
                'class' => $className
            ]);

            r("Singleton cleared for phase: {$phase}", "Internal");
        }
    }

    /**
     * Resets all singleton instances (typically at phase completion)
     */
    public static function resetAllPhaseInstances(): void {
        foreach (self::$phaseInstances as $phase => $instances) {
            self::clearPhaseInstance($phase);
        }

        h('singleton.reset.all');
    }
}
```

**Interaction with Other Patterns**: Singleton traits are commonly used by `Repository` implementations for shared data access points, by `Controller` patterns for shared request handlers, and in conjunction with `Proxy` patterns for controlled access to singleton resources.

### Trait/SoftDeletes

**Purpose**: Adds soft deletion behavior to phase-aware models, allowing records to be marked as deleted within specific phase contexts without permanent removal. This trait integrates with Node.php's phase execution model to provide phase-specific deletion tracking and restoration capabilities.

**Framework Integration**: SoftDeletes traits integrate with the `p()` phase system for deletion state management, use `h()` hooks for deletion validation and side effects, leverage `env()` configuration for deletion behavior settings, and utilize `r()` logging for audit trails of deletion operations.

```php
namespace Primitive\Trait;

trait PhaseSoftDeletes
{
    private ?int $deletedInPhase = null;
    private ?string $deletionPhase = null;
    private array $deletionContext = [];

    /**
     * Marks entity as deleted within current phase context
     * Uses h('softdelete.mark') hooks for validation and side effects
     */
    public function markAsDeleted(array $context = []): bool {
        $phase = $this->getCurrentPhase();

        // Pre-deletion hook can prevent deletion
        $allow = h('softdelete.mark.pre', [
            'entity' => $this,
            'phase' => $phase,
            'context' => $context
        ]);

        if ($allow === false) {
            r("Soft delete prevented by hook", "Audit", false, [
                'phase' => $phase,
                'entity_class' => get_class($this)
            ]);
            return false;
        }

        $this->deletedInPhase = time();
        $this->deletionPhase = $phase;
        $this->deletionContext = $context;

        // Post-deletion hook for side effects
        h('softdelete.mark.post', [
            'entity' => $this,
            'phase' => $phase,
            'deleted_at' => $this->deletedInPhase
        ]);

        r("Entity soft-deleted in phase: {$phase}", "Audit", true, [
            'entity_class' => get_class($this),
            'context' => $context
        ]);

        return true;
    }

    /**
     * Restores entity if deletion occurred in compatible phase
     * Uses h('softdelete.restore') hooks for validation
     */
    public function restoreFromDeletion(): bool {
        if (!$this->isDeleted()) {
            return false;
        }

        $phase = $this->getCurrentPhase();

        // Check if restoration is allowed from this phase
        $allowed = h('softdelete.restore.allowed', [
            'deletion_phase' => $this->deletionPhase,
            'current_phase' => $phase,
            'entity' => $this
        ]);

        if ($allowed !== false) {
            $this->deletedInPhase = null;
            $this->deletionPhase = null;
            $this->deletionContext = [];

            h('softdelete.restore.complete', [
                'entity' => $this,
                'phase' => $phase
            ]);

            r("Entity restored from deletion", "Audit", true, [
                'entity_class' => get_class($this),
                'restored_in_phase' => $phase
            ]);

            return true;
        }

        return false;
    }
}
```

**Interaction with Other Patterns**: SoftDeletes traits work closely with `Repository` patterns for persistence operations, integrate with `Proxy` patterns for access control around deleted entities, and are often used in conjunction with `Timestampable` traits for complete audit trails.

### Trait/Timestampable

**Purpose**: Automatically manages creation and update timestamps with phase context awareness, providing audit trail functionality that tracks when operations occur within specific execution phases. This trait integrates with Node.php's phase system to provide fine-grained timing information for phase execution analysis.

**Framework Integration**: Timestampable traits integrate with the `p()` phase system for timing operations, use `h()` hooks for timestamp customization and validation, leverage `env()` configuration for timestamp formatting and precision settings, and utilize `r()` logging for timing diagnostics.

```php
namespace Primitive\Trait;

trait PhaseTimestampable
{
    private array $phaseTimestamps = [];
    private bool $autoTimestamp = true;

    /**
     * Records timestamp for current phase operation
     * Uses h('timestamp.record') hooks for timestamp customization
     */
    public function timestampPhase(string $operation = 'update'): void {
        if (!$this->autoTimestamp) {
            return;
        }

        $phase = $this->getCurrentPhase();
        $timestamp = microtime(true);

        $record = h('timestamp.record', [
            'phase' => $phase,
            'operation' => $operation,
            'timestamp' => $timestamp,
            'object' => $this
        ]) ?? [
            'phase' => $phase,
            'operation' => $operation,
            'timestamp' => $timestamp,
            'human' => date('Y-m-d H:i:s', (int)$timestamp)
        ];

        $this->phaseTimestamps[] = $record;

        // Log timing if configured
        if (env('NODE:LOG_TIMESTAMPS', false)) {
            r("Phase timestamp recorded", "Internal", null, [
                'phase' => $phase,
                'operation' => $operation,
                'timestamp' => $timestamp
            ]);
        }
    }

    /**
     * Retrieves timestamps filtered by phase and/or operation
     * Uses h('timestamp.retrieve') hooks for filtering
     */
    public function getPhaseTimestamps(
        ?string $phase = null,
        ?string $operation = null
    ): array {
        $filtered = $this->phaseTimestamps;

        if ($phase !== null) {
            $filtered = array_filter($filtered, fn($t) => $t['phase'] === $phase);
        }

        if ($operation !== null) {
            $filtered = array_filter($filtered, fn($t) => $t['operation'] === $operation);
        }

        return h('timestamp.retrieve', $filtered) ?? $filtered;
    }

    /**
     * Calculates duration between phase operations
     * Useful for phase performance analysis
     */
    public function getPhaseDuration(string $startPhase, string $endPhase): ?float {
        $startTimestamps = $this->getPhaseTimestamps($startPhase);
        $endTimestamps = $this->getPhaseTimestamps($endPhase);

        if (empty($startTimestamps) || empty($endTimestamps)) {
            return null;
        }

        $start = end($startTimestamps)['timestamp'];
        $end = reset($endTimestamps)['timestamp'];

        return $end - $start;
    }
}
```

**Interaction with Other Patterns**: Timestampable traits are used extensively by `Controller` and `Endpoint` implementations for request timing, work with `Repository` patterns for data change tracking, integrate with `Middleware` patterns for pipeline timing, and complement `SoftDeletes` traits for complete audit trails.

## Framework Integration Strategy

Traits in Node.php follow a consistent integration strategy with the framework's core systems:

1. **Phase Awareness**: All traits are designed to be phase-aware, using the current execution phase from the `p()` system to contextualize their behavior.

2. **Hook Integration**: Each trait provides specific hook points (`h()`) that allow for behavior customization and extension without modifying the trait itself.

3. **Configuration Driven**: Trait behavior is configurable via `env()` settings, allowing runtime adaptation without code changes.

4. **Consistent Logging**: Traits use `r()` logging with appropriate log types and context for diagnostic and audit purposes.

5. **State Management**: Traits that manage state do so in a way that integrates with the framework's phase state model, ensuring consistency across the execution pipeline.

This integration approach ensures that traits provide reusable behavior while maintaining full compatibility with Node.php's phase-based execution model and architectural patterns.
