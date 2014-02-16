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
 */
class Cache extends AbstractCache implements InterfaceTaggedCache
{
    protected $tags;
    protected $tagBackend;
    protected $tagCache;

    /**
     * @param Backend $backend
     * @param string $namespace
     * @param int $defaultTTL
     * @param int $ttlVariation
     */
    public function __construct(Backend $backend, $namespace = '', $defaultTTL = null, $ttlVariation = 0)
    {
        $this->backend = $backend;
        $this->namespace = $namespace;
        $this->defaultTTL = $defaultTTL;
        $this->ttlVariation = $ttlVariation;

        /**
          * если кеш поддерживает теги, то используем встроенный функционал,
          * иначе будем использовать методы текущего класса
          *
          *  @todo разобраться, нужны ли ссылки для объектов
          */
        if ($this->backend->supportsTags()) {
            $this->tagBackend = $this->backend;
        } else {
            $this->tagBackend = $this;
        }
    }

    /**
     * Sets the  tags backend of this cache
     * 
     * @param Backend $backend
     */
    public function setTagBackend(Backend $backend)
    {
        $this->tagCache = $backend;
    }

    /**
     * Устанавливает теги для _ближайшего_ сохранения данных в кеш
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
     */
    public function add($id, $value, $ttl = null)
    {
        return $this->set($id, $value, $ttl = null);
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
            foreach ($this->tags as $tag) {
                $this->tagBackend->addTagForId($tag, $id);
            }

            //сбрасываем использованные теги
            $this->tags = array();
        }
        return $this->backend->set($id, $value, $ttl);
    }

    /**
     * Для кеша, не поддерживающего теги, добавляем теги собственными средствами
     */
    public function addTagForId($tag, $id)
    {
        if (!($this->tagCache instanceof Backend)) {
            // пока что в этом месте начинаем истерику
            // потом можно будет пересмотреть
            die ('Теги для кеша не настроены: не указан backend');
            // return false;
        }
        $this->appendTag($tag, $id);
    }

    /**
     * Переданное значение приписывается к уже существующему
     * Используется только для кеша, хранящего теги
     */
    public function appendTag($id, $value)
    {
        // преобразуем значение к массиву
        if (!is_array($value)) {
            $value = array($value);
        }

        $currentValue = $this->tagCache->get($id);
        if ($currentValue === null) {
            // ok
        } else if (is_array($currentValue)) {
            $value = array_merge($currentValue, $value);
        } else {
            // something wrong, but...
            $value = array_merge(array($currentValue), $value);
        }
        $this->tagCache->set($id, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function flushAll()
    {
        $this->tagBackend->flushAllTags();
        return $this->backend->flushAll();
    }

    /**
     * Сброс всех тегов
     */
    public function flushAllTags()
    {
        if (!($this->tagCache instanceof Backend)) {
            // пока что в этом месте начинаем истерику
            // потом можно будет пересмотреть
            die ('Теги для кеша не настроены: не указан backend');
            // return false;
        }
        return $this->tagCache->flushAll();
    }

    /**
     * Очистка кеша по конкретному тегу
     * Тег может быть либо строкой, либо результатом вызова 
     * чего-то типа UserCacheTag::for($userId)
     */
    public function flushByTag($tag)
    {
        return $this->tagBackend->flushByTagValue($tag);
    }

    /**
     * Очистка кеша по значению тега
     */
    public function flushByTagValue($tag)
    {
        if (!($this->tagCache instanceof Backend)) {
            // пока что в этом месте начинаем истерику
            // потом можно будет пересмотреть
            die ('Теги для кеша не настроены: не указан backend');
            // return false;
        }
        $tagRelations = $this->tagCache->get($tag);
        if ($tagRelations === null) {
            // nothing to do
            return true;
        } else {
            foreach ($tagRelations as $cacheId) {
                $this->delete($cacheId);
            }
            return $this->tagCache->delete($tag);
        }
    }

    /**
     * Очистка кеша по типу тегов
     * Тег может быть либо строкой для функции glob, либо результатом вызова 
     * чего-то типа UserCacheTag::all()
     */
    public function flushByTagType($tagExpression)
    {
        return $this->tagBackend->flushByTagTypeValue($tagExpression);
    }

    /**
     * Очистка кеша по типу тегов, внутренний метод
     */
    public function flushByTagTypeValue($tagExpression)
    {
        if (!($this->tagCache instanceof Backend)) {
            // пока что в этом месте начинаем истерику
            // потом можно будет пересмотреть
            die ('Теги для кеша не настроены: не указан backend');
            // return false;
        }

        $tags = $this->tagCache->getAll($tagExpression);
        if ($tags) {
            foreach ($tags as $tagPath) {
                $tmpAr = pathinfo($tagPath);
                $tag = $tmpAr['filename'];
                $this->flushByTag($tag);
            }
        }
    }
}