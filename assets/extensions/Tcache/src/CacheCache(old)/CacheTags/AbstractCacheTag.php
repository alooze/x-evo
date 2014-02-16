<?php
namespace CacheCache\CacheTags;

/**
 * Base class for backends
 */
abstract class AbstractCacheTag 
{
    /**
     * Метод для получения id тега
     */
    public static function by($id)
    {
        return get_class(self).$id;
    }

    /**
     * Метод для получения строки для glob (удаление всех тегов одного типа)
     */
    public static function all()
    {
        return get_class(self).'*';
    }
}