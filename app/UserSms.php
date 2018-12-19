<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSms extends Model
{
    protected $table = 'user_sms';

    public static function add_row($user_id,$sms_code,$reg_id,$phone_number){
        $obj = new self();
        $obj->user_id = $user_id;
        $obj->sms_code = $sms_code;
        $obj->reg_id = $reg_id;
        $obj->phone_number = $phone_number;
        $obj->save();
        return $obj;
    }

    public static function delete_row($user_id){

        return self::query()->where('user_id',$user_id)->delete();
    }

    public static function get_row($user_id, $sms_code){

        return self::query()->where('user_id',$user_id)
            ->where('sms_code',$sms_code)
            ->first();
    }

    public static function user_verified_phone($user_id){

        return self::query()->where('user_id',$user_id)
            ->where('verify',1)
            ->first();
    }

}
