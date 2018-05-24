<?php

namespace App\Models;

class Topic extends Model
{
    protected $fillable = ['title', 'body', 'user_id', 'category_id', 'reply_count', 'view_count', 'last_reply_user_id', 'order', 'excerpt', 'slug'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 不同的排序，使用不同的数据读取逻辑
     *
     * @param $query
     * @param $order
     * @return mixed
     */
    public function scopeWithOrder($query, $order)
    {
        switch ($order) {
            case 'recent':
                $query->recent();
                break;

            default:
                $query->recentReplied();
                break;
        }

        // 预加载防止 N+1 问题
        return $query->with('user', 'category');
    }

    /**
     * sort by updated_at
     *
     * @param $query
     * @return mixed
     */
    public function scopeRecentReplied($query)
    {
        // 当话题有新回复时，模型的 reply_count 属性将会更新，此时会自动触发框架对数据模型 updated_at 时间戳的更新
        return $query->orderBy('updated_at', 'desc');
    }

    /**
     * sort by created_at
     *
     * @param $query
     * @return mixed
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
