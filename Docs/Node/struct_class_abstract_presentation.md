# Abstract/Presentation Class Patterns in NodePHP Framework

## Overview: Presentation Foundation Classes

In the NodePHP framework, abstract presentation classes are located under `Primitive/Class/Abstract/Presentation/` and provide foundational structures for user interactions and outputs. They integrate with framework utilities like `r()` for response logging, `h()` for hooks (e.g., middleware), `f()` for template files, `p()` for phases (e.g., execute for rendering), and `env()` for content negotiation. These classes abstract delivery mechanisms, align with `Presentation/` structures like Controller/Api and View/Template, and enable extensions via hooks.

### Abstract Presentation Class Types Overview

| **Base Class** | **Presentation Role** | **Input Processing**                     | **Output Generation** | **Extension Focus**                                           |
| -------------- | --------------------- | ---------------------------------------- | --------------------- | ------------------------------------------------------------- |
| **Controller** | Request orchestration | HTTP parameters, body (via superglobals) | Response/Redirect     | Action methods, middleware via `h()`                          |
| **Endpoint**   | API exposure          | Structured payloads                      | Structured data       | Input validation, business logic, `h("endpoint_pre_execute")` |
| **Responder**  | Response formatting   | Data/Status codes                        | Formatted output      | Content negotiation, formatting via `env()`                   |
| **View**       | Content rendering     | View data, templates                     | Rendered content      | Template engines, partials via `f()`                          |

## Abstract Presentation Class Details

### Abstract/Presentation/Controller

**Purpose**: Provides foundational structure for HTTP request handlers, integrated with NodePHP's routing in `Coordination/Routes/` and phases. Uses `h()` for lifecycle hooks and `r()` for logging.

```php
<?php declare(strict_types=1);

abstract class Controller
{
    protected $request;
    protected $response;
    protected $action;
    protected $params = [];

    // Template method for request handling
    final public function handle($request): array {
        $this->initialize($request);
        h('controller_pre_action', $this); // Framework hook
        $this->beforeAction();
        if (!$this->authorize()) {
            return $this->unauthorizedResponse();
        }
        $actionResult = $this->dispatchAction();
        $this->afterAction($actionResult);
        $response = $this->buildResponse($actionResult);
        p('execute'); // Framework phase for response
        return $response;
    }

    // Initialization
    final protected function initialize($request): void {
        $this->request = $request ?? $_SERVER; // Framework superglobals
        $this->action = $this->request['action'] ?? 'index';
        $this->params = $this->request['params'] ?? [];
        $this->response = [
            'status' => 200,
            'headers' => [],
            'body' => null
        ];
    }

    // Action dispatch with RESTful conventions
    final protected function dispatchAction() {
        $method = strtolower($this->request['REQUEST_METHOD'] ?? 'get');
        // Try method-specific action first (getIndex, postStore)
        $methodAction = $method . ucfirst($this->action);
        if (method_exists($this, $methodAction)) {
            return $this->$methodAction();
        }
        // Fall back to plain action
        if (method_exists($this, $this->action)) {
            return $this->{$this->action}();
        }
        throw new RuntimeException(
            "Action {$this->action} not found for method {$method}"
        );
    }

    // Lifecycle hooks
    protected function beforeAction(): void {
        // Authentication, input validation, logging
        r("Controller beforeAction: " . $this->action, "Access");
    }

    protected function afterAction($result): void {
        // Response transformation, logging
        r("Controller afterAction: " . $this->action, "Access", null, ['result' => $result]);
    }

    protected function authorize(): bool {
        return true; // Default: authorized, override with env('APP_AUTH_ENABLED')
    }

    // Response building
    final protected function buildResponse($actionResult): array {
        // If action returned an array, merge with response
        if (is_array($actionResult)) {
            $this->response = array_merge($this->response, $actionResult);
        }
        // If action returned a string, use as body
        elseif (is_string($actionResult)) {
            $this->response['body'] = $actionResult;
        }
        return $this->response;
    }

    // Common response helpers
    final protected function json($data, $status = 200): array {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($data)
        ];
    }

    final protected function view($template, $data = []): array {
        return [
            'status' => 200,
            'headers' => ['Content-Type' => 'text/html'],
            'view' => $template,
            'data' => $data
        ];
    }

    final protected function redirect($url, $status = 302): array {
        return [
            'status' => $status,
            'headers' => ['Location' => $url],
            'redirect' => true
        ];
    }

    final protected function unauthorizedResponse(): array {
        return [
            'status' => 401,
            'body' => 'Unauthorized'
        ];
    }

    final protected function notFoundResponse(): array {
        return [
            'status' => 404,
            'body' => 'Not Found'
        ];
    }

    // Input accessors
    final protected function input($key = null, $default = null) {
        if ($key === null) {
            return $this->params;
        }
        return $this->params[$key] ?? $default;
    }

    final protected function hasInput($key): bool {
        return isset($this->params[$key]);
    }

    // RESTful action stubs (override as needed)
    protected function getIndex() {
        return $this->notFoundResponse();
    }

    protected function getShow($id) {
        return $this->notFoundResponse();
    }

    protected function postStore() {
        return $this->notFoundResponse();
    }

    protected function putUpdate($id) {
        return $this->notFoundResponse();
    }

    protected function deleteDestroy($id) {
        return $this->notFoundResponse();
    }

    // Magic parameter binding
    public function __call($name, $arguments) {
        // Allow dynamic method handling
        if (strpos($name, 'action') === 0) {
            $actionName = lcfirst(substr($name, 6));
            if (method_exists($this, $actionName)) {
                return $this->$actionName(...$arguments);
            }
        }
        throw new BadMethodCallException("Method {$name} not found");
    }
}
```

### Abstract/Presentation/Endpoint

**Purpose**: Base for API endpoints, with input/output handling. Integrates with `Presentation/Endpoint`, uses `h()` for validation hooks and `r()` for errors.

```php
<?php declare(strict_types=1);

abstract class Endpoint
{
    protected $input;
    protected $errors = [];
    protected $metadata = [];

    // Main execution template method
    final public function __invoke($input) {
        $this->initialize($input);
        h('endpoint_pre_execute', $this); // Framework hook
        if (!$this->validateInput()) {
            return $this->validationErrorResponse();
        }
        if (!$this->authorize()) {
            return $this->authorizationErrorResponse();
        }
        $result = $this->execute();
        if (!$this->validateOutput($result)) {
            return $this->processingErrorResponse();
        }
        return $this->buildSuccessResponse($result);
    }

    // Initialization
    final protected function initialize($input): void {
        $this->input = $this->normalizeInput($input);
        $this->errors = [];
        $this->metadata = [
            'endpoint' => static::class,
            'timestamp' => time(),
            'duration' => 0
        ];
    }

    // Input normalization hook
    protected function normalizeInput($input) {
        if (is_string($input)) {
            return json_decode($input, true) ?? [];
        }
        return (array) $input;
    }

    // Core processing - must be implemented
    abstract protected function execute();

    // Validation hooks
    protected function validateInput(): bool {
        return true; // Default: always valid
    }

    protected function validateOutput($result): bool {
        return true; // Default: always valid
    }

    protected function authorize(): bool {
        return true; // Default: authorized
    }

    // Error collection
    final protected function addError($field, $message): void {
        $this->errors[] = [
            'field' => $field,
            'message' => $message
        ];
        r("Endpoint error: {$message}", "Error", null, ['field' => $field]);
    }

    final protected function hasErrors(): bool {
        return !empty($this->errors);
    }

    // Response building
    final protected function buildSuccessResponse($data): array {
        return [
            'success' => true,
            'data' => $this->transformOutput($data),
            'meta' => $this->metadata
        ];
    }

    final protected function validationErrorResponse(): array {
        return [
            'success' => false,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Input validation failed',
                'details' => $this->errors
            ],
            'meta' => $this->metadata
        ];
    }

    final protected function authorizationErrorResponse(): array {
        return [
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => 'Not authorized to perform this action'
            ],
            'meta' => $this->metadata
        ];
    }

    final protected function processingErrorResponse(): array {
        return [
            'success' => false,
            'error' => [
                'code' => 'PROCESSING_ERROR',
                'message' => 'Failed to process request'
            ],
            'meta' => $this->metadata
        ];
    }

    // Input/output transformation hooks
    protected function transformInput($input) {
        return $input; // Default: no transformation
    }

    protected function transformOutput($output) {
        return $output; // Default: no transformation
    }

    // Input accessors
    final protected function get($key, $default = null) {
        return $this->input[$key] ?? $default;
    }

    final protected function getString($key, $default = ''): string {
        $value = $this->get($key, $default);
        return (string) $value;
    }

    final protected function getInt($key, $default = 0): int {
        $value = $this->get($key, $default);
        return (int) $value;
    }

    final protected function getArray($key, $default = []): array {
        $value = $this->get($key, $default);
        return (array) $value;
    }

    // Metadata management
    final protected function addMetadata($key, $value): void {
        $this->metadata[$key] = $value;
    }

    // Factory method for dependency injection
    public static function make() {
        return new static();
    }
}
```

### Abstract/Presentation/Responder

**Purpose**: Foundation for response formatters, with negotiation via `env()`. Uses `r()` for logging and `h()` for transformation hooks.

```php
<?php declare(strict_types=1);

abstract class Responder
{
    protected $data;
    protected $status = 200;
    protected $headers = [];
    protected $format = 'json';

    // Template method for response generation
    final public function respond($data, $status = 200, array $headers = []): array {
        $this->initialize($data, $status, $headers);
        h('responder_pre_format', $this); // Framework hook
        $this->beforeFormat();
        $formattedData = $this->formatData();
        $this->afterFormat($formattedData);
        return $this->buildResponse($formattedData);
    }

    // Error response template method
    final public function error($message, $status = 500, array $details = []): array {
        $errorData = [
            'error' => [
                'message' => $message,
                'status' => $status,
                'details' => $details,
                'timestamp' => time()
            ]
        ];
        r("Responder error: {$message}", "Error", null, ['status' => $status]);
        return $this->respond($errorData, $status);
    }

    // Initialization
    final protected function initialize($data, $status, array $headers): void {
        $this->data = $data;
        $this->status = $status;
        $this->headers = array_merge($this->getDefaultHeaders(), $headers);
        $this->format = $this->determineFormat();
    }

    // Format determination
    final protected function determineFormat(): string {
        // Check Accept header, URL extension, or configuration
        return env('RESPONDER_DEFAULT_FORMAT', 'json');
    }

    // Data formatting - must be implemented
    abstract protected function formatData();

    // Response building
    final protected function buildResponse($formattedData): array {
        return [
            'status' => $this->status,
            'headers' => $this->headers,
            'body' => $formattedData
        ];
    }

    // Lifecycle hooks
    protected function beforeFormat(): void {
        // Data validation, transformation
    }

    protected function afterFormat($formattedData): void {
        // Logging, metrics collection
        r("Response formatted: " . $this->format, "Internal");
    }

    // Default headers
    protected function getDefaultHeaders(): array {
        return [
            'X-Powered-By' => NODE_NAME,
            'X-Response-Time' => microtime(true) - $GLOBALS['TIME_START']
        ];
    }

    // Header management
    final public function setHeader($name, $value): void {
        $this->headers[$name] = $value;
    }

    final public function setHeaders(array $headers): void {
        $this->headers = array_merge($this->headers, $headers);
    }

    // Status code helpers
    final protected function isSuccessStatus(): bool {
        return $this->status >= 200 && $this->status < 300;
    }

    final protected function isErrorStatus(): bool {
        return $this->status >= 400;
    }

    // Data transformation hooks
    protected function transformData($data) {
        return $data; // Default: no transformation
    }

    // Content negotiation
    final public function supportsFormat($format): bool {
        return in_array($format, $this->getSupportedFormats());
    }

    protected function getSupportedFormats(): array {
        return ['json', 'xml', 'html'];
    }

    // Factory method for format-specific responders
    final public static function forFormat($format): Responder {
        switch ($format) {
            case 'json':
                return new JsonResponder();
            case 'xml':
                return new XmlResponder();
            case 'html':
                return new HtmlResponder();
            default:
                throw new InvalidArgumentException("Unsupported format: {$format}");
        }
    }

    // Convenience methods
    final public function json($data, $status = 200): array {
        $this->format = 'json';
        return $this->respond($data, $status);
    }

    final public function xml($data, $status = 200): array {
        $this->format = 'xml';
        return $this->respond($data, $status);
    }
}
```

### Abstract/Presentation/View

**Purpose**: Base for view renderers, managing templates via `f()`. Integrates with `Template/View`, uses `h()` for rendering hooks and `p("finalize")` for output.

```php
<?php declare(strict_types=1);

abstract class View
{
    protected $template;
    protected $data = [];
    protected $sections = [];
    protected $layout = null;

    // Template method for rendering
    final public function render($template = null, array $data = []): string {
        $this->initialize($template, $data);
        h('view_pre_render', $this); // Framework hook
        $this->beforeRender();
        $content = $this->renderTemplate();
        if ($this->layout) {
            $content = $this->renderWithLayout($content);
        }
        $this->afterRender($content);
        p('finalize'); // Framework phase
        return $content;
    }

    // Initialization
    final protected function initialize($template, array $data): void {
        $this->template = $template ?? $this->template;
        $this->data = array_merge($this->data, $data);
        if (empty($this->template)) {
            throw new RuntimeException("No template specified");
        }
    }

    // Template rendering - must be implemented
    abstract protected function renderTemplate(): string;

    // Layout rendering
    final protected function renderWithLayout($content): string {
        $this->data['content'] = $content;
        // Save current template and render layout
        $originalTemplate = $this->template;
        $this->template = $this->layout;
        $layoutContent = $this->renderTemplate();
        $this->template = $originalTemplate;
        return $layoutContent;
    }

    // Lifecycle hooks
    protected function beforeRender(): void {
        // Data preparation, section initialization
        r("View beforeRender: {$this->template}", "Internal");
    }

    protected function afterRender($content): void {
        // Content post-processing, caching
        r("View afterRender: {$this->template}", "Internal");
    }

    // Data management
    final public function with($key, $value): self {
        $this->data[$key] = $value;
        return $this;
    }

    final public function share(array $data): self {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    final public function get($key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    final public function has($key): bool {
        return array_key_exists($key, $this->data);
    }

    // Section management (for template inheritance)
    final public function startSection($name): void {
        ob_start();
        $this->sections[$name] = null;
    }

    final public function endSection(): void {
        $content = ob_get_clean();
        $lastSection = array_key_last($this->sections);
        if ($lastSection !== null) {
            $this->sections[$lastSection] = $content;
        }
    }

    final public function yieldSection($name, $default = ''): string {
        return $this->sections[$name] ?? $default;
    }

    // Layout management
    final public function layout($layout): self {
        $this->layout = $layout;
        return $this;
    }

    // Partial rendering
    final public function partial($template, array $data = []): string {
        $partialView = new static();
        return $partialView->render($template, array_merge($this->data, $data));
    }

    // Escaping helpers
    final protected function escape($value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    final protected function raw($value): string {
        return $value; // Explicitly mark as raw/trusted
    }

    // Template resolution
    protected function resolveTemplate($template): string {
        // Add extensions, check paths, etc.
        return f("Template/View/{$template}.php", 'find') ?? $template . '.php';
    }

    // Factory method for different template engines
    final public static function engine($engine): View {
        switch ($engine) {
            case 'php':
                return new PhpTemplateView();
            case 'twig':
                return new TwigView();
            case 'blade':
                return new BladeView();
            default:
                throw new InvalidArgumentException("Unsupported template engine: {$engine}");
        }
    }

    // Magic methods for convenient data access in templates
    public function __get($name) {
        return $this->get($name);
    }

    public function __set($name, $value) {
        $this->with($name, $value);
    }

    public function __isset($name): bool {
        return $this->has($name);
    }

    // String representation
    public function __toString(): string {
        try {
            return $this->render();
        } catch (Exception $e) {
            r("View render error: " . $e->getMessage(), "Exception");
            return 'Error rendering view: ' . $e->getMessage();
        }
    }
}
```

## Complementary Patterns in NodePHP

**Template Method**: Used in base classes for skeletons, integrated with `p()` phases. **Strategy Pattern**: Responder/View as strategies, selected via `env()`. **Decorator Pattern**: Wrap responses with `h()` hooks. **Composite Pattern**: Views composed of partials in `Template/Partial`. **Front Controller**: With Controller base, aligned with `Public/Entry/index.php`.

## Distinguishing Characteristics

**vs. Service Classes**: Presentation handles interaction; services in `Primitive/Class/Final/Infrastructure/Service` handle logic. **vs. Middleware**: Controllers orchestrate; middleware in `Presentation/Middleware` processes via `h()`. **vs. Template Engines**: View provides interface; engines in `Extension/Library`. **vs. Response Objects**: Responders format; objects in `Presentation/Http/Response`. **vs. Action Classes**: Endpoints presentation-focused; actions in `Primitive/Class/Final/Behavioral/Command`.
