<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    public static function add_row($data){
        $obj = new self();
        switch ($data['case']){
            case 'talent' :
                $obj->user_id = auth()->user()->id;
                $obj->talent_id = $data['talent_id'];
            break;
            case 'reward' :
                $obj->user_id = auth()->user()->id;
                $obj->reward_id = $data['reward_id'];
            break;
            case 'transfer' :
                $obj->sender_id = $data['sender_id'];
                $obj->receiver_id = $data['receiver_id'];
                $obj->sender_wallet_address = $data['sender_wallet_address'];
                $obj->partner_wallet_address = $data['partner_wallet_address'];

                $obj->fee = $data['fee'];
            break;

            default:
                return false;
        }
        $obj->case = $data['case'];
        $obj->amount = $data['amount'];
        $obj->status = 'success';
        $obj->comment = $data['comment'];
        $obj->save();

        return true;
    }

    public static function get_user_transaction_history($user_id){

        return self::query()->where('user_id',$user_id)
            ->orWhere('sender_id',$user_id)
            ->orWhere('receiver_id',$user_id)
            ->orderBy('created_at','desc')
            ->get();

    }


}
