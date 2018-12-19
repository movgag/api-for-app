<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    protected $table = 'user_rewards';

    public static function add_or_update_row($user_id, $reward_id, $talent_id){
        $result = self::query()->where('user_id',$user_id)->where('reward_id',$reward_id)->first();

        if($result && isset($result->id)){
            $obj = $result;
            $obj->quantity = $result->quantity + 1;

        } else {
            $obj = new self();
            $obj->user_id = $user_id;
            $obj->reward_id = $reward_id;
            $obj->talent_id = $talent_id;
            $obj->quantity = 1;
        }
        $obj->save();

        return $obj;
    }


}
