<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{

    public function sendEmail($data = array())
    {
        //from register case
        $view = 'mail.register_mail';
        $subject = 'Your Email SMS code';

        try {

            Mail::send($view,['data'=>$data], function ($message) use ($data,$subject){
                $message->from(env('MAIL_USERNAME'), 'Support');
                $message->subject($subject);
                $message->to($data['email']);
            });

            return true;
        } catch (\Exception $e){
            //
        }

        return false;
    }


}
