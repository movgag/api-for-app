<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $table = 'users';

    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public static function create_user($data)
    {
        $user = new self();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = bcrypt($data['password']);
        $user->mail_code = $data['mail_code'];
        $user->one_time_token = $data['one_time_token'];
        $user->status = 2; // user created case
        $user->save();

        return $user;
    }

    public static function get_user($params = array()){

        return self::query()->where($params)->first();
    }



    public function AauthAcessToken(){
        return $this->hasMany('\App\OauthAccessToken');
    }


}
