<?php

namespace App\Models\Traits;

use App\Models\Topic;
use App\Models\Reply;
use App\Models\User;
use Carbon\Carbon;
use Cache;
use DB;

trait ActiveUserHelper
{
    // 用于存放临时用户数据
    protected $users = [];

    // 配置信息
    protected $topic_weight = 4;    // 话题权重
    protected $reply_weight = 1;    // 回复权重
    protected $pass_day = 7;        // 多少天内发布过内容
    protected $user_member = 6;     // 取出多少用户

    // 缓存相关配置
    protected $cache_key = 'edubbs_active_users';
    protected $cache_expire_in_minutes = 65;

    public function getActiveUsers()
    {
        // 尝试从缓存中取出 cache_key 对应的数据。如果能取到，便直接返回数据。
        // 否则运行匿名函数中的代码来取出活跃用户数据，返回的同时做了缓存。
        return Cache::remember($this->cache_key, $this->cache_expire_in_minutes, function () {
            return $this->calculateActiveUsers();
        });
    }

    public function calculateAndCacheActiveUsers()
    {
        // 取得活跃用户
        $active_users = $this->calculateActiveUsers();
        // 缓存活跃用户
        $this->calculateActiveUsers($active_users);
    }

    private function calculateActiveUsers()
    {
        $this->calculateTopicScore();
        $this->calculateReplyScore();

        // 数组按照积分排序
        $users = array_sort($this->users, function ($user) {
            return $user['score'];
        });

        // 按照积分大小，倒序排序，第二个参数为保持数组的 KEY 不变
        $users = array_reverse($users, true);

        // 只获取我们想要的数量
        $users = array_slice($users, 0, $this->user_member, true);

        // 新建一个空集合
        $active_users = collect();

        foreach ($users as $user_id => $user) {
            // 查询是否存在这个用户
            $user = User::find($user_id);

            // 如果用户存在
            if ($user) {
                // 将用户实体放入集合结尾
                $active_users->push($user);
            }
        }

        // 返回数据
        return $active_users;
    }

    /**
     * Calculate points for users who have posted a topic within a specified time
     */
    private function calculateTopicScore()
    {
        // 从话题表里取出限定时间范围（$pass_days）内，发表过话题的用户，并同时取出用此段时间内发布话题的数量
        $topic_users = Topic::query()->select(DB::raw('user_id, count(*) as topic_count'))
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_day))
            ->groupBy('user_id')
            ->get();

        // 根据话题数量计算积分
        foreach ($topic_users as $user) {
            $this->users[$user->user_id]['score'] = $user->topic_count * $this->topic_weight;
        }
    }

    /**
     * Calculate points for users who have posted replies within a specified time
     */
    private function calculateReplyScore()
    {
        // 从回复数据表里取出限定时间范围内，发表过回复的用户，并同时取出此段时间内发布回复的数量
        $reply_users = Reply::query()->select(DB::raw('user_id, count(*) as reply_count'))
            ->where('created_at', '>=', Carbon::now()->subDays($this->pass_day))
            ->groupBy('user_id')
            ->get();

        // 根据回复数量计算得分
        foreach ($reply_users as $user) {
            $reply_score = $user->reply_count * $this->reply_weight;
            if (isset($this->users[$user->user_id])) {
                $this->users[$user->user_id]['score'] += $reply_score;
            } else {
                $this->users[$user->user_id]['score'] = $reply_score;
            }
        }
    }

    /**
     * Put the data into the cache
     *
     * @param $active_users
     */
    private function cacheActiveUsers($active_users)
    {
        Cache::put($this->cache_key, $active_users, $this->cache_expire_in_minutes);
    }
}