<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $table = 'rewards';

    public static function show_rewards()
    {
        return self::query()->get();
    }

    public static function get_rewardByID($id){
        return self::query()->find($id);
    }


// relations---------------------start

    public function talent(){
        return $this->belongsTo('App\Talent','talent_id','id');
    }

}
