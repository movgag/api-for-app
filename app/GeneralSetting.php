<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    protected $table = 'general_settings';

    public static function get_setting_value($slug){
        try {
            return self::query()->where('slug',$slug)->first()->val;
        } catch (\Exception $e){
            return false;
        }
    }

}
