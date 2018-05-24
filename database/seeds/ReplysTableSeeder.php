<?php

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Topic;
use App\Models\Reply;

class ReplysTableSeeder extends Seeder
{
    public function run()
    {
        // 所有用户 ID 数组，如：[1,2,3,4]
        $userIds = User::all()->pluck('id')->toArray();

        // 所有话题 ID 数组，如：[1,2,3,4]
        $topicIds = Topic::all()->pluck('id')->toArray();

        // 获取 Faker 实例
        $faker = app(Faker\Generator::class);

        $replys = factory(Reply::class)
                        ->times(1000)
                        ->make()
                        ->each(function ($reply, $index)
                            use ($faker, $userIds, $topicIds)
        {
            // 从用户 ID 数组中随机取出一个并赋值
            $reply->user_id = $faker->randomElement($userIds);

            // 话题 ID 同上
            $reply->topic_id = $faker->randomElement($topicIds);
        });

        // 将数据集合转换为数组，并插入到数据库中
        Reply::insert($replys->toArray());
    }

}

