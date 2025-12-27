# NodePHP Runtime Philosophy

## Overview

NodePHP is a modular, self-contained PHP framework designed around a **runtime-driven node structure**. Its architecture emphasizes **dynamic discovery, execution, and management of nodes**, rather than relying solely on static file inclusions or rigid entrypoints. Each node represents a discrete unit of functionality — classes, traits, functions, or entire subsystems — and the framework ensures they interact in a cohesive yet loosely coupled fashion.

The runtime can be thought of as the **living orchestration layer** of the framework, where configuration, execution, and CLI operations converge. It maintains a **symbiosis with NODE_STRUCTURE**, which acts as both a **map and contract** for the runtime, guiding behavior without hardcoding dependencies.

---

## Core Principles

### 1. **Node-Centric Execution**

At the heart of the runtime is the concept of nodes:

- Nodes are directories or files representing logical units.
- The runtime automatically discovers, deploys, and includes nodes based on the `NODE_STRUCTURE` definition.
- Nodes may contain CLI commands, extensions, core primitives, or application-specific classes.

This philosophy removes the need for pre-defined bootstrap files beyond a single node entrypoint. Each node knows its purpose and how to interact with the runtime, promoting modularity and extensibility.

---

### 2. **Phaseless vs. Phased Runtime**

NodePHP embraces a **dual-mode execution philosophy** that adapts depending on context:

#### a. **Phaseless Runtime (Default for CLI)**

- When executed from the command line without specifying a phase, NodePHP runs in **phaseless mode**:
    - No explicit stages are enforced.
    - Commands, hooks, and nodes are dynamically discovered and executed as needed.
    - Deferred execution is captured in `RUN_STRING`, allowing the runtime to queue commands safely.
    - Key functions ensuring safe phaseless execution:
        - `_node_structure_include()` — ensures nodes are loaded in the correct order and dependencies are respected.
        - `_node_execute_run()` — executes deferred commands with full argument awareness.
- This model prioritizes **speed, simplicity, and modularity**: developers can run arbitrary commands without bootstrapping unnecessary phases.

#### b. **Phased Runtime (HTTP or CLI with explicit phase)**

- When executed over HTTP or with a CLI argument that matches a predefined **phase**, NodePHP enters **phased execution mode**:
    - Default phase indicator is set to `phaseless`.
    - If `$argv[1]` matches a phase in `p("order")`, NodePHP sets `$NODE_PHASE` to that phase and begins execution up to it.
    - Phased execution allows systematic processing of all runtime phases, respecting dependencies and initialization order.
    - Example workflow:
        - CLI: `php node.php finalize` → NodePHP runs all phases in order, culminating in the `finalize` phase.
        - Each phase ensures that nodes, hooks, and runtime commands are executed in a **controlled and predictable sequence**.

#### c. **CLI Metrics and Reporting**

- After execution (phaseless or phased), NodePHP reports:
    - Total runtime (`Time`)
    - Peak memory usage (`RAM`)
    - Active global variables (excluding system superglobals)
- This **transparent reporting** reinforces NodePHP's philosophy of **observability and developer awareness**.

#### d. **Summary of Execution Philosophy**

- **Default CLI:** Phaseless, dynamic, flexible.
- **HTTP / explicit phase CLI:** Phased, deterministic, sequential.
- **Core principle:** The framework adapts to the context while ensuring nodes, commands, and hooks always execute safely and in the correct dependency order.

---

### 3. **Dynamic CLI Integration**

- CLI commands are **first-class citizens** within the node ecosystem.
- The runtime dynamically maps commands like `cli_backup`, `cli_make`, or `cli_test` to functions or node entries.
- Commands automatically respect NODE_STRUCTURE, meaning adding a new CLI node instantly integrates it into the system.
- Execution metrics (time, memory, and active globals) are reported post-run to maintain observability.

---

### 4. **NODE_STRUCTURE as Living Contract**

NODE_STRUCTURE is not merely a configuration file:

- It defines all **primitive elements** (functions, interfaces, classes, traits, enums) and **application constructs**.
- It enforces **consistency** across CLI, runtime, and automated resource management.
- All higher-level operations, including `cli_*` commands, resource inclusion, and boilerplate generation, depend on this structure.

The runtime interacts with NODE_STRUCTURE to:

1. Walk directories recursively.
2. Include nodes in proper order.
3. Dispatch execution calls safely.
4. Track hooks, predicates, transformers, and extensions.

This ensures the framework is **self-aware**, meaning new functionality can be introduced without modifying core runtime logic.

---

### 5. **Hooks and Runtime Observability**

- Hooks (`h()`) provide **extension points** for both CLI and runtime operations.
- Hooks can transform arguments, observe execution, or inject side-effects without altering node internals.
- Logging functions capture structured events across internal, system, and exception logs, maintaining transparency.

---

### 6. **Minimal Bootstrap with Maximum Flexibility**

- The runtime uses a **minimal bootstrap** (`node.php`) to initialize paths, global constants, and NODE_STRUCTURE.
- It dynamically includes required resources and CLI commands.
- Nodes can define their own inclusion logic (`node.include.php`) to participate in the runtime ecosystem.
- Memory and execution time are tracked and reported automatically.

---

## Philosophical Summary

NodePHP embodies a **runtime-first, structure-driven philosophy**:

1. **Modularity over Monoliths:** Each node is self-contained but aware of the global structure.
2. **Dynamic Inclusion over Hardcoded Paths:** Nodes register themselves and are executed via discovery.
3. **Structure as Contract:** NODE_STRUCTURE is both map and rulebook.
4. **Observability as a Core Principle:** Logs, metrics, and hooks maintain transparency and debuggability.
5. **CLI as Natural Extension:** The command line is simply another node-aware interface into the runtime.

In essence, NodePHP is not just a framework — it is a **living, self-organizing ecosystem** where nodes, hooks, and runtime operations coexist symbiotically. Developers build within this ecosystem, and the runtime ensures coherence, adaptability, and traceability across all layers of the application.
