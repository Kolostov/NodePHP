<?php /* /node.php */ declare(strict_types=1);

$LOCAL_PATH = realpath(__DIR__) . DIRECTORY_SEPARATOR;

if (defined("NODE_NAME") === !1) {
    # Main entry point declarations
    error_reporting(E_ALL);
    ini_set("display_errors", !0);
    $TIME_START = microtime(true);
    $ROOT_PATHS = [];

    define("D", DIRECTORY_SEPARATOR);

    define("ROOT_PATH", $LOCAL_PATH);
    define("LOG_PATH", ROOT_PATH . "Log" . D);
}

$ROOT_PATHS[] = $LOCAL_PATH;

if (!function_exists("f")) {
    /*
     * @param string $fn Complete path to file.
     * @param string $critical Die if file does not exist.
     *
     * @return string Real path.
     */
    function f(string $fn, bool $critical = true): string|null
    {
        if (file_exists($fn)) {
            return $fn;
        }

        global $ROOT_PATHS;

        $fn = ltrim(str_replace(ROOT_PATH, "", $fn), DIRECTORY_SEPARATOR);

        foreach ($ROOT_PATHS as $path) {
            $sfn = "{$path}{$fn}";
            if (file_exists($sfn)) {
                return $sfn;
            }
        }

        return $critical
            ? die("Error: function f() cannot find file: {$fn}")
            : null;
    }
}

$NODE_STRUCTURE_DEFINITIONS = "{$LOCAL_PATH}node.json";

try {
    if (
        $node = file_exists($NODE_STRUCTURE_DEFINITIONS)
            ? json_decode(
                file_get_contents($NODE_STRUCTURE_DEFINITIONS),
                !0,
                512,
                JSON_THROW_ON_ERROR,
            )
            : null
    ) {
        $NODE_STRUCTURE = $node["structure"] ?? [
            "Enum" => [
                "State" => "Finite lifecycle state (Draft, Active, ...)",
                "Status" => "Operational status (OK, Failed, Pending)",
                "Type" => "Categorization or classification",
                "Policy" => "Rule selection enum",
            ],

            "Function" => [
                "Helper" => "Global stateless helpers",
                "Predicate" => "Boolean-returning decision functions",
                "Transformer" => "Pure data-to-data transformations",
                "Presenter" => "Formatting helpers (headers, payloads)",
            ],

            "Trait" => [
                "Concern" => "Shared implementation across classes",
                "Capability" => "Adds opt-in behavior (Loggable, Gainable)",
                "Mixin" => "Pure helper logic without identity",
            ],

            "Interface" => [
                "Presentation" => [
                    "Controller" => "Inbound request handling contract",
                    "Endpoint" => "Public callable API contract",
                    "Responder" => "Response formatting contract",
                    "View" => "Renderable view contract",
                ],
                "Behavioral" => [
                    "Strategy" => "Algorithm interchangeable at runtime",
                    "Command" => "Executable request abstraction",
                    "Specification" => "Combinable business rule",
                    "Policy" => "Decision rule contract",
                    "State" => "State-dependent behavior contract",
                ],
                "Structural" => [
                    "Repository" => "Persistence abstraction",
                    "Adapter" => "Interface translation layer",
                    "Proxy" => "Access-controlling surrogate",
                    "Decorator" => "Behavior-extending wrapper",
                ],
                "Creational" => [
                    "Factory" => "Object creation abstraction",
                    "Builder" => "Stepwise object construction",
                ],
                "Infrastructure" => [
                    "EventDispatcher" => "Event publication contract",
                    "Bus" => "Message transport contract",
                    "Gateway" => "External system boundary",
                    "Client" => "Outbound communication contract",
                ],
            ],

            "Class" => [
                "Final" => [
                    "Presentation" => [
                        "Controller" => "Concrete request handler",
                        "Endpoint" => "Concrete public API endpoint",
                        "Responder" => "Concrete response formatter",
                        "View" => "Concrete renderable template",
                        "Component" => "Reusable UI or API component",
                    ],

                    "ValueObject" => "Immutable identity-less value",
                    "DTO" => "Transport-only data carrier",
                    "Entity" => "Domain object with identity",

                    "Behavioral" => [
                        "Strategy" => "Concrete interchangeable algorithm",
                        "Command" => "Executable intent",
                        "Specification" => "Concrete business rule",
                        "Policy" => "Concrete decision logic",
                    ],

                    "Structural" => [
                        "Decorator" => "Behavior-extending wrapper implem.",
                        "Adapter" => "Concrete interface translator",
                        "Proxy" => "Concrete access surrogate",
                        "Facade" => "Simplified subsystem interface",
                    ],

                    "Creational" => [
                        "Factory" => "Concrete object creator",
                        "Builder" => "Concrete stepwise constructor",
                    ],

                    "Coordination" => [
                        "Mediator" => "Central interaction coordinator",
                        "EventDispatcher" => "Concrete event publisher",
                        "Pipeline" => "Sequential processing chain",
                    ],

                    "Infrastructure" => [
                        "Service" => "Stateless application service",
                        "Client" => "Concrete outbound integration",
                        "Gateway" => "Concrete external boundary",
                    ],
                ],

                "Abstract" => [
                    "Presentation" => [
                        "Controller" => "Controller base",
                        "Endpoint" => "Endpoint base",
                        "Responder" => "Responder base",
                    ],
                    "Base" => [
                        "Controller" => "Request-handling base class",
                        "Service" => "Shared service logic base",
                        "Repository" => "Persistence base implementation",
                        "Command" => "Command base abstraction",
                    ],
                    "Infrastructure" => [
                        "Database" => "Database integration base",
                        "Migration" => "Abstract migration base",
                        "Transport" => "Communication transport base",
                        "Cache" => "Caching mechanism base",
                    ],
                ],
            ],
            "Public" => [
                "Entry" => "Front entrypoints (index.php, api.php, .htaccess)",
                "Static" => [
                    "Asset" => [
                        "CSS" => "Compiled or authored stylesheets",
                        "JS" => "Compiled or authored scripts",
                        "IMG" => "Images (png, jpg, svg, webp)",
                        "FONT" => "Web fonts",
                    ],
                    "Media" => [
                        "Upload" => "User-uploaded files",
                        "Cache" => "Publicly cacheable generated files",
                    ],
                    "Meta" => "robots.txt, security.txt, humans.txt, manifests",
                    "Build" => "Build outputs",
                ],
            ],
            "Migration" => [
                "Base" => "Abstract migration base class",
                "SQL" => "Raw SQL migration",
                "PHP" => "Programmatic migration class",
            ],
            "Test" => [
                "Unit" => "Self-contained class or function tests",
                "Integration" => "Tests involving multiple nodes",
                "Contract" => "Interface compliance tests",
                "E2E" => "Full end-to-end request/response tests",
            ],
            "Log" => [
                "Internal" => "Application runtime logs",
                "Access" => "HTTP request logs",
                "Error" => "Error and exception logs",
                "Audit" => "Security and audit trails",
            ],
            "Git" => [
                "Node" => "Node.php project repository",
                "Project" => "All excluding the Node.php",
            ],
        ];

        unset($NODE_STRUCTURE_DEFINITIONS);

        # Main entry point node.
        $NODE_NAME = $node["name"] ?? "Noname";
        if (defined("NODE_NAME") === !1) {
            define("NODE_NAME", $NODE_NAME);
            define("NODE_STRUCTURE", $NODE_STRUCTURE ?? []);
        }

        if (file_exists("{$LOCAL_PATH}.env")) {
            $lines = file(
                "{$LOCAL_PATH}.env",
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
            );
            foreach ($lines as $line) {
                if (str_starts_with($line, "#") || !str_contains($line, "=")) {
                    continue;
                }

                [$key, $value] = explode("=", $line, 2);
                $key = "{$NODE_NAME}:" . strtoupper(trim($key));
                $value = trim(trim($value), "'\"");

                $_ENV[$key] = $value;

                putenv("{$key}={$value}");
                unset($line, $key, $value);
            }
            unset($lines);
        }

        if (function_exists("env") === !1) {
            function env(string $key, mixed $default = null): mixed
            {
                $key = strtoupper(trim($key));

                $prefixedKey = NODE_NAME . ":{$key}";
                $value = $_ENV[$prefixedKey] ?? getenv($prefixedKey);

                if ($value !== null && $value !== false) {
                    return $value;
                }

                return $_ENV[$key] ??
                    ($_SERVER[$key] ?? (getenv($key) ?? $default));
            }
        }

        $NODE_REQUIRE = $node["require"] ?? [];

        unset($node, $NODE_NAME);
    }
} catch (Exception $e) {
    $msg = "Invalid {$NODE_STRUCTURE_DEFINITIONS}: " . json_last_error_msg();

    throw new RuntimeException($msg, 0, $e);
}

if (function_exists("walkStructure") === !1) {
    function walkStructure(
        array $array,
        callable $callback,
        string $location = "",
        string $LOCAL_PATH = "",
    ): array {
        $r = [];

        if (!empty($array)) {
            foreach ($array as $name => $val) {
                if (is_numeric($name)) {
                    continue;
                }
                $path = "{$LOCAL_PATH}{$location}{$name}";

                $r[] = $callback($path, $val);

                if (is_array($val)) {
                    $sub = array_is_list($val) ? array_flip($val) : $val;
                    $srl = "{$location}{$name}" . DIRECTORY_SEPARATOR;

                    $r = [
                        ...$r,
                        ...walkStructure($sub, $callback, $srl, $LOCAL_PATH),
                    ];
                }
            }
        }
        return array_filter($r, fn($x) => !empty($x));
    }
}

if (function_exists("deployStructure") === !1) {
    function deployStructure(): void
    {
        walkStructure(
            NODE_STRUCTURE,
            function (string $path): string {
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                    return $path;
                }
                return "";
            },
            "",
            ROOT_PATH,
        );
    }
    deployStructure(); # Only deploy for main entrypoint node.
}

$LOCAL_VENDOR = "{$LOCAL_PATH}vendor" . DIRECTORY_SEPARATOR . "autoload.php";
if (file_exists($LOCAL_VENDOR)) {
    include_once $LOCAL_VENDOR;
}
unset($LOCAL_VENDOR);

if (function_exists("includeStructure") === !1) {
    function includeStructure(
        array $NODE_STRUCTURE,
        string $LOCAL_PATH,
        array $NODE_REQUIRE,
    ): void {
        walkStructure(
            $NODE_STRUCTURE,
            function (string $path): string {
                $exclude = ["Git", "Test", "Public"];
                if (PHP_SAPI !== "cli") {
                    $exclude = [...$exclude, ["Migration"]];
                }

                foreach ($exclude as $part) {
                    $ex = DIRECTORY_SEPARATOR . $part . DIRECTORY_SEPARATOR;
                    if (strpos($path, $ex)) {
                        return "";
                    }
                }

                if (is_dir($path)) {
                    if ($php = glob($path . DIRECTORY_SEPARATOR . "*.php")) {
                        foreach ($php as $fn) {
                            include_once $fn;

                            return $fn;
                        }
                    }
                }
                return "";
            },
            "",
            $LOCAL_PATH,
        );

        if (is_array($NODE_REQUIRE) && !empty($NODE_REQUIRE)) {
            foreach ($NODE_REQUIRE as $node) {
                $path = $LOCAL_PATH . ".." . DIRECTORY_SEPARATOR . $node;

                if ($check = realpath($path)) {
                    $file = $check . DIRECTORY_SEPARATOR . "node.php";
                    file_exists($file) && (include_once $file);
                } else {
                    throw new Exception(
                        "Node {$LOCAL_PATH} requires node that does not exist at: {$path}. Fix this path or remove {$node} from node.json",
                        0,
                    );
                }
            }
        }
    }
}

if (function_exists("callStructure") === !1) {
    function callStructure(): array
    {
        static $calls = [];

        return walkStructure(
            NODE_STRUCTURE,
            function (string $path, mixed $v) use (&$calls): array {
                if (glob($path . DIRECTORY_SEPARATOR . "*", GLOB_ONLYDIR)) {
                    return [];
                }

                $exp = explode(DIRECTORY_SEPARATOR, $path);

                $i = 0;
                $l = count($exp);

                do {
                    $i++;
                    $slice = array_slice($exp, $l - $i, $i);
                    $call = implode(DIRECTORY_SEPARATOR, $slice);
                    if (!in_array($call, $calls, true)) {
                        $calls[] = $call;
                        break;
                    }
                } while ($i < $l);

                return [$call, $path, $v];
            },
            "",
            ROOT_PATH,
        );
    }
}

if (function_exists("generateBoilerplate") === !1) {
    function generateBoilerplate(
        string $call,
        string $name,
        string $LOCAL_PATH,
    ): array {
        $call = str_starts_with($call, $LOCAL_PATH)
            ? substr($call, strlen($LOCAL_PATH))
            : $call;

        $parts = explode(DIRECTORY_SEPARATOR, trim($call, DIRECTORY_SEPARATOR));
        $parts = array_filter($parts, fn($p) => !empty($p));

        $leaf = end($parts); // e.g., Repository, Command, Controller
        $type = reset($parts); // e.g., Class, Interface, Function

        $namespace = !empty($parts)
            ? "namespace " .
                implode("\\", array_map("ucfirst", $parts)) .
                ";\n\n"
            : "";

        $keyword = match ($type) {
            "Interface" => "interface",
            "Trait" => "trait",
            "Function" => "function",
            "Class" => in_array($parts[0] ?? "", ["Final", "Abstract"])
                ? strtolower($parts[0]) . " class"
                : "class",
            "Enum" => "enum",
            default => "class",
        };

        $className = match ($type) {
            "Enum" => $name,
            "Function" => $name,
            default => "{$name}{$leaf}",
        };

        // Generate appropriate body
        $body = match ($type) {
            "Interface" => "\n{\n\tpublic function execute(): void;\n}\n",
            "Function" => "\n{\n\t# TODO: Implement {$name} function\n}\n",
            "Trait" => "\n{\n\t# TODO: Implement trait methods\n}\n",
            "Class"
                => "\n{\n\tpublic function __construct()\n\t{\n\t\t# TODO: Initialize constructor\n\t}\n}\n",
            default => "\n{\n}\n",
        };

        return [
            <<<PHP
            <?php declare(strict_types=1);

            {$namespace}{$keyword} {$className}{$body}
            PHP
            ,
            $leaf,
        ];
    }
}

if (function_exists("cli_help") === !1) {
    function cli_help(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<void> Shows all commands and resources";
        }
        $r = "";
        foreach (get_defined_functions()["user"] as $fn) {
            if (str_starts_with($fn, "cli_")) {
                $r .= substr($fn, 4) . " " . $fn(true, []) . "\n";
            }
        }
        return "$r\n" .
            "Static file path resolution function\n\tf(string path, bool critical) : string\n" .
            "Result logging function\n\tr(string logMessage, mixed return, ?array|obj contextData = [], ?string logType = Internal)\n" .
            "LogTypes: [Internal, Access, Audit, Error]\n";
    }
}

if (!function_exists("r")) {
    function r(
        string $logMessage,
        mixed $return = null,
        null|array|object $dataArray = null,
        string $logType = "Internal",
    ): mixed {
        static $logDirs = [
            "Internal" => LOG_PATH . "Internal" . D,
            "Access" => LOG_PATH . "Access" . D,
            "Error" => LOG_PATH . "Error" . D,
            "Audit" => LOG_PATH . "Audit" . D,
        ];

        $logDir = $logDirs[$logType] ?? $logDirs["Internal"];

        $date = date("Y-m-d");
        $logFile = "{$logDir}{$date}.log";

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[1]["function"] ?? "#rootcode";
        $file = $backtrace[0]["file"] ?? "unknown";
        $line = $backtrace[0]["line"] ?? 0;

        $entry = [
            "timestamp" => date("Y-m-d H:i:s"),
            "type" => $logType,
            "file" => str_replace(ROOT_PATH, "", $file),
            "line" => $line,
            "function" => $caller,
            "message" => $logMessage,
            "data" => $dataArray ? (array) $dataArray : null,
            "result" => $return,
        ];

        if (PHP_SAPI !== "cli") {
            $entry["ip"] = $_SERVER["REMOTE_ADDR"] ?? "cli";
            $entry["method"] = $_SERVER["REQUEST_METHOD"] ?? "cli";
            $entry["uri"] = $_SERVER["REQUEST_URI"] ?? "cli";

            if (session_status() !== PHP_SESSION_NONE) {
                $entry["session_id"] = session_id();
                if (isset($_SESSION["loggedin"]["user_id"])) {
                    $entry["user_id"] = $_SESSION["loggedin"]["user_id"];
                }
            }
        }

        file_put_contents(
            $logFile,
            json_encode(
                $entry,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ) . "\n",
            FILE_APPEND,
        );

        return $return;
    }
}

if (!function_exists("logReadFile")) {
    function logReadFile(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $logs = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (trim($line) === "") {
                continue;
            }

            try {
                $logEntry = json_decode($line, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $logs[] = $logEntry;
                }
            } catch (Exception $e) {
                // Skip invalid JSON lines
            }
        }

        return $logs;
    }
}

if (!function_exists("logReadFilesArray")) {
    function logReadFilesArray(array $arrayOfPathsToLogFiles): array
    {
        if (empty($arrayOfPathsToLogFiles)) {
            return [];
        }

        $allLogs = [];
        foreach ($arrayOfPathsToLogFiles as $path) {
            $allLogs = [...$allLogs, ...logReadFile($path)];
        }

        // Sort by timestamp (newest first)
        usort(
            $allLogs,
            fn($a, $b) => strtotime($b["timestamp"] ?? "1970-01-01") <=>
                strtotime($a["timestamp"] ?? "1970-01-01"),
        );

        return $allLogs;
    }
}

if (!function_exists("getAllLogFiles")) {
    function getAllLogFiles(): array
    {
        $logFiles = [];

        // Internal logs from node structure
        $logTypes = ["Internal", "Access", "Error", "Audit"];
        foreach ($logTypes as $type) {
            $logDir = LOG_PATH . $type . D;
            if (is_dir($logDir)) {
                $files = glob("{$logDir}*.log");
                foreach ($files as $file) {
                    $logFiles[] = [
                        "type" => $type,
                        "path" => $file,
                        "size" => filesize($file),
                        "modified" => filemtime($file),
                    ];
                }
            }
        }

        // System logs (Apache, Nginx)
        $systemLogs = [
            // Apache
            "/var/log/apache2/access.log",
            "/var/log/apache2/error.log",
            "/var/log/httpd/access_log",
            "/var/log/httpd/error_log",
            // Nginx
            "/var/log/nginx/access.log",
            "/var/log/nginx/error.log",
            // Common locations
            "/var/log/syslog",
            "/var/log/messages",
        ];

        foreach ($systemLogs as $logPath) {
            if (file_exists($logPath) && is_readable($logPath)) {
                $logFiles[] = [
                    "type" => "system",
                    "path" => $logPath,
                    "size" => filesize($logPath),
                    "modified" => filemtime($logPath),
                ];
            }
        }

        return $logFiles;
    }
}

if (function_exists("cli_git") === !1) {
    function cli_git(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "[Node|Project] Toggle git repository target.";
        }

        $gitDir = ROOT_PATH . ".git";
        $mode = $argv[0] ?? "";

        if (!is_dir($gitDir) && empty($mode)) {
            return "E: Node git not configured at " . ROOT_PATH . "\n";
        }

        $gitIgnore = ROOT_PATH . ".gitignore";

        $isNodeMode =
            file_exists($gitIgnore) &&
            strpos(trim(file_get_contents($gitIgnore)), "!node.php");

        if (!$mode) {
            return "Git targeting " . ($isNodeMode ? "Node" : "Project") . "\n";
        }

        $target = ucfirst(strtolower($mode));
        $source = $target === "Node" ? "Project" : "Node";

        if (
            ($isNodeMode && $target === "Node") ||
            (!$isNodeMode && $target === "Project" && file_exists($gitIgnore))
        ) {
            return "Git already targeting {$target}\n";
        }

        $rootDir = ROOT_PATH . "Git" . DIRECTORY_SEPARATOR;
        $targetDir = $rootDir . $target . DIRECTORY_SEPARATOR;
        $sourceDir = $rootDir . $source . DIRECTORY_SEPARATOR;

        $flagMoveToSource = count(scandir($sourceDir)) > 2;

        $r =
            "Preparing to target Git...\n" .
            ($flagMoveToSource
                ? "Warning: {$sourceDir} contains files, skipping root->source moves.\n\n"
                : "\n");

        foreach ([".git", "README.md", ".gitignore"] as $file) {
            $rootFile = ROOT_PATH . $file;
            $sourceFile = "{$sourceDir}{$file}";
            $targetFile = "{$targetDir}{$file}";

            if (!$flagMoveToSource) {
                if (file_exists($rootFile) && !file_exists($sourceFile)) {
                    $r .= "mv root: {$rootFile} to {$sourceFile}\n";
                    rename($rootFile, $sourceFile);
                }
            }

            if (file_exists($targetFile) && !file_exists($rootFile)) {
                $r .= "mv source: {$targetFile} to {$rootFile}\n";
                rename($targetFile, $rootFile);
            }
        }

        return "{$r}\nGit now targeting {$target}\n";
    }
}

if (function_exists("cli_test") === !1) {
    function cli_test(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<type> [filter] Runs tests of specified type with optional filter.";
        }

        $type = $argv[0] ?? "Unit"; // Default to Unit tests
        $filter = $argv[1] ?? "";

        $testTypes = ["Unit", "Integration", "Contract", "E2E"];

        if (!in_array($type, $testTypes)) {
            return "E: Invalid test type. Available: " .
                implode(", ", $testTypes) .
                "\n";
        }

        $testPath = ROOT_PATH . "Test" . DIRECTORY_SEPARATOR . $type;

        if (!is_dir($testPath)) {
            return "E: Test directory not found: {$testPath}\n";
        }

        $phpFiles = glob($testPath . DIRECTORY_SEPARATOR . "*.php");

        if (empty($phpFiles)) {
            return "No {$type} tests found.\n";
        }

        $output = "Running {$type} tests...\n";
        $output .= "\n";

        $passed = 0;
        $failed = 0;
        $total = 0;

        foreach ($phpFiles as $testFile) {
            $fileName = basename($testFile);

            // Apply filter if provided
            if ($filter && strpos($fileName, $filter) === false) {
                continue;
            }

            $output .= "Test: {$fileName}\n";

            ob_start();
            try {
                include_once $testFile;

                $functions = get_defined_functions()["user"];
                $testFunctions = array_filter(
                    $functions,
                    fn($f) => str_starts_with($f, "test_"),
                );

                foreach ($testFunctions as $testFunc) {
                    $total++;
                    try {
                        $testFunc();
                        $output .= "\t✓ {$testFunc}()\n";
                        $passed++;
                    } catch (Exception $e) {
                        $output .=
                            "\t✗ {$testFunc}(): " . $e->getMessage() . "\n";
                        $failed++;
                    }
                }

                $classes = get_declared_classes();
                foreach ($classes as $class) {
                    if (
                        str_ends_with($class, "Test") ||
                        str_ends_with($class, "TestCase")
                    ) {
                        $reflection = new ReflectionClass($class);
                        if ($reflection->hasMethod("run")) {
                            $total++;
                            try {
                                $instance = $reflection->newInstance();
                                $instance->run();
                                $output .= "\t✓ {$class}::run()\n";
                                $passed++;
                            } catch (Exception $e) {
                                $output .=
                                    "\t✗ {$class}::run(): " .
                                    $e->getMessage() .
                                    "\n";
                                $failed++;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $output .= "\t✗ Error loading test: " . $e->getMessage() . "\n";
                $failed++;
            }
            ob_end_clean();
        }

        $output .= "\n";
        $output .= "Results: {$passed}/{$total} passed, {$failed} failed\n";

        if ($failed > 0) {
            http_response_code(1); // Non-zero exit code for CLI
        }

        return $output;
    }
}

if (function_exists("cli_env") === !1) {
    function cli_env(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<action> [key=value] Manage environment variables. Actions: list, set, get";
        }

        $action = $argv[0] ?? "list";
        $envFile = ROOT_PATH . ".env";

        if (!file_exists($envFile)) {
            file_put_contents($envFile, "# Environment variables\n");
        }

        switch ($action) {
            case "list":
                $lines = file(
                    $envFile,
                    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
                );
                $output = "Environment variables:\n";
                foreach ($lines as $line) {
                    if (!str_starts_with($line, "#")) {
                        $output .= "{$line}\n";
                    }
                }
                return $output;

            case "set":
                if (count($argv) < 2) {
                    return "E: Usage: env set KEY=VALUE\n";
                }

                $keyValue = $argv[1];
                if (strpos($keyValue, "=") === false) {
                    return "E: Invalid format. Use KEY=VALUE\n";
                }

                [$key, $value] = explode("=", $keyValue, 2);
                $key = trim($key);
                $value = trim($value);

                $lines = file($envFile, FILE_IGNORE_NEW_LINES);
                $found = false;

                foreach ($lines as &$line) {
                    if (str_starts_with($line, "{$key}=")) {
                        $line = "{$key}={$value}";
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $lines[] = "{$key}={$value}";
                }

                file_put_contents($envFile, implode("\n", $lines));
                return "Set {$key}={$value}\n";

            case "get":
                if (count($argv) < 2) {
                    return "E: Usage: env get KEY\n";
                }

                $key = $argv[1];
                $lines = file($envFile, FILE_IGNORE_NEW_LINES);

                foreach ($lines as $line) {
                    if (str_starts_with($line, $key . "=")) {
                        [, $value] = explode("=", $line, 2);
                        return "{$key}={$value}\n";
                    }
                }

                return "E: Key '{$key}' not found\n";

            default:
                return "E: Unknown action. Available: list, set, get\n";
        }
    }
}

if (function_exists("cli_new") === !1) {
    function cli_new(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<resource> <name> Creates new raw resource with improvised boilerplate.";
        }

        if (($call = $argv[0] ?? null) && ($name = $argv[1] ?? null)) {
            foreach (callStructure() as $c) {
                if ($c[0] === $call) {
                    if (strpos($c[1], "Migration") !== false) {
                        $timestamp = date("Ymd_His");
                        $safeName = preg_replace("/[^a-zA-Z0-9_]/", "_", $name);
                        $migrationName = "{$timestamp}_{$safeName}";

                        $fc = generateBoilerplate($c[1], $safeName, ROOT_PATH);
                        $fn =
                            $c[1] .
                            DIRECTORY_SEPARATOR .
                            "{$migrationName}{$fc[1]}.php";

                        if (strpos($c[1], "Migration/PHP") !== false) {
                            $className = str_replace(
                                ["-", "_"],
                                "",
                                ucwords($safeName, "-_"),
                            );
                            $content = <<<PHP
                            <?php declare(strict_types=1);

                            class {$className}PHP
                            {
                                public function up(): void
                                {
                                    // Migration logic here
                                }

                                public function down(): void
                                {
                                    // Rollback logic here
                                }
                            }
                            PHP;

                            file_put_contents($fn, $content);
                            $size = filesize($fn);
                        } else {
                            $size = file_put_contents($fn, $fc[0]);
                        }

                        return "Migration file created at {$fn} size {$size} bytes.\n";
                    } elseif (strpos($c[1], "Public") !== false) {
                        $ext = strpos($name, ".") !== false ? "" : ".php";
                        $fn = $c[1] . DIRECTORY_SEPARATOR . $name . $ext;
                        if (!file_exists($fn)) {
                            $size = file_put_contents($fn, "\n");
                            return "File created at {$fn} size {$size} bytes.\n";
                        } else {
                            return "E: File {$fn} already exists.\n";
                        }
                    } else {
                        // Regular resource creation
                        $fc = generateBoilerplate($c[1], $name, ROOT_PATH);
                        $fn =
                            $c[1] . DIRECTORY_SEPARATOR . "{$name}{$fc[1]}.php";
                        if (!file_exists($fn)) {
                            $size = file_put_contents($fn, $fc[0]);
                            return "File created at {$fn} size {$size} bytes.\n";
                        } else {
                            return "E: File {$fn} already exists.\n";
                        }
                    }
                }
            }
            return "E: Could not create resource, invalid resource name {$call}.\n";
        }
        return "E: Missing argument(s), call new <resource> <name>\n";
    }
}

if (function_exists("cli_list") === !1) {
    function cli_list(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<resource> Lists all existing resources of type.";
        }

        if ($call = $argv[0] ?? null) {
            foreach (callStructure() as $c) {
                if ($c[0] === $call) {
                    if (
                        $resources = glob($c[1] . DIRECTORY_SEPARATOR . "*.*")
                    ) {
                        $r = "Found (" . count($resources) . ") resources:\n";
                        foreach ($resources as $fp) {
                            $mtime = date("Y-m-d H:i:s", filemtime($fp));
                            $fsize = number_format(filesize($fp));
                            $r .= "{$mtime} $fp, {$fsize} bytes\n";
                        }
                        return $r;
                    }
                    return "No resources for {$call} found at {$c[1]}.\n";
                }
            }
            return "E: Could not list resource, invalid resource name {$call}.\n";
        }

        $r = "Available resources:\n";
        foreach (callStructure() as $resource) {
            $rp = str_replace(ROOT_PATH, "", $resource[1]);
            $r .= "<{$resource[0]}> {$resource[2]} ({$rp}) \n";
        }

        return "{$r}\nCall list <resource>\n";
    }
}

if (function_exists("cli_like") === !1) {
    function cli_like(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<search_term> Searches resources, functions and classes by name or path.";
        }

        if ($search = $argv[0] ?? null) {
            $search = strtolower($search);
            $matches = [];
            $seen = [];

            foreach (callStructure() as $c) {
                $relPath = str_starts_with($c[1], ROOT_PATH)
                    ? substr($c[1], strlen(ROOT_PATH))
                    : $c[1];

                if (strpos(strtolower($relPath), $search) !== false) {
                    $key = "resource:{$relPath}";
                    if (!isset($seen[$key])) {
                        $matches[] = "[resource] <{$c[0]}> {$relPath} - {$c[2]}";
                        $seen[$key] = true;
                    }
                }
            }

            # Search functions
            foreach (get_defined_functions() as $type => $functions) {
                foreach ($functions as $function) {
                    if (strpos(strtolower($function), $search) !== false) {
                        $key = "function:{$function}";
                        if (!isset($seen[$key])) {
                            $source = $type === "user" ? "user" : "internal";
                            $matches[] = "[function:{$source}] {$function}()";
                            $seen[$key] = true;
                        }
                    }
                }
            }

            # Search classes
            foreach (get_declared_classes() as $class) {
                if (strpos(strtolower($class), $search) !== false) {
                    $key = "class:{$class}";
                    if (!isset($seen[$key])) {
                        $reflection = new ReflectionClass($class);
                        $source = $reflection->isInternal()
                            ? "internal"
                            : "user";
                        $modifiers = [];
                        $reflection->isAbstract() &&
                            ($modifiers[] = "abstract");
                        $reflection->isFinal() && ($modifiers[] = "final");
                        $mod = $modifiers
                            ? "[" . implode(" ", $modifiers) . "] "
                            : "";
                        $matches[] = "[class:{$source}] {$mod}{$class}";
                        $seen[$key] = true;
                    }
                }
            }

            # Search interfaces
            foreach (get_declared_interfaces() as $interface) {
                if (strpos(strtolower($interface), $search) !== false) {
                    $key = "interface:{$interface}";
                    if (!isset($seen[$key])) {
                        $reflection = new ReflectionClass($interface);
                        $source = $reflection->isInternal()
                            ? "internal"
                            : "user";
                        $matches[] = "[interface:{$source}] {$interface}";
                        $seen[$key] = true;
                    }
                }
            }

            # Search traits
            foreach (get_declared_traits() as $trait) {
                if (strpos(strtolower($trait), $search) !== false) {
                    $key = "trait:{$trait}";
                    if (!isset($seen[$key])) {
                        $reflection = new ReflectionClass($trait);
                        $source = $reflection->isInternal()
                            ? "internal"
                            : "user";
                        $matches[] = "[trait:{$source}] {$trait}";
                        $seen[$key] = true;
                    }
                }
            }

            # Search constants with value preview
            foreach (get_defined_constants(true) as $scope => $constants) {
                foreach ($constants as $name => $value) {
                    if (strpos(strtolower($name), $search) !== false) {
                        $key = "constant:{$name}";
                        if (!isset($seen[$key])) {
                            $valuePreview = is_scalar($value)
                                ? (string) $value
                                : gettype($value);
                            if (strlen($valuePreview) > 30) {
                                $valuePreview =
                                    substr($valuePreview, 0, 27) . "...";
                            }
                            $matches[] = "[constant:{$scope}] {$name} = \"{$valuePreview}\"";
                            $seen[$key] = true;
                        }
                    }
                }
            }

            /* sort($matches); */

            if (!empty($matches) && ($c = count($matches))) {
                $ml = implode("\n", $matches);
                return "Found {$c} match(es):\n{$ml}\n";
            }
            return "No matches found for '{$search}'.\n";
        }
        return "E: Missing search term, call like <term>\n";
    }
}

if (function_exists("cli_new") === !1) {
    function cli_new(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<resource> <name> Creates new raw resource with improvised boilerplate.";
        }

        if (($call = $argv[0] ?? null) && ($name = $argv[1] ?? null)) {
            foreach (callStructure() as $c) {
                if ($c[0] === $call) {
                    $fc = generateBoilerplate($c[1], $name, ROOT_PATH);
                    $fn = $c[1] . DIRECTORY_SEPARATOR . "{$name}{$fc[1]}.php";
                    if (!file_exists($fn)) {
                        $size = file_put_contents($fn, $fc[0]);
                        return "File created at {$fn} size {$size} bytes.\n";
                    } else {
                        return "E: File {$fn} already exists.\n";
                    }
                }
            }
            return "E: Could not create resource, invalid resource name {$call}.\n";
        }
        return "E: Missing argument(s), call new <resource> <name>\n";
    }
}

if (function_exists("cli_migrate") === !1) {
    function cli_migrate(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "<action> [target] Manage migrations. Actions: up, down, status, create";
        }

        $action = $argv[0] ?? "status";
        $target = $argv[1] ?? "";

        $migrationPath = ROOT_PATH . "Migration";

        if (!is_dir($migrationPath)) {
            return "E: Migration directory not found: {$migrationPath}\n";
        }

        $trackingFile = ROOT_PATH . ".migrations.json";

        if (!file_exists($trackingFile)) {
            file_put_contents(
                $trackingFile,
                json_encode(
                    [
                        "SQL" => [],
                        "PHP" => [],
                    ],
                    JSON_PRETTY_PRINT,
                ),
            );
        }

        $tracking = json_decode(file_get_contents($trackingFile), true);

        switch ($action) {
            case "status":
                $output = "Migration Status:\n";
                $output .= "\n";

                foreach (["SQL", "PHP"] as $type) {
                    $output .= "\n{$type} Migrations:\n";

                    $migrations = glob(
                        $migrationPath .
                            DIRECTORY_SEPARATOR .
                            $type .
                            DIRECTORY_SEPARATOR .
                            "*.php",
                    );

                    if (empty($migrations)) {
                        $output .= "  No migrations found\n";
                        continue;
                    }

                    foreach ($migrations as $migration) {
                        $fileName = basename($migration, ".php");
                        $status = in_array($fileName, $tracking[$type] ?? [])
                            ? "APPLIED"
                            : "PENDING";
                        $output .= "  {$fileName}: {$status}\n";
                    }
                }
                return $output;

            case "up":
                return migrateUp(
                    $tracking,
                    $trackingFile,
                    $migrationPath,
                    $target,
                );

            case "down":
                return migrateDown(
                    $tracking,
                    $trackingFile,
                    $migrationPath,
                    $target,
                );

            case "create":
                if (!$target) {
                    return "E: Missing migration name. Usage: migrate create <name>\n";
                }
                return createMigration($migrationPath, $target);

            default:
                return "E: Unknown action. Available: status, up, down, create\n";
        }
    }

    function migrateUp(
        array $tracking,
        string $trackingFile,
        string $migrationPath,
        string $target,
    ): string {
        $output = "Running migrations up...\n";
        $applied = [];

        foreach (["SQL", "PHP"] as $type) {
            $migrations = glob(
                $migrationPath .
                    DIRECTORY_SEPARATOR .
                    $type .
                    DIRECTORY_SEPARATOR .
                    "*.php",
            );
            sort($migrations);

            foreach ($migrations as $migration) {
                $fileName = basename($migration, ".php");

                if (in_array($fileName, $tracking[$type] ?? [])) {
                    continue;
                }

                if ($target && $fileName !== $target) {
                    continue;
                }

                $output .= "Applying {$type} migration: {$fileName}\n";

                try {
                    if ($type === "PHP") {
                        include_once $migration;

                        $className = str_replace(
                            ["-", "_"],
                            "",
                            ucwords($fileName, "-_"),
                        );
                        if (class_exists($className)) {
                            $instance = new $className();
                            if (method_exists($instance, "up")) {
                                $instance->up();
                            }
                        }
                    } else {
                        $sqlFile = str_replace(".php", ".sql", $migration);
                        if (file_exists($sqlFile)) {
                            $output .= "\t[SQL execution would happen here]\n";
                        }
                    }

                    $tracking[$type][] = $fileName;
                    $applied[] = $fileName;
                } catch (Exception $e) {
                    $output .= "\t✗ Failed: " . $e->getMessage() . "\n";
                }
            }
        }

        if (!empty($applied)) {
            file_put_contents(
                $trackingFile,
                json_encode($tracking, JSON_PRETTY_PRINT),
            );
            $output .=
                "\nApplied migrations: " . implode(", ", $applied) . "\n";
        } else {
            $output .= "\nNo new migrations to apply.\n";
        }

        return $output;
    }

    function migrateDown(
        array $tracking,
        string $trackingFile,
        string $migrationPath,
        string $target,
    ): string {
        $output = "Rolling back migrations...\n";
        $rolledBack = [];

        foreach (["SQL", "PHP"] as $type) {
            if (empty($tracking[$type])) {
                continue;
            }

            $applied = array_reverse($tracking[$type]);

            foreach ($applied as $fileName) {
                if ($target && $fileName !== $target) {
                    continue;
                }

                $migrationFile =
                    $migrationPath .
                    DIRECTORY_SEPARATOR .
                    $type .
                    DIRECTORY_SEPARATOR .
                    $fileName .
                    ".php";

                if (!file_exists($migrationFile)) {
                    continue;
                }

                $output .= "Rolling back {$type} migration: {$fileName}\n";

                try {
                    if ($type === "PHP") {
                        include_once $migrationFile;

                        $className = str_replace(
                            ["-", "_"],
                            "",
                            ucwords($fileName, "-_"),
                        );
                        if (class_exists($className)) {
                            $instance = new $className();
                            if (method_exists($instance, "down")) {
                                $instance->down();
                            }
                        }
                    } else {
                        $sqlFile = str_replace(
                            ".php",
                            ".down.sql",
                            $migrationFile,
                        );
                        if (file_exists($sqlFile)) {
                            $output .= "  [SQL rollback would happen here]\n";
                        }
                    }

                    $key = array_search($fileName, $tracking[$type]);
                    if ($key !== false) {
                        unset($tracking[$type][$key]);
                        $tracking[$type] = array_values($tracking[$type]);
                    }

                    $rolledBack[] = $fileName;
                } catch (Exception $e) {
                    $output .= "\t✗ Failed: " . $e->getMessage() . "\n";
                }

                if ($target && $fileName === $target) {
                    break;
                }
            }
        }

        if (!empty($rolledBack)) {
            file_put_contents(
                $trackingFile,
                json_encode($tracking, JSON_PRETTY_PRINT),
            );
            $output .=
                "\nRolled back migrations: " .
                implode(", ", $rolledBack) .
                "\n";
        } else {
            $output .= "\nNo migrations to roll back.\n";
        }

        return $output;
    }

    function createMigration(string $migrationPath, string $name): string
    {
        $timestamp = date("Ymd_His");
        $safeName = preg_replace("/[^a-zA-Z0-9_]/", "_", $name);
        $fileName = "{$timestamp}_{$safeName}";

        echo "Migration type (SQL/PHP) [PHP]: ";
        $type = trim(fgets(STDIN)) ?: "PHP";
        $type = strtoupper($type);

        if (!in_array($type, ["SQL", "PHP"])) {
            return "E: Invalid migration type. Must be SQL or PHP.\n";
        }

        $migrationDir = $migrationPath . DIRECTORY_SEPARATOR . $type;

        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0777, true);
        }

        $className = str_replace(["-", "_"], "", ucwords($safeName, "-_"));

        if ($type === "PHP") {
            $content = <<<PHP
            <?php declare(strict_types=1);

            class {$className}
            {
                public function up(): void
                {
                    // Migration logic here
                }

                public function down(): void
                {
                    // Rollback logic here
                }
            }
            PHP;

            $filePath =
                $migrationDir . DIRECTORY_SEPARATOR . $fileName . ".php";
            file_put_contents($filePath, $content);

            return "Created PHP migration: {$filePath}\n";
        } else {
            $sqlFile = $migrationDir . DIRECTORY_SEPARATOR . $fileName . ".sql";
            $downSqlFile =
                $migrationDir . DIRECTORY_SEPARATOR . $fileName . ".down.sql";

            file_put_contents(
                $sqlFile,
                "-- SQL migration: {$name}\n-- Up migration\n",
            );
            file_put_contents(
                $downSqlFile,
                "-- SQL migration: {$name}\n-- Down migration (rollback)\n",
            );

            $content = <<<PHP
            <?php declare(strict_types=1);

            class {$className}
            {
                public function up(): void
                {
                    // This migration uses SQL files
                    // See: {$fileName}.sql
                }

                public function down(): void
                {
                    // Rollback SQL in: {$fileName}.down.sql
                }
            }
            PHP;

            $phpFilePath =
                $migrationDir . DIRECTORY_SEPARATOR . $fileName . ".php";
            file_put_contents($phpFilePath, $content);

            return "Created SQL migration:\n  {$sqlFile}\n  {$downSqlFile}\n  {$phpFilePath}\n";
        }
    }
}

if (function_exists("cli_serve") === !1) {
    function cli_serve(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "[port] Starts PHP built-in web server for current node.";
        }

        $port = $argv[0] ?? "8000";
        $host = "localhost";
        $documentRoot = ROOT_PATH . "Public" . DIRECTORY_SEPARATOR . "Entry";

        $socket = @fsockopen($host, (int) $port);
        if ($socket) {
            fclose($socket);
            return "E: Port {$port} is already in use.\n";
        }

        $command = sprintf(
            "php -S %s:%s -t %s",
            $host,
            $port,
            escapeshellarg($documentRoot),
        );

        $output = "Starting development server at http://{$host}:{$port}/\n";
        $output .= "Document root: {$documentRoot}\n";
        $output .= "Press Ctrl+C to stop\n\n";

        $output .= "Run manually:\n  {$command}\n";

        return $output;
    }
}

if (function_exists("cli_log") === !1) {
    function cli_log(bool $tooltip = false, array $argv): string
    {
        if ($tooltip) {
            return "[action] [options] Manage and view logs. Actions: list, show, clear, tail";
        }

        $action = $argv[0] ?? "list";
        $options = array_slice($argv, 1);

        return match ($action) {
            "list" => listLogs($options),
            "show" => showLogs($options),
            "clear" => clearLogs($options),
            "tail" => tailLogs($options),
            default
                => "E: Unknown action. Available: list, show, clear, tail\n",
        };
    }

    function listLogs(array $options): string
    {
        $logFiles = getAllLogFiles();
        $output = "Available Log Files:\n\n";

        $totalSize = 0;
        if (empty($logFiles)) {
            $output .= "No files found.\n";
        }

        foreach ($logFiles as $log) {
            $size = number_format($log["size"] / 1024, 2) . " KB";
            $modified = date("Y-m-d H:i:s", $log["modified"]);
            $type = str_pad($log["type"], 10);
            $output .= sprintf(
                "[%s] %-60s %12s %s\n",
                $type,
                $log["path"],
                $size,
                $modified,
            );
            $totalSize += $log["size"];
        }

        $size = number_format($totalSize / (1024 * 1024), 2);
        $output .= "\nTotal: " . count($logFiles) . " files, {$size} MB\n";

        return $output;
    }

    function showLogs(array $options): string
    {
        if (empty($options)) {
            return "E: Specify log file or type. Usage: log show <file|type> [limit]\n";
        }

        $target = $options[0];
        $limit = $options[1] ?? 50;

        $logFiles = getAllLogFiles();
        $selectedLogs = [];

        foreach ($logFiles as $log) {
            if ($log["type"] === $target || $log["path"] === $target) {
                $selectedLogs[] = $log["path"];
            }
        }

        if (empty($selectedLogs)) {
            foreach ($logFiles as $log) {
                if (strpos($log["path"], $target) !== false) {
                    $selectedLogs[] = $log["path"];
                }
            }
        }

        if (empty($selectedLogs)) {
            return "E: No logs found matching '{$target}'\n";
        }

        $allEntries = logReadFilesArray($selectedLogs);
        $limitedEntries = array_slice($allEntries, 0, $limit);

        $output =
            "Showing {$limit} of " . count($allEntries) . " log entries:\n";

        foreach ($limitedEntries as $entry) {
            $timestamp = $entry["timestamp"] ?? "unknown";
            $type = $entry["type"] ?? "unknown";
            $message = $entry["message"] ?? "";
            $function = $entry["function"] ?? "";

            $output .= sprintf(
                "[%s] %-10s %-20s %s\n",
                $timestamp,
                $type,
                $function,
                substr($message, 0, 40) . (strlen($message) > 40 ? "..." : ""),
            );

            if (isset($entry["data"]) && !empty($entry["data"])) {
                $dataStr = json_encode($entry["data"], JSON_PRETTY_PRINT);
                $lines = explode("\n", $dataStr);
                foreach (array_slice($lines, 0, 3) as $line) {
                    $output .= "  {$line}\n";
                }
                if (count($lines) > 3) {
                    $output .= "  ...\n";
                }
            }
        }

        return $output;
    }

    function clearLogs(array $options): string
    {
        if (empty($options)) {
            return "E: Specify what to clear. Usage: log clear <file|type|all>\n";
        }

        $target = $options[0];
        $logFiles = getAllLogFiles();
        $cleared = 0;
        $totalSize = 0;

        if ($target === "all") {
            foreach ($logFiles as $log) {
                if (file_exists($log["path"]) && is_writable($log["path"])) {
                    $size = filesize($log["path"]);
                    if (file_put_contents($log["path"], "") !== false) {
                        $cleared++;
                        $totalSize += $size;
                    }
                }
            }
            return "Cleared {$cleared} log files, freed " .
                number_format($totalSize / (1024 * 1024), 2) .
                " MB\n";
        }

        foreach ($logFiles as $log) {
            if (
                ($log["type"] === $target || $log["path"] === $target) &&
                file_exists($log["path"]) &&
                is_writable($log["path"])
            ) {
                $size = filesize($log["path"]);
                if (file_put_contents($log["path"], "") !== false) {
                    $cleared++;
                    $totalSize += $size;
                }
            }
        }

        if ($cleared > 0) {
            $size = number_format($totalSize / 1024, 2);
            return "Cleared {$cleared} log files, freed {$size} KB\n";
        }

        return "No logs cleared. Check file permissions or path.\n";
    }

    function tailLogs(array $options): string
    {
        if (empty($options)) {
            return "E: Specify log file. Usage: log tail <file> [lines]\n";
        }

        $file = $options[0];
        $lines = $options[1] ?? 10;

        if (!file_exists($file)) {
            return "E: File not found: {$file}\n";
        }

        $content = shell_exec("tail -n {$lines} " . escapeshellarg($file));

        return "Last {$lines} lines of {$file}:\n\n" .
            ($content ?: "No content or error reading file\n");
    }
}

# Include this node files and if $NODE_REQUIRE is not empty do subincludes.
includeStructure($NODE_STRUCTURE, $LOCAL_PATH, $NODE_REQUIRE);
unset($NODE_STRUCTURE, $NODE_REQUIRE);

# Controls.
if (PHP_SAPI === "cli" && $LOCAL_PATH === ROOT_PATH) {
    if (isset($argv[1])) {
        $cli_func = "cli_{$argv[1]}";
        if (function_exists($cli_func)) {
            $r = $cli_func(false, array_slice($argv, 2));
        }
        unset($cli_func);
    }
    $r ??= cli_help(false, []);

    $u = microtime(true) - $TIME_START;
    $m = memory_get_peak_usage() / 1048576;

    $title = NODE_NAME . " // PHP " . PHP_VERSION;
    printf("{$title}, Time: %.4fs, RAM: %.2fMB", $u, $m);

    unset($TIME_START, $LOCAL_PATH, $u, $m, $title);

    echo ", User defined global scope variables: [" .
        implode(
            ",",
            array_diff(array_keys(get_defined_vars()), [
                "r",
                "argv",
                "argc",
                "_GET",
                "_POST",
                "_COOKIE",
                "_FILES",
                "_SERVER",
            ]),
        ) .
        "]\n\n";

    die("{$r}\n");
}
