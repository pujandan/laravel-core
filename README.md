# DaniarDev Laravel Core

Reusable components for DaniarDev Laravel projects.

## Installation

Install the package via composer:

```bash
composer require daniardev/laravel-core
```

## Usage

### Helpers

#### ResponseHelper

```php
use DaniarDev\LaravelCore\Helpers\ResponseHelper;

// Success response
ResponseHelper::success($data, 'Success message', 200);

// Error response
ResponseHelper::error('Error message', 400, $errors);

// Paginated response
ResponseHelper::paginate($paginator, 'Success message');
```

#### ValidationHelper

```php
use DaniarDev\LaravelCore\Helpers\ValidationHelper;

// Get validation errors
$errors = ValidationHelper::getErrors($validator);

// Format validation errors
$formatted = ValidationHelper::formatErrors($validator);
```

### Traits

#### HasUuid

Add UUID primary key support to your models:

```php
use DaniarDev\LaravelCore\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasUuid;

    protected $primaryKey = 'uuid';
    public $incrementing = false;
    protected $keyType = 'string';
}
```

#### ApiResponse

Add API response methods to your controllers:

```php
use DaniarDev\LaravelCore\Traits\ApiResponse;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        return $this->successResponse($data, 'Success');
    }
}
```

### Exceptions

#### AppException

Create custom application exceptions:

```php
use DaniarDev\LaravelCore\Exceptions\AppException;

throw new AppException('Custom error message', 400, $errorData, 'ERROR_CODE');
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-core-config
```

## License

MIT
