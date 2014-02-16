<?php
namespace Tcache\CacheTags;

/**
 * Класс для получения ID тега по parentId
 */
class ParentCacheTag extends AbstractCacheTag
{
    /**
     * Метод для получения id тега
     */
    public static function by($parentId)
    {
        return 'docparent-'.$parentId;
    }

    /**
     * Метод для получения строки для glob (удаление всех тегов одного типа)
     */
    public static function all()
    {
        return 'docparent-*';
    }
}