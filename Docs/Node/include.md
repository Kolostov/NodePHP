# NodePHP — Inclusion Philosophy

## Overview

The `node.include.php` file is the **gateway for subnodes** into the NodePHP ecosystem. Unlike `node.php`, which serves as the main entrypoint, `node.include.php` is designed to **safely integrate a node into an existing runtime** without reinitializing global state or conflicting with other nodes.

Its purpose is to allow nodes to **participate in the runtime** while preserving consistency, avoiding double execution, and respecting the `NODE_STRUCTURE` architecture.

---

## Core Principles

### 1. Node as a Self-Registering Entity

- Nodes register themselves as ready for inclusion but **cannot execute independently**.
- Direct execution without a parent node is explicitly prevented, enforcing **controlled execution**.
- This ensures that subnodes cannot violate runtime integrity or execute in isolation.

---

### 2. Minimal Bootstrap, Maximal Safety

- Only essential paths and structures are initialized, such as `$LOCAL_PATH` and `$NODE_STRUCTURE_DEFINITIONS`.
- No constants or global variables are redefined if the runtime is already active.
- This allows multiple nodes to coexist safely within the same execution environment.

---

### 3. Structure-Driven Inclusion

- Inclusion respects the **NODE_STRUCTURE**, processing only declared nodes in `$NODE_REQUIRE`.
- Only PHP files within the node are included, and duplicate inclusions are prevented.
- Excluded directories, such as `Git`, `Test`, `Log`, and `Deprecated`, are ignored.
- This guarantees a coherent and predictable runtime, where only intended functionality is loaded.

---

### 4. Vendor and Dependency Awareness

- Each node can include its **vendor autoload file**, allowing dependencies to be loaded without affecting the global runtime.
- Nodes remain modular and **plug-and-play**, able to be included or excluded without side effects.

---

### 5. Phaseless Integration

- Inclusion follows the **phaseless philosophy**:
    - Nodes are not tied to explicit execution phases.
    - Features, hooks, and CLI commands become available dynamically upon inclusion.
- This enables runtime flexibility and supports modular orchestration.

---

### 6. Subnode Symbiosis

- Subnodes contribute to the runtime without taking control.
- They can define functions, classes, traits, and commands that integrate seamlessly.
- The main runtime acts as the **orchestrator**, while subnodes serve as **modular contributors**.

---

## Philosophical Summary

`node.include.php` embodies the principles of **modularity, safety, and runtime harmony**:

1. **Safe Inclusion over Independent Execution** – nodes cannot interfere with the runtime if misused.
2. **Lightweight Bootstrap** – only essential paths and structures are registered.
3. **Structure Awareness** – inclusion strictly follows `NODE_STRUCTURE`.
4. **Phaseless and Modular** – nodes integrate dynamically without rigid execution phases.
5. **Dependency Conscious** – external libraries can be loaded without impacting the global runtime.

It acts as the **bridge for subnodes**, allowing them to participate fully and safely in the NodePHP environment while maintaining system coherence and predictability.
