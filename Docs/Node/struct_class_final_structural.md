# Final/Structural Class Patterns

## Overview: Object Composition & Interface Management

Final structural classes in Node.php provide concrete implementations for organizing objects and interfaces to form larger, more flexible structures. These patterns address how objects are composed and how interfaces are adapted, wrapped, or simplified while maintaining integration with Node.php's framework utilities. Structural patterns are about relationships between objects—how they can be combined to form new functionality without inheritance.

### Structural Design in Node.php Context

Structural patterns in Node.php focus on object composition rather than inheritance, aligning with the framework's preference for composition over inheritance. Key principles:

- **Interface Adaptation**: Making incompatible interfaces work together
- **Behavior Extension**: Adding responsibilities to objects dynamically
- **Complexity Hiding**: Simplifying complex subsystems
- **Access Control**: Controlling object access with additional logic
- **Framework Integration**: Leveraging `h()` for behavior hooks, `r()` for operation logging

These patterns enable flexible object structures that can evolve without modifying existing code.

## Final Structural Class Details

### Final/Structural/Adapter

**Purpose**: Concrete interface translator that converts the interface of a class into another interface clients expect. Adapters in Node.php enable integration between components with incompatible interfaces while maintaining framework context and logging.

**Pattern Relationships**: Adapters wrap **Legacy** or **External** components to make them compatible with **Client** code. They often work with **Service** patterns to integrate external systems and use `h()` for interface transformation hooks.

```php
final class LegacyPaymentAdapter implements PaymentGatewayInterface
{
    private LegacyPaymentSystem $legacySystem;
    private string $adapterId;

    public function __construct(LegacyPaymentSystem $system)
    {
        $this->legacySystem = $system;
        $this->adapterId = uniqid('adapter_', true);

        r("Payment adapter initialized", 'Infrastructure', null, [
            'adapter_id' => $this->adapterId,
            'legacy_system' => get_class($system),
            'node' => NODE_NAME
        ]);
    }

    public function charge(float $amount, array $details): PaymentResult
    {
        // Convert modern interface to legacy format
        $legacyParams = [
            'amt' => $amount,
            'cc' => $details['card_number'],
            'exp' => $details['expiry_date'],
            'cvv' => $details['cvv']
        ];

        // Transform via hooks before legacy call
        $transformedParams = h('adapter.payment.legacy.transform', $legacyParams) ?? $legacyParams;

        // Call legacy system
        $legacyResult = $this->legacySystem->processPayment($transformedParams);

        // Convert legacy result to modern format
        $result = new PaymentResult(
            $legacyResult['success'],
            $legacyResult['transaction_id'],
            $legacyResult['success'] ? 'completed' : 'failed'
        );

        // Log adapter operation
        return r("Payment adapted to legacy system", 'Infrastructure', $result, [
            'adapter_id' => $this->adapterId,
            'amount' => $amount,
            'legacy_success' => $legacyResult['success'],
            'node' => NODE_NAME
        ]);
    }
}
```

**Key Characteristics**:

- **Interface Translation**: Converts between incompatible interfaces
- **Wrapper Pattern**: Wraps existing object with new interface
- **Bidirectional Conversion**: Can adapt in both directions
- **Framework Logging**: All adaptations logged via `r()`
- **Hook Transformation**: Interface conversion customizable via `h()`

### Final/Structural/Decorator

**Purpose**: Behavior-extending wrapper implementation that adds responsibilities to objects dynamically. Decorators in Node.php wrap components to add functionality transparently, supporting recursive composition and framework integration.

**Pattern Relationships**: Decorators wrap **Component** objects to add behavior, can be stacked to combine multiple behaviors, and often work with **Service** patterns. They use `h()` for behavior customization hooks.

```php
final class LoggingDecorator implements DataServiceInterface
{
    private DataServiceInterface $wrappedService;
    private string $decoratorId;

    public function __construct(DataServiceInterface $service)
    {
        $this->wrappedService = $service;
        $this->decoratorId = uniqid('decorator_', true);
    }

    public function fetchData(string $query): array
    {
        $startTime = microtime(true);

        // Log before operation
        r("Data fetch starting", 'Internal', null, [
            'decorator_id' => $this->decoratorId,
            'query' => $query,
            'node' => NODE_NAME
        ]);

        // Transform query via hooks
        $transformedQuery = h('decorator.data.query.transform', $query) ?? $query;

        // Call wrapped service
        $data = $this->wrappedService->fetchData($transformedQuery);

        // Log after operation
        $duration = microtime(true) - $startTime;

        return r("Data fetch completed", 'Internal', $data, [
            'decorator_id' => $this->decoratorId,
            'query' => $query,
            'duration' => $duration,
            'data_count' => count($data),
            'node' => NODE_NAME
        ]);
    }
}

// Usage: Stacking decorators
$service = new CachingDecorator(
    new LoggingDecorator(
        new ValidationDecorator(
            new DataService()
        )
    )
);
```

**Key Characteristics**:

- **Dynamic Extension**: Adds behavior at runtime
- **Transparent Wrapping**: Maintains original interface
- **Stackable Composition**: Multiple decorators can be combined
- **Recursive Structure**: Decorators can wrap other decorators
- **Framework Logging**: Added behaviors logged via `r()`

### Final/Structural/Facade

**Purpose**: Simplified subsystem interface that provides a unified, higher-level interface to a set of interfaces in a subsystem. Facades in Node.php hide complexity, reduce coupling, and provide framework-integrated access to complex subsystems.

**Pattern Relationships**: Facades provide simple interfaces to **Complex Subsystems** composed of multiple **Service** and **Component** patterns. They coordinate multiple operations and expose simplified methods to **Client** code.

```php
final class UserManagementFacade
{
    private UserService $userService;
    private EmailService $emailService;
    private AuditService $auditService;
    private string $facadeId;

    public function __construct()
    {
        // Get services from framework hooks
        $this->userService = h('service.user');
        $this->emailService = h('service.email');
        $this->auditService = h('service.audit');
        $this->facadeId = uniqid('facade_', true);

        r("User management facade initialized", 'Infrastructure', null, [
            'facade_id' => $this->facadeId,
            'node' => NODE_NAME
        ]);
    }

    public function registerUser(array $userData): User
    {
        // Single method hides complex multi-service operation

        // 1. Create user via UserService
        $user = $this->userService->create($userData);

        // 2. Send welcome email via EmailService
        $this->emailService->sendWelcomeEmail($user->email, $user->name);

        // 3. Log audit via AuditService
        $this->auditService->logRegistration($user->id, $_SERVER['REMOTE_ADDR'] ?? 'cli');

        // 4. Update metrics via hook system
        h('facade.user.registered', $user);

        // Log facade operation
        return r("User registration via facade", 'Infrastructure', $user, [
            'facade_id' => $this->facadeId,
            'user_id' => $user->id,
            'services_used' => ['user', 'email', 'audit'],
            'node' => NODE_NAME
        ]);
    }

    public function deactivateUser(string $userId, string $reason): bool
    {
        // Another simplified complex operation
        $success = $this->userService->deactivate($userId, $reason);

        if ($success) {
            $this->auditService->logDeactivation($userId, $reason);
            h('facade.user.deactivated', ['user_id' => $userId, 'reason' => $reason]);
        }

        return r("User deactivation via facade", 'Infrastructure', $success, [
            'facade_id' => $this->facadeId,
            'user_id' => $userId,
            'reason' => $reason,
            'success' => $success
        ]);
    }
}
```

**Key Characteristics**:

- **Complexity Hiding**: Simplifies complex subsystem interactions
- **Unified Interface**: Single entry point to multiple services
- **Operation Coordination**: Orchestrates multiple operations
- **Reduced Coupling**: Clients depend only on facade, not subsystem
- **Framework Logging**: All facade operations logged via `r()`

### Final/Structural/Proxy

**Purpose**: Concrete access surrogate that controls access to another object. Proxies in Node.php add access control, lazy initialization, logging, or other behavior when accessing objects, integrating with framework utilities.

**Pattern Relationships**: Proxies stand in for **Real Subject** objects, controlling access to **Expensive Resources** or **Sensitive Operations**. They can work with **Service** patterns and use `h()` for access control hooks.

```php
final class ImageProxy implements ImageInterface
{
    private ?Image $realImage = null;
    private string $filename;
    private string $proxyId;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
        $this->proxyId = uniqid('proxy_', true);

        r("Image proxy created", 'Infrastructure', null, [
            'proxy_id' => $this->proxyId,
            'filename' => $filename,
            'node' => NODE_NAME
        ]);
    }

    public function display(): string
    {
        // Lazy initialization: only load image when needed
        if ($this->realImage === null) {
            r("Loading real image (lazy initialization)", 'Internal', null, [
                'proxy_id' => $this->proxyId,
                'filename' => $this->filename
            ]);

            $this->realImage = new Image($this->filename);

            // Hook for post-initialization processing
            h('proxy.image.initialized', [
                'proxy_id' => $this->proxyId,
                'filename' => $this->filename,
                'image_size' => $this->realImage->getSize()
            ]);
        }

        // Access control: check permissions via hooks
        $accessAllowed = h('proxy.image.access.check', [
            'filename' => $this->filename,
            'proxy_id' => $this->proxyId
        ]) ?? true;

        if (!$accessAllowed) {
            return r("Image access denied via proxy", 'Access',
                "Access denied to image: {$this->filename}",
                ['proxy_id' => $this->proxyId, 'filename' => $this->filename]
            );
        }

        // Log access via proxy
        r("Image accessed via proxy", 'Internal', null, [
            'proxy_id' => $this->proxyId,
            'filename' => $this->filename,
            'access_time' => time()
        ]);

        return $this->realImage->display();
    }

    public function getDimensions(): array
    {
        // Virtual proxy: return metadata without loading full image
        if ($this->realImage === null) {
            // Get dimensions from file headers without full load
            $dimensions = getimagesize($this->filename);

            return r("Image dimensions via proxy (virtual)", 'Internal', [
                'width' => $dimensions[0],
                'height' => $dimensions[1]
            ], [
                'proxy_id' => $this->proxyId,
                'filename' => $this->filename,
                'virtual_access' => true
            ]);
        }

        return $this->realImage->getDimensions();
    }
}
```

**Key Characteristics**:

- **Access Control**: Controls access to real object
- **Lazy Initialization**: Defers expensive object creation
- **Virtual Proxy**: Can provide partial functionality without full object
- **Protection Proxy**: Adds security/permission checks
- **Framework Logging**: All proxy operations logged via `r()`

## Structural Pattern Relationships

```
Client Code
    ↓
Facade (simplifies complex subsystem)
    ↓
Adapter (translates interfaces)
    ↓
Proxy (controls access)
    ↓
Decorator (adds behavior)
    ↓
Real Component/Service
```

## Pattern Combinations in Node.php

**Adapter + Decorator**: An adapter that also adds logging or caching behavior
**Facade + Proxy**: A facade that controls access to the subsystem
**Proxy + Decorator**: A proxy that adds behavior when controlling access

## Complementary Patterns

**Service Pattern**: Often wrapped or adapted by structural patterns
**Hook Pattern (`h()`)**: Used for behavior customization in structural patterns
**Logging Pattern (`r()`)**: All structural operations logged appropriately
**Configuration Pattern**: Structural behavior configurable via `env()` and `f()`

## Framework Integration

Structural patterns in Node.php integrate with framework systems:

- **Hook Integration**: Behavior customization via `h()` hooks
- **Structured Logging**: All operations logged via `r()` with appropriate types
- **Configuration Awareness**: Behavior configurable via framework configuration
- **Context Propagation**: Includes NODE_NAME and operation IDs in logs
- **Error Handling**: Graceful degradation with proper error logging

These patterns enable flexible, maintainable object structures that can evolve with application needs while maintaining consistency with Node.php's architectural approach.
