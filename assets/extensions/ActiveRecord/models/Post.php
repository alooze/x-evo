<?php
namespace Modx\Ext\Xadmin\Models;

use ActiveRecord\Model;
use Modx\Ext\Xadmin\Models\Tvval as TVV;

class Post extends Model 
{
    static $table_name='ob_site_content';

    static $after_construct = array('loadTvv');
    
    
    /**
     * перегружаем метод, чтобы использовать instantiating_via_find
     */
    public function __construct(array $attributes=array(), $guard_attributes=true, $instantiating_via_find=true, $new_record=true)
    {
        parent::__construct($attributes, $guard_attributes, true, $new_record);
    }

    
    /**
     * пост-хук after_construct
     */
    public function loadTvv()
    {
        $all = TVV::find_all_by_contentid($this->id);
        foreach ($all as $one) {
            $this->assign_attribute($one->tv->name, $one->value);
        }
        
    }

    /**
     * Перегружаем метод для принудительного использования parent
     */
    public static function find(/* $type, $options */)
    {
        $class = get_called_class();

        if (func_num_args() <= 0)
            throw new RecordNotFound("Couldn't find $class without an ID");

        $args = func_get_args();
        $options = static::extract_and_validate_options($args);
        
        // print_r($options);
        // Book::all(array('conditions' => 'price < 15.00'));
        if (is_array($options['conditions'])) {
            $options['conditions'][0].= ' AND parent=?';
            //array_merge($options['conditions'], 'parent=2');
            $options['conditions'][] = 2;
        } elseif (isset($options['conditions'])) {
            $options['conditions'].= ' AND parent=2';
        } else {
            $options['conditions'] = 'parent=2';
        }
        

        $num_args = count($args);
        $single = true;

        if ($num_args > 0 && ($args[0] === 'all' || $args[0] === 'first' || $args[0] === 'last'))
        {
            switch ($args[0])
            {
                case 'all':
                    $single = false;
                    break;

                case 'last':
                    if (!array_key_exists('order',$options))
                        $options['order'] = join(' DESC, ',static::table()->pk) . ' DESC';
                    else
                        $options['order'] = SQLBuilder::reverse_order($options['order']);

                    // fall thru

                case 'first':
                    $options['limit'] = 1;
                    $options['offset'] = 0;
                    break;
            }

            $args = array_slice($args,1);
            $num_args--;
        }
        //find by pk
        elseif (1 === count($args) && 1 == $num_args)
            $args = $args[0];

        // anything left in $args is a find by pk
        if ($num_args > 0 && !isset($options['conditions']))
            return static::find_by_pk($args, $options);

        $options['mapped_names'] = static::$alias_attribute;
        $list = static::table()->find($options);

        return $single ? (!empty($list) ? $list[0] : null) : $list;
    }

}