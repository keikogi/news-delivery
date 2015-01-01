Keikogi News Delivery Tool
==========================

Requirements
------------
PHP 5.3+

PHP Mongo extension 1.5+

MongoDB 2.6+

Installation
------------
Add this to a composer.json file:
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/keikogi/news-delivery"
    }
],
"require": {
    "keikogi/news-delivery": ">=1.0.0"
}
```

Usage
-----
```php
require_once __DIR__ . '/vendor/autoload.php';

use Keikogi\NewsDelivery\Source\Innogest;

$options = array(
    'log.path' => __DIR__ . '/log/news_delivery.log'
);

(new Innogest($options))->run();
```
