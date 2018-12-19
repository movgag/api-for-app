<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPin extends Model
{
    protected $table = 'user_pins';

    public static function add_row($user_id,$pin){
        $obj = new self();
        $obj->user_id = $user_id;
        $obj->pin = $pin;
        $obj->save();

        return $obj;
    }

    public static function delete_row($user_id){

        return self::query()->where('user_id',$user_id)->delete();

    }

    public static function check_pin($user_id,$pin){
        return self::query()
            ->where('user_id',$user_id)
            ->where('pin',$pin)
            ->exists();
    }


}
