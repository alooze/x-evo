<?php
/*
 * This file is part of the Tcache package.
 */

namespace Tcache;

/**
 * Интерфейс для всех Backend классов, для работы с тегами
 */

interface InterfaceTaggedCache
{
    /**
     * Для кеша, не поддерживающего теги, добавляем теги собственными средствами
     */
    public function addTagForId($tag, $id);

    /**
     * Сброс всех тегов
     */
    public function flushAllTags();

    /**
     * Очистка кеша по значению тега
     */
    public function flushByTagValue($tag);

    /**
     * Очистка кеша по типу тегов, внутренний метод
     */
    public function flushByTagTypeValue($tagExpression);
}