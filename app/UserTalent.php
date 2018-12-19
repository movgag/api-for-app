<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserTalent extends Model
{
    protected $table = 'user_talents';


    public static function add_or_update_row($user_id,$talent_id,$amount){

        $result = self::query()->where('user_id',$user_id)->where('talent_id',$talent_id)->first();
        if($result && isset($result->id)){
            $obj = $result;
            $obj->amount = $result->amount + $amount;

        } else {
            $obj = new self();
            $obj->user_id = $user_id;
            $obj->talent_id = $talent_id;
            $obj->amount = $amount;
        }
        $obj->save();

        return $obj;
    }

    public static function get_user_talentsByUserID($user_id){

        return self::query()->where('user_id',$user_id)->with('talent')->get();
    }


// relations ---------------------------------------------------start
    public function talent(){
        return $this->belongsTo('App\Talent','talent_id','id');
    }

}
