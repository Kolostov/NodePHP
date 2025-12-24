<?php declare(strict_types=1);
error_reporting(E_ALL);

$LOCAL_PATH = realpath(__DIR__) . DIRECTORY_SEPARATOR;

# Main entry point declarations
if (!defined("NODE_NAME")) {
    ini_set("display_errors", !0);
    $TIME_START = microtime(true);
    $ROOT_PATHS = [$LOCAL_PATH];
    $RUN_STRING = [];

    define("D", DIRECTORY_SEPARATOR);

    define("ROOT_PATH", $LOCAL_PATH);
    define("LOG_PATH", ROOT_PATH . "Log" . D);

    define("SUPERGLOBALS", ["argv", "argc", "_GET", "_POST", "_COOKIE", "_FILES", "_SERVER"]);
} else {
    # Add self to root paths for file inclusion checkng.
    $ROOT_PATHS[] = $LOCAL_PATH;
}

/**
 * Constructed in node_structure
 * @var string $NODE_STRUCTURE_DEFINITIONS # Path to structure definitions file, usually 'node.json'
 * @var array  $NODE_STRUCTURE Array containing all resource types.
 * @var array  $NODE_REQUIRE Array of paths to another nodes required by this node.
 */

# node_structure begin
$NODE_STRUCTURE_DEFINITIONS = "{$LOCAL_PATH}node.json";

try {
    $node = file_exists($NODE_STRUCTURE_DEFINITIONS)
        ? json_decode(
            file_get_contents($NODE_STRUCTURE_DEFINITIONS),
            !0,
            512,
            JSON_THROW_ON_ERROR,
        )
        : null;

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
        "Database" => [
            "Schema" => "Database structure definitions and DDL",
            "Seed" => "Initial and test data population scripts",
            "Fixture" => "Test data sets and factories",
            "Procedure" => "Stored procedures and functions",
            "View" => "Database view definitions",
            "Trigger" => "Database trigger definitions",
            "Connection" => "Database configuration and connection pools",
            "Flat" => [
                "Storage" => "File-based database implementations",
                "JSON" => "JSON-based document storage",
                "Serialized" => "PHP serialized data files",
                "Index" => "Flat file indexing systems",
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
        "Deprecated" => "Files that are considered deprecated.",
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
        "Backup" => "Zips of backed up states",
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
                if (strtolower($value) === "true") {
                    return true;
                }
                if (strtolower($value) === "false") {
                    return false;
                }
                if (strtolower($value) === "null") {
                    return null;
                }
                if (is_numeric($value)) {
                    return strpos($value, ".") !== false
                        ? (float) $value
                        : (int) $value;
                }
                return $value;
            }

            return $default;
        }
    }

    $NODE_REQUIRE = $node["require"] ?? [];
    $RUN_STRING[] = $node["run"] ?? null;

    unset($node, $NODE_NAME);
} catch (Exception $e) {
    $msg = "Invalid {$NODE_STRUCTURE_DEFINITIONS}: " . json_last_error_msg();

    throw new RuntimeException($msg, 0, $e);
}
# node_structure end

# Checking if we're currently within root node to include
# all the base funcitonality.
if (ROOT_PATH !== $LOCAL_PATH) {
    # Skip all of the function declarations.
    #
    # This goto only works at runtime. It does not stop the PHP compiler from seeing the function
    # definition below it. In PHP, functions declared at the top level of a file are "hoisted"
    # (defined as soon as the file is compiled), regardless of whether they are inside an if or
    # after a goto.
    goto node_subinclude;
}

# Safeguard redeclaration of these functions from PHP compiler.
if (!function_exists("f")) {
    # f begin
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

        $fn = ltrim(str_replace(ROOT_PATH, "", $fn), D);

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
    # f end

    # r begin
    function r(
        string $logMessage,
        string $logType = "Internal",
        mixed $return = null,
        null|array|object $dataArray = null,
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
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) .
                "\n",
            FILE_APPEND,
        );

        return $return;
    }
    # r end

    # log_read_file begin
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
    # log_read_file end

    # log_read_files_array begin
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
    # log_read_files_array end

    # get_all_log_files begin
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
    # get_all_log_files end

    # walk_structure begin
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
                    $srl = "{$location}{$name}" . D;

                    $r = [
                        ...$r,
                        ...walkStructure($sub, $callback, $srl, $LOCAL_PATH),
                    ];
                }
            }
        }
        return array_filter($r, fn($x) => !empty($x));
    }
    # walk_structure end

    # deploy_structure begin
    if (function_exists("deployStructure") === !1) {
        function deployStructure(?array $NODE_STRUCTURE = null): void
        {
            walkStructure(
                $NODE_STRUCTURE ?? NODE_STRUCTURE,
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
        deployStructure($NODE_STRUCTURE); # Only deploy for main entrypoint node.
    }
    # deploy_structure end

    # vendor_autoload begin
    /**
     * @var string $LOCAL_PATH Node.php defined path of current node.
     */

    $LOCAL_VENDOR = "{$LOCAL_PATH}vendor" . D . "autoload.php";
    if (file_exists($LOCAL_VENDOR)) {
        include_once $LOCAL_VENDOR;
    }
    unset($LOCAL_VENDOR);
    # vendor_autoload end

    # include_structure begin
    function includeStructure(array $STRUCTURE, string $PATH, array $NODES): void
    {
        $walk = function (string $path): string {
            if (strpos($path, "..") !== false) {
                return "";
            }

            # Exclude from runtimes
            $e = ["Git", "Test", "Public", "Log", "Deprecated", "Backup"];
            if (PHP_SAPI !== "cli") {
                $e = [...$e, ...["Migration"]];
            }

            foreach ($e as $part) {
                $ex = D . $part . D;
                if (strpos($path, $ex)) {
                    return "";
                }
            }

            if (is_dir($path)) {
                if ($php = glob($path . D . "*.php")) {
                    foreach ($php as $fn) {
                        include_once $fn;

                        return $fn;
                    }
                }
            }
            return "";
        };

        # Walk local resources.
        walkStructure($STRUCTURE, $walk, "", $PATH);

        # Include requested nodes.
        if (is_array($NODES) && !empty($NODES)) {
            foreach ($NODES as $node) {
                $path = $PATH . ".." . D . $node;

                if ($check = realpath($path)) {
                    $file = $check . D . "node.php";
                    $size = filesize(__FILE__);

                    if (file_exists($file) && filesize($file) === $size) {
                        include_once $file;
                    } else {
                        # Impossible optimization:
                        # 1. set new node location,
                        # 2. goto to beginnong of current node.php,
                        # 3. process as if in included node
                        # 4. continue within this oop
                        # instead we throw

                        throw new Exception(
                            "Node {$PATH} requires node that is different at: {$path}.\nFix this by updating php node git Node\ngit pull",
                            0,
                        );
                    }
                } else {
                    # Targeted directory simply did not exist.
                    throw new Exception(
                        "Node {$PATH} requires node that does not exist at: {$path}.\nFix this path or remove {$node} from node.json",
                        0,
                    );
                }
            }
        }
    }
    # include_structure end

    # call_structure begin
    function callStructure(): array
    {
        static $calls = [];
        static $structure = [];

        if (!empty($structure)) {
            return $structure;
        }

        $structure = walkStructure(
            NODE_STRUCTURE,
            function (string $path, mixed $v) use (&$calls): array {
                if (glob($path . D . "*", GLOB_ONLYDIR)) {
                    return [];
                }

                $exp = explode(D, $path);

                $i = 0;
                $l = count($exp);

                do {
                    $i++;
                    $slice = array_slice($exp, $l - $i, $i);
                    $call = implode(D, $slice);
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
        return $structure;
    }
    # call_structure end

    # generate_boilerplate begin
    function generateBoilerplate(
        string $call,
        string $name,
        string $LOCAL_PATH,
    ): array {
        $call = str_starts_with($call, $LOCAL_PATH)
            ? substr($call, strlen($LOCAL_PATH))
            : $call;

        $parts = explode(D, trim($call, D));
        $parts = array_filter($parts, fn($p) => !empty($p));

        $leaf = end($parts); // e.g., Repository, Command, Controller
        $type = reset($parts); // e.g., Class, Interface, Function

        $namespace = !empty($parts)
            ? "namespace " . implode("\\", array_map("ucfirst", $parts)) . ";\n\n"
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
    # generate_boilerplate end
}
node_subinclude:

# Include this node files and if $NODE_REQUIRE is not empty do subincludes.
includeStructure($NODE_STRUCTURE, $LOCAL_PATH, $NODE_REQUIRE);

# Free memory
unset($NODE_STRUCTURE, $NODE_REQUIRE);

# Check if node is the root node and load
# all of the CLI only once at root node path.
if ($LOCAL_PATH === ROOT_PATH) {
    if (!empty($RUN_STRING) && ($RUN_STRING = array_reverse($RUN_STRING))) {
        # execute_run begin
        function executeRun(string $entry): void
        {
            if (
                preg_match(
                    '/^([^:]+)::([^(]+)(?:\((.*)\))?$/',
                    $entry,
                    $matches,
                )
            ) {
                $class = $matches[1];
                $method = $matches[2];
                $argString = $matches[3] ?? "";

                if (class_exists($class) && method_exists($class, $method)) {
                    $arguments = [];
                    if ($argString) {
                        $arguments = array_map(
                            "trim",
                            explode(",", $argString),
                        );
                        $arguments = array_map(function ($arg) {
                            if (preg_match('/^[\'"](.*)[\'"]$/', $arg, $m)) {
                                return $m[1];
                            }
                            return $arg;
                        }, $arguments);
                    }

                    call_user_func_array([$class, $method], $arguments);
                    return;
                }
            }

            if (str_contains($entry, "::")) {
                [$class, $method] = explode("::", $entry, 2);

                if (class_exists($class)) {
                    if (method_exists($class, $method)) {
                        call_user_func([$class, $method]);
                        return;
                    } else {
                        r("Entry method {$entry} not found", "Error");
                    }
                } else {
                    r("Entry class {$class} not found", "Error");
                }
            } elseif (class_exists($entry)) {
                $instance = new $entry();

                if (method_exists($instance, "__invoke")) {
                    $instance();
                } elseif (method_exists($instance, "run")) {
                    $instance->run();
                } elseif (method_exists($instance, "execute")) {
                    $instance->execute();
                } else {
                    r("Entry class {$entry} has no executable method", "Error");
                }
                return;
            } elseif (function_exists($entry)) {
                $entry();
                return;
            }

            r("Invalid entry point: {$entry}", "Error");
            http_response_code(500);

            die(
                "Application entry point configuration error [node.json -> run]."
            );
        }
        # execute_run end

        foreach (array_filter($RUN_STRING, fn($x) => !empty($x)) as $run) {
            executeRun($run);
        }
        unset($run);
    }
    unset($RUN_STRING);

    if (PHP_SAPI === "cli") {
        # cli_backup begin
        function cli_backup(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<name> Creates a backup zip of the node.";
            }

            $backupName = $argv[0] ?? date("Ymd");
            $backupDir = ROOT_PATH . "Backup" . D;

            $zipName = "{$backupDir}{$backupName}.zip";

            $hasZip = false;
            if (extension_loaded("zip")) {
                $hasZip = true;
            } elseif (function_exists("class_exists") && class_exists("ZipArchive")) {
                $hasZip = true;
            } elseif (function_exists("zip_open")) {
                $hasZip = true;
            }

            if (!$hasZip) {
                return createTarBackup($backupDir, $backupName);
            }

            if (file_exists($zipName)) {
                return "E: Backup '{$backupName}.zip' already exists\n";
            }

            $zip = new ZipArchive();
            if ($zip->open($zipName, ZipArchive::CREATE) !== true) {
                return "E: Cannot create zip file\n";
            }

            $exclude = ["Backup", "Log", "Deprecated", "vendor", "node_modules"];
            $added = 0;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    ROOT_PATH,
                    RecursiveDirectoryIterator::SKIP_DOTS,
                ),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach ($iterator as $file) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen(ROOT_PATH));

                $skip = false;
                foreach ($exclude as $ex) {
                    if (strpos($relativePath, $ex . D) === 0 || $relativePath === $ex) {
                        $skip = true;
                        break;
                    }
                }

                if ($skip) {
                    continue;
                }

                if ($file->isDir()) {
                    $zip->addEmptyDir($relativePath);
                } else {
                    $zip->addFile($filePath, $relativePath);
                    $added++;
                }
            }

            $zip->close();
            $size = filesize($zipName);

            return "Backup created: {$zipName} ({$added} files, " .
                number_format($size / 1024 / 1024, 2) .
                " MB)\n";
        }

        function createTarBackup(string $backupDir, string $backupName): string
        {
            $tarName = "{$backupDir}{$backupName}.tar.gz";

            if (file_exists($tarName)) {
                return "E: Backup '{$backupName}.tar.gz' already exists\n";
            }

            $excludeFile = "{$backupDir}exclude.txt";
            $excludes = ["Backup", "Log", "Deprecated", "vendor", "node_modules"];

            file_put_contents($excludeFile, implode("\n", $excludes));

            $currentDir = getcwd();
            chdir(ROOT_PATH);

            $cmd =
                "tar -czf " .
                escapeshellarg($tarName) .
                " --exclude-from=" .
                escapeshellarg($excludeFile) .
                " . 2>&1";

            exec($cmd, $output, $returnCode);

            chdir($currentDir);
            unlink($excludeFile);

            if ($returnCode === 0) {
                $size = filesize($tarName);
                $fileCount = countFilesInTar($tarName);

                return "Backup created (tar.gz): {$tarName} ({$fileCount} files, " .
                    number_format($size / 1024 / 1024, 2) .
                    " MB)\n" .
                    "Note: Using tar.gz as PHP zip extension is not available.\n";
            }

            return "E: Failed to create backup. Try installing:\n" .
                "  Debian/Ubuntu: sudo apt-get install php8.5-zip\n" .
                "  Or enable in php.ini: extension=zip.so\n" .
                "  Error: " .
                implode("\n", $output) .
                "\n";
        }

        function countFilesInTar(string $tarFile): int
        {
            exec(
                "tar -tzf " . escapeshellarg($tarFile) . " 2>/dev/null | wc -l",
                $output,
                $returnCode,
            );

            if ($returnCode === 0 && isset($output[0]) && is_numeric($output[0])) {
                return (int) $output[0];
            }

            return 0;
        }

        function test_cli_backup(): int
        {
            $backupDir = ROOT_PATH . "Backup" . D;

            if (!is_dir($backupDir)) {
                return 1;
            }

            if (!is_writable($backupDir)) {
                return 2;
            }

            $hasZip =
                extension_loaded("zip") ||
                (function_exists("class_exists") && class_exists("ZipArchive"));

            if (!$hasZip) {
                exec("which tar 2>/dev/null", $tarOutput, $tarCode);
                if ($tarCode !== 0) {
                    return 3;
                }
            }

            $backupName = "TEST_BACKUP_" . date("Ymd_His") . "_" . uniqid();
            $expectedExt = $hasZip ? ".zip" : ".tar.gz";
            $expectedFile = $backupDir . $backupName . $expectedExt;

            if (file_exists($expectedFile)) {
                if (!unlink($expectedFile)) {
                    return 4;
                }
            }

            $result = cli_backup(false, [$backupName]);

            if (strpos($result, "E: ") === 0) {
                return 5;
            }

            if (strpos($result, "Backup created") === false) {
                return 6;
            }

            if (!file_exists($expectedFile)) {
                return 7;
            }

            $fileSize = filesize($expectedFile);
            if ($fileSize === 0 || $fileSize < 100) {
                return 8;
            }

            unlink($expectedFile);

            return 0;
        }
        # cli_backup end

        # cli_ctx begin
        function cli_ctx(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<class|function> Show complete context with dependencies";
            }

            if (empty($argv)) {
                return "E: Provide class or function name\n";
            }

            $target = $argv[0];
            $output = "";

            $excludedDirs = ["vendor", "Database", "Logs", "Backup", "Deprecated"];
            $extensions = ["php"];

            function findTarget(string $target, array $structure, array $excludedDirs): ?array
            {
                foreach ($structure as [$call, $path, $desc]) {
                    foreach ($excludedDirs as $excludedDir) {
                        if (str_contains($path, D . $excludedDir . D)) {
                            continue 2;
                        }
                    }

                    foreach (["php"] as $ext) {
                        $pattern = $path . D . "*." . $ext;
                        $files = glob($pattern);
                        foreach ($files as $file) {
                            $content = file_get_contents($file);
                            if (!$content) {
                                continue;
                            }

                            // Check for function
                            if (!str_contains($target, "::") && !str_contains($target, "->")) {
                                $funcContent = getFunctionBodyWithDocblock($content, $target);
                                if ($funcContent) {
                                    return [
                                        "type" => "function",
                                        "file" => $file,
                                        "desc" => $desc,
                                        "content" => $funcContent,
                                        "fullContent" => $content,
                                    ];
                                }
                            }

                            // Check for class
                            $className = $target;
                            if (str_contains($target, "::")) {
                                $className = explode("::", $target)[0];
                            }

                            if (preg_match("/\bclass\s+" . preg_quote($className, "/") . "\b/", $content)) {
                                // Extract the whole class
                                $pattern =
                                    "/(\/\*\*.*?\*\/\s*)?\bclass\s+" .
                                    preg_quote($className, "/") .
                                    "\b[^{]*\{((?:[^{}]+|\{(?:[^{}]+|\{[^{}]*\})*\})*)\}/s";
                                if (preg_match($pattern, $content, $matches)) {
                                    return [
                                        "type" => "class",
                                        "file" => $file,
                                        "desc" => $desc,
                                        "content" => $matches[0],
                                        "fullContent" => $content,
                                        "className" => $className,
                                    ];
                                }
                            }
                        }
                    }
                }

                return null;
            }

            function findExternalDependencies(string $content): array
            {
                $deps = [];
                $tokens = token_get_all("<?php " . $content);

                for ($i = 0; $i < count($tokens); $i++) {
                    $token = $tokens[$i];

                    if (is_array($token)) {
                        // Check for class references in extends
                        if ($token[0] === T_EXTENDS) {
                            // Look for class name after extends
                            for ($j = $i + 1; $j < count($tokens); $j++) {
                                if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                                    $deps[] = $tokens[$j][1] . " (class)";
                                    break;
                                } elseif (!is_array($tokens[$j]) && $tokens[$j] === "{") {
                                    break;
                                }
                            }
                        }

                        // Check for new statements
                        if ($token[0] === T_NEW) {
                            // Look for class name after new
                            for ($j = $i + 1; $j < count($tokens); $j++) {
                                if (is_array($tokens[$j])) {
                                    if ($tokens[$j][0] === T_WHITESPACE) {
                                        continue;
                                    }
                                    if ($tokens[$j][0] === T_STRING) {
                                        $deps[] = $tokens[$j][1] . " (class)";
                                        break;
                                    }
                                } elseif ($tokens[$j] === "(") {
                                    break;
                                }
                            }
                        }

                        // Check for function calls
                        if ($token[0] === T_STRING) {
                            $tokenValue = $token[1];

                            // Skip PHP keywords
                            $skip = [
                                "echo",
                                "print",
                                "if",
                                "else",
                                "elseif",
                                "for",
                                "foreach",
                                "while",
                                "do",
                                "switch",
                                "case",
                                "default",
                                "break",
                                "continue",
                                "return",
                                "function",
                                "class",
                                "interface",
                                "trait",
                                "namespace",
                                "use",
                                "extends",
                                "implements",
                                "new",
                                "instanceof",
                                "clone",
                                "true",
                                "false",
                                "null",
                                "self",
                                "parent",
                                "static",
                                "array",
                                "string",
                                "int",
                                "float",
                                "bool",
                                "void",
                                "mixed",
                                "iterable",
                                "callable",
                                "object",
                                "public",
                                "private",
                                "protected",
                                "static",
                                "abstract",
                                "final",
                                "const",
                                "isset",
                                "empty",
                                "eval",
                                "exit",
                                "die",
                                "list",
                                "unset",
                                "include",
                                "include_once",
                                "require",
                                "require_once",
                            ];

                            if (!in_array(strtolower($tokenValue), $skip)) {
                                // Check if it's a function call (next non-whitespace token is '(')
                                for ($j = $i + 1; $j < count($tokens); $j++) {
                                    if (is_array($tokens[$j])) {
                                        if ($tokens[$j][0] === T_WHITESPACE) {
                                            continue;
                                        }
                                        if ($tokens[$j][0] === T_COMMENT || $tokens[$j][0] === T_DOC_COMMENT) {
                                            continue;
                                        }
                                    }
                                    if ($tokens[$j] === "(") {
                                        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $tokenValue)) {
                                            $deps[] = $tokenValue . " (function)";
                                        }
                                        break;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }

                return array_unique($deps);
            }

            function findClassContent(string $className, array $structure, array $excludedDirs): ?array
            {
                foreach ($structure as [$call, $path, $desc]) {
                    foreach ($excludedDirs as $excludedDir) {
                        if (str_contains($path, D . $excludedDir . D)) {
                            continue 2;
                        }
                    }

                    $pattern = $path . D . "*.php";
                    $files = glob($pattern);
                    foreach ($files as $file) {
                        $content = file_get_contents($file);

                        if (preg_match("/\bclass\s+" . preg_quote($className, "/") . "\b/", $content)) {
                            // Extract the whole class
                            $pattern =
                                "/(\/\*\*.*?\*\/\s*)?\bclass\s+" .
                                preg_quote($className, "/") .
                                "\b[^{]*\{((?:[^{}]+|\{(?:[^{}]+|\{[^{}]*\})*\})*)\}/s";
                            if (preg_match($pattern, $content, $matches)) {
                                return [
                                    "file" => $file,
                                    "desc" => $desc,
                                    "content" => $matches[0],
                                ];
                            }
                        }
                    }
                }

                return null;
            }

            function findFunctionContent(string $functionName, array $structure, array $excludedDirs): ?array
            {
                foreach ($structure as [$call, $path, $desc]) {
                    foreach ($excludedDirs as $excludedDir) {
                        if (str_contains($path, D . $excludedDir . D)) {
                            continue 2;
                        }
                    }

                    $pattern = $path . D . "*.php";
                    $files = glob($pattern);
                    foreach ($files as $file) {
                        $content = file_get_contents($file);
                        $funcContent = getFunctionBodyWithDocblock($content, $functionName);
                        if ($funcContent) {
                            return [
                                "file" => $file,
                                "desc" => $desc,
                                "content" => $funcContent,
                            ];
                        }
                    }
                }

                return null;
            }

            $structure = [...[["", ROOT_PATH, ""]], ...callStructure()];
            $targetData = findTarget($target, $structure, $excludedDirs);

            if (!$targetData) {
                return "E: {$target} not found\n";
            }

            $relativePath = str_replace(ROOT_PATH, "", $targetData["file"]);

            // Output main target
            $output .= "# " . $relativePath . "\n";
            if ($targetData["desc"]) {
                $output .= "# " . $targetData["desc"] . "\n";
            }
            $output .= "\n";
            $output .= $targetData["content"] . "\n\n";

            // Find ALL dependencies
            $deps = findExternalDependencies($targetData["content"]);
            $relatedCode = [];

            foreach ($deps as $dep) {
                $originalDep = $dep;

                // Clean type suffix
                if (str_contains($dep, " (class)")) {
                    $dep = str_replace(" (class)", "", $dep);
                    $depType = "class";
                } elseif (str_contains($dep, " (function)")) {
                    $dep = str_replace(" (function)", "", $dep);
                    $depType = "function";
                } else {
                    $depType = "unknown";
                }

                if (!in_array($dep, ["cli_ctx", "cli_rank", "cli_search", "cli_help", "cli_wrap", "TestCase"])) {
                    // Find class
                    if ($depType === "class") {
                        $classData = findClassContent($dep, $structure, $excludedDirs);
                        if ($classData && $classData["file"] !== $targetData["file"]) {
                            $relatedCode[$originalDep] = [
                                "file" => str_replace(ROOT_PATH, "", $classData["file"]),
                                "desc" => $classData["desc"],
                                "content" => $classData["content"],
                                "type" => "class",
                            ];
                        }
                    }
                    // Find function
                    elseif ($depType === "function") {
                        $funcData = findFunctionContent($dep, $structure, $excludedDirs);
                        if ($funcData && $funcData["file"] !== $targetData["file"]) {
                            $relatedCode[$originalDep] = [
                                "file" => str_replace(ROOT_PATH, "", $funcData["file"]),
                                "desc" => $funcData["desc"],
                                "content" => $funcData["content"],
                                "type" => "function",
                            ];
                        }
                    }
                }
            }

            // Output related code
            if (!empty($relatedCode)) {
                $output .= "# related code for context below:\n\n";
                foreach ($relatedCode as $depName => $depData) {
                    $output .= "# " . $depData["file"] . "\n";
                    if ($depData["desc"]) {
                        $output .= "# " . $depData["desc"] . "\n";
                    }
                    $output .= "\n" . $depData["content"] . "\n\n";
                }
            }

            // Add ranking - rank the FILE, not the class as function
            $rankOutput = cli_rank(false, [$targetData["file"]]);
            $resourcesOutput = ""; //cli_list(false, []);

            $rankLines = explode("\n", "{$rankOutput}\n{$resourcesOutput}");
            $output .= "# ranking analysis of: {$targetData["file"]} \n#\n";
            foreach ($rankLines as $line) {
                $output .= "# {$line}\n";
            }

            return $output;
        }
        # cli_ctx end

        # cli_deprecate begin
        function cli_deprecate(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<resource> <name> Makes a copy of deprecated code.";
            }

            if (($call = $argv[0] ?? null) && ($name = $argv[1] ?? null)) {
                foreach (callStructure() as $c) {
                    if ($c[0] === $call) {
                        $path = $c[1];

                        $patterns = [
                            "{$path}" . D . "{$name}.php",
                            "{$path}" . D . "{$name}.*.php",
                            "{$path}" . D . "*{$name}*.php",
                        ];

                        $foundFile = null;
                        foreach ($patterns as $pattern) {
                            $matches = glob($pattern);
                            if (!empty($matches)) {
                                $foundFile = $matches[0];
                                break;
                            }
                        }

                        if (!$foundFile) {
                            return "E: File not found for resource '{$call}' with name '{$name}'\n";
                        }

                        $timestamp = date("Ymd_His");
                        $fileName = basename($foundFile, ".php");
                        $rPath = str_replace(ROOT_PATH, "", $path);

                        $deprecatedDir = ROOT_PATH . "Deprecated" . D . $rPath . D;
                        $deprecatedFile = "{$deprecatedDir}{$fileName}_{$timestamp}.php";

                        if (!is_dir($deprecatedDir)) {
                            mkdir($deprecatedDir, 0777, true);
                        }

                        if (copy($foundFile, $deprecatedFile)) {
                            $size = filesize($foundFile);
                            return "Deprecated: {$foundFile} → {$deprecatedFile} ({$size} bytes)\n";
                        } else {
                            return "E: Failed to copy file to deprecated directory\n";
                        }
                    }
                }
                return "E: Invalid resource name '{$call}'\n";
            }
            return "E: Missing arguments. Usage: deprecate <resource> <name>\n";
        }

        function test_cli_deprecate(): int
        {
            $r = "State";
            $n = "SomeName";
            if (str_starts_with(cli_new(false, [$r, $n]), "E:")) {
                return 1;
            }

            $structures = callStructure();
            $path = array_first(array_filter($structures, fn($x) => $x[0] == $r))[1];

            $nFile = "{$path}" . D . "{$n}{$r}.php";
            if (!file_exists($nFile)) {
                return 2;
            }

            if (str_starts_with(cli_deprecate(false, [$r, $n]), "E:")) {
                return 3;
            }

            unlink($nFile);

            $dFile = ROOT_PATH . "Deprecated" . D . str_replace(ROOT_PATH, "", $nFile);
            $gFile = substr($dFile, 0, strlen($dFile) - 4) . "*.php";
            $glob = glob($gFile);
            if (empty($glob)) {
                return 4;
            }
            $dFile = array_first($glob);

            if (!file_exists($dFile)) {
                return 5;
            }
            unlink($dFile);

            return 0;
        }
        # cli_deprecate end

        # cli_dump begin
        function cli_dump(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<var> Dumps a variable's value.";
            }

            if (empty($argv)) {
                return "E: Usage: dump <variable>\n";
            }

            $input = $argv[0];
            $varName = ltrim($input, '$');

            if (!isset($GLOBALS[$varName])) {
                return "E: Variable \${$varName} not found\n";
            }

            $value = $GLOBALS[$varName];

            ob_start();
            var_dump($value);
            return ob_get_clean();
        }
        # cli_dump end

        # cli_env begin
        function cli_env(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<action> <key|key = value> Actions: list, set, get";
            }

            $action = $argv[0] ?? "list";
            $envFile = ROOT_PATH . ".env";

            if (!file_exists($envFile)) {
                file_put_contents($envFile, "# Environment variables\n");
            }

            switch ($action) {
                case "list":
                    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $output = "Environment variables:\n";
                    foreach ($lines as $line) {
                        if (!str_starts_with($line, "#")) {
                            $output .= "{$line}\n";
                        }
                    }
                    return $output;

                case "set":
                    if (count($argv) < 2) {
                        return "E: Usage: env set KEY = VALUE\n";
                    }

                    $keyValue = $argv[1];
                    if (strpos($keyValue, "=") === false) {
                        return "E: Invalid format. Use KEY = VALUE\n";
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

        function test_cli_env(): int
        {
            $envFile = ROOT_PATH . ".env";
            $backup = null;

            if (file_exists($envFile)) {
                $backup = file_get_contents($envFile);
            }

            file_put_contents($envFile, "# Environment variables\nTEST_KEY=test_value\nANOTHER_KEY=another_value\n");

            if (str_starts_with(cli_env(true, []), "<action>") === false) {
                file_put_contents($envFile, $backup);
                return 1;
            }

            $listResult = cli_env(false, ["list"]);
            if (
                strpos($listResult, "TEST_KEY=test_value") === false ||
                strpos($listResult, "ANOTHER_KEY=another_value") === false
            ) {
                file_put_contents($envFile, $backup);
                return 2;
            }

            $setResult = cli_env(false, ["set", "TEST_KEY=new_value"]);
            if (strpos($setResult, "Set TEST_KEY=new_value") === false) {
                file_put_contents($envFile, $backup);
                return 3;
            }

            $getResult = cli_env(false, ["get", "TEST_KEY"]);
            if ($getResult !== "TEST_KEY=new_value\n") {
                file_put_contents($envFile, $backup);
                return 4;
            }

            $setNewResult = cli_env(false, ["set", "NEW_KEY=new_value_123"]);
            if (strpos($setNewResult, "Set NEW_KEY=new_value_123") === false) {
                file_put_contents($envFile, $backup);
                return 5;
            }

            $listResult2 = cli_env(false, ["list"]);
            if (strpos($listResult2, "NEW_KEY=new_value_123") === false) {
                file_put_contents($envFile, $backup);
                return 6;
            }

            $missingKeyResult = cli_env(false, ["get", "MISSING_KEY"]);
            if (strpos($missingKeyResult, "E: Key 'MISSING_KEY' not found") === false) {
                file_put_contents($envFile, $backup);
                return 7;
            }

            $invalidSetResult = cli_env(false, ["set"]);
            if (strpos($invalidSetResult, "E: Usage: env set KEY = VALUE") === false) {
                file_put_contents($envFile, $backup);
                return 8;
            }

            $invalidFormatResult = cli_env(false, ["set", "NO_EQUALS_SIGN"]);
            if (strpos($invalidFormatResult, "E: Invalid format. Use KEY = VALUE") === false) {
                file_put_contents($envFile, $backup);
                return 9;
            }

            $invalidGetResult = cli_env(false, ["get"]);
            if (strpos($invalidGetResult, "E: Usage: env get KEY") === false) {
                file_put_contents($envFile, $backup);
                return 10;
            }

            $unknownActionResult = cli_env(false, ["unknown"]);
            if (strpos($unknownActionResult, "E: Unknown action. Available: list, set, get") === false) {
                file_put_contents($envFile, $backup);
                return 11;
            }

            if ($backup !== null) {
                file_put_contents($envFile, $backup);
            } else {
                unlink($envFile);
            }

            return 0;
        }
        # cli_env end

        # cli_git begin
        function cli_git(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<Node|Project> Toggle git repository target.";
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

            $rootDir = ROOT_PATH . "Git" . D;
            $targetDir = $rootDir . $target . D;
            $sourceDir = $rootDir . $source . D;

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
        # cli_git end

        # cli_help begin
        function cli_help(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<descriptions yes|no> Shows all commands";
            }

            $items = [];
            foreach (get_defined_functions()["user"] as $fn) {
                if (str_starts_with($fn, "cli_")) {
                    $name = substr($fn, 4);
                    if (empty($argv[0])) {
                        $tooltip = explode(">", $fn(true, []));
                        array_pop($tooltip);
                        $tooltip = implode(">", $tooltip) . ">";
                    } else {
                        $tooltip = $fn(true, []);
                    }
                    $items[] = [$name, $tooltip];
                }
            }

            $maxlen = 0;
            foreach ($items as $item) {
                $len = strlen("{$item[0]} {$item[1]}");
                if ($len > $maxlen) {
                    $maxlen = $len;
                }
            }

            $half = (int) ceil(count($items) / 2);
            $lines = [];

            for ($i = 0; $i < $half; $i++) {
                $left = $items[$i] ?? null;
                $right = $items[$i + $half] ?? null;

                $line = "";

                if ($left) {
                    $line .= "{$left[0]} {$left[1]}";
                    $line .= str_repeat(
                        " ",
                        $maxlen - strlen("{$left[0]} {$left[1]}") + 2,
                    );
                }

                if ($right) {
                    $line .= "{$right[0]} {$right[1]}";
                }

                $lines[] = $line;
            }

            $r = implode("\n", $lines);

            $fFnC = "f(string path, bool critical) : string";
            $fFn = "Static file path resolution function\n\t{$fFnC}\n";

            $rFnC = "r(str logMsg, ?str logType, ?mix return, ?arr|obj ctxData = [])";
            $rFn = "Result logging function\n\t{$rFnC}\n";
            $logTypes = "LogTypes: [Internal, Access, Audit, Error]\n";

            return "$r\n\n{$fFn}{$rFn}{$logTypes}";
        }
        # cli_help end

        # cli_info begin
        function cli_info(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<void> Shows node system information.";
            }

            $info = [];
            $info[] = "Node: " . NODE_NAME;
            $info[] = "Path: " . ROOT_PATH;
            $info[] = "PHP: " . PHP_VERSION . " (" . PHP_SAPI . ")";
            $info[] = "Structure: " . count(NODE_STRUCTURE) . " categories";

            $loaded =
                count(get_declared_classes()) -
                count(get_declared_interfaces());
            $info[] = "Loaded: {$loaded} classes";

            $logFiles = getAllLogFiles();
            $info[] = "Logs: " . count($logFiles) . " files";

            return implode("\n", $info) . "\n";
        }
        # cli_info end

        # cli_like begin
        function cli_like(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<term> Searches resources by name or path.";
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
                            $source = $reflection->isInternal() ? "internal" : "user";
                            $modifiers = [];
                            $reflection->isAbstract() && ($modifiers[] = "abstract");
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
                            $source = $reflection->isInternal() ? "internal" : "user";
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
                            $source = $reflection->isInternal() ? "internal" : "user";
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
        # cli_like end

        # cli_list begin
        function cli_list(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<resource> Lists all existing resources of type.";
            }

            if ($call = $argv[0] ?? null) {
                foreach (callStructure() as $c) {
                    if ($c[0] === $call) {
                        if ($resources = glob($c[1] . D . "*.*")) {
                            $r =
                                "Found (" .
                                count($resources) .
                                ") resources:\n";
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
        # cli_list end

        # cli_log begin
        function cli_log(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<action> <options> Actions: list, show, clear, tail";
            }

            $action = $argv[0] ?? "list";
            $options = array_slice($argv, 1);

            return match ($action) {
                "list" => listLogs($options),
                "show" => showLogs($options),
                "clear" => clearLogs($options),
                "tail" => tailLogs($options),
                default => "E: Unknown action. Available: list, show, clear, tail\n",
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
                return listLogs([]) .
                    "\nE: Specify log file or type. Usage: log show <file|type> [limit]\n";
            }

            $target = $options[0];

            if ($target === "system") {
                return "E: System logs can only be Tailed, use sudo php node log tail <fn> <?rows>\n";
            }

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
            $c = count($allEntries);
            $limitedEntries = array_slice($allEntries, 0, $limit);

            $output = "Showing {$limit} of {$c} log in [{$target}] entries:\n";

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
                return listLogs([]) .
                    "\nE: Specify what to clear. Usage: log clear <file|type|all>\n";
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
                return listLogs([]) .
                    "\nE: Specify log file. Usage: log tail <file> [lines]\n";
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
        # cli_log end

        # cli_make begin
        function cli_make(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<gitrepo> <foldername> Creates new node from git.";
            }

            if (count($argv) < 2) {
                return "E: Usage: make <gitrepo> <foldername>\nExample: make Kolostov/RouterNode Router\n";
            }

            $gitRepo = $argv[0];
            $folderName = $argv[1];
            $parentDir = dirname(ROOT_PATH) . D;
            $newNodePath = $parentDir . $folderName . D;

            if (file_exists($newNodePath)) {
                return "E: Directory already exists: {$newNodePath}\n";
            }

            $gitUrls = getGitUrls();
            if (!$gitUrls["node"]) {
                return "E: Cannot determine node git URL\n";
            }

            $nodeGitUrl = $gitUrls["node"];
            $projectGitUrl = $gitUrls["base"]
                ? $gitUrls["base"] . $gitRepo . ".git"
                : "https://github.com/{$gitRepo}.git";

            $output = "Creating new node '{$folderName}'...\n";
            $output .= "Node Git: {$nodeGitUrl}\n";
            $output .= "Project Git: {$projectGitUrl}\n\n";

            mkdir($newNodePath, 0777, true);
            mkdir("{$newNodePath}Git", 0777, true);
            mkdir("{$newNodePath}Git" . D . "Node", 0777, true);
            mkdir("{$newNodePath}Git" . D . "Project", 0777, true);

            $originalDir = getcwd();

            try {
                chdir("{$newNodePath}Git" . D . "Node");
                exec("git clone {$nodeGitUrl} . 2>&1", $nodeOutput, $nodeCode);

                if ($nodeCode !== 0) {
                    throw new Exception(
                        "Failed to clone node: " . implode("\n", $nodeOutput),
                    );
                }

                $output .= "✓ Node cloned\n";

                chdir("{$newNodePath}Git" . D . "Project");
                exec("git clone {$projectGitUrl} . 2>&1", $projectOutput, $projectCode);

                if ($projectCode !== 0) {
                    throw new Exception(
                        "Failed to clone project: " . implode("\n", $projectOutput),
                    );
                }

                $output .= "✓ Project cloned\n";

                chdir($newNodePath);

                $nodeFile = "{$newNodePath}Git" . D . "Node" . D . "node.php";
                $symlink = "{$newNodePath}Git" . D . "Node" . D . "node";

                if (file_exists($nodeFile)) {
                    rename($nodeFile, "{$newNodePath}node.php");
                    $output .= "✓ node.php moved to root\n";
                }

                if (file_exists("{$newNodePath}node.php")) {
                    if (file_exists($symlink)) {
                        if (rename($symlink, "{$newNodePath}node")) {
                            $output .= "✓ node symlink moved to root\n";
                        }
                    } else {
                        chdir($newNodePath);
                        if (symlink("node.php", "node")) {
                            $output .= "✓ New node symlink created\n";
                        } else {
                            $output .= "Note: Could not create node symlink\n";
                        }
                    }

                    chdir($newNodePath);
                    exec("php node.php git Node 2>&1", $gitOutput, $gitCode);

                    if ($gitCode === 0) {
                        $output .= "✓ Node set to Node mode\n";
                    } else {
                        $output .= "N({$gitCode}): Run manually: php node git Node\n";
                        $output .= implode("\n", $gitOutput) . "\n";
                    }
                } else {
                    $output .= "E: could not move node.php \n";
                }

                $nodeConfig = [
                    "name" => $folderName,
                    "run" => null,
                    "require" => [],
                ];

                file_put_contents(
                    "{$newNodePath}node.json",
                    json_encode(
                        $nodeConfig,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                    ),
                );

                $output .= "✓ node.json created\n";
            } catch (Exception $e) {
                chdir($originalDir);
                if (is_dir($newNodePath)) {
                    exec("rm -rf " . escapeshellarg($newNodePath));
                }
                return "E: " . $e->getMessage() . "\n";
            }

            chdir($originalDir);

            $output .= "\nNew node created at: {$newNodePath}\n";
            $output .= "To enter: cd ../" . escapeshellarg($folderName) . "\n";
            $output .= "To start: php node serve\n";

            return $output;
        }

        function getGitUrls(): array
        {
            $result = ["node" => "", "base" => null];

            $gConf = ROOT_PATH . ".git" . D . "config";
            if (file_exists($gConf)) {
                $cfg = file_get_contents($gConf);
                $result["node"] = preg_match("/url\s*=\s*(.+)/", $cfg, $mcs)
                    ? trim($mcs[1])
                    : null;
            } else {
                $nConf = ROOT_PATH . "Git" . D . "Node" . D . ".git" . D . "config";
                if (file_exists($nConf)) {
                    $cfg = file_get_contents($nConf);
                    $result["node"] = preg_match("/url\s*=\s*(.+)/", $cfg, $mcs)
                        ? trim($mcs[1])
                        : null;
                }
            }

            $result["base"] = !empty($result["node"])
                ? str_replace("Kolostov/NodePHP.git", "", $result["node"])
                : null;

            return $result;
        }
        # cli_make end

        # cli_migrate begin
        function cli_migrate(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<action> <target> A: up, down, status, create; T: name";
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

                        $migrations = glob($migrationPath . D . $type . D . "*.php");

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
                    return migrateUp($tracking, $trackingFile, $migrationPath, $target);

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

        # migrate_up begin
        function migrateUp(
            array $tracking,
            string $trackingFile,
            string $migrationPath,
            string $target,
        ): string {
            $output = "Running migrations up...\n";
            $applied = [];

            foreach (["SQL", "PHP"] as $type) {
                $migrations = glob($migrationPath . D . $type . D . "*.php");
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
                $output .= "\nApplied migrations: " . implode(", ", $applied) . "\n";
            } else {
                $output .= "\nNo new migrations to apply.\n";
            }

            return $output;
        }
        # migrate_up end

        # migrate_down begin
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
                        $migrationPath . D . $type . D . $fileName . ".php";

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
                            $sqlFile = str_replace(".php", ".down.sql", $migrationFile);
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
                    "\nRolled back migrations: " . implode(", ", $rolledBack) . "\n";
            } else {
                $output .= "\nNo migrations to roll back.\n";
            }

            return $output;
        }
        # migrate_down end

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

            $migrationDir = $migrationPath . D . $type;

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

                $filePath = $migrationDir . D . $fileName . ".php";
                file_put_contents($filePath, $content);

                return "Created PHP migration: {$filePath}\n";
            } else {
                $sqlFile = $migrationDir . D . $fileName . ".sql";
                $downSqlFile = $migrationDir . D . $fileName . ".down.sql";

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

                $phpFilePath = $migrationDir . D . $fileName . ".php";
                file_put_contents($phpFilePath, $content);

                return "Created SQL migration:\n  {$sqlFile}\n  {$downSqlFile}\n  {$phpFilePath}\n";
            }
        }

        function test_cli_migrate(): int
        {
            $migrationPath = ROOT_PATH . "Migration";
            $trackingFile = ROOT_PATH . ".migrations.json";

            $backupTracking = null;
            $backupMigrationDir = null;

            if (file_exists($trackingFile)) {
                $backupTracking = file_get_contents($trackingFile);
            }

            if (is_dir($migrationPath)) {
                $backupMigrationDir = true;
                $oldFiles = glob($migrationPath . D . "*" . D . "*");
                foreach ($oldFiles as $file) {
                    if (is_file($file)) {
                        rename($file, $file . ".backup");
                    }
                }
            } else {
                mkdir($migrationPath, 0777, true);
            }

            file_put_contents(
                $trackingFile,
                json_encode(["SQL" => [], "PHP" => []], JSON_PRETTY_PRINT),
            );

            if (str_starts_with(cli_migrate(true, []), "<action>") === false) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 1;
            }

            $statusResult = cli_migrate(false, ["status"]);
            if (strpos($statusResult, "Migration Status:") === false) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 2;
            }

            $unknownActionResult = cli_migrate(false, ["unknown_action"]);
            if (strpos($unknownActionResult, "E: Unknown action") === false) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 3;
            }

            $upResult = cli_migrate(false, ["up"]);
            if (strpos($upResult, "No new migrations to apply") === false) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 4;
            }

            $downResult = cli_migrate(false, ["down"]);
            if (strpos($downResult, "No migrations to roll back") === false) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 5;
            }

            $createMissingNameResult = cli_migrate(false, ["create"]);
            if (
                strpos($createMissingNameResult, "E: Missing migration name") === false
            ) {
                restoreMigrationState($backupTracking, $backupMigrationDir);
                return 6;
            }

            restoreMigrationState($backupTracking, $backupMigrationDir);

            return 0;
        }

        function restoreMigrationState($backupTracking, $backupMigrationDir): void
        {
            $migrationPath = ROOT_PATH . "Migration";
            $trackingFile = ROOT_PATH . ".migrations.json";

            if ($backupTracking !== null) {
                file_put_contents($trackingFile, $backupTracking);
            } else {
                unlink($trackingFile);
            }

            if ($backupMigrationDir !== null) {
                $oldFiles = glob($migrationPath . D . "*" . D . "*");
                foreach ($oldFiles as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                $backupFiles = glob($migrationPath . D . "*" . D . "*.backup");
                foreach ($backupFiles as $backupFile) {
                    rename($backupFile, substr($backupFile, 0, -7));
                }
            } else {
                $files = glob($migrationPath . D . "*" . D . "*");
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                $subdirs = glob($migrationPath . D . "*", GLOB_ONLYDIR);
                foreach ($subdirs as $dir) {
                    rmdir($dir);
                }

                if (is_dir($migrationPath)) {
                    rmdir($migrationPath);
                }
            }
        }
        # cli_migrate end

        # cli_new begin
        function cli_new(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<resource> <name> Creates new resource from boilerplate.";
            }

            if (($call = $argv[0] ?? null) && ($name = $argv[1] ?? null)) {
                foreach (callStructure() as $c) {
                    if ($c[0] === $call) {
                        if (strpos($c[1], "Migration") !== false) {
                            $timestamp = date("Ymd_His");
                            $safeName = preg_replace("/[^a-zA-Z0-9_]/", "_", $name);
                            $migrationName = "{$timestamp}_{$safeName}";

                            $fc = generateBoilerplate($c[1], $safeName, ROOT_PATH);
                            $fn = $c[1] . D . "{$migrationName}{$fc[1]}.php";

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
                            $fn = $c[1] . D . $name . $ext;
                            if (!file_exists($fn)) {
                                $size = file_put_contents($fn, "\n");
                                return "File created at {$fn} size {$size} bytes.\n";
                            } else {
                                return "E: File {$fn} already exists.\n";
                            }
                        } else {
                            // Regular resource creation
                            $fc = generateBoilerplate($c[1], $name, ROOT_PATH);
                            $fn = $c[1] . D . "{$name}{$fc[1]}.php";
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

        function test_cli_new(): int
        {
            $r = "State";
            $n = "MyTestState";

            $result = cli_new(false, [$r, $n]);

            if (str_starts_with($result, "E:")) {
                return 1;
            }

            if (strpos($result, "File created at") === false) {
                return 2;
            }

            $structures = callStructure();
            $path = null;
            foreach ($structures as $structure) {
                if ($structure[0] === $r) {
                    $path = $structure[1];
                    break;
                }
            }

            if ($path === null) {
                return 3;
            }

            $expectedFile = $path . D . $n . $r . ".php";
            if (!file_exists($expectedFile)) {
                return 4;
            }

            $fileSize = filesize($expectedFile);
            if ($fileSize < 50) {
                unlink($expectedFile);
                return 5;
            }

            $content = file_get_contents($expectedFile);
            if (strpos($content, "<?php declare(strict_types=1);") === false) {
                unlink($expectedFile);
                return 6;
            }

            if (strpos($content, "enum MyTestState") === false) {
                unlink($expectedFile);
                return 7;
            }

            $duplicateResult = cli_new(false, [$r, $n]);
            if (
                !str_starts_with($duplicateResult, "E: File") ||
                strpos($duplicateResult, "already exists") === false
            ) {
                unlink($expectedFile);
                return 8;
            }

            $missingArgsResult = cli_new(false, []);
            if (!str_starts_with($missingArgsResult, "E: Missing argument")) {
                unlink($expectedFile);
                return 9;
            }

            $invalidResourceResult = cli_new(false, ["InvalidResourceName", $n]);
            if (
                !str_starts_with(
                    $invalidResourceResult,
                    "E: Could not create resource",
                ) ||
                strpos($invalidResourceResult, "invalid resource name") === false
            ) {
                unlink($expectedFile);
                return 10;
            }

            $tooltip = cli_new(true, []);
            if (strpos($tooltip, "<resource> <name>") === false) {
                unlink($expectedFile);
                return 11;
            }

            unlink($expectedFile);

            return 0;
        }
        # cli_new end

        # cli_rank begin
        function cli_rank(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<file> <?func> Analyze and rank file contents";
            }

            $file = $argv[0] ?? "";
            if (!$file || !file_exists($file)) {
                return "E: Provide valid file path\n";
            }

            $src = file_get_contents($file);
            $fns = _file_functions($src);

            if (empty($fns)) {
                return "No functions found in file\n";
            }

            $fileMetrics = computeFileMetrics($src);
            $fileTotal = array_sum($fileMetrics);

            $targetFn = $argv[1] ?? null;
            if ($targetFn && !in_array($targetFn, $fns)) {
                return "E: Function '{$targetFn}' not found in file\n";
            }

            if ($targetFn) {
                $fns = [$targetFn];
            }

            $analysis = [];
            foreach ($fns as $fn) {
                $body = getFunctionBodyWithDocblock($src, $fn);
                $rawScore = 0.0;
                $functionMetrics = [];

                $functionMetrics["call"] = $value = metricCalls($src, $fn, $body);
                $rawScore += $value;
                $functionMetrics["docs"] = $value = metricDocblock($fn, $body);
                $rawScore += $value;
                $functionMetrics["ln"] = $value = metricLines($fn, $body);
                $rawScore += $value;
                $functionMetrics["args"] = $value = metricParameters($fn, $body);
                $rawScore += $value;
                $functionMetrics["branch"] = $value = metricBranching($fn, $body);
                $rawScore += $value;
                $functionMetrics["divisions"] = $value = metricDivision($fn, $body);
                $rawScore += $value;
                $functionMetrics["string_ops"] = $value = metricStringOps($fn, $body);
                $rawScore += $value;
                $functionMetrics["builtin"] = $value = metricBuiltinUsage($fn, $body);
                $rawScore += $value;
                $functionMetrics["ifelse"] = $value = metricIfElseBalance($fn, $body);
                $rawScore += $value;

                $analysis[$fn] = [
                    "metrics" => $functionMetrics,
                    "file" => $fileMetrics,
                    "raw" => $rawScore,
                    "score" => $rawScore + $fileTotal,
                ];
            }

            if ($targetFn) {
                $data = $analysis[$targetFn];
                $output = "Function: {$targetFn}()\n";
                $output .= "Total Score: " . number_format($data["score"], 1) . "\n";
                $output .= "Raw Score: " . number_format($data["raw"], 1) . "\n";
                $output .= "File Score: " . number_format($fileTotal, 1) . "\n\n";

                $output .= "Function Metrics:\n";

                $metricNotes = [
                    "call" => "More calls = higher score. Functions used throughout codebase are valuable.",
                    "docs" => "Add docblock with @param, @return tags. More tags = higher score.",
                    "ln" => "Shorter functions (<20 lines) are easier to understand and test.",
                    "args" => "Reduce parameters (<4 ideal). Use objects/arrays for related parameters.",
                    "branch" => "Reduce complex branching (if/else/switch). Extract conditions to methods.",
                    "divisions" => "Avoid division operations which can cause precision issues.",
                    "string_ops" => "Minimize string concatenation. Use string builders or templates.",
                    "builtin" => "Use built-in PHP functions over custom implementations when possible.",
                    "ifelse" => "Balance if statements with else/elseif. Unhandled edge-cases can cause bugs.",
                ];

                $hasNegativeMetrics = false;
                foreach ($data["metrics"] as $metric => $value) {
                    if ($value < 0) {
                        $hasNegativeMetrics = true;
                        $output .= "* {$metric}: " . number_format($value, 1) . ";";
                        if (isset($metricNotes[$metric])) {
                            $output .= " // " . $metricNotes[$metric] . "\n";
                        }
                    }
                }

                if (!$hasNegativeMetrics) {
                    $output .= "No negative function metrics found. Good job!\n";
                }

                $fileMetricNotes = [
                    "strict_types" => "Add 'declare(strict_types=1);' at top of file for type safety.",
                    "typed_properties" => "Use type hints for class properties (PHP 7.4+).",
                    "namespace" => "Add namespace declaration to avoid global scope pollution.",
                    "no_superglobals" => "Avoid direct \$_GET/\$_POST usage. Use input validation/sanitization.",
                    "final_class" => "Mark classes as 'final' when not designed for inheritance.",
                    "modern_visibility" => "Use 'public/private/protected' instead of old 'var' keyword.",
                    "constructor_property_promotion" => "Use PHP 8 constructor property promotion for cleaner code.",
                    "union_types" => "Use union types (TypeA|TypeB) for flexible parameter/return types.",
                    "nullsafe_operator" => "Use ?-> operator instead of null checks for method/property access.",
                    "match_expression" => "Prefer 'match()' over 'switch()' for expression-based control flow.",
                    "named_arguments" => "Use named arguments for clarity when calling functions with many parameters.",
                    "attributes" => "Use PHP 8 attributes for metadata instead of docblock annotations.",
                    "enums" => "Use enums for type-safe constant sets (PHP 8.1+).",
                    "readonly_properties" => "Mark properties as 'readonly' when they shouldn't change after construction.",
                    "never_return_type" => "Use ': never' return type for functions that always exit/throw.",
                    "array_is_list" => "Use array_is_list() instead of manual array key checking.",
                    "first_class_callable" => "Use first-class callables (fn(...)) for cleaner callback syntax.",
                    "pure_annotations" => "Add @pure or #[Pure] annotations for functions without side effects.",
                    "immutable_objects" => "Design immutable objects with private properties and no setters.",
                    "cohesion" => "Improve class cohesion - methods should share data/behavior.",
                    "cyclomatic_complexity" => "Reduce branching logic. Extract complex conditions into methods.",
                    "dependency_inversion" => "Depend on abstractions (interfaces) not concrete implementations.",
                    "no_magic_numbers" => "Replace magic numbers with named constants or configuration.",
                    "no_global_functions" => "Wrap global functions in class methods for better testability/encapsulation.",
                    "interface_segregation" => "Split large interfaces into smaller, focused ones.",
                    "single_responsibility" => "Split large classes (>200 lines) into smaller, focused classes.",
                    "security_metrics" => "Use prepared statements, input validation, and output escaping.",
                    "performance_hints" => "Avoid N+1 queries, use generators for large datasets, cache results.",
                    "documentation" => "Add docblocks to public/protected methods describing purpose and parameters.",
                    "test_coverage" => "Add unit tests and use mocking for better test coverage.",
                    "coding_standards" => "Readable code: line length < 120, no trailing whitespace, brace style.",
                ];

                $output .= "\nFile Metrics:\n";
                $hasNegativeFileMetrics = false;
                foreach ($data["file"] as $metric => $value) {
                    if ($value < 0) {
                        $hasNegativeFileMetrics = true;
                        $output .= "* {$metric}: " . number_format($value, 1) . ";";
                        if (isset($fileMetricNotes[$metric])) {
                            $output .= " // " . $fileMetricNotes[$metric] . "\n";
                        }
                    }
                }

                if (!$hasNegativeFileMetrics) {
                    $output .= "No negative file metrics found. Good job!\n";
                }

                return $output;
            }

            usort($fns, fn($a, $b) => $analysis[$a]["score"] <=> $analysis[$b]["score"]);

            $output = "File Score: " . number_format($fileTotal, 1) . "\n";

            $worst = $fns;
            $maxLen = max(array_map("strlen", $fns));
            $best = array_slice(array_reverse($fns), -min(2, count($fns)));

            if (!empty($best)) {
                $output .= "\nTop Functions:\n";
                foreach ($best as $fn) {
                    $score = $analysis[$fn]["score"];
                    $output .= "* {$fn}(); " . str_repeat(" ", $maxLen - strlen($fn)) . number_format($score, 1) . "\n";
                }
            }

            if (!empty($worst)) {
                $output .= "\nNeeds Improvement:\n";
                foreach ($worst as $fn) {
                    $score = $analysis[$fn]["score"];
                    $output .= "* {$fn}(); " . str_repeat(" ", $maxLen - strlen($fn)) . number_format($score, 1) . " // ";
                    foreach ($analysis[$fn]["metrics"] as $metric => $value) {
                        if ($value != 0) {
                            $output .= "{$metric}: " . number_format($value, 1) . "; ";
                        }
                    }
                    $output .= "\n";
                }
            }

            $positiveMetrics = [];
            $negativeMetrics = [];

            foreach ($fileMetrics as $metric => $value) {
                if ($value > 0) {
                    $positiveMetrics[] = [$metric, $value];
                } elseif ($value < 0) {
                    $negativeMetrics[] = [$metric, $value];
                }
            }

            usort($positiveMetrics, fn($a, $b) => $b[1] <=> $a[1]);
            usort($negativeMetrics, fn($a, $b) => $a[1] <=> $b[1]);

            $metricItems = [];
            foreach ($positiveMetrics as [$metric, $value]) {
                $metricItems[] = [$metric, number_format($value, 1)];
            }
            foreach ($negativeMetrics as [$metric, $value]) {
                $metricItems[] = [$metric, number_format($value, 1)];
            }

            $maxlenMetric = 0;
            foreach ($metricItems as $item) {
                $len = strlen("{$item[0]} {$item[1]}");
                if ($len > $maxlenMetric) {
                    $maxlenMetric = $len;
                }
            }

            $thirdMetric = (int) ceil(count($metricItems) / 3);
            $metricLines = [];

            for ($i = 0; $i < $thirdMetric; $i++) {
                $line = "";
                $col1 = $metricItems[$i] ?? null;
                $col2 = $metricItems[$i + $thirdMetric] ?? null;
                $col3 = $metricItems[$i + $thirdMetric * 2] ?? null;

                if ($col1) {
                    $line .= "{$col1[0]} {$col1[1]}";
                    $line .= str_repeat(" ", $maxlenMetric - strlen("{$col1[0]} {$col1[1]}") + 2);
                }

                if ($col2) {
                    $line .= "{$col2[0]} {$col2[1]}";
                    $line .= str_repeat(" ", $maxlenMetric - strlen("{$col2[0]} {$col2[1]}") + 2);
                }

                if ($col3) {
                    $line .= "{$col3[0]} {$col3[1]}";
                }

                $metricLines[] = $line;
            }

            $output .= "\nFile Metrics:\n" . implode("\n", $metricLines) . "\n";

            return $output;
        }

        /**
         * @var string $LOCAL_PATH inherited from node.php
         */

        # _file_functions begin
        function _file_functions(string $src): array
        {
            return preg_match_all(
                '/function\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/',
                $src,
                $m,
            )
                ? $m[1]
                : [];
        }
        # _file_functions end

        # get_function_body_with_docblock begin
        function getFunctionBodyWithDocblock(string $src, string $functionName): string
        {
            $ename = preg_quote($functionName, "/");
            $pattern =
                "/(?:\/\*\*(?:[^*]|\*(?!\/))*\*\/\s*)?\s*(?:(?:public|private|protected|static)\s+)*function\s+" .
                $ename .
                "\s*\([^)]*\)\s*(?::\s*[^\s{]+)?\s*\{((?:[^{}]+|\{(?:[^{}]+|\{[^{}]*\})*\})*)\}/s";
            if (preg_match($pattern, $src, $m)) {
                return $m[0];
            }
            return extractFunctionWithBraceCounting($src, $functionName);
        }
        # get_function_body_with_docblock end

        # extract_function_with_brace_counting begin
        function extractFunctionWithBraceCounting(
            string $src,
            string $functionName,
        ): string {
            $ename = preg_quote($functionName, "/");
            $declPat =
                "/(?:\/\*\*.*?\*\/\s*)?\s*(?:(?:public|private|protected|static)\s+)*function\s+" .
                $ename .
                "\s*\([^)]*\)\s*(?::\s*[^\s{]+)?\s*\{/s";
            if (!preg_match($declPat, $src, $m, PREG_OFFSET_CAPTURE)) {
                return "";
            }
            $startPos = $m[0][1];
            $openingBracePos = strpos($src, "{", $startPos);
            if ($openingBracePos === false) {
                return "";
            }

            $braceCount = 1;
            $inString = false;
            $schar = "";
            $esc = false;
            $len = strlen($src);

            for ($i = $openingBracePos + 1; $i < $len; $i++) {
                $c = $src[$i];
                if (!$inString) {
                    if ($c === "{") {
                        $braceCount++;
                    } elseif ($c === "}") {
                        $braceCount--;
                        if ($braceCount === 0) {
                            $fstart = findFunctionStart($src, $startPos);
                            return substr($src, $fstart, $i - $fstart + 1);
                        }
                    } elseif ($c === '"' || $c === "'" || $c === "`") {
                        $inString = true;
                        $schar = $c;
                    }
                } else {
                    if (!$esc) {
                        if ($c === $schar) {
                            $inString = false;
                        } elseif ($c === "\\") {
                            $esc = true;
                        }
                    } else {
                        $esc = false;
                    }
                }
            }
            return "";
        }
        # extract_function_with_brace_counting end

        # find_function_start begin
        function findFunctionStart(string $src, int $pos): int
        {
            $limit = max(0, $pos - 1000);
            for ($i = $pos - 1; $i >= $limit; $i--) {
                if ($i > 1 && $src[$i] === "/" && $src[$i - 1] === "*") {
                    for ($j = $i - 2; $j >= $limit; $j--) {
                        if ($j > 0 && $src[$j] === "/" && $src[$j - 1] === "*") {
                            return $j - 1;
                        }
                    }
                }
                if (
                    $i > 5 &&
                    preg_match(
                        '/\n\s*(?:public|private|protected|static)\b/',
                        substr($src, $i - 6, 7),
                    )
                ) {
                    for ($j = $i - 6; $j >= $limit; $j--) {
                        if ($src[$j] === "\n") {
                            return $j + 1;
                        }
                    }
                    return max(0, $i - 6);
                }
            }
            return $pos;
        }
        # find_function_start end

        # compute_file_metrics begin
        function computeFileMetrics(string $src): array
        {
            return [
                "strict_types" => _file_strict_types($src),
                "typed_properties" => _file_typed_properties($src),
                "namespace" => _file_namespace($src),
                "no_superglobals" => _file_no_superglobals($src),
                "final_class" => _file_final_class($src),
                "modern_visibility" => _file_modern_visibility($src),
                "constructor_property_promotion" => _file_constructor_property_promotion(
                    $src,
                ),
                "union_types" => _file_union_types($src),
                "nullsafe_operator" => _file_nullsafe_operator($src),
                "match_expression" => _file_match_expression($src),
                "named_arguments" => _file_named_arguments($src),
                "attributes" => _file_attributes($src),
                "enums" => _file_enums($src),
                "readonly_properties" => _file_readonly_properties($src),
                "never_return_type" => _file_never_return_type($src),
                "array_is_list" => _file_array_is_list($src),
                "first_class_callable" => _file_first_class_callable($src),
                "pure_annotations" => _file_pure_annotations($src),
                "immutable_objects" => _file_immutable_objects($src),
                "cohesion" => _file_cohesion($src),
                "cyclomatic_complexity" => _file_cyclomatic_complexity($src),
                "dependency_inversion" => _file_dependency_inversion($src),
                "no_magic_numbers" => _file_no_magic_numbers($src),
                "no_global_functions" => _file_no_global_functions($src),
                "interface_segregation" => _file_interface_segregation($src),
                "single_responsibility" => _file_single_responsibility($src),
                "security_metrics" => _file_security_metrics($src),
                "performance_hints" => _file_performance_hints($src),
                "documentation" => _file_documentation($src),
                "test_coverage" => _file_test_coverage($src),
                "coding_standards" => _file_coding_standards($src),
            ];
        }
        # compute_file_metrics end

        # _file_nullsafe_operator begin
        function _file_nullsafe_operator(string $src): float
        {
            $matches = preg_match_all("/\?\->/", $src);
            return $matches * 1.0;
        }
        # _file_nullsafe_operator end

        # _file_match_expression begin
        function _file_match_expression(string $src): float
        {
            $matchCount = preg_match_all("/\bmatch\s*\(/", $src);
            $switchCount = preg_match_all("/\bswitch\s*\(/", $src);

            if ($switchCount === 0) {
                return 0.0;
            }
            $ratio = $matchCount / $switchCount;
            return $ratio * 3.0;
        }
        # _file_match_expression end

        # _file_named_arguments begin
        function _file_named_arguments(string $src): float
        {
            $matches = preg_match_all("/\w+\s*\([^)]*?\b\w+\s*:/", $src);
            return $matches * 0.8;
        }
        # _file_named_arguments end

        # _file_attributes begin
        function _file_attributes(string $src): float
        {
            $matches = preg_match_all("/#\[(?!Deprecated\b|\w+\(deprecated)/", $src);
            return $matches * 2.0;
        }
        # _file_attributes end

        # _file_enums begin
        function _file_enums(string $src): float
        {
            $matches = preg_match_all("/\benum\s+\w+/", $src);
            return $matches * 4.0;
        }
        # _file_enums end

        # _file_array_is_list begin
        function _file_array_is_list(string $src): float
        {
            $arrayIsList = preg_match_all("/\barray_is_list\s*\(/", $src);
            $manualChecks = preg_match_all(
                '/(?:array_keys\s*\(\s*\$[^)]+\)\s*===\s*range\s*\(|isset\s*\(\s*\$[^)]+\[\d+\])/',
                $src,
            );

            if ($manualChecks === 0) {
                return 0.0;
            }
            $ratio = $arrayIsList / $manualChecks;
            return $ratio * 2.0;
        }
        # _file_array_is_list end

        # _file_first_class_callable begin
        function _file_first_class_callable(string $src): float
        {
            $matches = preg_match_all("/(\w+(?:::)?\w*)\s*\(\.\.\.\)/", $src);
            return $matches * 2.0;
        }
        # _file_first_class_callable end

        # _file_pure_annotations begin
        function _file_pure_annotations(string $src): float
        {
            $hasReturnTypeWillChange = preg_match(
                "/#\[\s*ReturnTypeWillChange\s*\]/",
                $src,
            );
            $hasPure = preg_match("/@psalm-(pure|immutable)|#\[Pure\]/", $src);

            return ($hasPure ? 2.0 : 0.0) - ($hasReturnTypeWillChange ? 1.0 : 0.0);
        }
        # _file_pure_annotations end

        # _file_constructor_property_promotion begin
        function _file_constructor_property_promotion(string $src): float
        {
            $pattern =
                '/public\s+function\s+__construct\s*\((?:[^)]*?\b(?:public|protected|private)\s+(?:readonly\s+)?(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\s+\$\w+(?:\s*=[^,)]+)?[^)]*)+\)/s';
            $matches = preg_match_all($pattern, $src);
            return $matches * 2.5;
        }
        # _file_constructor_property_promotion end

        # _file_union_types begin
        function _file_union_types(string $src): float
        {
            $pattern1 =
                '/function\s+\w+\s*\([^)]*?\b(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\s+\$\w+\s*:[^)]*?\|[^)]*?\)/';
            $pattern2 =
                '/@(?:param|return|var|property)\s+(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\|/';

            $matches = 0;
            $matches += preg_match_all($pattern1, $src);
            $matches += preg_match_all($pattern2, $src);

            return $matches * 1.2;
        }
        # _file_union_types end

        # _file_readonly_properties begin
        function _file_readonly_properties(string $src): float
        {
            $pattern =
                '/\b(?:public|protected|private)\s+readonly\s+(?:static\s+)?(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\s+\$\w+/';
            $matches = preg_match_all($pattern, $src);
            return $matches * 3.0;
        }
        # _file_readonly_properties end

        # _file_never_return_type begin
        function _file_never_return_type(string $src): float
        {
            $matches = preg_match_all("/:\s*never\b/", $src);
            return $matches * 2.5;
        }
        # _file_never_return_type end

        # _file_immutable_objects begin
        function _file_immutable_objects(string $src): float
        {
            $hasNoSetters = !preg_match("/public\s+function\s+set\w+\s*\(/", $src);

            $privatePattern =
                '/private\s+(?:readonly\s+)?(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\s+\$\w+/';
            $totalPattern =
                '/(?:public|protected|private)\s+(?:readonly\s+)?(?:static\s+)?(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\??)*)\s+\$\w+/';

            $hasAllPropertiesPrivate = preg_match_all($privatePattern, $src);
            $totalProperties = preg_match_all($totalPattern, $src);

            if ($totalProperties === 0) {
                return 0.0;
            }

            $privateRatio = $hasAllPropertiesPrivate / $totalProperties;
            $immutabilityScore = $privateRatio * 5.0;
            return $hasNoSetters ? $immutabilityScore + 2.0 : $immutabilityScore;
        }
        # _file_immutable_objects end

        # _file_cohesion begin
        function _file_cohesion(string $src): float
        {
            preg_match_all(
                "/\bclass\s+(\w+).*?\{(.*?)\}\s*(?=class|\Z)/s",
                $src,
                $matches,
                PREG_SET_ORDER,
            );

            $cohesionScore = 0.0;
            $classCount = 0;

            foreach ($matches as $match) {
                $classBody = $match[2];

                preg_match_all(
                    "/(?:public|protected|private)\s+function\s+(\w+)\s*\([^)]*\)\s*\{(.*?)\}(?=\s*(?:public|protected|private)\s+function|\Z)/s",
                    $classBody,
                    $methods,
                    PREG_SET_ORDER,
                );

                $methodCount = count($methods);
                if ($methodCount < 2) {
                    continue;
                }

                $sharedPropertyUsage = 0;
                foreach ($methods as $i => $method1) {
                    foreach ($methods as $j => $method2) {
                        if ($i >= $j) {
                            continue;
                        }
                        preg_match_all(
                            '/\$this->(\w+)/',
                            $method1[2] . " " . $method2[2],
                            $sharedProps,
                        );
                        if (count(array_unique($sharedProps[1] ?? [])) > 0) {
                            $sharedPropertyUsage++;
                        }
                    }
                }

                $maxPairs = ($methodCount * ($methodCount - 1)) / 2;
                if ($maxPairs > 0) {
                    $cohesionScore += ($sharedPropertyUsage / $maxPairs) * 10.0;
                    $classCount++;
                }
            }

            return $classCount > 0 ? $cohesionScore / $classCount : 0.0;
        }
        # _file_cohesion end

        # _file_cyclomatic_complexity begin
        function _file_cyclomatic_complexity(string $src): float
        {
            $decisionPoints = preg_match_all(
                "/\b(?:if|elseif|while|for|foreach|case|catch|and\s*\(|or\s*\(|\|\||&&)\b/",
                $src,
            );
            $functions = preg_match_all("/function\s+\w+\s*\(/", $src);

            if ($functions === 0) {
                return 0.0;
            }

            $avgComplexity = $decisionPoints / $functions;

            if ($avgComplexity <= 5) {
                return 0.0;
            }
            if ($avgComplexity <= 10) {
                return -3.0;
            }
            if ($avgComplexity <= 15) {
                return -6.0;
            }
            if ($avgComplexity <= 20) {
                return -9.0;
            }
            return -12.0;
        }
        # _file_cyclomatic_complexity end

        # _file_dependency_inversion begin
        function _file_dependency_inversion(string $src): float
        {
            $interfaceParams = preg_match_all(
                '/@param\s+(\w+)\s+\$\w+/',
                $src,
                $paramMatches,
            );
            $totalParams = preg_match_all(
                "/function\s+\w+\s*\(([^)]*)\)/",
                $src,
                $funcMatches,
            );

            $interfaceCount = 0;
            foreach ($paramMatches[1] ?? [] as $type) {
                if (
                    preg_match("/^[A-Z]/", $type) &&
                    !preg_match(
                        '/^(int|string|bool|float|array|callable|iterable|mixed|void)$/',
                        $type,
                    )
                ) {
                    $interfaceCount++;
                }
            }

            if ($totalParams === 0) {
                return 0.0;
            }

            $ratio = $interfaceCount / $totalParams;
            return $ratio * 10.0;
        }
        # _file_dependency_inversion end

        # _file_no_magic_numbers begin
        function _file_no_magic_numbers(string $src): float
        {
            $constants = preg_match_all("/\bconst\s+\w+\s*=/", $src);
            $magicNumbers = preg_match_all("/\b(?:[1-9]\d*|0)\b(?!\s*::)/", $src);

            if ($magicNumbers === 0) {
                return 0.0;
            }

            $ratio = $constants / $magicNumbers;
            return $ratio * 10.0;
        }
        # _file_no_magic_numbers end

        # _file_no_global_functions begin
        function _file_no_global_functions(string $src): float
        {
            $globalFunctions = preg_match_all(
                "/\b(?:header|setcookie|session_start|mysql_|pg_)\s*\(/i",
                $src,
            );
            $wrappedCalls = preg_match_all(
                "/->(?:setHeader|setCookie|startSession|query)\s*\(/",
                $src,
            );

            if ($globalFunctions === 0) {
                return 0.0;
            }

            $ratio = $wrappedCalls / $globalFunctions;
            return $ratio * 10.0;
        }
        # _file_no_global_functions end

        # _file_interface_segregation begin
        function _file_interface_segregation(string $src): float
        {
            $interfaceMethods = preg_match_all(
                "/interface\s+\w+\s*\{[^}]*\bfunction\s+\w+\s*\([^)]*\)[^}]+\}/s",
                $src,
                $interfaceMatches,
            );
            $avgMethodsPerInterface = 0;

            foreach ($interfaceMatches[0] ?? [] as $interface) {
                $methodCount = preg_match_all("/function\s+\w+\s*\(/", $interface);
                $avgMethodsPerInterface += $methodCount;
            }

            if ($interfaceMethods === 0) {
                return 0.0;
            }

            $avgMethodsPerInterface /= $interfaceMethods;

            if ($avgMethodsPerInterface <= 3) {
                return 0.0;
            }
            if ($avgMethodsPerInterface <= 5) {
                return -3.0;
            }
            if ($avgMethodsPerInterface <= 8) {
                return -6.0;
            }
            return -9.0;
        }
        # _file_interface_segregation end

        # _file_single_responsibility begin
        function _file_single_responsibility(string $src): float
        {
            $linesPerClass = [];
            preg_match_all(
                "/\bclass\s+\w+(?:.*?)\{(.*?)\}(?=\s*class|\Z)/s",
                $src,
                $classMatches,
                PREG_SET_ORDER,
            );

            foreach ($classMatches as $match) {
                $lines = substr_count($match[1], "\n");
                $linesPerClass[] = $lines;
            }

            if (empty($linesPerClass)) {
                return 0.0;
            }

            $avgLines = array_sum($linesPerClass) / count($linesPerClass);

            if ($avgLines <= 50) {
                return 0.0;
            }
            if ($avgLines <= 100) {
                return -2.0;
            }
            if ($avgLines <= 200) {
                return -5.0;
            }
            if ($avgLines <= 300) {
                return -8.0;
            }
            return -12.0;
        }
        # _file_single_responsibility end

        # _file_security_metrics begin
        function _file_security_metrics(string $src): float
        {
            $penalty = 0.0;

            $sqlInjection = preg_match_all(
                '/\$_(?:GET|POST)\s*\[.*?\]\s*\.\s*\$/',
                $src,
            );
            $penalty -= $sqlInjection * 15.0;

            $xss = preg_match_all('/echo\s+\$_(?:GET|POST|REQUEST)\s*\[/i', $src);
            $penalty -= $xss * 12.0;

            $fileInclusion = preg_match_all(
                '/(?:include|require)(?:_once)?\s*\(\s*\$/',
                $src,
            );
            $penalty -= $fileInclusion * 10.0;

            $positive = 0.0;
            $positive +=
                preg_match_all("/htmlspecialchars|htmlentities|strip_tags/", $src) *
                3.0;
            $positive += preg_match_all("/password_hash|password_verify/", $src) * 4.0;
            $positive +=
                preg_match_all(
                    "/PDO::quote|mysqli_real_escape_string|prepared.*statement/i",
                    $src,
                ) * 5.0;

            return $penalty + $positive;
        }
        # _file_security_metrics end

        # _file_performance_hints begin
        function _file_performance_hints(string $src): float
        {
            $score = 0.0;

            $selectStar = preg_match_all("/SELECT\s*\*\s*FROM/i", $src);
            $score -= $selectStar * 5.0;

            $nPlusOne = preg_match_all(
                "/N\+1\s+problem|loop.*query|query.*loop/i",
                $src,
            );
            $score -= $nPlusOne * 8.0;

            $syncHttp = preg_match_all('/file_get_contents\s*\(\s*["\']http/', $src);
            $score -= $syncHttp * 3.0;

            $generators = preg_match_all("/yield\b|Generator\b/", $src);
            $score += $generators * 4.0;

            $caching = preg_match_all("/\bcache\b|\bCache\b|\bcaching\b/i", $src);
            $score += $caching * 3.0;

            return $score;
        }
        # _file_performance_hints end

        # _file_documentation begin
        function _file_documentation(string $src): float
        {
            // Match any function, with or without visibility keywords
            // Handles: public function, static function, or just function
            $totalMethods = preg_match_all("/(?:(?:public|protected|private|static)\s+)*function\s+\w+\s*\(/i", $src, $matches);

            // Match docblocks followed by those same function signatures
            // The 's' modifier allows '.' to match newlines, making it safer
            $docblockMethods = preg_match_all(
                "/\/\*\*.*?\*\/\s*(?:(?:public|protected|private|static)\s+)*function\s+\w+/s",
                $src,
                $matches,
            );

            if ($totalMethods === 0) {
                return 0.0;
            }

            $docPercentage = ($docblockMethods / $totalMethods) * 100;

            // Use a match expression (PHP 8.0+) for cleaner scoring
            return match (true) {
                $docPercentage >= 90 => 0.0,
                $docPercentage >= 75 => -2.0,
                $docPercentage >= 50 => -5.0,
                $docPercentage >= 25 => -8.0,
                default => -12.0,
            };
        }
        # _file_documentation end

        # _file_test_coverage begin
        function _file_test_coverage(string $src): float
        {
            $hasTests = preg_match_all(
                "/\@test|\@covers|\@dataProvider|PHPUnit/",
                $src,
            );
            $hasMocking = preg_match_all("/\bmock\b|Mockery|createMock/", $src);

            $positive = $hasTests * 6.0 + $hasMocking * 4.0;

            if ($positive === 0.0) {
                return -10.0;
            }

            return $positive;
        }
        # _file_test_coverage end

        # _file_coding_standards begin
        function _file_coding_standards(string $src): float
        {
            $violations = 0;
            $lines = explode("\n", $src);

            // 1. Check for the "Disgusting" Header Gap
            // Since you want declare on line 1, we penalize empty line 2 if it's just a gap
            if (isset($lines[1]) && trim($lines[1]) === "" && str_contains($lines[0], "declare")) {
                $violations++;
            }

            foreach ($lines as $i => $line) {
                $trimmed = rtrim($line);

                // 2. Trailing Whitespace (Zed's 'trim_trailing_whitespace' default)
                if ($trimmed !== $line) {
                    $violations++;
                }

                // 3. Line Length (Matching your 'preferred_line_length': 120)
                // We exclude long URLs or strings to avoid unfair penalties
                if (strlen($trimmed) > 120) {
                    $isComment = preg_match("/^\s*(?:\/\/|\/\*|#|\*)/", $trimmed);
                    $isLongString = preg_match("/['\"].{80,}['\"]/", $trimmed);

                    if (!$isComment && !$isLongString) {
                        $violations++;
                    }
                }

                // 4. Enforce K&R Braces (The "Same Line" Rule)
                // This looks for a declaration that DOES NOT end with {
                // then checks if the next line starts with {
                $isStatement = preg_match("/\b(if|else|for|foreach|while|function|class|try|catch)\b/", $trimmed);
                $hasBraceOnSameLine = str_ends_with($trimmed, "{");

                if ($isStatement && !$hasBraceOnSameLine) {
                    // Check if the next non-empty line is just a brace
                    $nextIdx = $i + 1;
                    while (isset($lines[$nextIdx]) && trim($lines[$nextIdx]) === "") {
                        $nextIdx++;
                    }

                    if (isset($lines[$nextIdx]) && trim($lines[$nextIdx]) === "{") {
                        // This is an "Allman" style brace, which violates your K&R preference
                        $violations++;
                    }
                }

                // 5. Check for Tab vs Space (Zed defaults to 4 spaces)
                if (str_starts_with($line, "\t")) {
                    $violations++;
                }
            }

            // 6. File Length Penalty
            if (count($lines) > 1000) {
                $violations += 5;
            }

            // Return a weighted score
            return -($violations * 1.5);
        }
        # _file_coding_standards end

        # _file_strict_types begin
        function _file_strict_types(string $src): float
        {
            return str_contains($src, "declare(strict_types=1)") ? 9.0 : -5.0;
        }
        # _file_strict_types end

        # _file_typed_properties begin
        function _file_typed_properties(string $src): float
        {
            $totalProperties = 0;
            $typedProperties = 0;
            $unionTypedProperties = 0;

            preg_match_all(
                '/\b(?:public|protected|private)\s+(?:static\s+)?(?:readonly\s+)?(?:(\??[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(?:\s*\|\s*[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*)\s+)?(\$\w+)/',
                $src,
                $matches,
                PREG_SET_ORDER,
            );

            foreach ($matches as $match) {
                $totalProperties++;
                if (!empty($match[1])) {
                    $typedProperties++;
                    if (strpos($match[1], "|") !== false) {
                        $unionTypedProperties++;
                    }
                }
            }

            if ($totalProperties === 0) {
                return 0.0;
            }

            $untypedRatio = ($totalProperties - $typedProperties) / $totalProperties;
            $score = -($untypedRatio * 10.0);
            $score += $unionTypedProperties * 1.0;

            return $score;
        }
        # _file_typed_properties end

        # _file_namespace begin
        function _file_namespace(string $src): float
        {
            return str_contains($src, "namespace ") ? 0.0 : -3.0;
        }
        # _file_namespace end

        # _file_no_superglobals begin
        function _file_no_superglobals(string $src): float
        {
            $superglobals = preg_match_all(
                '/\$\_(GET|POST|SESSION|COOKIE|SERVER|REQUEST|FILES)\b/',
                $src,
            );
            return -($superglobals * 3.0);
        }
        # _file_no_superglobals end

        # _file_final_class begin
        function _file_final_class(string $src): float
        {
            $finalClasses = preg_match_all("/\bfinal\b\s+\bclass\b/i", $src);
            $totalClasses = preg_match_all("/\bclass\s+\w+/", $src);

            if ($totalClasses === 0) {
                return 0.0;
            }

            $nonFinalRatio = ($totalClasses - $finalClasses) / $totalClasses;
            return -($nonFinalRatio * 5.0);
        }
        # _file_final_class end

        # _file_modern_visibility begin
        function _file_modern_visibility(string $src): float
        {
            $varUsage = preg_match_all('/\bvar\s+\$/', $src);
            $modernVisibility = preg_match_all(
                '/\b(?:public|private|protected)\s+\$/',
                $src,
            );

            $score = $modernVisibility * 0.1;
            $score -= $varUsage * 5.0;

            return $score;
        }
        # _file_modern_visibility end

        # metric_calls begin
        function metricCalls(string $src, string $name, string $body): float
        {
            static $callCache = [];

            # All tests should be granted no penalty for existing.
            if (str_starts_with($name, "test")) {
                return 0.0;
            }

            if (!isset($callCache[$name])) {
                $totalCalls = 0;

                $pat = "/(?<!function\s)" . preg_quote($name, "/") . "\s*\(/";
                $totalCalls += preg_match_all($pat, $src);

                $structure = [...[["", ROOT_PATH, ""]], ...callStructure()];
                foreach ($structure as [$call, $path]) {
                    if (
                        str_contains($path, D . "vendor" . D) ||
                        str_contains($path, D . "Database" . D) ||
                        str_contains($path, D . "Logs" . D) ||
                        str_contains($path, D . "Backup" . D) ||
                        str_contains($path, D . "Deprecated" . D)
                    ) {
                        continue;
                    }

                    $phpFiles = glob($path . D . "*.php");
                    foreach ($phpFiles as $phpFile) {
                        $fileContent = @file_get_contents($phpFile);
                        if ($fileContent === false) {
                            continue;
                        }

                        $totalCalls += preg_match_all($pat, $fileContent);
                    }
                }

                $callCache[$name] = $totalCalls;
            }

            $count = $callCache[$name];
            return $count * 1.25 - 50.0;
        }
        # metric_calls end

        # metric_docblock begin
        function metricDocblock(string $name, string $body): float
        {
            if (!str_contains($body, "/**")) {
                return -15.0;
            }
            $count = substr_count($body, "@");
            return $count * 1.5;
        }
        # metric_docblock end

        # metric_lines begin
        function metricLines(string $name, string $body): float
        {
            if (preg_match("/\{([\s\S]*)\}/", $body, $m)) {
                $inner = $m[1];
                $lines = substr_count($inner, "\n");
                if (!empty(trim($inner)) && !str_ends_with($inner, "\n")) {
                    $lines++;
                }
                return 20 + $lines * -0.25;
            }
            return 0.0;
        }
        # metric_lines end

        # metric_parameters begin
        function metricParameters(string $name, string $body): float
        {
            $pat = "/function\s+" . preg_quote($name, "/") . "\s*\(([^)]*)\)/";
            if (preg_match($pat, $body, $m)) {
                $p = trim($m[1]);
                $cnt = $p === "" ? 0 : count(array_filter(explode(",", $p)));
                return $cnt * -0.5;
            }
            return 0.0;
        }
        # metric_parameters end

        # metric_branching begin
        function metricBranching(string $name, string $body): float
        {
            $dp = 0;
            $patterns = [
                "/\bif\s*\(/i",
                "/\belseif\s*\(/i",
                "/\belse\s*{?\s*(?!\s*if)/i",
                "/\bswitch\s*\(/i",
                "/\bcase\b/",
                "/\bdefault\s*:/i",
                "/\bfor\s*\(/i",
                "/\bforeach\s*\(/i",
                "/\bwhile\s*\(/i",
                "/\bdo\s*{[^}]*\bwhile\b/i",
                "/\?\s*(?!:)/",
                '/:\s*(?![\'"]|[\s]*(int|float|string|bool|void|array|mixed|self|parent|null|\?))/i',
                "/\|\|/",
                "/&&/",
                "/\bcatch\s*\(/i",
                "/\bmatch\s*\(/i",
                "/\bcontinue\s+[\d]+;/i",
                "/\bbreak\s+[\d]+;/i",
                "/\bgoto\b/i",
            ];
            foreach ($patterns as $p) {
                $dp += preg_match_all($p, $body);
            }
            $q = preg_match_all("/\?/", $body);
            $col = preg_match_all("/:/", $body);
            $dp -= $q;
            $dp += min($q, $col);
            if (
                preg_match("/(\bif|\bfor|\bforeach|\bwhile)\s*\([^}]{100,}\)/s", $body)
            ) {
                $dp += 2;
            }
            return $dp * -2.5;
        }
        # metric_branching end

        # metric_division begin
        function metricDivision(string $name, string $body): float
        {
            if (preg_match("/\{([\s\S]*?)\}/", $body, $m)) {
                $body = $m[1];
            }
            $div = preg_match_all("/[^a-zA-Z0-9_]\s*\/\s*(?![\/*])/", $body);
            $div += preg_match_all("/\/\=/", $body);
            return $div * -3.0;
        }
        # metric_division end

        # metric_string_ops begin
        function metricStringOps(string $name, string $body): float
        {
            if (preg_match("/\{([\s\S]*?)\}/", $body, $m)) {
                $body = $m[1];
            }
            $ops = preg_match_all("/\.\=?/", $body);
            $funcs = [
                "str_replace",
                "str_ireplace",
                "strpos",
                "stripos",
                "strrpos",
                "substr",
                "strtolower",
                "strtoupper",
                "trim",
                "ltrim",
                "rtrim",
                "implode",
                "explode",
                "join",
                "sprintf",
                "preg_replace",
                "preg_match",
                "str_pad",
                "chunk_split",
            ];
            foreach ($funcs as $f) {
                $ops += preg_match_all("/\b" . $f . "\s*\(/i", $body);
            }
            $ops += preg_match_all('/\.\s*\$\w+\s*\.\s*\$\w+/', $body) * 2;
            return $ops * -2.0;
        }
        # metric_string_ops end

        # metric_builtin_usage begin
        function metricBuiltinUsage(string $name, string $body): float
        {
            if (!preg_match("/\{([\s\S]*?)\}/", $body, $m)) {
                return 0.0;
            }
            $inner = $m[1];
            $praise = 0.0;
            $all = get_defined_functions();
            $builtin = array_map("strtolower", $all["internal"] ?? []);
            $user = array_map("strtolower", $all["user"] ?? []);
            preg_match_all(
                '/\b([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\(/',
                $inner,
                $matches,
            );
            $skip = [
                "if",
                "else",
                "elseif",
                "for",
                "foreach",
                "while",
                "do",
                "switch",
                "match",
                "function",
                "class",
                "new",
                "return",
                "echo",
                "print",
            ];

            foreach ($matches[1] as $func) {
                $lower = strtolower($func);
                if (in_array($lower, $skip)) {
                    continue;
                }
                if (in_array($lower, $builtin)) {
                    $praise += 2.0;
                } elseif (in_array($lower, $user)) {
                    $praise += 1.0;
                }
            }
            return $praise;
        }
        # metric_builtin_usage end

        # metric_if_else_balance begin
        function metricIfElseBalance(string $name, string $body): float
        {
            if (preg_match("/\{([\s\S]*?)\}/", $body, $m)) {
                $body = $m[1];
            }

            $ifCount = preg_match_all("/\bif\s*\(/i", $body);
            $elseCount = preg_match_all("/\belse\b/i", $body);

            $elseifCount = preg_match_all("/\belseif\b/i", $body);
            $elseIfCount = preg_match_all("/\belse\s+if\b/i", $body);

            $totalElse = $elseCount + $elseifCount + $elseIfCount;

            if ($ifCount > $totalElse) {
                $unbalancedIfs = $ifCount - $totalElse;
                return $unbalancedIfs * -0.15;
            }

            return 0.0;
        }
        # metric_if_else_balance end
        # cli_rank end

        # cli_search begin
        function cli_search(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<query...> Search across all code files";
            }

            if (empty($argv)) {
                return "E: Provide search query\n";
            }

            $searchQuery = implode(" ", $argv);
            $output = "";

            $extensions = ["php", "js", "css", "html", "scss", "json", "xml", "yml", "yaml", "md", "txt"];
            $excludedDirs = ["vendor", "Database", "Logs", "Backup", "Deprecated"];

            $structure = [...[["", ROOT_PATH, ""]], ...callStructure()];
            foreach ($structure as [$call, $path]) {
                foreach ($excludedDirs as $excludedDir) {
                    if (str_contains($path, D . $excludedDir . D)) {
                        continue 2;
                    }
                }

                foreach ($extensions as $ext) {
                    $pattern = $path . D . "*." . $ext;
                    $files = glob($pattern);
                    foreach ($files as $fileN => $file) {
                        $lines = @file($file, FILE_IGNORE_NEW_LINES);
                        if ($lines === false) {
                            continue;
                        }

                        $hasMatch = false;

                        foreach ($lines as $lineNum => $line) {
                            if (stripos($line, $searchQuery) !== false) {
                                if (!$hasMatch) {
                                    $output .= $file . ":" . ($lineNum + 1) . "\n";
                                    $hasMatch = true;
                                }

                                if (strlen($line) > 114) {
                                    $pos = stripos($line, $searchQuery);
                                    $start = max(0, $pos - 50);
                                    $snippet = substr($line, $start, 100);
                                    $snippet = ".. {$snippet} ..";
                                    $output .= "{$snippet}" . "\n";
                                } else {
                                    $output .= ">  {$line}\n";
                                }
                            }
                        }

                        if ($hasMatch) {
                            $output .= "\n";
                        }
                    }
                }
            }

            if (empty($output)) {
                return "No matches found for: {$searchQuery}\n";
            }

            return $output;
        }
        # cli_search end

        # cli_serve begin
        function cli_serve(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<port> Starts PHP built-in web server for current node.";
            }

            $port = $argv[0] ?? "8000";
            $host = "localhost";
            $documentRoot = ROOT_PATH . "Public" . D . "Entry";

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
        # cli_serve end

        # cli_test begin
        function cli_test(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<type|internal> <filter> Use 'internal' for node testing.";
            }

            if (empty($argv)) {
                return "Usage: test <type|internal> <filter>\nTypes: Unit, Integration, Contract, E2E\nInternal: Runs all test_* functions\nExample: test internal cli_backup\n";
            }

            $type = $argv[0] ?? "Unit";
            $filter = $argv[1] ?? "";

            if ($type === "internal") {
                return runInternalTests($filter);
            }

            $testTypes = ["Unit", "Integration", "Contract", "E2E"];

            if (!in_array($type, $testTypes)) {
                return "E: Invalid test type. Available: " .
                    implode(", ", $testTypes) .
                    "\n";
            }

            $testPath = ROOT_PATH . "Test" . D . $type;

            if (!is_dir($testPath)) {
                return "E: Test directory not found: {$testPath}\n";
            }

            $phpFiles = glob($testPath . D . "*.php");

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
                            $result = (string) $testFunc();
                            $output .= trim($result) . " :: OK {$testFunc}()\n";
                            $passed++;
                        } catch (Exception $e) {
                            $output .= "FAIL {$testFunc}(): " . $e->getMessage() . "\n";
                            $failed++;
                        }
                    }

                    $classes = get_declared_classes();
                    foreach ($classes as $class) {
                        if (
                            str_ends_with($class, "Contract") ||
                            str_ends_with($class, "E2E") ||
                            str_ends_with($class, "Integration") ||
                            str_ends_with($class, "Unit")
                        ) {
                            $reflection = new ReflectionClass($class);

                            $testMethods = array_filter(
                                $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
                                fn($m) => str_starts_with($m->getName(), "test"),
                            );

                            if (
                                !empty($testMethods) &&
                                !$reflection->isAbstract() &&
                                str_contains($reflection->getNamespaceName(), "Test")
                            ) {
                                $total += count($testMethods);

                                foreach ($testMethods as $method) {
                                    $testName = $method->getName();
                                    try {
                                        $instance = $reflection->newInstance($testName);
                                        $instance->$testName();
                                        $output .= "OK {$class}::{$testName}()\n";
                                        $passed++;
                                    } catch (Throwable $e) {
                                        $output .=
                                            "FAIL {$class}::{$testName}(): " .
                                            $e->getMessage() .
                                            "\n";
                                        $failed++;
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $output .= "FAIL Error loading test: " . $e->getMessage() . "\n";
                    $failed++;
                }
                ob_end_clean();
            }

            $output .= "\n";
            $output .= "Results: {$passed}/{$total} passed, {$failed} failed\n";

            if ($failed > 0) {
                http_response_code(1);
            }

            return $output;
        }

        function runInternalTests(string $filter = ""): string
        {
            $output = "Running internal tests...\n\n";

            $allFunctions = get_defined_functions()["user"];

            $testFunctions = array_filter(
                $allFunctions,
                fn($functionName) => str_starts_with($functionName, "test_"),
            );

            if ($filter !== "") {
                $searchName = "test_{$filter}";
                $found = false;

                foreach ($testFunctions as $testFunc) {
                    if ($testFunc === $searchName) {
                        $testFunctions = [$testFunc];
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $testFunctions = array_filter(
                        $testFunctions,
                        fn($functionName) => strpos($functionName, $searchName) !==
                            false,
                    );

                    if (empty($testFunctions)) {
                        return "No internal test found matching: {$filter}\n";
                    }
                }
            }

            natsort($testFunctions);

            if (empty($testFunctions)) {
                return "No internal tests found.\n";
            }

            $passed = 0;
            $failed = 0;
            $total = 0;

            foreach ($testFunctions as $testFunc) {
                $total++;

                if (!function_exists($testFunc)) {
                    $output .= "{$testFunc} failed: Function does not exist\n";
                    $failed++;
                    continue;
                }

                try {
                    $result = $testFunc();

                    if ($result === 0) {
                        $output .= "{$testFunc} passed\n";
                        $passed++;
                    } else {
                        $output .= "{$testFunc} failed: {$result}\n";
                        $failed++;
                    }
                } catch (Exception $e) {
                    $output .=
                        "{$testFunc} failed: Exception - " . $e->getMessage() . "\n";
                    $failed++;
                } catch (Error $e) {
                    $output .= "{$testFunc} failed: Error - " . $e->getMessage() . "\n";
                    $failed++;
                }
            }

            $output .= "\n";
            $output .= "Results: {$passed}/{$total} passed, {$failed} failed\n";

            if ($failed > 0) {
                http_response_code(1);
            }

            return $output;
        }
        # cli_test end

        # cli_wrap begin
        function cli_wrap(bool $tooltip = false, array $argv = []): string
        {
            if ($tooltip) {
                return "<open|close> Wraps/unwraps node.php into separate files.";
            }

            $action = $argv[0] ?? "";

            return match ($action) {
                "open" => wrapOpen(),
                "close" => wrapClose(),
                default => "E: Usage: wrap <open|close>\n",
            };
        }

        function wrapOpen(): string
        {
            $nodeFile = ROOT_PATH . "node.php";
            if (!file_exists($nodeFile)) {
                return "E: node.php not found\n";
            }

            $content = file_get_contents($nodeFile);
            $sections = extractSections($content);

            if (empty($sections)) {
                return "✓ node.php is already wrapped\n";
            }

            $newContent = processSectionsOpen($content, $sections);
            file_put_contents($nodeFile, $newContent);

            return "✓ Wrapped " . count($sections) . " sections\n";
        }

        function wrapClose(): string
        {
            $nodeFile = ROOT_PATH . "node.php";
            if (!file_exists($nodeFile)) {
                return "E: node.php not found\n";
            }

            $content = file_get_contents($nodeFile);
            $sections = findWrappedSections($content);

            if (empty($sections)) {
                return "✓ node.php is already unwrapped\n";
            }

            $newContent = processSectionsClose($content, $sections);
            file_put_contents($nodeFile, $newContent);

            return "✓ Unwrapped " . count($sections) . " sections\n";
        }

        function extractSections(string $content, string $parentPath = ""): array
        {
            $lines = explode("\n", $content);
            $sections = [];
            $i = 0;
            $n = count($lines);

            while ($i < $n) {
                $line = $lines[$i];

                if (
                    preg_match('/^(\s*)#\s*([a-z_]+)\s+begin\s*$/', $line, $beginMatch)
                ) {
                    $markerIndent = $beginMatch[1];
                    $sectionName = $beginMatch[2];
                    $startIndex = $i;

                    $j = $i + 1;
                    $foundEnd = false;

                    while ($j < $n) {
                        if (
                            preg_match(
                                "/^\s*#\s*" .
                                    preg_quote($sectionName, "/") .
                                    '\s+end\s*$/',
                                $lines[$j],
                            )
                        ) {
                            $endIndex = $j;
                            $foundEnd = true;
                            break;
                        }
                        $j++;
                    }

                    if ($foundEnd) {
                        $sectionLines = array_slice($lines, $i + 1, $endIndex - $i - 1);
                        $sectionContent = implode("\n", $sectionLines);
                        $sectionContent = rtrim($sectionContent);

                        if (!preg_match("/^\s*include_once\s+/", $sectionContent)) {
                            $fullName = $parentPath
                                ? "{$parentPath}.{$sectionName}"
                                : $sectionName;

                            $innerSections = extractSections(
                                $sectionContent,
                                $fullName,
                            );
                            if (!empty($innerSections)) {
                                $sectionContent = processSectionsOpen(
                                    $sectionContent,
                                    $innerSections,
                                );
                            }

                            $sections[] = [
                                "name" => $sectionName,
                                "fullName" => $fullName,
                                "indent" => $markerIndent,
                                "start" => $startIndex,
                                "end" => $endIndex,
                                "content" => $sectionContent,
                            ];
                        }

                        $i = $endIndex;
                    }
                }

                $i++;
            }

            return $sections;
        }

        function processSectionsOpen(string $content, array $sections): string
        {
            $lines = explode("\n", $content);
            $offset = 0;

            foreach ($sections as $section) {
                $start = $section["start"] + $offset;
                $end = $section["end"] + $offset;

                $cleanContent = removeRelativeIndentation(
                    $section["content"],
                    $section["indent"],
                );

                $sectionFile = ROOT_PATH . "node.{$section["fullName"]}.php";
                file_put_contents(
                    $sectionFile,
                    "<?php declare(strict_types=1);\n\n{$cleanContent}\n",
                );

                $replacement = [
                    "{$section["indent"]}# {$section["name"]} begin",
                    "{$section["indent"]}include_once \"{\$LOCAL_PATH}node.{$section["fullName"]}.php\";",
                    "{$section["indent"]}# {$section["name"]} end",
                ];

                array_splice($lines, $start, $end - $start + 1, $replacement);
                $offset += count($replacement) - ($end - $start + 1);
            }

            return implode("\n", $lines);
        }

        function findWrappedSections(string $content): array
        {
            $lines = explode("\n", $content);
            $sections = [];
            $i = 0;
            $n = count($lines);

            while ($i < $n) {
                $line = $lines[$i];

                if (
                    preg_match('/^(\s*)#\s*([a-z_]+)\s+begin\s*$/', $line, $beginMatch)
                ) {
                    $markerIndent = $beginMatch[1];
                    $sectionName = $beginMatch[2];

                    if (
                        $i + 1 < $n &&
                        preg_match(
                            '/^\s*include_once\s+["\'][^"\']*node\.([a-z_.]+)\.php["\'];\s*$/',
                            $lines[$i + 1],
                            $includeMatch,
                        )
                    ) {
                        $fullName = $includeMatch[1];

                        $j = $i + 2;
                        $foundEnd = false;

                        while ($j < $n) {
                            if (
                                preg_match(
                                    "/^\s*#\s*" .
                                        preg_quote($sectionName, "/") .
                                        '\s+end\s*$/',
                                    $lines[$j],
                                )
                            ) {
                                $foundEnd = true;
                                break;
                            }
                            $j++;
                        }

                        if ($foundEnd) {
                            $sections[] = [
                                "name" => $sectionName,
                                "fullName" => $fullName,
                                "indent" => $markerIndent,
                                "start" => $i,
                                "end" => $j,
                            ];

                            $i = $j;
                        }
                    }
                }

                $i++;
            }

            return $sections;
        }

        function processSectionsClose(string $content, array $sections): string
        {
            $lines = explode("\n", $content);
            $offset = 0;

            foreach ($sections as $section) {
                $start = $section["start"] + $offset;
                $end = $section["end"] + $offset;

                $sectionFile = ROOT_PATH . "node.{$section["fullName"]}.php";

                if (file_exists($sectionFile)) {
                    $sectionContent = file_get_contents($sectionFile);
                    $sectionContent = preg_replace(
                        '/^<\?php\s+declare\(strict_types=1\);\s*\n+/',
                        "",
                        $sectionContent,
                        1,
                    );
                    $sectionContent = rtrim($sectionContent);

                    $innerSections = findWrappedSections($sectionContent);
                    if (!empty($innerSections)) {
                        $sectionContent = processSectionsClose(
                            $sectionContent,
                            $innerSections,
                        );
                    }

                    $indentedContent = addRelativeIndentation(
                        $sectionContent,
                        $section["indent"],
                    );

                    $replacement = ["{$section["indent"]}# {$section["name"]} begin"];
                    $replacement = array_merge(
                        $replacement,
                        explode("\n", $indentedContent),
                    );
                    $replacement[] = "{$section["indent"]}# {$section["name"]} end";

                    unlink($sectionFile);

                    array_splice($lines, $start, $end - $start + 1, $replacement);
                    $offset += count($replacement) - ($end - $start + 1);
                } else {
                    $i = $end;
                }
            }

            return implode("\n", $lines);
        }

        function removeRelativeIndentation(string $content, string $baseIndent): string
        {
            if (empty($baseIndent)) {
                return $content;
            }

            $lines = explode("\n", $content);
            $cleaned = [];
            $baseLen = strlen($baseIndent);

            foreach ($lines as $line) {
                $cleaned[] =
                    substr($line, 0, $baseLen) === $baseIndent
                        ? substr($line, $baseLen)
                        : $line;
            }

            return implode("\n", $cleaned);
        }

        function addRelativeIndentation(string $content, string $baseIndent): string
        {
            if (empty($baseIndent)) {
                return $content;
            }

            $lines = explode("\n", $content);
            $indented = [];

            foreach ($lines as $line) {
                $indented[] = $line === "" ? "" : "{$baseIndent}{$line}";
            }

            return implode("\n", $indented);
        }
        # cli_wrap end

        # Check if any argument got set over CLI.
        if (isset($argv[1]) && ($cli_func = "cli_{$argv[1]}")) {
            if (function_exists($cli_func)) {
                $r = $cli_func(false, array_slice($argv, 2));
                unset($cli_func);
            }
        } else {
            $r = cli_help(false, []);
        }

        # Begin CLI metrics.
        $u = microtime(true) - $TIME_START;
        $m = memory_get_peak_usage() / 1048576;

        $title = NODE_NAME . " // PHP " . PHP_VERSION;
        printf("{$title}, Time: %.4fs, RAM: %.2fMB", $u, $m);

        unset($TIME_START, $LOCAL_PATH, $u, $m, $title);

        echo ", Global variables: [" .
            implode(",", array_diff(array_keys(get_defined_vars()), [...["r"], ...SUPERGLOBALS])) .
            "]\n\n";

        unset($ROOT_PATHS);
        die("{$r}\n");
    }
}
