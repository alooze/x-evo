<?php

namespace CacheCache;
use CacheCache\Backends\File;
use CacheCache\Backends\Tfile;
use CacheCache\CacheManager;

/**
 * Наследуем базовый класс для внедрения поддержки тегов
 */
class Tcache extends Cache
{
    protected $tags;

    /**
     * @param Backend $backend
     * @param string $namespace
     * @param int $defaultTTL
     * @param int $ttlVariation
     */
    // public function __construct(Backend $backend, $namespace = '', $defaultTTL = null, $ttlVariation = 0)
    public function __construct($namespace = '', $defaultTTL = null, $ttlVariation = 0)
    {
        // создаем основной кеш
        $backend = new Tfile();

        // if (!($backend instanceof File)) {
        //     die('Функционал тегирования доступен в этой версии только для кеша в файлах');
        // }
        $this->backend = $backend;
        $this->namespace = $namespace;
        $this->defaultTTL = $defaultTTL;
        $this->ttlVariation = $ttlVariation;

        // создаем кеш для тегов
        $options['dir'] = MODX_BASE_PATH.'assets/cache/tcache/tags';
        $options['file_extension'] = '.tag';
        $options['id_as_filename'] = true;
        
        $_tagCache = new Cache(new Tfile($options));
        CacheManager::set('tagCache', $_tagCache);
    }

    /**
     * Sets the backend of this cache
     * 
     * @param Backend $backend
     */
    public function setBackend(Backend $backend)
    {
        if (!($backend instanceof Tfile)) {
            die('Функционал тегирования доступен в этой версии только для кеша в файлах');
        }
        $this->backend = $backend;
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
            $id.= $this->getIdForConsider($type, $rules);
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
    public function getIdForConsider($name, $rules)
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

    /**
     * Устанавливает теги для ближайшего сохранения данных в кеш
     *
     * Теги могут быть просто строками:
     * $cache->withTags(array('a','b'))->add($key, $value, $ttl);
     * 
     * или объектами классов, расширяющих AbstractCacheTag
     * $cache->withTags(array(UserCacheTag::for($userId), ParentCacheTag::for($parentId)))
     *      ->add($key, $value, $ttl);
     * 
     *
     * @param mixed $tags Список тегов
     * @return string
     */
    public function withTags($tags)
    {
        // принудительно приводим список к массиву
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        foreach ($tags as $tag) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     * ! Метод работает и как set и как add
     */
    public function set($id, $value, $ttl = null)
    {
        $id = $this->id($id);
        $ttl = $this->computeTTL($ttl);

        // сохраняем теги для текущего id
        if (is_array($this->tags)) {
            $_tagCache = CacheManager::get('tagCache'); //задан в конструкторе
            foreach ($this->tags as $tag) {
                $val = $_tagCache->backend->getRaw($tag);
                if ($val === null) {
                    $_tagCache->backend->setRaw($tag, $id."\n");
                } else {
                    $_tagCache->backend->setRaw($tag, $val.$id."\n");
                }                
            }
            //сбрасываем использованные теги
            $this->tags = array();
        }
        return $this->backend->set($id, $value, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function add($id, $value, $ttl = null)
    {
        return $this->set($id, $value, $ttl = null);
    }

    /**
     * {@inheritDoc}
     */
    public function flushAll()
    {
        $_tagCache = CacheManager::get('tagCache');
        $_tagCache->backend->flushAll();
        return $this->backend->flushAll();
    }

    /**
     * Очистка кеша по конкретному тегу
     * Тег может быть либо строкой, либо результатом вызова 
     * чего-то типа UserCacheTag::for($userId)
     */
    public function flushByTag($tag)
    {
        $_tagCache = CacheManager::get('tagCache'); //задан в конструкторе
        $val = $_tagCache->backend->getRaw($tag);
        if ($val === null) {
            // нечего сбрасывать
            return true;
        } else {
            $filesToDel = explode("\n", $val);
            foreach ($filesToDel as $id) {
                $this->delete($id);
            }
            // и очистим сразу сам тег
            return $_tagCache->delete($tag);
        } 
    }

    /**
     * Очистка кеша по типу тегов
     * Тег может быть либо строкой для функции glob, либо результатом вызова 
     * чего-то типа UserCacheTag::all()
     */
    public function flushByTagType($tagExpression)
    {
        $_tagCache = CacheManager::get('tagCache'); //задан в конструкторе
        $tags = $_tagCache->backend->getAll($tagExpression);

        if ($tags) {
            foreach ($tags as $tagPath) {
                $tmpAr = pathinfo($tagPath);
                $tag = $tmpAr['filename'];
                $this->flushByTag($tag);
            }
        } 
    }
}