# Interface/Behavioral Patterns in NodePHP Framework

## Overview: Behavioral Contract Definitions

In the NodePHP framework, behavioral interfaces are defined under `Primitive/Interface/Behavioral/` and provide contracts for algorithms, communication, and interactions that vary independently. These interfaces enable runtime flexibility, promote the Open/Closed Principle, and facilitate the Strategy Pattern, separating behavior from implementation. They integrate with framework utilities like `r()` for interaction logging, `h()` for implementation hooks (e.g., pre-execute), `p()` for phase-dependent behavior (e.g., execute for commands), and `env()` for configurable policies.

### Behavioral Interface Types Overview

| **Interface Type** | **Interaction Pattern**  | **Method Signature**             | **Statefulness**         | **Typical Implementations**                         |
| ------------------ | ------------------------ | -------------------------------- | ------------------------ | --------------------------------------------------- |
| **Command**        | Execute-and-forget       | `execute(): void`                | Stateless/Contextual     | CLI commands in `Console/Command`, Undoable actions |
| **Listener**       | Event subscription       | `handle(Event): void`            | Stateless                | Event handlers hooked via `h()`, Loggers            |
| **Observer**       | State monitoring         | `update(Subject): void`          | Stateless                | UI components, Notifiers with `r()`                 |
| **Policy**         | Decision making          | `apply(Context): mixed`          | Configurable via `env()` | Business rules, Validators in phases                |
| **Specification**  | Rule evaluation          | `isSatisfiedBy(Candidate): bool` | Immutable                | Query filters, Validators combinable                |
| **State**          | State-dependent behavior | `handle(): mixed`                | Stateful                 | State machine states with `p()` transitions         |
| **Strategy**       | Algorithm selection      | `execute(Input): Output`         | Stateless                | Sort algorithms, Payment methods swappable          |

## Interface Details

### Interface/Behavioral/Command

**Purpose**: Defines executable requests, encapsulating actions/parameters. Enables queuing/logging/undo, integrates with `p("execute")` and `h()` for hooks.

```php
<?php declare(strict_types=1);

interface Command
{
    public function execute(): void;
    public function undo(): void; // Optional for undoable commands
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/Command)
final class CreateUserCommand implements Command
{
    private $name;
    private $email;

    public function __construct($name, $email) {
        $this->name = $name;
        $this->email = $email;
    }

    public function execute(): void {
        h('command_pre_execute', $this); // Framework hook
        // Create user logic
        r("Creating user: {$this->name}", "Audit");
        echo "Creating user: {$this->name}\n";
        p('execute'); // Framework phase
    }

    public function undo(): void {
        r("Rolling back user creation: {$this->name}", "Audit");
        echo "Rolling back user creation\n";
        f('rollback'); // Framework rollback
    }
}
```

### Interface/Behavioral/Listener

**Purpose**: Contract for event responses. Decouples producers/consumers, enables event-driven via `h()`, with `r()` for handling logs.

```php
<?php declare(strict_types=1);

interface Listener
{
    public function handle($event): void;
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/Listener)
final class UserRegisteredListener implements Listener
{
    public function handle($event): void {
        // $event contains user data
        r("Handling user registered: {$event['email']}", "Internal");
        echo "Sending welcome email to: {$event['email']}\n";
        // Can dispatch other events or call services via h()
    }
}
```

### Interface/Behavioral/Observer

**Purpose**: Defines notification for state changes. Implements publish-subscribe, with `r()` for updates and `h()` for observer hooks.

```php
<?php declare(strict_types=1);

interface Observer
{
    public function update($subject): void;
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/Observer)
final class StockLevelObserver implements Observer
{
    public function update($subject): void {
        if ($subject->getStock() < 10) {
            r("Low stock alert: {$subject->getStock()} items left", "Audit");
            echo "Low stock alert: {$subject->getStock()} items left\n";
        }
        h('observer_update', $subject); // Framework hook
    }
}
```

### Interface/Behavioral/Policy

**Purpose**: Contract for varying business rules/decisions. Enables runtime selection, configurable via `env()`, with `r()` for application logs.

```php
<?php declare(strict_types=1);

interface Policy
{
    public function apply($context);
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/Policy)
final class DiscountPolicy implements Policy
{
    private $discountRate;

    public function __construct($rate) {
        $this->discountRate = env('DISCOUNT_RATE', $rate);
    }

    public function apply($context) {
        $result = $context['amount'] * $this->discountRate;
        r("Policy applied", "Internal", null, ['result' => $result]);
        return $result;
    }
}
```

### Interface/Behavioral/Specification

**Purpose**: Defines combinable rules for candidate evaluation. Supports AND/OR/NOT, immutable, with `r()` for unsatisfied logs.

```php
<?php declare(strict_types=1);

interface Specification
{
    public function isSatisfiedBy($candidate): bool;
    public function and(Specification $other): Specification;
    public function or(Specification $other): Specification;
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/Specification)
final class PremiumUserSpecification implements Specification
{
    public function isSatisfiedBy($user): bool {
        $satisfied = $user['subscription'] === 'premium' && $user['active'] === true;
        if (!$satisfied) {
            r("Specification not satisfied", "Internal", null, ['user_id' => $user['id']]);
        }
        return $satisfied;
    }

    public function and($other) { /* combine logic */ }
    public function or($other) { /* combine logic */ }
}
```

### Interface/Behavioral/State

**Purpose**: Contract for state-dependent behavior in machines. Each state provides different behavior, with `p("mutate")` for transitions and `r()` for state logs.

```php
<?php declare(strict_types=1);

interface State
{
    public function process($context);
    public function next($context): State;
}

// Concrete implementation (in Primitive/Class/Final/Behavioral/State)
final class OrderProcessingState implements State
{
    public function process($order) {
        r("Processing order #{$order['id']}", "Internal");
        echo "Processing order #{$order['id']}\n";
        // State-specific logic
    }

    public function next($order): State {
        if ($order['payment_received']) {
            p('mutate'); // Framework phase
            return new OrderShippedState();
        }
        return $this; // Stay in current state
    }
}
```

### Interface/Behavioral/Strategy

**Purpose**: Defines interchangeable algorithms. Enables runtime selection, with `h()` for strategy hooks and `r()` for execution logs.

```php
<?php declare(strict_types=1);

interface Strategy
{
    public function execute($input);
}

// Concrete implementations (in Primitive/Class/Final/Behavioral/Strategy)
final class QuickSortStrategy implements Strategy
{
    public function execute($array) {
        h('strategy_pre_execute', $array); // Framework hook
        sort($array);
        r("QuickSort executed", "Internal");
        return $array;
    }
}

final class MergeSortStrategy implements Strategy
{
    public function execute($array) {
        // Merge sort implementation
        r("MergeSort executed", "Internal");
        return $array;
    }
}

// Context class that uses strategy (e.g., in Coordination/)
class Sorter
{
    private $strategy;

    public function setStrategy(Strategy $strategy) {
        $this->strategy = $strategy;
    }

    public function sort($array) {
        return $this->strategy->execute($array);
    }
}
```

## Complementary Patterns

**Command Processor Pattern** uses Command interfaces for queues in `Coordination/Mediator`. **Event Dispatcher Pattern** with Listener interfaces via `h()` for handling. **State Machine Pattern** on State interfaces for switching with `p()`. **Specification Pattern** uses Specification interfaces for rule composition in `Behavioral/Specification`. **Strategy Context** classes utilize Strategy interfaces for selection via `env()`.

## Distinguishing Characteristics

**vs. Structural Interfaces**: Behavioral define interactions; structural in `Structural/` define composition. **vs. Marker Interfaces**: Behavioral have methods; markers none, used in traits. **vs. Service Interfaces**: Behavioral focus algorithms; service in `Infrastructure/` on capabilities. **vs. Template Method**: Behavioral enable runtime swapping; Template Method defines skeleton compile-time in abstracts.
