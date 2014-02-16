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

/**
 * Backends provide a way to store cached data.
 */
interface InterfaceCache
{
    /**
     * Checks if the $id exists
     *
     * @param string $id
     * @return bool
     */
    function exists($id);

    /**
     * Retreives the value associated to the $id from the cache
     *
     * Must return NULL if the $id does not exists.
     *
     * @param string $id
     * @return mixed
     */
    function get($id);

    /**
     * Retreives multiple values at once
     *
     * An array will be returned, containing the values in the 
     * same order as the $ids.
     *
     * @param array $ids
     * @return array
     */
    function getMulti(array $ids);

    /**
     * Stores a $value in the cache under the specified $id only 
     * if it does not exist already.
     *
     * @param string $id
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     */
    function add($id, $value, $ttl = null);

    /**
     * Stores a $value in the cache under the specified $id.
     * Overwrite any existing $id.
     *
     * @param string $id
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     */
    function set($id, $value, $ttl = null);

    /**
     * Sets multiple $id/$value pairs at once
     *
     * @param array $items
     * @param int $ttl Time to live in seconds
     */
    function setMulti(array $items, $ttl = null);

    /**
     * Deletes an $id from the cache
     *
     * @param string $id
     */
    function delete($id);

    /**
     * Deletes all data from the cache
     */
    function flushAll();

    /**
     * Whether this backend supports tags
     *
     * @return bool
     */
    function supportsTags();

    /**
     * Deletes all data with tag
     *
     * @param string $tag
     */
    function flushByTag($tag);

    /**
     * Deletes all data with tag
     *
     * @param array $tags
     */
    function flushByTagType($tags);

    /**
     * Whether this backend supports pipelines
     *
     * @see Pipeline
     * @return bool
     */
    function supportsPipelines();

    /**
     * Creates a new pipeline
     *
     * Pipelines can be custom classes
     *
     * @return object
     */
    function createPipeline();
}
