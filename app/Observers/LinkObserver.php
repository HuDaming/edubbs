<?php

namespace App\Observers;

use App\Models\Link;
use Cache;

class LinkObserver
{
    // 保存时清空缓存
    public function saved(Link $link)
    {
        Cache::forget($link->cache_key);
    }
}