<?php
namespace Modx\Ext\Xadmin\Models;

use ActiveRecord\Model;

class Tvval extends Model 
{
    /**
     * пока нет способа передавать префикс таблиц в статические атрибуты
     * @Todo: пересмотреть в сторону упрощения
     */
    static $table_name='ob_site_tmplvar_contentvalues';

    static $belongs_to = array(
            array('post', 'foreign_key' => 'contentid', 'class_name'=>'Post'),
            array('tv', 'foreign_key' => 'tmplvarid', 'class_name'=>'Tv')
    );

}