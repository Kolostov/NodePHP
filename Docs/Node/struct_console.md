# Console System Patterns in NodePHP Framework

## Overview: Command-Line Architecture

In the NodePHP framework, console patterns are structured under `Console/` and provide interfaces for CLI operations and automation. They integrate with the framework's CLI entrypoint (e.g., `php node ...`), bridging logic with OS execution via phases like `p("execute")` for commands and `p("finalize")` for cleanup. This enables interactive and scheduled tasks, using `r()` for logging, `h()` for hooks, `env()` for config, and `$argv` for input parsing.

### Console Components Overview

| **Component** | **Primary Purpose**  | **Execution Context**        | **Lifecycle** | **Pattern Type** |
| ------------- | -------------------- | ---------------------------- | ------------- | ---------------- |
| **Command**   | Task encapsulation   | User-initiated via `$argv`   | Short-lived   | Behavioral       |
| **Kernel**    | System orchestration | Bootstrapping in CLI SAPI    | Long-running  | Structural       |
| **Schedule**  | Temporal automation  | Time-based via cron or phase | Periodic      | Behavioral       |

## Component Details

### Console/Command

**Purpose**: Encapsulates CLI operations as reusable units with inputs, outputs, and logic. Provides interfaces for tasks with validation and help, integrated with `r()` for execution logging and `h()` for pre/post hooks.

```php
<?php declare(strict_types=1);

final class CreateUserCommand
{
    private $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function handle() {
        h('command_pre_handle', $this); // Framework hook
        // Create user logic
        r("Creating user: {$this->name}", "Internal");
        echo "User {$this->name} created\n";
        p('execute'); // Framework phase
        return 0; // Exit code
    }

    public function getSignature() {
        return 'user:create {name}';
    }
}
```

### Console/Kernel

**Purpose**: Central dispatcher for console commands, handling bootstrapping, registration, and routing. Acts as CLI entrypoint, using framework's phase system and `r()` for command auditing.

```php
<?php declare(strict_types=1);

final class ConsoleKernel
{
    private $commands = [];

    public function __construct() {
        p('boot'); // Framework phase for init
        h('kernel_register_commands', $this); // Hook for adding commands
    }

    public function register($command) {
        $this->commands[$command->getSignature()] = $command;
        r("Command registered: " . $command->getSignature(), "Internal");
    }

    public function handle($input) {
        $args = explode(' ', $input);
        $signature = $args[0];
        if (isset($this->commands[$signature])) {
            return $this->commands[$signature]->handle();
        }
        echo "Command not found\n";
        r("Command not found: {$signature}", "Error");
        return 1;
    }
}
```

### Console/Schedule

**Purpose**: Defines time-based automation for commands, managing scheduling with expressions. Enables maintenance, integrates with `h()` for task hooks and `r()` for run logging.

```php
<?php declare(strict_types=1);

final class TaskScheduler
{
    private $tasks = [];

    public function cron($expression, $command) {
        $this->tasks[] = [
            'expression' => $expression,
            'command' => $command
        ];
        r("Scheduled task: {$expression}", "Internal");
    }

    public function dueTasks() {
        $due = [];
        foreach ($this->tasks as $task) {
            if ($this->isDue($task['expression'])) {
                h('schedule_pre_run', $task); // Framework hook
                $due[] = $task['command'];
            }
        }
        return $due;
    }
}
```

## Complementary Patterns

**Command Pattern** foundational for Console/Command, encapsulating ops with `p("execute")`. **Registry Pattern** in Console/Kernel for registration/retrieval via signatures. **Builder Pattern** for schedule expressions, fluent via methods. **Strategy Pattern** for scheduling algos, swappable via `env()`. **Observer Pattern** notifies via `h()` on executions.

## Distinguishing Characteristics

**vs. Service Layer**: Commands CLI-specific with exit codes; services in `Infrastructure/Service` general logic. **vs. Cron Directives**: Schedule programmatic abstraction over cron, testable with phases. **vs. Application Controller**: Kernel CLI bootstrap; controllers in `Presentation/Controller` for web.
