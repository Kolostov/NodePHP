### `_node_cli_rank` Documentation

**Purpose:**
Analyze a PHP file and rank its functions based on a variety of metrics for maintainability, readability, performance, and modern best practices.

---

## `_node_cli_rank`

**Signature:**

```php
_node_cli_rank(bool $tooltip = false, array $argv = []): string
```

**Parameters:**

- `tooltip` (`bool`): Returns a brief usage string if true.
  Output: `"<file> <?func> Analyze and rank file contents"`
- `argv` (`array`):
    - `argv[0]`: File path to analyze.
    - `argv[1]` (optional): Specific function name to analyze. If omitted, all functions are ranked.

**Behavior:**

1. **Validation:**
    - File must exist. Returns error `"E: Provide valid file path"` if invalid.
    - If a specific function is requested, it must exist in the file.

2. **Function Analysis:**
    - Extracts all functions using `_node_file_functions()`.
    - For each function (or the target function):
        - Retrieves function body with docblock using `_node_get_function_body_with_docblock()`.
        - Computes metrics:
            - `call` → function usage count
            - `docs` → docblock completeness
            - `ln` → number of lines
            - `args` → number of parameters
            - `branch` → branching complexity (if/switch)
            - `divisions` → division operations
            - `string_ops` → string manipulations
            - `builtin` → built-in function usage
            - `ifelse` → if/else balance
        - Calculates `rawScore` and `score` (including file-level metrics).

3. **File Metrics:**
   Uses `_node_compute_file_metrics()` to score overall file for PHP best practices, e.g., `strict_types`, typed properties, namespace, no superglobals, final class, modern visibility, constructor promotion, union types, readonly properties, enums, nullsafe operators, match expressions, named arguments, attributes, immutable objects, cohesion, cyclomatic complexity, dependency inversion, coding standards, test coverage, and more.

4. **Output:**
    - **Specific Function:** Detailed metrics with notes for negative values, raw and total score.
    - **All Functions:**
        - Sorted list of top and worst functions.
        - Per-function metric breakdown.
        - File-level metric summary, sorted and formatted in three columns.

5. **Notes:**
    - Includes guidance for improving functions and file practices.
    - Metrics consider modern PHP features (PHP 7.4+ and 8.x).
    - Highlights potential risks in code (e.g., magic numbers, unbalanced branches, lack of docblocks).

---

## Included Helper Files

The following files are included to support `_node_cli_rank`:

- Function extraction and parsing:
    - `_file_functions`, `get_function_body_with_docblock`, `extract_function_with_brace_counting`, `find_function_start`
- File-level metrics:
    - `compute_file_metrics`, `_file_nullsafe_operator`, `_file_match_expression`, `_file_named_arguments`, `_file_attributes`, `_file_enums`, `_file_array_is_list`, `_file_first_class_callable`, `_file_pure_annotations`, `_file_constructor_property_promotion`, `_file_union_types`, `_file_readonly_properties`, `_file_never_return_type`, `_file_immutable_objects`, `_file_cohesion`, `_file_cyclomatic_complexity`, `_file_dependency_inversion`, `_file_no_magic_numbers`, `_file_no_global_functions`, `_file_interface_segregation`, `_file_single_responsibility`, `_file_security_metrics`, `_file_performance_hints`, `_file_documentation`, `_file_test_coverage`, `_file_coding_standards`, `_file_strict_types`, `_file_typed_properties`, `_file_namespace`, `_file_no_superglobals`, `_file_final_class`, `_file_modern_visibility`
- Function-level metrics:
    - `metric_calls`, `metric_docblock`, `metric_lines`, `metric_parameters`, `metric_branching`, `metric_division`, `metric_string_ops`, `metric_builtin_usage`, `metric_if_else_balance`

**Notes on Included Files:**
Each included file contains specialized logic for extracting metrics, parsing function bodies, analyzing file features, and detecting adherence to modern PHP practices.

---

**Usage Examples:**

```bash
php node.php rank path/to/file.php        # Rank all functions in the file
php node.php rank path/to/file.php myFunc # Rank a specific function
```

**Output Highlights:**

- Function score: weighted sum of function metrics + file metrics.
- Raw score: function-specific metrics only.
- File metrics: highlights code quality and modern PHP practice adherence.
- Detailed guidance provided for metrics with negative values.
