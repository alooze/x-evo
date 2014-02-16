<?php
namespace Tcache\CacheTags;

/**
 * Класс для получения ID тега по переменной в REQUEST
 */
class RequestCacheTag extends AbstractCacheTag
{
    /**
     * Метод для получения id тега
     */
    public static function by($varName)
    {
        if (!isset($_REQUEST[$varName])) {
            return 'Req-'.$varName.'-NULL';
        } else if (is_int($_REQUEST[$varName])) {
            return 'Req-'.$varName.'-'.$_REQUEST[$varName];
        } else {
            return 'Req-'.$varName.'-'.md5(serialize($_REQUEST[$varName]));
        }
    }

    /**
     * Метод для получения строки для glob (удаление всех тегов одного типа)
     */
    public static function all($varName='')
    {
        if ($varName == '' || $varName == '*') {
            return 'Req-*';
        }
        return 'Req-'.$varName.'-*';
    }
}