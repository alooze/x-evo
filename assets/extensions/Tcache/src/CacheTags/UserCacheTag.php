<?php
namespace Tcache\CacheTags;

/**
 * Класс для получения ID тега по id пользователя
 */
class UserCacheTag extends AbstractCacheTag
{
    /**
     * Метод для получения id тега
     */
    public static function by($userId)
    {
        return 'user-'.$userId;
    }

    /**
     * Метод для получения строки для glob (удаление всех тегов одного типа)
     */
    public static function all()
    {
        return 'user-*';
    }
}