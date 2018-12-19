<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JetRequest extends Model
{
    protected $table = 'jet_requests';

    public static function add_row($arr){
        $obj = new self();
        $obj->amount = $arr['amount'];
        $obj->fee = $arr['fee'];
        $obj->request_owner_id = $arr['request_owner_id'];
        $obj->request_owner_wallet_address = $arr['request_owner_wallet_address'];
        $obj->partner_id = $arr['partner_id'];
        $obj->partner_wallet_address = $arr['partner_wallet_address'];
        $obj->save();

        return $obj;
    }



}
