# Final/Behavioral Class Patterns in NodePHP Framework

## Overview: Concrete Behavioral Implementations

In the NodePHP framework, final behavioral classes are located under `Primitive/Class/Final/Behavioral/` and are concrete, closed-for-modification implementations of behavioral patterns. They leverage framework utilities like `r()` for logging actions, `h()` for event hooks, `f()` for persistence if needed, `p()` for phase integration (e.g., execute for commands), and `env()` for configurable rules. These classes ensure stable behavior, align with structures like `Behavioral/Command` and `Behavioral/Policy`, and prevent inheritance fragility by being final.

### Final Behavioral Class Types Overview

| **Final Class**   | **Behavioral Pattern** | **Responsibility Scope** | **Immutable** | **Typical Instantiation**                  |
| ----------------- | ---------------------- | ------------------------ | ------------- | ------------------------------------------ |
| **Command**       | Command Pattern        | Single action execution  | Often         | Per operation, via `p("execute")`          |
| **Listener**      | Observer Pattern       | Event reaction           | Usually       | Per event type, hooked via `h()`           |
| **Observer**      | Observer Pattern       | State monitoring         | Usually       | Per observation, with `r()` logging        |
| **Policy**        | Strategy Pattern       | Decision making          | Often         | Per rule, configurable via `env()`         |
| **Specification** | Specification Pattern  | Rule evaluation          | Always        | Per criteria, combinable                   |
| **Strategy**      | Strategy Pattern       | Algorithm implementation | Usually       | Per algorithm, swappable                   |
| **Validator**     | Validation Pattern     | Data validation          | Usually       | Per validation rule, with `h()` extensions |

## Final Behavioral Class Details

### Final/Behavioral/Command

**Purpose**: Concrete, non-extendable command objects that encapsulate a specific action. Integrates with `p("execute")` for lifecycle and `r()` for auditing.

```php
<?php declare(strict_types=1);

final class CreateUserCommand
{
    private string $username;
    private string $email;
    private string $password;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $username,
        string $email,
        string $password
    ) {
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = new DateTimeImmutable();
    }

    public function execute(): User
    {
        h('command_pre_execute', $this); // Framework hook
        // Validate input
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid email address");
        }

        if (strlen($this->password) < 8) {
            throw new RuntimeException("Password too short");
        }

        // Business logic
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        $userId = uniqid('user_', true);

        // Create and return user
        $user = new User(
            $userId,
            $this->username,
            $this->email,
            $hashedPassword,
            $this->createdAt
        );
        r("User created: {$userId}", "Audit", null, ['username' => $this->username]);
        p('execute'); // Framework phase
        return $user;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    // No setters - immutable after construction
}
// Usage - cannot be extended, only used as-is
$command = new CreateUserCommand('john_doe', 'john@example.com', 'secure123');
$user = $command->execute();
```

### Final/Behavioral/Listener

**Purpose**: Concrete event listener for specific events. Uses `h()` for registration and `r()` for logging reactions.

```php
<?php declare(strict_types=1);

final class UserRegisteredListener
{
    private EmailService $emailService;
    private Logger $logger;

    public function __construct(
        EmailService $emailService,
        Logger $logger
    ) {
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    public function handle(UserRegisteredEvent $event): void
    {
        // Log the registration
        $this->logger->info("User registered: {$event->getUserId()}");
        r("User registered event handled: {$event->getUserId()}", "Internal");

        // Send welcome email
        $this->emailService->sendWelcomeEmail(
            $event->getEmail(),
            $event->getName()
        );

        // Send admin notification
        $this->emailService->sendAdminNotification(
            'admin@example.com',
            "New user registered: {$event->getUsername()}"
        );

        // Update metrics
        Metrics::increment('users.registered');
    }

    public function supports($event): bool
    {
        return $event instanceof UserRegisteredEvent;
    }

    // No extension allowed - behavior is fixed
}
// Usage - registered with event dispatcher via hook
$listener = new UserRegisteredListener($emailService, $logger);
h('user_registered', [$listener, 'handle']); // Framework hook registration
```

### Final/Behavioral/Observer

**Purpose**: Concrete observer for state changes. Integrates `r()` for monitoring logs and ensures fixed reactions.

```php
<?php declare(strict_types=1);

final class StockLevelObserver
{
    private int $lowStockThreshold;
    private NotificationService $notifier;

    public function __construct(
        int $lowStockThreshold,
        NotificationService $notifier
    ) {
        $this->lowStockThreshold = $lowStockThreshold;
        $this->notifier = $notifier;
    }

    public function update(Product $product): void
    {
        if ($product->getStockLevel() <= $this->lowStockThreshold) {
            $this->handleLowStock($product);
        }

        if ($product->getStockLevel() <= 0) {
            $this->handleOutOfStock($product);
        }

        // Always log the stock level
        $this->logStockLevel($product);
    }

    private function handleLowStock(Product $product): void
    {
        $message = sprintf(
            "Low stock alert: Product '%s' has only %d units left",
            $product->getName(),
            $product->getStockLevel()
        );

        $this->notifier->sendToWarehouse($message);
        $this->notifier->sendToPurchasing($message);

        r($message, "Audit");
    }

    private function handleOutOfStock(Product $product): void
    {
        $message = sprintf(
            "Out of stock: Product '%s' is no longer available",
            $product->getName()
        );

        $this->notifier->sendToManagement($message);
        $this->notifier->sendToSales($message);

        r($message, "Audit");
    }

    private function logStockLevel(Product $product): void
    {
        f('Log/Internal/stock.log', 'write', json_encode([
            'product_id' => $product->getId(),
            'stock_level' => $product->getStockLevel(),
            'timestamp' => time()
        ]) . "\n"); // Framework file append
    }
}
// Usage - attached to observable subject
$observer = new StockLevelObserver(10, $notificationService);
$product->attach($observer);
```

### Final/Behavioral/Policy

**Purpose**: Concrete policy for decision logic. Configurable via `env()` and logs decisions with `r()`.

```php
<?php declare(strict_types=1);

final class DiscountPolicy
{
    private float $baseDiscountRate;
    private int $minimumPurchaseAmount;
    private array $excludedCategories;

    public function __construct(
        float $baseDiscountRate,
        int $minimumPurchaseAmount = 100,
        array $excludedCategories = []
    ) {
        $this->baseDiscountRate = $baseDiscountRate;
        $this->minimumPurchaseAmount = env('DISCOUNT_MIN_AMOUNT', $minimumPurchaseAmount);
        $this->excludedCategories = $excludedCategories;
    }

    public function apply(Order $order): float
    {
        // Check minimum purchase
        if ($order->getTotalAmount() < $this->minimumPurchaseAmount) {
            return 0.0;
        }

        // Check excluded categories
        if ($this->hasExcludedItems($order)) {
            return $this->calculatePartialDiscount($order);
        }

        // Apply full discount
        $discount = $order->getTotalAmount() * $this->baseDiscountRate;
        r("Full discount applied: {$discount}", "Audit", null, ['order_id' => $order->getId()]);
        return $discount;
    }

    private function hasExcludedItems(Order $order): bool
    {
        foreach ($order->getItems() as $item) {
            if (in_array($item->getCategory(), $this->excludedCategories, true)) {
                return true;
            }
        }
        return false;
    }

    private function calculatePartialDiscount(Order $order): float
    {
        $eligibleAmount = 0.0;

        foreach ($order->getItems() as $item) {
            if (!in_array($item->getCategory(), $this->excludedCategories, true)) {
                $eligibleAmount += $item->getTotalPrice();
            }
        }

        $discount = $eligibleAmount * $this->baseDiscountRate;
        r("Partial discount applied: {$discount}", "Audit", null, ['order_id' => $order->getId()]);
        return $discount;
    }

    public function getPolicyDescription(): string
    {
        return sprintf(
            "%.1f%% discount on orders over $%d, excluding categories: %s",
            $this->baseDiscountRate * 100,
            $this->minimumPurchaseAmount,
            implode(', ', $this->excludedCategories)
        );
    }
}
// Usage - injected where discount calculation is needed
$policy = new DiscountPolicy(0.1, 100, ['electronics', 'alcohol']);
$discount = $policy->apply($order);
```

### Final/Behavioral/Specification

**Purpose**: Concrete specification for rule evaluation. Immutable, combinable, and uses `r()` for failed evaluations.

```php
<?php declare(strict_types=1);

final class IsPremiumUserSpecification
{
    private int $minimumAccountAgeDays;
    private float $minimumMonthlySpend;

    public function __construct(
        int $minimumAccountAgeDays = 30,
        float $minimumMonthlySpend = 100.0
    ) {
        $this->minimumAccountAgeDays = $minimumAccountAgeDays;
        $this->minimumMonthlySpend = $minimumMonthlySpend;
    }

    public function isSatisfiedBy(User $user): bool
    {
        $satisfied = $this->isAccountOldEnough($user)
            && $this->hasMinimumSpend($user)
            && $this->isAccountActive($user)
            && !$this->hasChargebacks($user);

        if (!$satisfied) {
            r("User not premium: {$user->getId()}", "Audit");
        }
        return $satisfied;
    }

    private function isAccountOldEnough(User $user): bool
    {
        $accountAge = (new DateTime())->diff($user->getCreatedAt());
        return $accountAge->days >= $this->minimumAccountAgeDays;
    }

    private function hasMinimumSpend(User $user): bool
    {
        $monthlySpend = $user->getMonthlySpend();
        return $monthlySpend >= $this->minimumMonthlySpend;
    }

    private function isAccountActive(User $user): bool
    {
        return $user->isActive()
            && !$user->isSuspended()
            && !$user->isBanned();
    }

    private function hasChargebacks(User $user): bool
    {
        return $user->getChargebackCount() > 0;
    }

    // Specification combination (creates new specification)
    public function and(IsPremiumUserSpecification $other): IsPremiumUserSpecification
    {
        return new IsPremiumUserSpecification(
            max($this->minimumAccountAgeDays, $other->minimumAccountAgeDays),
            max($this->minimumMonthlySpend, $other->minimumMonthlySpend)
        );
    }

    public function or(IsPremiumUserSpecification $other): IsPremiumUserSpecification
    {
        return new IsPremiumUserSpecification(
            min($this->minimumAccountAgeDays, $other->minimumAccountAgeDays),
            min($this->minimumMonthlySpend, $other->minimumMonthlySpend)
        );
    }

    public function not(): IsPremiumUserSpecification
    {
        return new IsPremiumUserSpecification(0, 0.0);
    }
}
// Usage - evaluate users against fixed criteria
$spec = new IsPremiumUserSpecification(30, 100.0);
if ($spec->isSatisfiedBy($user)) {
    $user->upgradeToPremium();
}
```

### Final/Behavioral/Strategy

**Purpose**: Concrete algorithm implementation. Final to prevent changes, swappable via context, with `r()` for performance logging.

```php
<?php declare(strict_types=1);

final class QuickSortStrategy
{
    public function sort(array $data): array
    {
        if (count($data) < 2) {
            return $data;
        }

        $pivot = $data[0];
        $left = $right = [];

        for ($i = 1; $i < count($data); $i++) {
            if ($data[$i] < $pivot) {
                $left[] = $data[$i];
            } else {
                $right[] = $data[$i];
            }
        }

        $sorted = array_merge(
            $this->sort($left),
            [$pivot],
            $this->sort($right)
        );
        r("QuickSort executed", "Internal", null, ['data_size' => count($data)]);
        return $sorted;
    }

    public function getName(): string
    {
        return 'Quick Sort';
    }

    public function getTimeComplexity(): string
    {
        return 'O(n log n) average, O(n²) worst';
    }

    public function getSpaceComplexity(): string
    {
        return 'O(log n)';
    }
}

final class MergeSortStrategy
{
    public function sort(array $data): array
    {
        if (count($data) <= 1) {
            return $data;
        }

        $mid = (int) (count($data) / 2);
        $left = array_slice($data, 0, $mid);
        $right = array_slice($data, $mid);

        $left = $this->sort($left);
        $right = $this->sort($right);

        $sorted = $this->merge($left, $right);
        r("MergeSort executed", "Internal", null, ['data_size' => count($data)]);
        return $sorted;
    }

    private function merge(array $left, array $right): array
    {
        $result = [];

        while (!empty($left) && !empty($right)) {
            if ($left[0] <= $right[0]) {
                $result[] = array_shift($left);
            } else {
                $result[] = array_shift($right);
            }
        }

        return array_merge($result, $left, $right);
    }

    public function getName(): string
    {
        return 'Merge Sort';
    }

    public function getTimeComplexity(): string
    {
        return 'O(n log n)';
    }

    public function getSpaceComplexity(): string
    {
        return 'O(n)';
    }
}
// Usage - strategy selection at runtime
$sorter = new Sorter();
$sorter->setStrategy(new QuickSortStrategy());
$sorted = $sorter->sort([3, 1, 4, 1, 5, 9]);
// Can switch strategy
$sorter->setStrategy(new MergeSortStrategy());
$sorted = $sorter->sort([2, 7, 1, 8, 2, 8]);
```

### Final/Behavioral/Validator

**Purpose**: Concrete validation with fixed rules. Uses `h()` for custom validation hooks and `r()` for errors.

```php
<?php declare(strict_types=1);

final class EmailValidator
{
    private bool $checkMXRecords;
    private bool $allowPlusAddressing;
    private array $allowedDomains;
    private array $blockedDomains;

    public function __construct(
        bool $checkMXRecords = false,
        bool $allowPlusAddressing = true,
        array $allowedDomains = [],
        array $blockedDomains = []
    ) {
        $this->checkMXRecords = env('VALIDATOR_CHECK_MX', $checkMXRecords);
        $this->allowPlusAddressing = $allowPlusAddressing;
        $this->allowedDomains = $allowedDomains;
        $this->blockedDomains = $blockedDomains;
    }

    public function validate(string $email): ValidationResult
    {
        h('validator_pre_validate', ['email' => $email]); // Framework hook
        $errors = [];

        // Basic format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
            return new ValidationResult(false, $errors);
        }

        // Extract domain
        $domain = substr(strrchr($email, '@'), 1);

        // Check allowed/blocked domains
        if (!empty($this->allowedDomains) && !in_array($domain, $this->allowedDomains, true)) {
            $errors[] = 'Domain not allowed';
        }

        if (in_array($domain, $this->blockedDomains, true)) {
            $errors[] = 'Domain is blocked';
        }

        // Check plus addressing
        if (!$this->allowPlusAddressing && strpos($email, '+') !== false) {
            $errors[] = 'Plus addressing not allowed';
        }

        // Check MX records if enabled
        if ($this->checkMXRecords && !$this->hasMXRecords($domain)) {
            $errors[] = 'Domain has no mail server';
        }

        if (!empty($errors)) {
            r("Email validation failed: {$email}", "Error", null, ['errors' => $errors]);
        }

        return new ValidationResult(empty($errors), $errors);
    }

    private function hasMXRecords(string $domain): bool
    {
        return checkdnsrr($domain, 'MX');
    }

    public function sanitize(string $email): string
    {
        $email = trim($email);
        $email = strtolower($email);

        // Remove plus addressing if not allowed
        if (!$this->allowPlusAddressing) {
            $parts = explode('@', $email);
            $local = explode('+', $parts[0])[0];
            $email = $local . '@' . $parts[1];
        }

        return $email;
    }

    public function getValidationRules(): array
    {
        return [
            'check_mx' => $this->checkMXRecords,
            'allow_plus_addressing' => $this->allowPlusAddressing,
            'allowed_domains' => $this->allowedDomains,
            'blocked_domains' => $this->blockedDomains
        ];
    }
}
// Usage - validate data with fixed rules
$validator = new EmailValidator(true, false, ['example.com'], ['temp.com']);
$result = $validator->validate('user@example.com');
if ($result->isValid()) {
    $sanitized = $validator->sanitize('user+tag@example.com');
}
```

## Complementary Patterns in NodePHP

**Command Processor**: Executes final Command objects, integrated with `Console/Command`. **Event Dispatcher**: Triggers final Listener objects via `h()`. **Specification Combinator**: Combines final Specification objects. **Strategy Context**: Uses final Strategy objects, swappable via `env()`. **Validator Chain**: Sequences final Validator objects with `h()`.

## Distinguishing Characteristics

**vs. Abstract Behavioral Classes**: Final classes are concrete; abstracts in `Primitive/Class/Abstract/Behavioral/` for extension. **vs. Behavioral Interfaces**: Final implement behavior; interfaces in `Primitive/Interface/Behavioral/` define contracts. **vs. Service Classes**: Final are single-responsibility; services in `Primitive/Class/Final/Infrastructure/Service` coordinate. **vs. Utility Functions**: Final encapsulate state; utilities in `Primitive/Function/Helper` are stateless. **vs. Value Objects**: Final focus on behavior; value objects in `Primitive/Class/Final/Domain/ValueObject` on data.
