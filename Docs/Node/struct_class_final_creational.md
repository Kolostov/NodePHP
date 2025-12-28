# Final/Creational Class Patterns

## Overview: Immutable Object Construction Systems

Final creational classes in Node.php provide concrete, non-extendable mechanisms for object construction that integrate seamlessly with the framework's file-based configuration and hook-driven architecture. Unlike abstract creational patterns that define interfaces for extension, these final implementations offer specific, optimized construction strategies that leverage Node.php's utilities (`f()`, `h()`, `env()`, `r()`) while maintaining immutability and predictable behavior across the application. They represent the "construction sites" where domain objects are assembled with framework context and configuration.

### Philosophical Foundation

Node.php's approach to object creation emphasizes configuration over code, immutability over mutability, and framework integration over standalone construction. Final creational classes embody these principles by:

- Reading construction parameters from files via `f()` or environment variables via `env()`
- Integrating with the hook system (`h()`) for extensible construction pipelines
- Providing immutable construction results that cannot be modified post-creation
- Logging construction activities through `r()` for auditability and debugging
- Respecting the framework's phase system (`p()`) when construction has side effects

### Construction Taxonomy in Node.php

```
File-based Construction
├── Builder (complex, multi-step assembly)
└── Factory (simple, direct instantiation)

Hook-integrated Creation
├── Pre-creation hooks (validation, transformation)
├── Creation logic (builder/factory execution)
└── Post-creation hooks (initialization, registration)

Framework-aware Construction
├── Environment configuration (env())
├── File configuration (f())
├── Phase-aware creation (p())
└── Audit logging (r())
```

## Final Creational Class Details

### Final/Creational/Builder

**Purpose**: Concrete, stepwise constructor for assembling complex objects with many moving parts, configuration dependencies, and validation requirements. Builders in Node.php are not just object assemblers but framework-integrated construction pipelines that read blueprints from configuration files, validate each construction step through hooks, and produce immutable domain objects ready for use within the framework's ecosystem.

**Framework Integration Strategy**: Builders leverage `f()` to read construction blueprints from JSON/YAML files, use `h()` to validate each construction step, integrate with `env()` for environment-specific construction rules, and log construction activities through `r()`. They often execute within specific phases (`p()`) to coordinate with other framework activities and can trigger events upon completion through the hook system.

```php
/**
 * DatabaseConnectionBuilder - Constructs database connections with framework integration
 *
 * This builder reads database configuration from multiple sources (env, files),
 * validates each configuration parameter through hooks, assembles the connection
 * with appropriate defaults and extensions, and produces an immutable connection
 * object ready for framework use. All construction steps are logged and can be
 * extended through the hook system.
 */
final class DatabaseConnectionBuilder
{
    private array $config = [];
    private array $validationErrors = [];
    private string $constructionId;

    public function __construct()
    {
        $this->constructionId = uniqid('build_', true);

        // Log builder initialization
        r("Builder initialized: " . static::class, 'Internal', null, [
            'construction_id' => $this->constructionId,
            'node' => NODE_NAME
        ]);
    }

    public function build(): DatabaseConnection
    {
        // Start construction phase
        p('construction.database', function ($phase, $state) {
            $state['construction_id'] = $this->constructionId;
            $state['builder_class'] = static::class;
            $state['start_time'] = microtime(true);

            // Step 1: Load configuration from framework sources
            $this->loadConfiguration();

            // Step 2: Validate through hook system
            $this->validateConfiguration();

            // Step 3: Apply environment-specific transformations
            $this->applyEnvironmentTransformations();

            // Step 4: Create immutable connection object
            $connection = $this->createConnection();

            // Step 5: Register with framework services
            $this->registerWithFramework($connection);

            $state['connection'] = $connection->getId();
            $state['success'] = true;
            $state['duration'] = microtime(true) - $state['start_time'];

            return $state;
        });

        // Retrieve connection from phase state
        $constructionState = p('construction.database');
        $connectionId = $constructionState['connection'] ?? null;

        if (!$connectionId) {
            throw new ConstructionException(
                "Database connection construction failed",
                $this->validationErrors
            );
        }

        // Construction completion event
        h('builder.construction.completed', [
            'builder' => static::class,
            'construction_id' => $this->constructionId,
            'connection_id' => $connectionId,
            'node' => NODE_NAME
        ]);

        return DatabaseConnection::retrieve($connectionId);
    }

    private function loadConfiguration(): void
    {
        // Priority: environment variables > configuration files > defaults

        // 1. Load from environment variables
        $envConfig = [
            'host' => env('NODE:DB_HOST', null),
            'database' => env('NODE:DB_DATABASE', null),
            'username' => env('NODE:DB_USERNAME', null),
            'password' => env('NODE:DB_PASSWORD', null),
            'port' => env('NODE:DB_PORT', 3306)
        ];

        // 2. Load from configuration files (if env not set)
        $fileConfig = [];
        if (!$envConfig['host']) {
            $configFile = f('Config/Database/connections.json', 'find');
            if ($configFile) {
                $fileContent = f($configFile, 'read');
                $fileConfig = json_decode($fileContent, true)['default'] ?? [];
            }
        }

        // 3. Merge with framework defaults
        $this->config = array_merge(
            [
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'timezone' => '+00:00',
                'strict' => true,
                'engine' => 'InnoDB',
                'node_context' => NODE_NAME
            ],
            $fileConfig,
            array_filter($envConfig) // Only include non-null env values
        );

        // Configuration loaded hook for transformations
        $this->config = h('builder.database.config.loaded', $this->config) ?? $this->config;

        r("Configuration loaded for database construction", 'Internal', null, [
            'construction_id' => $this->constructionId,
            'sources' => array_keys(array_filter([
                'environment' => !empty(array_filter($envConfig)),
                'file' => !empty($fileConfig),
                'defaults' => true
            ])),
            'node' => NODE_NAME
        ]);
    }

    private function validateConfiguration(): void
    {
        // Validate each parameter through dedicated hooks
        $validators = [
            'host' => 'builder.database.validate.host',
            'database' => 'builder.database.validate.database',
            'username' => 'builder.database.validate.username',
            'password' => 'builder.database.validate.password'
        ];

        foreach ($validators as $param => $hook) {
            $value = $this->config[$param] ?? null;
            $validationResult = h($hook, [
                'value' => $value,
                'parameter' => $param,
                'construction_id' => $this->constructionId,
                'config' => $this->config
            ]);

            if ($validationResult === false ||
                (is_array($validationResult) && !($validationResult['valid'] ?? true))) {
                $this->validationErrors[$param] = $validationResult['message'] ?? "Invalid $param";

                r("Validation failed for parameter: $param", 'Internal', null, [
                    'construction_id' => $this->constructionId,
                    'parameter' => $param,
                    'value' => $this->obfuscateValue($param, $value),
                    'node' => NODE_NAME
                ]);
            }
        }

        // Overall configuration validation hook
        $overallValidation = h('builder.database.validate.configuration', [
            'config' => $this->config,
            'construction_id' => $this->constructionId,
            'validation_errors' => $this->validationErrors
        ]);

        if (!empty($this->validationErrors)) {
            throw new ValidationException(
                "Database configuration validation failed",
                $this->validationErrors
            );
        }

        r("Configuration validation passed", 'Internal', null, [
            'construction_id' => $this->constructionId,
            'validated_parameters' => count($validators),
            'node' => NODE_NAME
        ]);
    }

    private function createConnection(): DatabaseConnection
    {
        // Apply pre-creation transformations through hooks
        $finalConfig = h('builder.database.pre_creation', $this->config) ?? $this->config;

        // Create immutable connection object
        $connection = new DatabaseConnection(
            $finalConfig['host'],
            $finalConfig['database'],
            $finalConfig['username'],
            $finalConfig['password'],
            [
                'charset' => $finalConfig['charset'],
                'collation' => $finalConfig['collation'],
                'timezone' => $finalConfig['timezone'],
                'strict' => $finalConfig['strict'],
                'engine' => $finalConfig['engine'],
                'metadata' => [
                    'constructed_by' => static::class,
                    'construction_id' => $this->constructionId,
                    'constructed_at' => time(),
                    'node' => NODE_NAME,
                    'config_source' => $finalConfig['node_context'] ?? 'unknown'
                ]
            ]
        );

        // Post-creation hook for initialization
        h('builder.database.post_creation', $connection);

        r("Database connection created successfully", 'Internal', null, [
            'construction_id' => $this->constructionId,
            'connection_id' => $connection->getId(),
            'database' => $finalConfig['database'],
            'host' => $finalConfig['host'],
            'node' => NODE_NAME
        ]);

        return $connection;
    }

    private function registerWithFramework(DatabaseConnection $connection): void
    {
        // Register with framework service system
        h('service.database', $connection);

        // Register connection lifecycle hooks
        h('database.connection.established', function () use ($connection) {
            r("Database connection established", 'Internal', null, [
                'connection_id' => $connection->getId(),
                'node' => NODE_NAME
            ]);
        });

        h('database.connection.closed', function () use ($connection) {
            r("Database connection closed", 'Internal', null, [
                'connection_id' => $connection->getId(),
                'node' => NODE_NAME
            ]);
        });

        r("Database connection registered with framework", 'Internal', null, [
            'construction_id' => $this->constructionId,
            'connection_id' => $connection->getId(),
            'service_hook' => 'service.database',
            'node' => NODE_NAME
        ]);
    }

    private function obfuscateValue(string $param, $value): string
    {
        // Security: obfuscate sensitive values in logs
        if (in_array($param, ['password', 'secret', 'token', 'key'])) {
            return '***' . substr((string) $value, -4);
        }
        return (string) $value;
    }
}

// Usage example within Node.php application
$builder = new DatabaseConnectionBuilder();

// Construction can be extended via hooks
h('builder.database.pre_creation', function ($config) {
    // Add connection pooling for production
    if (env('NODE:ENV') === 'production') {
        $config['pool_size'] = 20;
        $config['pool_timeout'] = 30;
    }
    return $config;
});

h('builder.database.validate.host', function ($validationData) {
    // Custom host validation
    $host = $validationData['value'];
    if (!filter_var($host, FILTER_VALIDATE_DOMAIN) && $host !== 'localhost') {
        return ['valid' => false, 'message' => 'Invalid hostname format'];
    }
    return ['valid' => true];
});

// Build the connection (triggers all hooks and phases)
$connection = $builder->build();
```

**Construction Flow Analysis**:

1. **Initialization**: Builder creates unique construction ID and logs initialization
2. **Configuration Loading**: Hierarchical loading from env → files → defaults
3. **Hook-based Validation**: Each parameter validated through dedicated hooks
4. **Environment Transformation**: Config modified based on NODE:ENV and other variables
5. **Immutable Object Creation**: DatabaseConnection created with framework metadata
6. **Framework Registration**: Connection registered with service system and lifecycle hooks
7. **Completion Event**: Construction completion published via hook system

**Design Considerations**:

- **Immutable Construction**: Once built, objects cannot be modified, ensuring consistency
- **Hook-driven Extensibility**: Every construction step can be extended via hooks
- **Phase-aware Execution**: Construction runs within dedicated framework phase
- **Comprehensive Logging**: Every step logged with construction context
- **Security-aware**: Sensitive data obfuscated in logs
- **Multi-source Configuration**: Environment variables, files, and defaults combined

### Final/Creational/Factory

**Purpose**: Concrete, direct object creator that produces specific types of objects based on input parameters, configuration, or context. Factories in Node.php are not just simple instantiators but framework-integrated object generators that understand the application context, respect environment configurations, and produce objects that are immediately usable within the framework's ecosystem. They handle object variations, dependency resolution, and framework registration in a single, predictable operation.

**Framework Integration Strategy**: Factories use `env()` to determine object variations based on environment, `f()` to load object templates or configurations, `h()` to resolve dependencies and apply transformations, and `r()` to log object creation. They often work in conjunction with the framework's service system and can trigger object lifecycle events through hooks.

```php
/**
 * LoggerFactory - Creates logging instances with framework context
 *
 * This factory produces logger instances configured for specific channels,
 * environments, and use cases. It reads logging configuration from files,
 * respects environment-specific logging levels, integrates with framework
 * hook system for log processing, and ensures all loggers include framework
 * context (node name, request ID, etc.) automatically.
 */
final class LoggerFactory
{
    private static array $registry = [];
    private string $factoryId;

    public function __construct()
    {
        $this->factoryId = uniqid('factory_', true);

        r("Logger factory initialized", 'Internal', null, [
            'factory_id' => $this->factoryId,
            'factory_class' => static::class,
            'node' => NODE_NAME
        ]);
    }

    public function create(string $channel, array $options = []): LoggerInterface
    {
        $creationId = uniqid('create_', true);

        // Pre-creation hook for option transformation
        $options = h('factory.logger.pre_create', [
            'channel' => $channel,
            'options' => $options,
            'factory_id' => $this->factoryId,
            'creation_id' => $creationId
        ])['options'] ?? $options;

        // Check registry for existing logger (singleton pattern per channel)
        $registryKey = $channel . ':' . md5(serialize($options));
        if (isset(self::$registry[$registryKey])) {
            r("Returning cached logger for channel: $channel", 'Internal', null, [
                'creation_id' => $creationId,
                'factory_id' => $this->factoryId,
                'channel' => $channel,
                'node' => NODE_NAME
            ]);
            return self::$registry[$registryKey];
        }

        // Determine logger type based on environment and configuration
        $loggerType = $this->determineLoggerType($channel, $options);

        // Create logger instance through type-specific method
        $logger = match($loggerType) {
            'file' => $this->createFileLogger($channel, $options, $creationId),
            'database' => $this->createDatabaseLogger($channel, $options, $creationId),
            'syslog' => $this->createSyslogLogger($channel, $options, $creationId),
            'null' => $this->createNullLogger($channel, $options, $creationId),
            default => $this->createDefaultLogger($channel, $options, $creationId)
        };

        // Apply framework context to logger
        $this->applyFrameworkContext($logger, $channel, $creationId);

        // Register with framework hook system
        $this->registerLoggerHooks($logger, $channel, $creationId);

        // Cache in registry
        self::$registry[$registryKey] = $logger;

        // Post-creation event
        h('factory.logger.created', [
            'logger' => $logger,
            'channel' => $channel,
            'type' => $loggerType,
            'factory_id' => $this->factoryId,
            'creation_id' => $creationId,
            'node' => NODE_NAME
        ]);

        r("Logger created successfully", 'Internal', null, [
            'creation_id' => $creationId,
            'factory_id' => $this->factoryId,
            'channel' => $channel,
            'type' => $loggerType,
            'cached' => true,
            'node' => NODE_NAME
        ]);

        return $logger;
    }

    private function determineLoggerType(string $channel, array $options): string
    {
        // Priority: options > environment configuration > file configuration > defaults

        // 1. Check options
        if (isset($options['type'])) {
            return $options['type'];
        }

        // 2. Check environment variables
        $envType = env("NODE:LOG_{$channel}_TYPE", null);
        if ($envType) {
            return $envType;
        }

        // 3. Check configuration files
        $configFile = f('Config/Log/channels.json', 'find');
        if ($configFile) {
            $config = json_decode(f($configFile, 'read'), true);
            $fileType = $config[$channel]['type'] ?? $config['default']['type'] ?? null;
            if ($fileType) {
                return $fileType;
            }
        }

        // 4. Environment-based defaults
        $environment = env('NODE:ENV', 'production');
        return match($environment) {
            'local', 'development' => 'file',
            'testing' => 'null',
            'staging', 'production' => env('NODE:LOG_DEFAULT_TYPE', 'file'),
            default => 'file'
        };
    }

    private function createFileLogger(string $channel, array $options, string $creationId): FileLogger
    {
        // Determine log path from framework configuration
        $logPath = $options['path'] ??
                   env("NODE:LOG_{$channel}_PATH", null) ??
                   LOG_PATH . ucfirst($channel) . D;

        // Ensure directory exists using framework file utilities
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
            r("Created log directory: $logPath", 'Internal', null, [
                'creation_id' => $creationId,
                'channel' => $channel,
                'node' => NODE_NAME
            ]);
        }

        // Determine log level from environment
        $level = $options['level'] ??
                env("NODE:LOG_{$channel}_LEVEL", null) ??
                env('NODE:LOG_LEVEL', 'info');

        // Create file logger with framework context
        $logger = new FileLogger(
            $logPath,
            $level,
            [
                'max_files' => env("NODE:LOG_{$channel}_MAX_FILES", 30),
                'max_file_size' => env("NODE:LOG_{$channel}_MAX_SIZE", 10485760), // 10MB
                'date_format' => 'Y-m-d',
                'permissions' => 0644,
                'metadata' => [
                    'channel' => $channel,
                    'created_by' => static::class,
                    'creation_id' => $creationId,
                    'created_at' => time(),
                    'node' => NODE_NAME,
                    'log_path' => $logPath
                ]
            ]
        );

        // File logger specific hooks
        h('factory.logger.file.created', [
            'logger' => $logger,
            'channel' => $channel,
            'path' => $logPath,
            'level' => $level,
            'creation_id' => $creationId
        ]);

        return $logger;
    }

    private function applyFrameworkContext(LoggerInterface $logger, string $channel, string $creationId): void
    {
        // Add framework context to all log entries
        $logger->setContextProcessor(function (array $record) use ($channel) {
            $record['extra']['framework'] = [
                'node' => NODE_NAME,
                'channel' => $channel,
                'timestamp' => time(),
                'memory' => memory_get_usage(true),
                'php_sapi' => PHP_SAPI
            ];

            // Add request context for web requests
            if (PHP_SAPI !== 'cli') {
                $record['extra']['request'] = [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'cli',
                    'uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ];
            }

            // Add session context if available
            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['loggedin'])) {
                $record['extra']['session'] = [
                    'user_id' => $_SESSION['loggedin']['user_id'] ?? null,
                    'session_id' => session_id()
                ];
            }

            return $record;
        });

        r("Framework context applied to logger", 'Internal', null, [
            'creation_id' => $creationId,
            'channel' => $channel,
            'logger_class' => get_class($logger),
            'node' => NODE_NAME
        ]);
    }

    private function registerLoggerHooks(LoggerInterface $logger, string $channel, string $creationId): void
    {
        // Register logger with framework service system
        h("service.logger.{$channel}", $logger);

        // Register log processing hooks
        h("log.{$channel}.before_write", function (array $record) use ($logger, $channel) {
            // Pre-process log entries
            $record = h('log.before_write', $record) ?? $record;
            $record = h("log.before_write.{$channel}", $record) ?? $record;

            // Add creation metadata
            $record['extra']['creation'] = [
                'factory_id' => $this->factoryId,
                'creation_id' => $creationId
            ];

            return $record;
        });

        // Register logger lifecycle hooks
        h("logger.{$channel}.initialized", function () use ($logger, $channel, $creationId) {
            r("Logger initialized: $channel", 'Internal', null, [
                'creation_id' => $creationId,
                'channel' => $channel,
                'logger_id' => $logger->getId(),
                'node' => NODE_NAME
            ]);
        });

        r("Logger hooks registered", 'Internal', null, [
            'creation_id' => $creationId,
            'channel' => $channel,
            'service_hook' => "service.logger.{$channel}",
            'processing_hooks' => ["log.{$channel}.before_write", "logger.{$channel}.initialized"],
            'node' => NODE_NAME
        ]);
    }

    public static function flushRegistry(): void
    {
        $count = count(self::$registry);
        self::$registry = [];

        r("Logger factory registry flushed", 'Internal', null, [
            'loggers_removed' => $count,
            'node' => NODE_NAME
        ]);

        h('factory.logger.registry_flushed', ['count' => $count]);
    }
}

// Usage example within Node.php application
$factory = new LoggerFactory();

// Create loggers with different configurations
$appLogger = $factory->create('application', [
    'level' => 'debug',
    'type' => 'file'
]);

$errorLogger = $factory->create('error', [
    'type' => 'database'  // Errors go to database for analysis
]);

$auditLogger = $factory->create('audit', [
    'type' => 'syslog'  // Audit logs to system log
]);

// Extend factory behavior via hooks
h('factory.logger.pre_create', function ($data) {
    // Add correlation ID to all loggers in web context
    if (PHP_SAPI !== 'cli') {
        $data['options']['correlation_id'] = $_SERVER['HTTP_X_CORRELATION_ID'] ??
                                            uniqid('corr_', true);
    }
    return $data;
});

h('factory.logger.created', function ($data) {
    // Notify monitoring system about new logger
    if (env('NODE:MONITORING_ENABLED', false)) {
        h('monitoring.logger_created', [
            'channel' => $data['channel'],
            'type' => $data['type'],
            'node' => NODE_NAME
        ]);
    }
});

// Retrieve logger from framework service system
$retrievedLogger = h('service.logger.application');
```

**Factory Creation Flow**:

1. **Creation Request**: Factory receives channel and options
2. **Pre-creation Hooks**: Options transformed through hook system
3. **Type Determination**: Logger type determined via env → files → defaults hierarchy
4. **Instance Creation**: Specific creator method called based on type
5. **Framework Context**: Logger configured with node context, request data, etc.
6. **Hook Registration**: Logger registered with service system and lifecycle hooks
7. **Caching**: Logger cached in registry (singleton pattern per configuration)
8. **Post-creation Event**: Creation event published via hooks

**Design Considerations**:

- **Singleton Pattern**: Same configuration produces same instance (cached)
- **Environment-aware**: Logger type and configuration varies by NODE:ENV
- **Framework Context**: All loggers automatically include node context
- **Hook-driven Extensibility**: Creation pipeline extensible at multiple points
- **Multi-channel Support**: Different channels can have different configurations
- **Registry Management**: Central registry with flush capability for testing

## Builder vs. Factory: Framework Perspective

| **Aspect**                  | **Builder in Node.php**                             | **Factory in Node.php**                       |
| --------------------------- | --------------------------------------------------- | --------------------------------------------- |
| **Construction Complexity** | Multi-step, validated assembly                      | Direct, configuration-based creation          |
| **Configuration Sources**   | Environment → Files → Hooks → Defaults              | Options → Environment → Files → Defaults      |
| **Framework Integration**   | Phase-based execution (`p()`)                       | Service registration (`h('service.*')`)       |
| **Output Characteristics**  | Complex domain objects with metadata                | Service instances with framework context      |
| **Caching Strategy**        | No caching (fresh construction each time)           | Singleton registry per configuration          |
| **Primary Use Case**        | Database connections, HTTP clients, complex DTOs    | Loggers, mailers, validators, simple services |
| **Hook Integration Points** | Pre-config, validation, pre-creation, post-creation | Pre-create, type determination, post-create   |
| **Error Handling**          | ValidationException with detailed errors            | Fallback to default type with warning         |

## Complementary Patterns in Node.php

**Service Provider Pattern**: Factories often work with providers to register created services via `h('service.*')` hooks.

**Phase Pattern**: Builders execute within construction phases (`p('construction.*')`) for coordinated assembly.

**Hook Pattern**: Both builders and factories extensively use `h()` for extensibility at every construction step.

**Registry Pattern**: Factories implement internal registry for singleton instances.

**Configuration Pattern**: Both read from hierarchical configuration (env → files → defaults).

**Metadata Pattern**: Created objects include framework metadata (node, creation ID, timestamps).

## Framework Integration Summary

Node.php's final creational patterns are deeply integrated with framework architecture:

1. **File-based Blueprints**: Construction specifications loaded via `f()` from JSON/YAML files
2. **Environment-aware**: `env()` variables drive construction parameters and variations
3. **Hook-driven Pipeline**: `h()` hooks allow extension at every construction step
4. **Phase-coordinated**: Complex construction runs within `p()` phases for predictability
5. **Service Registration**: Created objects automatically registered via `h('service.*')`
6. **Audit Trail**: All construction logged via `r()` with context and metadata
7. **Immutable Results**: Objects are immutable post-construction, ensuring consistency
8. **Framework Context**: All objects include node context and creation metadata

This integration ensures that object construction in Node.php is not just about creating instances, but about creating _framework-aware_ instances that are immediately usable within the application ecosystem, properly configured for their environment, extensible through hooks, and observable through comprehensive logging.
