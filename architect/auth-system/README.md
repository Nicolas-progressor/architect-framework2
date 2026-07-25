# Architect Auth System

Modern authentication and authorization (RBAC) system for Architect Framework.

## Features

- **Authentication** (login, logout, session management)
- **Authorization** (role-based access control)
- **JWT support** (via `firebase/php-jwt`)
- **PSR-11** (Container), **PSR-15** (Middleware) compatible
- **Dependency Injection** ready
- **Configurable** roles and permissions
- **Extensible** via interfaces
- **Event system** (custom event dispatcher with pattern matching, priority, propagation control)
- **OAuth2** (Google, GitHub, extensible)
- **Migration support** (via Axiom ORM)
- **Multi‑application config** (per‑app auth settings)
- **Redirect URL configuration**
- **Console command** for generating auth migrations

## Installation

```bash
composer require architect/auth-system
```

## Configuration

Create `app/config/auth.json` in your Architect application:

```json
{
    "driver": "database",
    "table_prefix": "auth_",
    "session_lifetime": 1440,
    "jwt_secret": "your-secret-key",
    "jwt_ttl": 3600,
    "default_role": "guest",
    "urls": {
        "login": "/login",
        "logout": "/logout",
        "register": "/register",
        "redirect_after_login": "/",
        "redirect_after_logout": "/",
        "redirect_after_register": "/"
    },
    "oauth": {
        "google": {
            "client_id": "",
            "client_secret": "",
            "redirect_uri": "/oauth/google/callback"
        },
        "github": {
            "client_id": "",
            "client_secret": "",
            "redirect_uri": "/oauth/github/callback"
        }
    },
    "roles": {
        "admin": {
            "permissions": ["*"],
            "description": "Administrator"
        },
        "user": {
            "permissions": ["view_posts", "create_posts"],
            "description": "Regular user"
        }
    },
    "permissions": {
        "view_posts": "View posts",
        "create_posts": "Create posts"
    }
}
```

### Per‑application configuration

You can also place an `auth.json` inside `apps/{app}/config/` to override settings for a specific application.

## Usage

### Authentication

```php
use Architect\AuthSystem\Contracts\AuthenticationInterface;

// Via container
$auth = $container->get(AuthenticationInterface::class);

// Login
if ($auth->login('username', 'password')) {
    echo 'Logged in';
}

// Check authentication
if ($auth->isLoggedIn()) {
    $user = $auth->getUser();
}

// Logout
$auth->logout();
```

### Authorization

```php
use Architect\AuthSystem\Contracts\AuthorizationInterface;

$authorization = $container->get(AuthorizationInterface::class);

if ($authorization->hasRole($user, 'admin')) {
    // User has admin role
}

if ($authorization->hasPermission($user, 'create_posts')) {
    // User can create posts
}
```

### Middleware

Middleware are registered automatically via `AuthServiceProvider`.

**AuthMiddleware** – requires authentication.

```php
// In routes configuration
$router->get('/dashboard', [AuthMiddleware::class, 'handle'], [DashboardController::class, 'index']);
```

**GuestMiddleware** – redirects authenticated users away.

```php
$router->get('/login', [GuestMiddleware::class, 'handle'], [AuthController::class, 'login']);
```

**RoleMiddleware** – requires specific role(s).

```php
$router->get('/admin', [RoleMiddleware::class, 'handle', ['admin']], [AdminController::class, 'index']);
```

### Controller Example

```php
namespace App\Controllers;

use Architect\AuthSystem\Controllers\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
    // Inherits login, logout, register actions
}
```

## Events

The system includes a powerful event dispatcher (`AuthEventDispatcher`) that implements `EventDispatcherInterface` and provides:

- **Priority‑based listener ordering**
- **Pattern matching** (e.g., `auth.*` matches all auth events)
- **Propagation control** (events can be stopped)
- **Integration with Statement Architect** (optional)

### Available events

- `auth.login` – after successful login (`LoginEvent`)
- `auth.logout` – after logout (`LogoutEvent`)
- `auth.register` – after user registration (`RegisterEvent`)
- `auth.failed` – after failed login attempt (`FailedAuthenticationEvent`)
- `auth.permission_denied` – when a permission check fails (`PermissionDeniedEvent`)

### Subscribing to events

```php
use Architect\AuthSystem\Events\EventDispatcherInterface;

$dispatcher = $container->get(EventDispatcherInterface::class);

// Simple listener
$dispatcher->subscribe('auth.login', function ($event) {
    // $event is an instance of LoginEvent
    log_action('User logged in', $event->getUser());
});

// Pattern listener
$dispatcher->subscribe('auth.*', function ($event) {
    // Fires for any auth event
});

// Listener with priority (higher priority runs first)
$dispatcher->subscribe('auth.logout', function ($event) {
    // Priority 10
}, 10);
```

### Stopping event propagation

Inside a listener you can call `$event->stopPropagation()` to prevent further listeners from being executed.

```php
$dispatcher->subscribe('auth.login', function (LoginEvent $event) {
    if ($event->getUser()['id'] === 1) {
        $event->stopPropagation();
    }
});
```

## OAuth2

The system supports OAuth2 authentication through Google, GitHub and other providers.

### Configuration

Add client credentials to `auth.json` under the `oauth` key (see example above).

### Usage

```php
use Architect\AuthSystem\Services\OAuth2\OAuthManager;

$oauth = $container->get(OAuthManager::class);

// Get authorization URL
$url = $oauth->getAuthorizationUrl('google', ['openid', 'email', 'profile']);

// After callback, exchange code for user
$user = $oauth->authenticate('google', $_GET['code']);
```

### Adding a custom provider

1. Implement `OAuthProviderInterface`.
2. Extend `AbstractOAuthProvider` for convenience.
3. Register the provider in `OAuthManager::createProvider()`.

## Migrations

Database tables are created via Axiom ORM migrations. The package provides a console command to generate a migration tailored to your auth configuration.

### Generating a migration

Run the following command to create a migration file with the correct table prefix and structure:

```bash
php bin/arc make:auth-migration
```

This will create a file in the `migrations/` directory with a name like `2026_03_15_183257_create_auth_system_tables.php`.

### Running migrations

Apply the migration using the standard Architect CLI:

```bash
php bin/arc db:migrate
```

### Manual migration

If you prefer to write the migration manually, the generated migration creates the following tables:

- `auth_roles`
- `auth_users`
- `auth_user_oauth`
- `auth_permissions`
- `auth_role_permission`

The table prefix is taken from the `table_prefix` setting in your auth configuration (default: `auth_`).

## Models

- `Architect\AuthSystem\Models\User`
- `Architect\AuthSystem\Models\Role`
- `Architect\AuthSystem\Models\Permission`

Extend them if needed.

## Testing

Run PHPUnit tests:

```bash
composer test
```

The package includes unit tests for the event dispatcher and configuration service.

## License

MIT