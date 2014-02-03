<?php
namespace Modx\Ext\Xadmin\Models;

use ActiveRecord\Model;

class Document extends Model 
{
    /**
     * пока нет способа передавать префикс таблиц в статические атрибуты
     * @Todo: пересмотреть в сторону упрощения
     */
    static $table_name='ob_site_content';

    static $has_many = array(
        array('tvval', 'foreign_key' => 'contentid', 'class_name'=>'Tvval'),
        array('tv', 'through' => 'tvval', 'foreign_key' => 'tmplvarid', 'class_name'=>'Tv')
    );

}