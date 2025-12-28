# Final/Domain Class Patterns

## Overview: Business Logic Core Implementation

Final domain classes in Node.php constitute the heart of the application—the business logic layer that defines what the system _does_ rather than how it does it. These patterns implement Domain-Driven Design (DDD) principles within Node.php's phase-driven execution model, providing concrete, non-extendable implementations that encapsulate business rules, enforce invariants, and maintain data integrity. The domain layer operates within the `p()` phase system, ensuring business operations are transactional, reversible, and deterministic.

### Domain Philosophy in Node.php Context

In Node.php, the domain represents business concepts, rules, and operations that execute within the framework's phase orchestration system. Key principles:

- **Phase-Bound Execution**: Business logic executes within specific `p()` phases (`mutate` for state changes, `persist` for storage)
- **Transactional Consistency**: Complex business operations either complete fully across phases or roll back completely
- **State Isolation**: Each phase operates on a copy of state, preventing corruption during business processing
- **Framework Integration**: Domain objects leverage `h()` for business rule hooks, `r()` for business activity logging, and `f()` for business configuration

The domain layer is intentionally separate from infrastructure concerns, focusing purely on business logic while leveraging framework utilities for cross-cutting concerns.

## Final Domain Class Details

### Final/Domain/Aggregate

**Purpose**: A transactional boundary containing a cluster of related domain objects (entities and value objects) that must change consistently. The aggregate root coordinates all access and enforces business invariants across the entire cluster. In Node.php, aggregates leverage the phase system to ensure all changes within the boundary are atomic—either all succeed and persist in the appropriate phases, or all are rolled back.

**Phase Integration**: Aggregate operations are designed to execute across multiple phases:

- Business validation and state changes occur in `mutate` phase
- Complex coordination happens in `execute` phase
- Final persistence occurs in `persist` phase
- Any phase failure triggers automatic rollback of all aggregate changes

```php
final class OrderAggregate
{
    private Order $root;
    private array $lineItems = [];

    public function placeOrder(Customer $customer, array $items): void
    {
        // Phase 1: Mutation - validate and prepare order
        p('mutate', function ($phaseName, $state) use ($customer, $items) {
            $this->root = Order::create($customer->getId());

            foreach ($items as $item) {
                $lineItem = $this->createLineItem($item);
                $this->validateItemBusinessRules($lineItem);
                $this->lineItems[] = $lineItem;
            }

            $this->enforceOrderInvariants();
            return ['order_id' => $this->root->getId()];
        });

        // Phase 2: Persistence - save after successful mutation
        p('persist', function ($phaseName, $state) {
            $repository = h('service.repository.orders');
            $repository->save($this->root);

            foreach ($this->lineItems as $item) {
                $repository->saveLineItem($item);
            }

            h('domain.order.placed', $this->root);
        });
    }
}
```

**Key Characteristics**:

- **Transactional Boundary**: All changes succeed or fail together
- **Phase-Coordinated**: Operations span appropriate phases
- **Invariant Enforcement**: Business rules validated before phase completion
- **Root-Controlled Access**: All operations go through aggregate root
- **Automatic Rollback**: Phase failures trigger full rollback

### Final/Domain/Entity

**Purpose**: Domain objects with distinct identity that evolve through state transitions governed by business rules. Entities encapsulate both data and behavior, representing the core business concepts that change over time. In Node.php, entities are phase-aware, ensuring state changes occur in appropriate phases and can be rolled back if business rules are violated.

**Phase Integration**: Entity methods validate they're called within correct phases (`mutate` for state changes, `execute` for business operations). They collect validation errors that can trigger phase rollback if business rules are violated.

```php
final class Order extends Entity
{
    private OrderId $id;
    private OrderStatus $status;

    public function cancel(string $reason): void
    {
        // Validate phase context
        $currentPhase = p(':name');
        if (!in_array($currentPhase, ['mutate', 'execute'])) {
            throw new PhaseViolation("Order.cancel() requires mutate or execute phase");
        }

        // Business rule enforcement
        if ($this->status->isShipped()) {
            throw new BusinessRuleViolation("Cannot cancel shipped order");
        }

        // State transition with hook validation
        $validation = h('domain.order.cancel.validate', $this);
        if ($validation['valid'] === false) {
            throw new BusinessRuleViolation($validation['message']);
        }

        $this->status = OrderStatus::CANCELLED();
        h('domain.order.cancelled', $this);
    }
}
```

**Key Characteristics**:

- **Identity-Based**: Compared by ID, not attributes
- **Behavior-Encapsulating**: Methods represent business operations
- **Phase-Aware**: Validates execution context
- **Hook-Validated**: Business rules extensible via `h()`
- **Stateful**: Maintains identity across phase transitions

### Final/Domain/Model

**Purpose**: Persistence-aware domain objects that bridge business logic with data storage while maintaining business behavior. Models in Node.php separate business operations (in `mutate` phase) from persistence operations (in `persist` phase), ensuring business rules are enforced before any data is saved.

**Phase Integration**: Models stage changes during business phases and persist them only in the `persist` phase. This separation ensures business logic completes successfully before any persistence occurs.

```php
final class User extends Model
{
    protected $table = 'users';
    private array $pendingChanges = [];

    public function updateEmail(string $newEmail): void
    {
        // Business logic in mutate phase
        p('mutate', function ($phase, $state) use ($newEmail) {
            $this->validateEmail($newEmail);
            $this->pendingChanges['email'] = $newEmail;
            return ['user_id' => $this->id];
        });
    }

    public function save(): bool
    {
        // Only persist in appropriate phase
        if (p(':name') !== 'persist') return false;

        return p('persist', function ($phase, $state) {
            $connection = h('service.database');
            $connection->update($this->table, $this->pendingChanges, ['id' => $this->id]);
            $this->pendingChanges = [];
            return ['saved' => true];
        });
    }
}
```

**Key Characteristics**:

- **Phase-Separated**: Business logic and persistence in different phases
- **Change Staging**: Accumulates changes for later persistence
- **Persistence Integration**: Knows about tables, columns, relationships
- **Business + Storage**: Combines domain behavior with ORM capabilities
- **Auditable**: All changes tracked and logged

### Final/Domain/Repository

**Purpose**: Domain persistence abstraction that provides collection-like interfaces using domain language rather than technical jargon. Repositories in Node.php are phase-aware, ensuring find operations can occur in any phase but save/delete operations only happen in `persist` phase.

**Phase Integration**: Repositories validate phase context for persistence operations and can stage operations for later execution. They participate in the phase system's transactional guarantees.

```php
final class OrderRepository
{
    private array $stagedSaves = [];

    public function save(Order $order): void
    {
        $currentPhase = p(':name');

        if ($currentPhase !== 'persist') {
            // Stage for later persistence
            $this->stagedSaves[] = $order;
            return;
        }

        // Execute in persist phase
        p('persist', function ($phase, $state) use ($order) {
            $connection = h('service.database');
            $connection->save('orders', $order->toPersistence());
            h('repository.order.saved', $order);
            return ['order_saved' => $order->getId()];
        });
    }

    public function executeStaged(): void
    {
        if (p(':name') !== 'persist') {
            throw new PhaseViolation("Staged operations require persist phase");
        }

        foreach ($this->stagedSaves as $order) {
            $this->save($order);
        }
    }
}
```

**Key Characteristics**:

- **Domain Language Interface**: `find()`, `save()`, `remove()` methods
- **Phase-Aware Persistence**: Validates phase context for operations
- **Operation Staging**: Can defer persistence to appropriate phase
- **Persistence Abstraction**: Hides storage details from domain
- **Transaction Participation**: Integrates with phase rollback/commit

### Final/Domain/Service

**Purpose**: Stateless domain logic orchestration that coordinates multiple entities/aggregates to fulfill complex business use cases. Services in Node.php define and execute multi-phase business workflows, ensuring transactional consistency across complex operations.

**Phase Integration**: Services are the primary orchestrators of multi-phase business processes. They define the phase flow for complex operations and handle phase failures with appropriate rollback and recovery.

```php
final class OrderFulfillmentService
{
    public function fulfillOrder(OrderId $orderId): void
    {
        // Define phase flow for fulfillment
        try {
            p('mutate', fn($phase, $state) => $this->validateOrder($orderId, $state));
            p('execute', fn($phase, $state) => $this->processFulfillment($orderId, $state));
            p('persist', fn($phase, $state) => $this->persistFulfillment($orderId, $state));
        } catch (Throwable $e) {
            // Phase system automatically rolls back state and filesystem
            h('domain.order.fulfillment.failed', ['order_id' => $orderId, 'error' => $e]);
            throw $e;
        }
    }

    private function validateOrder(OrderId $orderId, array $state): array
    {
        $order = h('service.repository.orders')->find($orderId);
        if (!$order->canBeFulfilled()) {
            throw new BusinessRuleViolation("Order cannot be fulfilled");
        }
        return array_merge($state, ['order_valid' => true]);
    }
}
```

**Key Characteristics**:

- **Stateless Coordination**: No internal state, only operation coordination
- **Multi-Phase Orchestration**: Defines and executes phase sequences
- **Business Workflow Management**: Coordinates complex business processes
- **Failure Handling**: Manages phase failures and business exceptions
- **Transaction Definition**: Specifies what constitutes a business transaction

### Final/Domain/ValueObject

**Purpose**: Immutable, identity-less values that represent domain concepts and measurements. ValueObjects in Node.php are inherently phase-safe due to immutability—they can be freely passed between phases without risk of corruption or unwanted side effects.

**Phase Integration**: ValueObjects are designed to be included in phase state safely. Their immutability ensures they cannot be modified during phase execution, making them ideal building blocks for entity state.

```php
final class Money extends ValueObject
{
    private int $amount; // cents
    private string $currency;

    public function __construct(int $amount, string $currency)
    {
        // Business rules in constructor
        if ($amount < 0) throw new BusinessRuleViolation("Amount cannot be negative");
        if (!in_array($currency, ['USD', 'EUR'])) throw new BusinessRuleViolation("Invalid currency");

        $this->amount = $amount;
        $this->currency = $currency;
    }

    // Immutable operations return new instances
    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new BusinessRuleViolation("Currencies must match");
        }
        return new Money($this->amount + $other->amount, $this->currency);
    }

    // Safe for phase state inclusion
    public function toPhaseState(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
            '_immutable' => true
        ];
    }
}
```

**Key Characteristics**:

- **Immutability**: Cannot be modified after creation
- **Value-Based Equality**: Compared by attribute values, not identity
- **Business Rule Encapsulation**: Validation in constructor
- **Phase-Safe**: Can be included in any phase state
- **Composable**: Can be combined to form complex values

## Domain Pattern Phase Relationships

```
Phase: boot/discover
    ↓ Domain objects reconstituted, services initialized
    ↓
Phase: mutate
    ↓ Entities change state, business rules enforced
    ↓
Phase: execute
    ↓ Services coordinate complex operations
    ↓
Phase: persist
    ↓ Repositories save state, aggregates persisted
    ↓
Phase: finalize
    ↓ Domain events published, cleanup performed
```

## Complementary Patterns in Node.php

**Phase Pattern (`p()`)**: Domain operations execute within phase boundaries for transactional consistency.

**Hook Pattern (`h()`)**: Business rule validation and domain events use hook system.

**Repository Pattern**: Provides phase-aware persistence abstraction.

**Value Object Pattern**: Provides immutable building blocks for entity state.

**Aggregate Pattern**: Defines transactional boundaries for business operations.

## Framework Integration Summary

Node.php's domain patterns leverage the framework's unique capabilities:

1. **Phase-Driven Execution**: Business logic executes within `p()` phase system
2. **Transactional Guarantees**: Phase failures trigger automatic rollback
3. **State Isolation**: Each phase operates on state copies
4. **Business Rule Hooks**: Validation extensible via `h()` system
5. **Persistence Separation**: Business logic (`mutate`) separated from storage (`persist`)
6. **Immutable Foundation**: ValueObjects provide phase-safe data building blocks
7. **Audit Trail**: Business activities logged via `r()` with phase context

This integration ensures that business logic in Node.php is not just object-oriented design but is executed within a framework that guarantees consistency, provides transaction boundaries, and separates concerns through its phase-based execution model.
