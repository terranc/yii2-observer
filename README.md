# This is my package yii2-observer

This is a package that allows you to write code in Yii2 using the same habits as Laravel Observer.


## Installation

You can install the package via composer:

```bash
composer require terranc/yii2-observer
```

## Usage

### Step 1
```php
namespace common\models;

use yii\db\ActiveRecord;
use Terran\YiiObserver\ObserverTrait;

class User extends ActiveRecord {
    use ObserverTrait;
}
```

### Step 2
Add File: `common/observers/UserObserver.php`
```php
namespace common\observers;
use common\models\User;

class UserObserver {
    public function creating(User $user) {
        // Your code ...
    }
    public function created(User $user) {
        // Your code ...
    }
    public function updating(User $user) {
        // Your code ...
    }
    public function updated(User $user) {
        // Your code ...
    }
    public function deleting(User $user) {
        // Your code ...
    }
    public function deleted(User $user) {
        // Your code ...
    }
}

```

### Step 3
Modify `common/config/bootstrap.php`:
```php
// ...
// Add the following code as needed
\common\models\User::observe(\common\observers\UserObserver::class);
// ...
```



## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [TerranChao](https://github.com/terranc)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
