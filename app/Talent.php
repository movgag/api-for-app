<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    protected $table = 'talents';

    public static function get_talents($params){
        return self::query()->where($params)->get();
    }

    public static function get_talentByID($id){
        return self::query()->find($id);
    }

    public static function get_talentWithRewardsByID($id){
        return self::query()->where('id',$id)->with('rewards')->first();
    }

    public static function search_talent($field,$val){
        return self::query()->where($field,$val)->first();
    }

// relations ---------------------------------------------------start
    public function user_talents(){
        return $this->hasMany('App\UserTalent','talent_id','id');
    }

    public function rewards(){
        return $this->hasMany('App\Reward','talent_id','id');
    }
    
}
