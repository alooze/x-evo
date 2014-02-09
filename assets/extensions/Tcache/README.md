# CacheCache

Caching framework for PHP 5.3+

## Версия для проекта x-evo

Дополненный функционал:

 - Добавлено кеширование сниппетов MODX прямо в коде
 - Добавлена поддержка тегов (только для файлового кеша в этой версии)
 - Очистка кеша по ключу, тегу или типу тегов
 

## Подробнее

Работа со сниппетами в коде MODX:

```
<?php
use CacheCache\Tcache as Cache;
$c = new Cache();
echo $c->runSnippet('Ditto', array('parents'=>0)); // первый вызов
// Mem : 2.75 mb, MySQL: 0.0573 s, 20 request(s), PHP: 0.2261 s, total: 0.2834 s, document from database.

echo $c->runSnippet('Ditto', array('parents'=>0)); // тут же второй вызов
//Mem : 2 mb, MySQL: 0.0028 s, 3 request(s), PHP: 0.0594 s, total: 0.0622 s, document from database.
```

Учитываем окружение при запуске сниппета:


```
echo $c->runSnippet('Ditto', array('parents'=>0, 'display'=>4, 'paginate'=>1), array('GET'=>'start'));
// для каждой страницы при пагинации Ditto будет создан отдельный кеш-файл
```

Добавляем теги к вызову:

```
<?php
use CacheCache\Tcache as Cache;
use CacheCache\CacheTags\UserCacheTag as Utag;
use CacheCache\CacheTags\RequestCacheTag as Rtag;

$c = new Cache();

$userId = $modx->getLoginUserID('mgr');
echo $c->withTags(array(Utag::by($userId), Rtag::by('start')))->runSnippet('Ditto', array('parents'=>0, 'display'=>4, 'paginate'=>1), array('GET'=>'start'));
// каждому из кеш-файлов, созданных при пагинации Ditto, будет присвоен тег 'user-НОМЕР' 
// и тег 'Request-start-НОМЕР'
```

Очистка кеша по тегу ParentTag (сохраняем страницу, которая будет влиять на вывод Ditto):

```
use CacheCache\Tcache as Cache;
use CacheCache\CacheTags\ParentCacheTag as Ptag;
$c = new Cache();
$c->flushByTag(Ptag::by($modx->documentObject['parent']));
```

Очистка кеша по всем тегам RequestTag 

```
use CacheCache\CacheTags\RequestCacheTag as Rtag;
...
$c->flushByTagType(Rtag::all('start'));
// или вообще ПО ВСЕМ
$c->flushByTagType(Rtag::all());
```


## Оригинальный текст

[![Build Status](https://secure.travis-ci.org/maximebf/CacheCache.png)](http://travis-ci.org/maximebf/CacheCache)

Features:

 - Easy retreival and storing of key, value pairs using the many available methods
 - Cache function calls
 - Available object wrapper to cache calls to methods
 - Pipelines ala Predis (see below)
 - Namespaces
 - TTL variations to avoid all caches to expire at the same time
 - Multiple backends support (apc, file, memcache(d), memory, redis, session)
 - [Monolog](https://github.com/Seldaek/monolog) support
 - Very well documented

CacheCache features are exposed through a Cache object which itself uses backends to store the data.
Multiple instances of Cache objects can be managed using the CacheManager.

Full documentation at [http://maximebf.github.com/CacheCache/](http://maximebf.github.com/CacheCache/)

Examples:

    $cache = new CacheCache\Cache(new CacheCache\Backends\Memory());

    if (($foo = $cache->get('foo')) === null) {
        $foo = 'bar';
        $cache->set('foo', $foo);
    }

    if (!$cache->start('foo')) {
        echo "bar\n";
        $cache->end();
    }

    $cache->call('sleep', array(2));
    $cache->call('sleep', array(2)); // won't sleep!

    $r = $cache->pipeline(function($pipe) {
        $pipe->set('foo', 'bar');
        $pipe->set('bar', 'foo');
        $pipe->get('foo');
        $pipe->set('foo', 'foobar');
        $pipe->get('foo');
    });

More examples in [examples/](https://github.com/alooze/x-evo/tree/master/assets/extensions/Tcache/examples)
