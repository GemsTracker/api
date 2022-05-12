<?php

namespace Gems\Rest\Cache;

use Symfony\Contracts\Cache\ItemInterface;

trait Psr6CacheHelpers
{
    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache;

    protected function getCacheItem($key)
    {
        if ($this->cache->hasItem($key)) {
            $item = $this->cache->getItem($key);
            return $item->get();
        }

        return null;
    }

    protected function setCacheItem($key, $value, $tag=null)
    {
        $item = $this->cache->getItem($key);
        if ($tag !== null && $item instanceof ItemInterface) {
            $item->tag($tag);
        }
        $item->set($value);
        $this->cache->save($item);
    }
}
