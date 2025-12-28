# Interface/Creational Patterns

## Overview: Creation Contract Definitions

Creational interfaces in Node.php define contracts for object instantiation mechanisms that abstract creation from domain logic. These interfaces enable dependency injection through the framework's `p()` phase system and support `Primitive/Interface/Creational` organization for runtime object construction strategies.

### Creational Interface Types in Node Structure

| **Interface Type** | **Framework Path**                       | **Phase Integration** | **Use with p()**              | **Node Structure Role**          |
| ------------------ | ---------------------------------------- | --------------------- | ----------------------------- | -------------------------------- |
| **Builder**        | `Primitive/Interface/Creational/Builder` | `resolve`, `execute`  | Object construction pipelines | Stepwise complex object creation |
| **Factory**        | `Primitive/Interface/Creational/Factory` | `boot`, `discover`    | Service instantiation         | Runtime type selection           |

## Interface Details Aligned with Node.php

### Interface/Creational/Builder

**Framework Context**: Used in `Primitive/Class/Final/Behavioral` implementations and `Database/Migration` components for constructing complex objects during the `resolve` and `execute` phases.

```php
// Node.php Structure: Primitive/Interface/Creational/Builder.php
namespace Primitive\Interface\Creational;

interface Builder
{
    /**
     * Resets builder state, often used in p('mutate') phase
     */
    public function reset(): void;

    /**
     * Builds a component, returning self for chaining
     * Used with h('build.*') hooks
     */
    public function buildPart($data): self;

    /**
     * Finalizes construction, typically called in p('persist')
     */
    public function getResult(): mixed;
}

// Concrete example from Database/Migration context
class MigrationBuilder implements Primitive\Interface\Creational\Builder
{
    private $operations = [];
    private $table = '';

    public function reset(): void {
        $this->operations = [];
        $this->table = '';
    }

    public function buildPart($data): self {
        // Hook into framework's build system
        h('migration.build', function($op) {
            $this->operations[] = $op;
        })($data);

        return $this;
    }

    public function getResult(): string {
        $sql = "CREATE TABLE {$this->table} (";
        $sql .= implode(', ', $this->operations);
        $sql .= ")";

        // Log via framework's r() function
        r("Migration built: {$sql}", "Internal", $sql, [
            'table' => $this->table,
            'operations' => count($this->operations)
        ]);

        return $sql;
    }
}

// Usage in phase context
p('resolve', function($phase, $state) {
    $builder = new MigrationBuilder();
    $builder->buildPart('id INT AUTO_INCREMENT PRIMARY KEY');
    $builder->buildPart('name VARCHAR(255)');
    return ['migration_sql' => $builder->getResult()];
});
```

### Interface/Creational/Factory

**Framework Context**: Used in `Primitive/Class/Final/Creational` implementations and `Extension/Plugin` systems for runtime object creation during `boot` and `discover` phases.

```php
// Node.php Structure: Primitive/Interface/Creational/Factory.php
namespace Primitive\Interface\Creational;

interface Factory
{
    /**
     * Creates object based on type/context
     * Integrates with env() configuration
     */
    public function create(string $type = null, array $context = []);
}

// Concrete example from Extension/Plugin context
class PluginFactory implements Primitive\Interface\Creational\Factory
{
    public function create(string $type = null, array $context = []) {
        $type = $type ?: env('NODE:DEFAULT_PLUGIN_TYPE', 'core');

        // Use framework's file discovery
        $pluginFile = f("Extension/Plugin/{$type}.php", "find");

        if (!$pluginFile) {
            r("Plugin type not found: {$type}", "Error", null, [
                'context' => $context,
                'available' => $this->discoverPlugins()
            ]);
            throw new \RuntimeException("Plugin type {$type} not found");
        }

        // Include and instantiate via framework's autoload
        include_once $pluginFile;
        $className = "Extension\\Plugin\\" . ucfirst($type);

        return new $className($context);
    }

    private function discoverPlugins(): array {
        // Use NODE_STRUCTURE to discover available plugins
        global $NODE_STRUCTURE;
        return $NODE_STRUCTURE['Extension']['Plugin'] ?? [];
    }
}

// Usage in boot phase
p('boot', function($phase, $state) {
    $factory = new PluginFactory();

    // Create plugins based on configuration
    $plugins = [];
    foreach (env('NODE:ACTIVE_PLUGINS', []) as $pluginType) {
        $plugins[] = $factory->create($pluginType, ['phase' => $phase]);
    }

    return ['plugins' => $plugins];
});
```

## Builder vs. Factory in Node.php Context

| **Aspect**            | **Builder in Node.php**         | **Factory in Node.php**              |
| --------------------- | ------------------------------- | ------------------------------------ |
| **Phase Usage**       | `resolve`, `execute`, `mutate`  | `boot`, `discover`, `transpilate`    |
| **State Integration** | Modifies state in p() phases    | Creates objects for state            |
| **Hook Integration**  | Uses `h('build.*')` hooks       | Uses `h('factory.*')` hooks          |
| **Configuration**     | Uses env() for build parameters | Uses env() for type selection        |
| **Logging**           | Logged via r() with context     | Logged via r() with type info        |
| **Error Handling**    | Rollback via f('rollback')      | Critical failures via r() exceptions |

## Node.php Specific Integration Patterns

### Phase-Driven Creation

```php
// Factory in discover phase
p('discover', function($phase, $state) {
    $factory = new Primitive\Class\Final\Creational\Factory();
    $services = [];

    // Discover services from node.json
    $node = json_decode(f('node.json', 'read'), true);
    foreach ($node['services'] ?? [] as $serviceType) {
        $services[] = $factory->create($serviceType, [
            'phase' => $phase,
            'node_name' => NODE_NAME
        ]);
    }

    return ['discovered_services' => $services];
});

// Builder in mutate phase
p('mutate', function($phase, $state) {
    $builder = new Primitive\Class\Final\Creational\Builder();

    // Build complex object from state
    $result = $builder
        ->buildPartA($state['config'] ?? [])
        ->buildPartB($state['data'] ?? [])
        ->buildPartC($state['transform'] ?? [])
        ->getResult();

    // Store in state for persist phase
    return ['mutated_object' => $result];
});
```

### Hook-Integrated Creation

```php
// Factory with hook support
class HookAwareFactory implements Primitive\Interface\Creational\Factory
{
    public function create(string $type = null, array $context = []) {
        // Allow hooks to modify type selection
        $type = h('factory.type_select', $type);

        // Allow hooks to modify context
        $context = h('factory.context', $context);

        // Create with filtered context
        $instance = $this->createInstance($type, $context);

        // Post-creation hook
        return h('factory.created', $instance);
    }
}

// Builder with hook support
class HookAwareBuilder implements Primitive\Interface\Creational\Builder
{
    public function buildPart($data): self {
        // Hook before building
        $data = h('builder.pre_build', $data);

        // Actual build logic
        $this->parts[] = $data;

        // Hook after building
        h('builder.post_build', ['part' => $data, 'builder' => $this]);

        return $this;
    }
}
```

### Environment-Configured Creation

```php
// Factory using env() configuration
class EnvFactory implements Primitive\Interface\Creational\Factory
{
    public function create(string $type = null, array $context = []) {
        $type = $type ?: env('NODE:CREATION_TYPE', 'default');

        switch ($type) {
            case 'database':
                return $this->createDatabaseInstance($context);
            case 'cache':
                return $this->createCacheInstance($context);
            case 'queue':
                return $this->createQueueInstance($context);
            default:
                // Use fallback from node.json
                $fallback = env('NODE:CREATION_FALLBACK');
                return $this->create($fallback, $context);
        }
    }
}
```

## Complementary Patterns in Node Structure

**Abstract Factory**: Found in `Primitive/Interface/Creational` as extended pattern for related object families. **Director**: Implemented in `Primitive/Class/Final/Coordination` for construction sequencing. **Pool**: Used in `Database/Connection` with factory interfaces for pooled connections. **Prototype**: May be implemented in `Primitive/Class/Final/Creational` for cloning. **Singleton**: Available in `Primitive/Trait/Singleton` for single-instance creation.

## Framework-Specific Considerations

**Phase State Integration**: Both patterns receive and return state in p() phases. **Hook Compatibility**: Must support h() hook system for extensibility. **Error Logging**: Must use r() for consistent logging across phases. **File Operations**: Can use f() for file-based configuration discovery. **Environment Variables**: Should respect env() configuration for type selection. **Rollback Support**: Builders should support f('rollback') for failed constructions. **Node Structure Discovery**: Can use $NODE_STRUCTURE for available types.
