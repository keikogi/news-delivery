Keikogi News Delivery Tool
==========================

Requirements
------------
PHP 7.0+

PHP Mongo extension 1.3+

MongoDB 3.4+

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
