# Enumeration Patterns in NodePHP Framework

## Overview: Type-Safe Constant Systems

In the NodePHP framework, enumerations are defined under `Primitive/Enum/` as final classes with constants, providing type-safe, self-documenting sets of values. They integrate with framework utilities like `r()` for value logging, `h()` for usage hooks (e.g., pre-validation), `env()` for configurable overrides, and `p()` for phase-dependent states (e.g., execute for transitions). Each category ensures compile-time safety and runtime clarity, supporting domains from HTTP to business logic, with methods for validation and operations.

### Enumeration Categories Overview

| **Enum Type**  | **Domain Context**     | **Value Nature** | **Mutability**              | **Common Operations**                                          |
| -------------- | ---------------------- | ---------------- | --------------------------- | -------------------------------------------------------------- |
| **Http**       | Network Communication  | Protocol-defined | Immutable                   | Response handling, Request routing via `h("http_pre_request")` |
| **Permission** | Security/Authorization | Action-based     | Runtime mutable via `env()` | Access checks, Policy evaluation with logging                  |
| **Policy**     | Business Rules         | Rule selection   | Configurable                | Decision making, Rule application in phases                    |
| **Role**       | User Management        | Hierarchy-based  | Admin mutable               | Access control, UI rendering hooked via `h()`                  |
| **State**      | Lifecycle Management   | Sequential flow  | Transitional                | State validation, Workflow control with `p()`                  |
| **Status**     | Operational Health     | Outcome-based    | Volatile                    | Monitoring, Error handling logged via `r()`                    |
| **Type**       | Classification         | Categorical      | Stable                      | Filtering, Serialization, Validation via predicates            |

## Enumeration Details

### Enum/Http

**Purpose**: Defines HTTP constants for methods, codes, and headers. Ensures RFC compliance, integrates with `Presentation/Http/`, uses `r()` for request logging and `h()` for hooks.

```php
<?php declare(strict_types=1);

final class HttpMethod
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    public static function isValid($method) {
        $valid = in_array($method, [self::GET, self::POST, self::PUT, self::DELETE]);
        if (!$valid) {
            r("Invalid HTTP method: {$method}", "Error");
        }
        h('http_method_validate', $method); // Framework hook
        return $valid;
    }
}
```

### Enum/Permission

**Purpose**: Represents granular permissions for security. Maps actions to resources, configurable via `env()`, with `r()` for access audits.

```php
<?php declare(strict_types=1);

final class Permission
{
    const READ_POST = 'post.read';
    const WRITE_POST = 'post.write';
    const DELETE_POST = 'post.delete';
    const MANAGE_USERS = 'users.manage';

    public static function forResource($resource) {
        $perms = array_filter(get_class_constants(self::class), fn($p) => str_starts_with($p, $resource));
        r("Permissions for resource: {$resource}", "Audit", null, $perms);
        return $perms;
    }
}
```

### Enum/Policy

**Purpose**: Encapsulates rule selection for logic. Enables policy patterns, uses `env()` for rates, `p("resolve")` for decisions.

```php
<?php declare(strict_types=1);

final class DiscountPolicy
{
    const NONE = 'none';
    const QUANTITY = 'quantity_based';
    const SEASONAL = 'seasonal';
    const LOYALTY = 'loyalty_program';

    public static function calculate($policy, $context) {
        p('resolve'); // Framework phase
        $result = match ($policy) {
            self::QUANTITY => $context['quantity'] * env('DISCOUNT_QUANTITY_RATE', 0.1),
            self::LOYALTY => $context['years'] * env('DISCOUNT_LOYALTY_RATE', 5),
            default => 0
        };
        r("Policy calculated: {$policy}", "Internal", null, ['result' => $result]);
        return $result;
    }
}
```

### Enum/Role

**Purpose**: Defines user roles for RBAC. Hierarchical, mutable via admin, with `h()` for role change hooks.

```php
<?php declare(strict_types=1);

final class UserRole
{
    const GUEST = 1;
    const USER = 2;
    const EDITOR = 3;
    const ADMIN = 4;

    public static function canPromote($from, $to) {
        $can = $to > $from && $to <= self::ADMIN;
        if ($can) {
            h('role_promote', ['from' => $from, 'to' => $to]); // Framework hook
        }
        return $can;
    }
}
```

### Enum/State

**Purpose**: Models FSM for lifecycles. Ensures valid transitions, integrates with `p()` for state changes, `r()` for invalid attempts.

```php
<?php declare(strict_types=1);

final class OrderState
{
    const DRAFT = 'draft';
    const CONFIRMED = 'confirmed';
    const SHIPPED = 'shipped';
    const DELIVERED = 'delivered';
    const CANCELLED = 'cancelled';

    private static $transitions = [
        self::DRAFT => [self::CONFIRMED, self::CANCELLED],
        self::CONFIRMED => [self::SHIPPED, self::CANCELLED],
        self::SHIPPED => [self::DELIVERED]
    ];

    public static function canTransition($from, $to) {
        $can = in_array($to, self::$transitions[$from] ?? []);
        if (!$can) {
            r("Invalid state transition: {$from} to {$to}", "Error");
        }
        if ($can) {
            p('mutate'); // Framework phase for state change
        }
        return $can;
    }
}
```

### Enum/Status

**Purpose**: Represents outcomes for monitoring. Distinguishes states, uses `r()` for status changes.

```php
<?php declare(strict_types=1);

final class OperationStatus
{
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const FAILED = 'failed';

    public static function isFinal($status) {
        $isFinal = in_array($status, [self::COMPLETED, self::FAILED]);
        r("Status check: {$status} is final: {$isFinal}", "Internal");
        return $isFinal;
    }
}
```

### Enum/Type

**Purpose**: Provides classification for entities. Enables filtering/serialization, with `h()` for type-specific hooks.

```php
<?php declare(strict_types=1);

final class ContentType
{
    const ARTICLE = 'article';
    const VIDEO = 'video';
    const PODCAST = 'podcast';
    const GALLERY = 'gallery';

    public static function getRenderer($type) {
        h('content_type_renderer', $type); // Framework hook
        return match($type) {
            self::ARTICLE => ArticleRenderer::class,
            self::VIDEO => VideoPlayer::class,
            self::PODCAST => AudioPlayer::class,
            default => DefaultRenderer::class
        };
    }
}
```

## Complementary Patterns

**Strategy Pattern** uses Policy enums to select algorithms via `p("resolve")`. **State Pattern** builds on State enums for behavior changes with phases. **Chain of Responsibility** uses Role enums for escalation, hooked via `h()`. **Factory Method** uses Type enums for instances in `Creational/Factory`. **Specification Pattern** leverages Permission enums for queries in `Behavioral/Specification`.

## Distinguishing Characteristics

**vs. Constant Arrays**: Enums provide safety/methods; arrays just values in helpers. **vs. Database Lookup Tables**: Enums compile-time; lookups runtime in `Database/Flat/`. **vs. Bit Flags**: Enums single values; flags combinable via traits. **vs. Configuration**: Enums closed sets; configs open-ended via `env()`.
