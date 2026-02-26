# DaniarDev Laravel Core

> Reusable Laravel package with Helpers, Traits, and Patterns for consistent code structure across projects.

[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-11%2C%12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.2-purple.svg)](https://php.net)

## Features

- 🎯 **Consistent Code Structure** - Standardized helpers and patterns
- 🔐 **Audit Trail** - Built-in audit fields tracking
- 💾 **Transaction Management** - Safe transaction enforcement
- 📝 **Request Transformation** - Auto camelCase/snakeCase conversion
- 🚀 **API Response** - Standardized JSON response format
- 🛡️ **Safe Execution** - Silent execution for side effects

## Installation

```bash
composer require daniardev/laravel-core
```

The package will be auto-discovered by Laravel.

## What's Included

### Helpers

| Helper | Description |
|--------|-------------|
| `AppHelper` | String manipulation, camelCase conversion |
| `AppResponse` | Standardized JSON API responses |
| `AppRequest` | Request pagination helpers |
| `AppQuery` | Query pagination with metadata |
| `AppResource` | Resource pagination formatting |
| `AppSafe` | Silent execution for side effects (emails, etc) |
| `AppMigration` | Migration macros and helpers |
| `AppValidation` | Validation helpers |
| `AppLog` | Logging helpers |
| `AppPermission` | Permission helpers |
| `AppSecure` | Security helpers |

### Traits

| Trait | Description |
|-------|-------------|
| `AppAuditable` | Adds `created_by`, `updated_by`, `deleted_by` tracking |
| `AppTransactional` | Enforces DB::transaction in write methods |
| `AppRequestTrait` | Auto-transforms request keys to snake_case |
| `AppPagination` | Pagination helpers |

### Exceptions

| Exception | Description |
|-----------|-------------|
| `AppException` | Custom exception with error code and data |

### Migration Macros

| Macro | Description |
|-------|-------------|
| `auditFields()` | Add audit columns (created_by, updated_by, deleted_by) |
| `auditFieldsSafe()` | Safe version for existing tables |
| `statusFields()` | Add status columns (is_active, activated_at) |

## Usage

### Model with Audit Trail

```php
use DaniarDev\LaravelCore\Traits\AppAuditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Model
{
    use HasUuids, AppAuditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];
}
```

### Migration with Audit Fields

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email');

    // Adds: created_by, updated_by, deleted_by (if softDeletes)
    $table->auditFields();

    $table->timestamps();
    $table->softDeletes();
});
```

### Service with Transaction

```php
use DaniarDev\LaravelCore\Traits\AppTransactional;
use DaniarDev\LaravelCore\Exceptions\AppException;

class UserService implements UserInterface
{
    use AppTransactional;

    public function create(string $name, string $email): User
    {
        $this->requireTransaction(); // Enforces transaction

        $existing = User::where('email', $email)->first();
        if ($existing) {
            throw new AppException('Email already exists', 422);
        }

        return User::create(compact('name', 'email'));
    }
}
```

### Controller with Transaction

```php
use DaniarDev\LaravelCore\Helpers\AppResponse;

class UserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = $this->service->create(
                name: $request->name,
                email: $request->email
            );

            return AppResponse::success(
                UserResource::make($user),
                __('message.saved')
            );
        });
    }
}
```

### Resource with CamelCase

```php
use DaniarDev\LaravelCore\Helpers\AppHelper;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'full_name' => $this->name,
            'email_address' => $this->email,
        ];

        return AppHelper::toCamelCase($data);
        // Output: { id, fullName, emailAddress }
    }
}
```

### Safe Execution (Side Effects)

```php
use DaniarDev\LaravelCore\Helpers\AppSafe;

// Send email without breaking main flow
AppSafe::run('Welcome email', fn() =>
    Mail::to($user->email)->send(new WelcomeEmail($user))
);

// With retry for external APIs
$result = AppSafe::runWithRetry('External API', $callback, maxAttempts: 3);
```

## Coding Standards

This package follows these Laravel patterns:

| Pattern | Rule |
|---------|------|
| **Controller** | Inject Service, use DB::transaction, return AppResponse |
| **Service** | MUST have Interface, use AppTransactional, ALL parameters explicit |
| **Model** | Use HasUuids, AppAuditable, guarded instead of fillable |
| **Request** | Use AppRequestTrait |
| **Resource** | Use AppHelper::toCamelCase(), include audit info |

## Configuration

Publish config (optional):

```bash
php artisan vendor:publish --tag=laravel-core-config
```

## Requirements

- PHP >= 8.2
- Laravel >= 11.0

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Author

**DaniarDev**

- Website: https://daniar.dev
- GitHub: [@daniardev](https://github.com/daniardev)

## Support

- Create an issue for bugs or feature requests
- Star ⭐ the repo if you find it useful!

---

Made with ❤️ by DaniarDev