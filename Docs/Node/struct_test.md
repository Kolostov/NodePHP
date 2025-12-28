# Test Directory Structure

## Overview: Comprehensive Testing Strategy

The Test directory implements a multi-layered testing approach following the testing pyramid. Each test type targets different levels of the application with appropriate isolation and scope, ensuring code quality while supporting Node.php's phase-driven architecture and hook system.

## Test Directory Components

### Test/Contract

**Purpose**: Interface compliance tests that verify implementations adhere to defined contracts/interfaces.

**Characteristics**:

- Tests interface method signatures and return types
- Ensures Liskov Substitution Principle compliance
- Verifies implementation contracts across versions
- Uses PHPUnit's interface assertion methods

```php
// Test/Contract/RepositoryInterfaceTest.php
final class RepositoryInterfaceTest extends TestCase
{
    public function test_implements_repository_interface(): void
    {
        $repository = new UserRepository();
        $this->assertInstanceOf(RepositoryInterface::class, $repository);

        // Verify all required methods exist
        $this->assertMethodExists($repository, 'find');
        $this->assertMethodExists($repository, 'save');
        $this->assertMethodExists($repository, 'delete');
    }
}
```

### Test/E2E

**Purpose**: End-to-end tests that simulate real user scenarios across the entire application.

**Characteristics**:

- Tests complete user workflows
- Uses real HTTP requests (via Guzzle/BrowserKit)
- Tests database and external service integration
- Verifies phase execution flow via `p()` monitoring

```php
// Test/E2E/UserRegistrationTest.php
final class UserRegistrationTest extends E2ETestCase
{
    public function test_complete_registration_flow(): void
    {
        // Simulate user registration flow
        $this->visit('/register')
             ->type('test@example.com', 'email')
             ->type('password123', 'password')
             ->press('Register')
             ->seeInDatabase('users', ['email' => 'test@example.com'])
             ->seeEmailSent('test@example.com', 'Welcome');

        // Verify phase execution
        $this->assertPhaseExecuted('mutate');
        $this->assertPhaseExecuted('persist');
    }
}
```

### Test/Feature

**Purpose**: Feature-level tests that verify complete features work correctly from a user perspective.

**Characteristics**:

- Tests specific application features
- May involve multiple components but mock external services
- Verifies business logic and user experience
- Tests hook system integration

```php
// Test/Feature/UserProfileFeatureTest.php
final class UserProfileFeatureTest extends FeatureTestCase
{
    public function test_user_can_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->put('/profile', ['name' => 'Updated Name'])
             ->assertRedirect('/profile')
             ->assertSessionHas('success');

        // Verify hooks were triggered
        $this->assertHookCalled('user.profile.updated');
        $this->assertLogged('Profile updated', 'Audit');
    }
}
```

### Test/Integration

**Purpose**: Tests involving multiple nodes or external systems to verify integration points.

**Characteristics**:

- Tests cross-node communication
- Verifies external service integration (APIs, databases, queues)
- Tests file system operations via `f()`
- Verifies phase coordination between nodes

```php
// Test/Integration/NodeCommunicationTest.php
final class NodeCommunicationTest extends IntegrationTestCase
{
    public function test_nodes_can_share_state(): void
    {
        // Set up multiple node environments
        $nodeA = new NodeEnvironment('node-a');
        $nodeB = new NodeEnvironment('node-b');

        // Node A writes shared state
        $nodeA->executePhase('mutate', function () {
            return ['shared_value' => 'test-data'];
        });

        // Node B should see the shared state
        $nodeBState = $nodeB->executePhase('resolve');
        $this->assertEquals('test-data', $nodeBState['shared_value']);

        // Verify cross-node hook communication
        $this->assertHookPropagated('node.state.shared', 'node-a', 'node-b');
    }
}
```

### Test/Unit

**Purpose**: Self-contained class or function tests that verify individual units in isolation.

**Characteristics**:

- Tests single classes, methods, or functions
- Mocks all dependencies
- Fast execution, no external systems
- Tests framework utility functions (`h()`, `r()`, `p()`, `f()`)

```php
// Test/Unit/PhaseFunctionTest.php
final class PhaseFunctionTest extends TestCase
{
    public function test_p_function_registers_handler(): void
    {
        // Reset phase system
        PhaseRegistry::reset();

        // Register a test handler
        $handlerCalled = false;
        p('test', function () use (&$handlerCalled) {
            $handlerCalled = true;
            return ['test' => 'complete'];
        });

        // Execute phase
        $result = p('test');

        $this->assertTrue($handlerCalled);
        $this->assertEquals(['test' => 'complete'], $result);
    }

    public function test_r_function_logs_and_returns_value(): void
    {
        // Capture log output
        $logCapture = new LogCapture();

        // Call r() with return value
        $result = r('Test message', 'Internal', 'return-value', ['data' => 'test']);

        $this->assertEquals('return-value', $result);
        $this->assertLogContains('Test message', $logCapture);
    }
}
```

## Test Infrastructure

### Test Case Classes

- `ContractTestCase`: Base for interface compliance tests
- `E2ETestCase`: Full application tests with HTTP client
- `FeatureTestCase`: Feature tests with authenticated users
- `IntegrationTestCase`: Cross-system integration tests
- `UnitTestCase`: Isolated unit tests with mocking

### Testing Framework Integration

- **PHPUnit**: Primary testing framework
- **Mockery**: Mocking library for dependencies
- **Database Transactions**: Test database isolation
- **File System Sandbox**: Isolated `f()` operations
- **Hook Mocking**: Mock `h()` calls for isolation
- **Phase Monitoring**: Track `p()` phase execution

### Test Utilities

```php
// Common test utilities for Node.php patterns
trait NodeTestingUtilities
{
    protected function mockHook(string $name, $returnValue = null): void
    {
        HookRegistry::mock($name, $returnValue);
    }

    protected function assertPhaseExecuted(string $phase): void
    {
        $this->assertTrue(PhaseMonitor::wasExecuted($phase));
    }

    protected function assertLogged(string $message, string $type): void
    {
        $this->assertTrue(LogCapture::contains($message, $type));
    }

    protected function withFileSandbox(callable $callback): void
    {
        FileSandbox::create()->run($callback);
    }
}
```

## Testing Node.php Specifics

### Phase System Testing

- Verify phase execution order
- Test phase rollback behavior
- Monitor state isolation between phases
- Test phase handler registration

### Hook System Testing

- Mock hook responses for isolation
- Verify hook propagation
- Test hook error handling
- Monitor hook execution counts

### Framework Function Testing

- Test `r()` logging and return values
- Verify `f()` file operations in sandbox
- Test `h()` hook registration and execution
- Verify `p()` phase orchestration

### Pattern Integration Testing

- Test patterns work together correctly
- Verify pattern contracts are maintained
- Test cross-pattern dependencies
- Verify framework integration points

This testing structure ensures comprehensive coverage while respecting Node.php's unique architectural patterns, particularly the phase system and hook architecture that define the framework's execution model.
