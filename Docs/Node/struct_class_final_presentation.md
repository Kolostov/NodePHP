# Final/Presentation Class Patterns

## Overview: User Interface & API Layer Implementation

Final presentation classes in Node.php handle all user interaction concerns—HTTP request/response cycles, API endpoints, UI rendering, and response formatting. These patterns implement the presentation layer while maintaining strict separation from business logic and leveraging Node.php's framework capabilities (`r()` logging, `h()` hooks, `p()` phases) for consistent, observable behavior.

### Presentation Layer Philosophy in Node.php

The presentation layer in Node.php serves as the boundary between external consumers (web browsers, API clients, CLI users) and the application's internal systems. Key design principles:

- **Request/Response Isolation**: Presentation components handle HTTP concerns without business logic
- **Framework Context Propagation**: All responses include NODE_NAME and request tracing
- **Structured Logging**: All presentation operations logged via `r()` with appropriate types
- **Hook-Driven Customization**: Response formatting and request processing extensible via `h()`
- **Phase-Aware Execution**: Complex presentation flows can integrate with `p()` phases

Presentation patterns transform internal data structures into external formats while maintaining framework consistency.

## Final Presentation Class Details

### Final/Presentation/Controller/Action

**Purpose**: Single action handler that processes a specific HTTP request method and route. Action controllers are minimal, focused handlers that execute one specific operation, making them ideal for simple endpoints or RPC-style APIs.

**Pattern Relationships**: Actions are typically called by **Router** patterns, use **Request** objects for input, return **Response** objects, and may call **Service** patterns for business logic. They integrate with **Middleware** for cross-cutting concerns.

```php
final class CreateUserAction
{
    public function __invoke(HttpRequest $request): HttpResponse
    {
        // Validate input via Form/Request patterns
        $form = new UserRegistrationForm($request->all());

        if (!$form->validate()) {
            return r("Validation failed", 'Access',
                HttpResponse::badRequest($form->errors()),
                ['action' => 'create_user']
            );
        }

        // Call domain service via Service pattern
        $userService = h('service.user');
        $user = $userService->create($form->validated());

        // Return response via Response pattern
        return r("User created", 'Access',
            HttpResponse::created(['id' => $user->id]),
            ['user_id' => $user->id]
        );
    }
}
```

**Key Characteristics**:

- **Single Responsibility**: One action per class
- **Minimal Logic**: Focus on request/response handling only
- **Service Delegation**: Business logic delegated to services
- **Framework Logging**: All actions logged via `r()` with Access type

### Final/Presentation/Controller/Api

**Purpose**: API-specific controller that handles RESTful operations with standardized response formats, error handling, and content negotiation. API controllers implement consistent patterns for success/error responses, status codes, and data formatting.

**Pattern Relationships**: API controllers use **Resource** patterns for data transformation, integrate with **Middleware** for authentication/rate limiting, and return standardized **Response** objects. They often coordinate multiple **Action** patterns.

```php
final class UserApiController
{
    public function index(HttpRequest $request): HttpResponse
    {
        // Use Query pattern for data retrieval specification
        $query = UserListQuery::fromRequest($request->query());

        // Call Repository pattern for data access
        $users = h('repository.users')->findAll($query);

        // Transform via Resource pattern
        $resource = new UserResourceCollection($users);

        // Return standardized API response
        return r("Users list retrieved", 'Access',
            HttpResponse::json($resource->toArray()),
            ['count' => count($users), 'query' => $query->toArray()]
        );
    }
}
```

**Key Characteristics**:

- **RESTful Conventions**: Standard HTTP methods and status codes
- **Resource Transformation**: Data formatted via Resource patterns
- **Content Negotiation**: Supports JSON, XML, etc.
- **Consistent Error Handling**: Standardized error responses

### Final/Presentation/Controller/Page

**Purpose**: Web page controller that renders HTML views with server-side data binding. Page controllers handle traditional web requests, manage session state, and coordinate view rendering for server-rendered applications.

**Pattern Relationships**: Page controllers use **View** patterns for template rendering, integrate with **Session** middleware, and may use **Form** patterns for user input. They return HTML **Response** objects.

```php
final class UserProfilePageController
{
    public function show(HttpRequest $request): HttpResponse
    {
        // Check authentication via Middleware
        $userId = $request->session()->get('user_id');

        // Call Service pattern for business data
        $userService = h('service.user');
        $user = $userService->find($userId);

        // Prepare view data
        $viewData = [
            'user' => $user,
            'page_title' => 'User Profile'
        ];

        // Render via View pattern
        $view = new TemplateView('users/profile.php', $viewData);

        return r("Profile page rendered", 'Access',
            HttpResponse::html($view->render()),
            ['user_id' => $userId]
        );
    }
}
```

**Key Characteristics**:

- **HTML Focus**: Returns HTML responses
- **Session Integration**: Manages user sessions
- **Server-Side Rendering**: Templates rendered on server
- **View Coordination**: Manages multiple view components

### Final/Presentation/Controller/Resource

**Purpose**: RESTful resource controller that implements full CRUD operations for a domain resource. Resource controllers follow REST conventions with standardized routes, methods, and responses for resource management.

**Pattern Relationships**: Resource controllers implement all CRUD actions, use **Repository** patterns for persistence, transform data via **Resource** patterns, and return appropriate **Response** objects for each operation.

```php
final class ArticleResourceController
{
    // Implements full REST interface:
    // GET /articles (index)
    // POST /articles (store)
    // GET /articles/{id} (show)
    // PUT/PATCH /articles/{id} (update)
    // DELETE /articles/{id} (destroy)

    public function store(HttpRequest $request): HttpResponse
    {
        // Validate via Form pattern
        $form = new ArticleForm($request->all());

        if (!$form->validate()) {
            return HttpResponse::unprocessableEntity($form->errors());
        }

        // Create via Repository pattern
        $article = h('repository.articles')->create($form->validated());

        // Return resource representation
        $resource = new ArticleResource($article);

        return r("Article created", 'Access',
            HttpResponse::created($resource->toArray()),
            ['article_id' => $article->id]
        );
    }
}
```

**Key Characteristics**:

- **Full CRUD Implementation**: All REST operations
- **Resource-Oriented**: Operations centered on a domain resource
- **Standardized Responses**: Consistent response formats
- **Idempotent Operations**: PUT/DELETE operations idempotent

### Final/Presentation/Endpoint

**Purpose**: Concrete public API endpoint that exposes a specific operation via HTTP. Endpoints are self-contained operations that may not fit RESTful resource patterns, such as authentication, file uploads, or complex operations.

**Pattern Relationships**: Endpoints are registered with the **Router**, use **Request** objects for input, may call multiple **Service** patterns, and return **Response** objects. They often integrate with **Middleware** for security.

```php
final class UploadFileEndpoint
{
    public function __invoke(HttpRequest $request): HttpResponse
    {
        // Validate file upload via Form pattern
        $validator = new FileUploadValidator($request->files());

        if (!$validator->validate()) {
            return HttpResponse::badRequest($validator->errors());
        }

        // Process via Service pattern
        $storageService = h('service.storage');
        $file = $storageService->store($request->file('file'));

        // Return operation result
        return r("File uploaded", 'Access',
            HttpResponse::ok(['file_id' => $file->id, 'url' => $file->url]),
            ['file_size' => $file->size, 'mime_type' => $file->mime_type]
        );
    }
}
```

**Key Characteristics**:

- **Operation-Focused**: Single specific operation
- **Public Interface**: Exposed directly via routing
- **Self-Contained**: Complete operation in one endpoint
- **Service Coordination**: May call multiple services

### Final/Presentation/Filter

**Purpose**: Request filtering implementation that transforms or validates incoming request data before it reaches controllers. Filters provide reusable request processing logic for data sanitization, transformation, or validation.

**Pattern Relationships**: Filters are registered as **Middleware** in the request pipeline, process **Request** objects, and can modify requests before they reach **Controller** patterns. They often work with **Form** patterns for validation.

```php
final class TrimStringsFilter
{
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        // Trim all string input
        $input = $request->all();
        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = trim($value);
            }
        });

        // Replace request input with trimmed values
        $request->replace($input);

        // Log filtering operation
        r("Request strings trimmed", 'Internal', null, [
            'filter' => static::class,
            'fields_trimmed' => count(array_filter($input, 'is_string'))
        ]);

        return $next($request);
    }
}
```

**Key Characteristics**:

- **Request Transformation**: Modifies request data
- **Reusable Logic**: Applied to multiple routes/controllers
- **Middleware Integration**: Part of request processing pipeline
- **Framework Logging**: Operations logged via `r()`

### Final/Presentation/Http/Request

**Purpose**: Inbound HTTP request object that encapsulates all request data with validation and framework context. Request objects provide type-safe access to input data with built-in validation and framework metadata.

**Pattern Relationships**: Request objects are created by the framework from superglobals, used by **Controller** and **Endpoint** patterns, validated by **Form** patterns, and may be modified by **Filter** patterns.

```php
final class HttpRequest
{
    private array $data;
    private array $headers;
    private string $method;
    private string $uri;
    private array $context;

    public function __construct()
    {
        $this->context = [
            'request_id' => uniqid('req_', true),
            'node' => NODE_NAME,
            'received_at' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli'
        ];

        // Log request receipt
        r("HTTP request received", 'Access', null, array_merge(
            $this->context,
            ['method' => $this->method, 'uri' => $this->uri]
        ));
    }

    public function input(string $key = null, $default = null)
    {
        // Type-safe input access with hook transformation
        $value = $key ? ($this->data[$key] ?? $default) : $this->data;
        return h("request.input.{$key}", $value) ?? $value;
    }
}
```

**Key Characteristics**:

- **Type-Safe Access**: Methods for different input types
- **Framework Context**: Includes request ID, node name, timestamps
- **Hook Transformation**: Input values transformable via hooks
- **Request Logging**: All requests logged via `r()` with Access type

### Final/Presentation/Http/Resource

**Purpose**: API resource transformer that converts internal data structures to external API representations. Resource patterns handle data presentation concerns—field selection, relationship embedding, hypermedia links, and response formatting.

**Pattern Relationships**: Resource patterns transform data from **Entity** or **Model** patterns, are used by **Controller** patterns in responses, and can include related resources via **Repository** patterns.

```php
final class UserResource
{
    public function __construct(private User $user) {}

    public function toArray(): array
    {
        $data = [
            'id' => $this->user->id,
            'type' => 'user',
            'attributes' => [
                'email' => $this->user->email,
                'name' => $this->user->name,
                'created_at' => $this->formatDate($this->user->created_at)
            ],
            'links' => [
                'self' => "/users/{$this->user->id}"
            ]
        ];

        // Include relationships via hook system
        $relationships = h('resource.user.relationships', $this->user);
        if ($relationships) {
            $data['relationships'] = $relationships;
        }

        return $data;
    }
}
```

**Key Characteristics**:

- **Data Transformation**: Internal → external format conversion
- **Field Control**: Selects/excludes fields for API consumers
- **Hypermedia Links**: Includes API navigation links
- **Relationship Embedding**: Can include related resources

### Final/Presentation/Http/Response

**Purpose**: HTTP response object that encapsulates response data, status codes, and headers. Response patterns provide a consistent interface for creating HTTP responses with proper status codes, headers, and content formatting.

**Pattern Relationships**: Response objects are returned by **Controller**, **Endpoint**, and **Middleware** patterns, can contain data from **Resource** patterns, and are formatted by **Responder** patterns.

```php
final class HttpResponse
{
    private int $status;
    private array $headers;
    private $content;

    public static function json(array $data, int $status = 200): self
    {
        $response = new self($status);
        $response->headers['Content-Type'] = 'application/json';
        $response->content = json_encode($data, JSON_PRETTY_PRINT);

        // Log response creation
        r("JSON response created", 'Internal', null, [
            'status' => $status,
            'data_size' => strlen($response->content),
            'node' => NODE_NAME
        ]);

        return $response;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->content;
    }
}
```

**Key Characteristics**:

- **Status Code Management**: Proper HTTP status codes
- **Header Management**: Content type, caching, CORS headers
- **Content Formatting**: JSON, HTML, plain text, etc.
- **Framework Logging**: Response creation logged via `r()`

### Final/Presentation/Middleware

**Purpose**: Request/response processing layer that intercepts HTTP requests to perform cross-cutting concerns. Middleware patterns provide reusable processing logic for authentication, logging, CORS, rate limiting, etc.

**Pattern Relationships**: Middleware sits between the **Router** and **Controller** patterns, processes **Request** objects, can modify **Response** objects, and is organized in a pipeline pattern.

```php
final class AuthenticationMiddleware
{
    public function handle(HttpRequest $request, Closure $next): HttpResponse
    {
        // Extract token from request
        $token = $request->bearerToken() ?? $request->input('token');

        if (!$token) {
            return r("Authentication failed: no token", 'Access',
                HttpResponse::unauthorized(),
                ['middleware' => static::class]
            );
        }

        // Validate token via Service pattern
        $authService = h('service.auth');
        $user = $authService->validateToken($token);

        if (!$user) {
            return r("Authentication failed: invalid token", 'Access',
                HttpResponse::unauthorized(),
                ['middleware' => static::class, 'token' => substr($token, 0, 10) . '...']
            );
        }

        // Attach user to request for downstream handlers
        $request->setUser($user);

        r("Authentication successful", 'Access', null, [
            'user_id' => $user->id,
            'middleware' => static::class
        ]);

        return $next($request);
    }
}
```

**Key Characteristics**:

- **Cross-Cutting Concerns**: Authentication, logging, CORS, etc.
- **Pipeline Processing**: Ordered execution chain
- **Request/Response Modification**: Can modify both request and response
- **Framework Logging**: All middleware operations logged via `r()`

### Final/Presentation/Responder

**Purpose**: Concrete response formatter that transforms data into appropriate output formats based on content negotiation. Responder patterns handle the final presentation formatting for different content types (JSON, XML, HTML, CSV).

**Pattern Relationships**: Responder patterns are called by **Controller** patterns, format data from **Resource** patterns, and produce **Response** objects. They integrate with content negotiation from **Request** objects.

```php
final class JsonResponder
{
    public function respond($data, int $status = 200, array $headers = []): HttpResponse
    {
        // Transform data via hooks
        $transformedData = h('responder.json.transform', $data) ?? $data;

        // Create JSON response
        $response = HttpResponse::json($transformedData, $status);

        // Add custom headers
        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        // Add framework headers
        $response->header('X-Node-Name', NODE_NAME);
        $response->header('X-Response-Time', microtime(true) - REQUEST_START);

        r("JSON response formatted", 'Internal', null, [
            'status' => $status,
            'data_type' => gettype($data),
            'responder' => static::class
        ]);

        return $response;
    }
}
```

**Key Characteristics**:

- **Content Negotiation**: Formats based on Accept header
- **Data Transformation**: Final data formatting before output
- **Header Management**: Sets appropriate content headers
- **Framework Integration**: Includes node context in headers

### Final/Presentation/View/Component

**Purpose**: Reusable UI or API component that encapsulates presentation logic for a specific UI element or API fragment. Components are self-contained presentation units that can be composed into larger views.

**Pattern Relationships**: Components are used by **Template** patterns, can be rendered by **Renderer** patterns, and may use **Resource** patterns for data transformation. They support composition and reuse.

```php
final class UserAvatarComponent
{
    public function render(User $user, array $options = []): string
    {
        $size = $options['size'] ?? 'medium';
        $showName = $options['show_name'] ?? false;

        // Generate avatar URL (could use Gravatar, uploaded image, etc.)
        $avatarUrl = h('view.component.avatar.url', $user->email) ??
                    $this->generateDefaultAvatar($user);

        $html = "<img src=\"{$avatarUrl}\" alt=\"{$user->name}\" class=\"avatar avatar-{$size}\">";

        if ($showName) {
            $html .= "<span class=\"avatar-name\">{$user->name}</span>";
        }

        r("Avatar component rendered", 'Internal', null, [
            'component' => static::class,
            'user_id' => $user->id,
            'size' => $size
        ]);

        return $html;
    }
}
```

**Key Characteristics**:

- **Reusable Units**: Self-contained presentation logic
- **Composable**: Can be combined into larger views
- **Configurable**: Options for customization
- **Framework Logging**: Component rendering logged via `r()`

### Final/Presentation/View/Renderer

**Purpose**: Template rendering implementation that processes templates with data binding. Renderer patterns handle the technical details of template processing—parsing, variable substitution, inheritance, and output generation.

**Pattern Relationships**: Renderer patterns process **Template** patterns with data from **Controller** patterns, can include **Component** patterns, and produce output for **Response** patterns.

```php
final class PhpTemplateRenderer
{
    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->resolveTemplate($template);

        // Extract data to local variables for template
        extract($data, EXTR_SKIP);

        // Start output buffering
        ob_start();

        try {
            include $templatePath;
            $content = ob_get_clean();

            r("Template rendered", 'Internal', null, [
                'template' => $template,
                'data_keys' => array_keys($data),
                'renderer' => static::class
            ]);

            return $content;
        } catch (Throwable $e) {
            ob_end_clean();

            return r("Template rendering failed", 'Error',
                "Error rendering template: {$template}",
                ['error' => $e->getMessage(), 'template' => $template]
            );
        }
    }
}
```

**Key Characteristics**:

- **Template Processing**: Parses and executes templates
- **Data Binding**: Injects data into template context
- **Error Handling**: Graceful template error recovery
- **Output Buffering**: Captures template output

### Final/Presentation/View/Template

**Purpose**: Concrete renderable template that defines presentation structure with placeholders for dynamic data. Template patterns separate presentation structure from rendering logic, supporting inheritance, partials, and reusable layout patterns.

**Pattern Relationships**: Template patterns are processed by **Renderer** patterns, include **Component** patterns, receive data from **Controller** patterns, and produce output for **Response** patterns.

```php
// users/profile.php template
<div class="user-profile">
    <h1><?= htmlspecialchars($user->name) ?>'s Profile</h1>

    <?php include 'components/avatar.php' ?>

    <div class="profile-details">
        <p>Email: <?= htmlspecialchars($user->email) ?></p>
        <p>Member since: <?= date('F j, Y', $user->created_at) ?></p>
    </div>

    <?php if ($user->bio): ?>
        <div class="bio">
            <h2>About</h2>
            <p><?= nl2br(htmlspecialchars($user->bio)) ?></p>
        </div>
    <?php endif; ?>
</div>
```

**Key Characteristics**:

- **Presentation Structure**: HTML/XML/Text structure with dynamic parts
- **Template Inheritance**: Support for base layouts and overrides
- **Partial Inclusion**: Reusable template fragments
- **Data Escaping**: Automatic escaping for security

## Presentation Pattern Flow

```
HTTP Request
    ↓
Request Object (encapsulates input)
    ↓
Middleware Pipeline (filtering, auth, logging)
    ↓
Router (matches to Controller/Endpoint)
    ↓
Controller/Endpoint (orchestrates response)
    ↓
Service/Repository (business logic/data)
    ↓
Resource (data transformation)
    ↓
Responder/View (formatting/rendering)
    ↓
Response Object (final output)
    ↓
HTTP Response
```

## Pattern Integration Summary

Presentation patterns in Node.php work together to handle user interaction while maintaining framework consistency:

- **Consistent Logging**: All presentation operations use `r()` with appropriate log types
- **Hook Extensibility**: Request/response processing customizable via `h()`
- **Framework Context**: All responses include NODE_NAME and request tracing
- **Separation of Concerns**: Clear boundaries between presentation, business logic, and data access
- **Reusable Components**: Middleware, filters, and components promote reuse

This layered approach ensures that presentation concerns are handled consistently, observably, and extensibly throughout the application.
