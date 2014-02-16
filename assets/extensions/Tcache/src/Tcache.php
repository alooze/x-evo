<?php
/*
 * This file is part of the Tcache package.
 *
 * Based on CacheCache code by
 * (c) 2012 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tcache;
use Tcache\Backends\AbstractBackend as Backend;

/**
 * Наследуем базовый класс для внедрения поддержки тегов
 * И 
 */
class Tcache extends Cache
{
    /**
     * @param Backend $backend
     * @param string $namespace
     * @param int $defaultTTL
     * @param int $ttlVariation
     */
    public function __construct($namespace = '', $defaultTTL = null, $ttlVariation = 0)
    {
        // создаем основной кеш
        $options = array('dir'=>MODX_BASE_PATH.'assets/cache/tcache',
                        'sub_dirs'=>false,
                        'id_as_filename'=>true,
                        'file_extension'=>'.cache'
            );

        $backend = new \Tcache\Backends\File($options);
        
        parent::__construct($backend, $namespace, $defaultTTL, $ttlVariation);

        // файловый кеш не поддерживает теги, создаем кеш для тегов
        $options = array('dir'=>MODX_BASE_PATH.'assets/cache/tcache/tags',
                        'sub_dirs'=>false,
                        'id_as_filename'=>true,
                        'file_extension'=>'.tag'
            );

        $this->setTagBackend(new \Tcache\Backends\File($options)); 
    }

    
    /**
     * Выполнение сниппета в контексте modx и сохранение кеша "на лету"
     *
     * Следующий код выполнит сниппет только первый раз, последующие вызовы
     * будут отдавать код из кеша. При этом отслеживается УНИКАЛЬНОСТЬ
     * GET['start'] (для пагинации Ditto) и поля 'parent' у текущего документа
     *
     * <code>
     *      $options = array('parents'=>1, 'tpl'=>'ditto.tpl');
     *      $consider = array('GET'=>'start,ditto_order','document'=>'parent');
     *      $cache->runSnippet('Ditto', $options, $consider);
     * </code>
     *
     *
     * @param string $name Название сниппета
     * @param array $options Параметры вызова сниппета
     * @param array $consider Массив с переменными, которые нужно учитывать при сохранении
     * @return mixed
     */
    public function runSnippet($name, array $options=array(), array $consider=array())
    {
        global $modx;

        // получаем ключ кеша
        $id = serialize($name).serialize($options);

        foreach ($consider as $type => $rules) {
            // в зависимости от указанных для отслеживания переменных, получаем данные для id
            $id.= $this->getIdGivenThe($type, $rules);
        }

        $id = $name.':'.md5($id); // пока искусственно добавляем неймспейс
        
        if (($value = $this->get($id)) === null) {
            //готовим данные для сохранения в кеше

            //получаем все установленные плейсхолдеры ДО вызова сниппета
            if (!is_array($modx->placeholders)) {
                $tmpAr = array();
            } else {
                $tmpAr = $modx->placeholders;
            }

            $value = $modx->runSnippet($name, $options);

            // отделяем плейсхолдеры, которые установил вызванный сниппет
            if (!is_array($modx->placeholders)) {
                $phAr = array();
            } else {
                $phAr = array_diff($modx->placeholders, $tmpAr);
            }

            //дописываем плейсхолдеры в кеш
            $res = serialize($phAr).'~~~SPLITTER~~~'.$value;
            $this->add($id, $res);
            return $value;
        }

        $tmpAr = explode('~~~SPLITTER~~~', $value);
        $modx->toPlaceholders(unserialize($tmpAr[0]));

        return $tmpAr[1];
    }

    /**
     * Возвращает уникальный ID в зависимости от переданных условий
     *
     *
     * @param string $name Вид условия
     * @param mixed $rules Список значений
     * @return string
     */
    public function getIdGivenThe($name, $rules)
    {
        global $modx;

        // принудительно приводим список к массиву
        if (!is_array($rules)) {
            $rules = explode(',', $rules);
        }

        $id = '';

        // используем условия
        switch ($name) {
            case 'GET':
            case 'POST':
            case 'REQUEST':
                foreach ($rules as $rule) {
                    // $varName = '_'.$name;
                    // if (isset($$varName[$rule])) {
                    if (isset($_GET[$rule])) {
                        // $id.= serialize($$varName[$rule]);
                        $id.= serialize($_GET[$rule]);
                    }
                }

                return $id;
                break;

            case 'document':
            case 'resource':
                foreach ($rules as $rule) {
                    if (isset($modx->documentObject[$rule])) {
                        $id.= serialize($modx->documentObject[$rule]);
                    }
                }
                return $id;
                break;
            
            default:
                return '';
                break;
        }
    }
}