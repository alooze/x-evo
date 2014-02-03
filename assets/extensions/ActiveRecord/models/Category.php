<?php
namespace Modx\Ext\Xadmin\Models;

use ActiveRecord\Model;

class Category extends Model 
{
    /**
     * пока нет способа передавать префикс таблиц в статические атрибуты
     * @Todo: пересмотреть в сторону упрощения
     */
    static $table_name='ob_xa_categories';

    /**
     * Указываем, что данные по родительским категориям находятся тут же
     */
    static $belongs_to = array(
        array('pcat', 'class_name' => 'Category', 'foreign_key'=>'parent')
    );
}