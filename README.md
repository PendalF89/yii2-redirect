Yii2 redirect component
================
Component for convenient URL redirects in Yii2.

Features
------------
* All redirects are stored in the DB
* Fast speed
* Easy installation and usage

Installation
------------
The preferred way to install this extension is through [composer](https://getcomposer.org/download/).

Either run

```
php composer.phar require pendalf89/yii2-redirect
```

or add

```
"pendalf89/yii2-redirect": "^1.0.0"
```

to the require section of your `composer.json` file.

Create table in your database (MySQL, Postgres etc.)
```sql
CREATE TABLE `redirect` (
    `source` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `target` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`source`) USING BTREE,
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Configuration:

```php
'on beforeRequest' => function() {
    Yii::$app->redirect->run();
},
'components' => [
    'redirect' => 'pendalf89\redirect\Redirect',
],
```

Installation done.

Usage
------------
Just add your urls to DB:

```
Yii::$app->redirect->add('https://example.com/from/', 'https://example.com/to/');
```

After that the redirects will work.