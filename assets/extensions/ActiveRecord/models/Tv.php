<?php
namespace Modx\Ext\Xadmin\Models;

use ActiveRecord\Model;

class Tv extends Model 
{
    /**
     * пока нет способа передавать префикс таблиц в статические атрибуты
     * @Todo: пересмотреть в сторону упрощения
     */
    static $table_name='ob_site_tmplvars';

    static $has_many = array(
        array('tvv', 'foreign_key' => 'tmplvarid', 'class_name'=>'Tvval')
    );

}