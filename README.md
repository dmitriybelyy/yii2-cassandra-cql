

yii2-cassandra-cql
==================
A Cassandra CQL3 client wrapper over phpcassa for Yii 2

Provides object oriented access to Cassandra using CQL3 in a familiar Yii Style.
This project is a wrapper over the famous phpcassa library.

This extension also handles issues with the phpcassa library 'Data Types' while using the latest CQL3 API provided by cassandra.
The following discussion on StackOverflow describes the problem:

http://stackoverflow.com/questions/16139362/cassandra-is-not-retrieving-the-correct-integer-value

Deprecated
------------
Phpcassa has been deprecated and will no longer be supported!
Please use  https://github.com/datastax/php-driver

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist beliy/yii2-cassandra-cql "*"
```

or

```
composer require beliy/yii2-cassandra-cql:dev-master
```

or add

```
"beliy/yii2-cassandra-cql": "*"
```

to the require section of your `composer.json` file.


Usage
-----
    'components' => [
    ...
      'cassandra' => [
            'class' => '\beliy\cassandra\Connection',
            'keyspace' => 'youkeyspace',
            'servers' => ['127.0.0.1', ...],
        ],
        'cache' => [
          'class' => '\beliy\cassandra\Cache',
          'tableName' => 'cache', // dedault 'cache'
          'tablePrefix' => 'mega', // default ''
        ],
        ...
      ]

      $cass = Yii::$app->cassandra;
      $cql = 'SELECT value FROM yourtable WHERE id=1';
      $query_result = $cass->cql3Query($cql);
      $rows = $cass->cqlGetRows($query_result);

      Usage for cache:
      First you may init column fammily storage fro cache.
      $cache = Yii::$app->cache;
      $cache->createTable();

      Than use standart Yii2 Cache APIs.

**REQUIREMENTS**

Yii 2.0.3 / PHP 5.5+

**Resources**

Fork extensions from http://www.yiiframework.com/extension/cassandra-cql

External Projects used in this extension is the phpcassa library for PHP and Cassandra https://github.com/thobbs/phpcassa

