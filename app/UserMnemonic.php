<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserMnemonic extends Model
{
    protected $table = 'user_mnemonics';

    public static function add_row($user_id,$words){
        $obj = new self();
        $obj->user_id = $user_id;
        $obj->mnemonic = $words;
        $obj->save();

        return $obj;
    }

    public static function get_mnemonic($user_id){

        return self::query()->where('user_id',$user_id)->first();
    }

    public static function get_mnemonic_row($mnemonic){

        return self::query()->where('mnemonic', '=', $mnemonic)->first();
    }


}
