<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Controllers\MailController;
use App\User;
use App\UserPin;
use App\UserMnemonic;
use App\UserSms;
use App\Talent;
use App\UserTalent;
use App\Reward;
use App\UserReward;
use App\Transaction;
use App\GeneralSetting;
use App\JetRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Validator;

use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    public $successStatus = 200;
    public $dangerStatus = 400;
    public $validFailedStatus = 422;


    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(){
        $rules = [
            'password' => 'required',
            'email' => 'required|email',
        ];
        $validator = Validator::make(['email'=>request('email'),'password' => request('password')], $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json([
                'type'=>'error',
                'message'=>$message],
                $this->validFailedStatus);
        }

        $params = array('email'=>request('email'));
        $user = User::get_user($params);

        if($user && isset($user->id)){

            if($user->status != 1){
                return \Response::json(['type'=>'error',
                    'message' => 'You have not finished registration process',
                ], $this->dangerStatus);
            }

            $hasher = app('hash');
            if ($hasher->check(request('password'), $user->password)) {

                try {
                    DB::beginTransaction();

                    $user->one_time_token = str_random(18);
                    $user->update();

                    UserPin::delete_row($user->id);

                    DB::commit();

                    return response()->json(['type'=>'success',
                        'message'=>'True email and and true password',
                        'data' => [
                            'userID' =>$user->id,
                            'email' =>$user->email,
                            'user_wallet' => $user->wallet_address,
                            'one_time_token' => $user->one_time_token
                        ],
                    ], $this->successStatus);

                } catch (\Exception $e){

                    DB::rollBack();

                    return \Response::json(['type'=>'error',
                        'message' => 'Login process is failed',
                    ], $this->dangerStatus);
                }

            } else {
                return \Response::json(['type'=>'error',
                                        'message' => 'Email or Password is invalid',
                        ], $this->dangerStatus);
            }

        } else {

            return \Response::json(['type'=>'error',
                                    'message' => 'User with current email address does not exists',
                        ], $this->dangerStatus);
        }
    }  //+++

    public function applyPin(Request $request)
    {
        $rules = [
            'one_time_token' => 'required|size:18',
            'pin_code' => 'required|digits:4',
            'email' => 'required|email',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json([
                'type'=>'error',
                'message'=>$message
            ], $this->validFailedStatus);
        }

        $pin_code = $request->input('pin_code');
        $one_time_token = $request->input('one_time_token');
        $user_email = $request->input('email');

        $params = array('email'=>$user_email, 'one_time_token' => $one_time_token);

        $user = User::get_user($params);

        if($user && isset($user->id)){
            //Auth::attempt(['email' => request('email'), 'password' => request('password')])

            if($user->status != 1 && $user->status != 4){
                return \Response::json(['type'=>'error',
                    'message' => 'You have not finished registration process',
                ], $this->dangerStatus);
            }

            try {

                DB::beginTransaction();

                UserPin::delete_row($user->id);
                UserPin::add_row($user->id,$pin_code);

                // the case of last step of registration
                if($user->status == 4){
                    $user->wallet = 10000; //temporary
                    $user->wallet_address = str_random(12); //temporary
                    $user->status = 1;
                    $user->update();
                }

                DB::table('oauth_access_tokens')->where('user_id',$user->id)->delete();

                Auth::loginUsingId($user->id);

                $user = Auth::user();
                $success['token'] =  $user->createToken('MyApp')->accessToken;

                DB::commit();

                // phone state
                $user_sms_row = UserSms::user_verified_phone($user->id);

                $phone_state = 0;
                if($user_sms_row && isset($user_sms_row->id)){
                    $phone_state = 1;
                }

                //mnemonic state
                $user_mnemonic_row = UserMnemonic::get_mnemonic($user->id);

                $mnemonic_state = 0;
                if($user_mnemonic_row && isset($user_mnemonic_row->id)){
                    $mnemonic_state = 1;
                }

                return response()->json([
                    'success' => $success,
                    'data'=> [
                        'userID' => $user->id,
                        'name' => $user->name,
                        'user_wallet' => $user->wallet_address,
                        'phone_state' => $phone_state,
                        'mnemonic_state' => $mnemonic_state,
                    ]
                ], $this->successStatus);

            } catch (\Exception $e){

                DB::rollBack();

                return response()->json(['type'=>'error',
                    'message' => $e->getMessage(),
                ], 401);
            }

        } else {
            return \Response::json(['type'=>'error',
                'message' => 'User not finded',
            ], $this->dangerStatus);
        }
    } //+++

    public function checkPin(Request $request)
    {
        if (Auth::check()) {
            $rules = [
                'pin_code' => 'required|digits:4',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type'=>'error',
                    'message'=>$message
                ], $this->validFailedStatus);
            }

            $user = Auth::user();

            $res = UserPin::check_pin($user->id,$request->input('pin_code'));

            if($res){
                return response()->json(['type'=>'success',
                    'message'=>'True Pin Code',
                    'data' => [
                        'userID' => $user->id,
                        'email' =>$user->email
                    ],
                ], $this->successStatus);
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Wrong Pin Code'
                ], $this->dangerStatus);
            }
        }
    } //+++

    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
// register routes -------------------------------------------start
    public function register(Request $request)
    {
        $temp_array = array('email'=>$request->input('email'));

        $check_user = User::get_user($temp_array);

        if($check_user && isset($check_user->id)){

            if($check_user->status != 1 ){
                $check_user->delete();
            } else {
                return \Response::json([
                    'type' => 'error',
                    'message' =>'This email is already taken!',
                ],$this->validFailedStatus);
            }
        }
        $rules = [
            'password' => 'required|min:6|max:40',
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
            //'c_password' => 'required|same:password|min:6|max:40',
        ];
        $validator = Validator::make($request->all(), $rules);


        if ($validator->fails()) {

            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }

            return response()->json([
                'type'=>'error',
                'message'=>$message
                ],
                $this->validFailedStatus);
        }

        $input = $request->all();
        $input['mail_code'] = str_random(6);
        $input['one_time_token'] = str_random(18);

        $user = User::create_user($input);

        if($user && isset($user->id)){
            $arr = array();
            $arr['name'] = $user->name;
            $arr['email'] = $user->email;
            $arr['mail_code'] = $user->mail_code;

            $obj = new MailController();
            $result = $obj->sendEmail($arr);

            if($result) {

                return response()->json(['type'=>'success',
                    'message'=>'User created and email is sent to user',
                    'data' => [
                        'email' => $user->email,
                        'one_time_token' => $user->one_time_token
                    ]
                ], $this->successStatus);

            } else {

                return \Response::json(['type'=>'error',
                    'message' => 'User created but email is not sent to user'
                ], $this->dangerStatus);
            }
        } else {

            return \Response::json(['type'=>'error',
                'message' => 'User is not created'
            ], $this->dangerStatus);
        }

        // $input['password'] = bcrypt($input['password']);

        // $user = User::create($input);

        //$success['token'] =  $user->createToken('MyApp')->accessToken;
        //$success['name'] =  $user->name;
    }

    public function resendMailCode(Request $request)
    {
        $rules = [
            'one_time_token' => 'required',
            'email' => 'required|email',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json([
                'type'=>'error',
                'message'=>$message
            ], $this->validFailedStatus);
        }

        $params = array('email'=>$request->input('email'),'one_time_token'=>$request->input('one_time_token'),'status'=>2);

        $user = User::get_user($params);

        if($user && isset($user->id)){

            $user->one_time_token = str_random(18);
            $user->update();

            $arr = array();
            $arr['name'] = $user->name;
            $arr['email'] = $user->email;
            $arr['mail_code'] = $user->mail_code;

            $obj = new MailController();
            $result = $obj->sendEmail($arr);

            if($result){
                return response()->json(['type'=>'success',
                    'message'=>'Mail code is sent to user again',
                    'data' => [
                        'email' => $user->email,
                        'one_time_token' => $user->one_time_token
                    ]
                ], $this->successStatus);
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Resend mail code is failed',
                ], $this->dangerStatus);
            }

        } else {
            return \Response::json(['type'=>'error',
                'message' => 'User not found!',
            ], $this->dangerStatus);
        }
    }

    public function verifyMailCode(Request $request)
    {
        $rules = [
            'one_time_token' => 'required',
            'mail_code' => 'required|size:6',
            'email' => 'required|email',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
        }
        $params = array('email' => $request->input('email'),'mail_code'=>$request->input('mail_code'),'one_time_token'=>$request->input('one_time_token'),'status'=>2);

        $user = User::get_user($params);

        if($user && isset($user->id)){

            if (!Storage::exists('public/qrcodes')) {
                Storage::makeDirectory('public/qrcodes', 0777, true);
                Storage::put('public/qrcodes/index.php', '<?php echo "404"; ?>');
            }

            $qr_text = str_random(8);

            $qr_img_name_with_format = $qr_text.str_random(6).'.png';

            try {
                \SimpleSoftwareIO\QrCode\Facades\QrCode::encoding('UTF-8')->format('png')->size(300)->generate($qr_text,storage_path('app/public/').'qrcodes/'.$qr_img_name_with_format);

            } catch (\Exception $e){
                return \Response::json(['type'=>'error',
                    'message' => 'Qr code generation process is failed!',
                ], $this->dangerStatus);
            }

            try {
                $user->qr_code = $qr_text;
                $user->qr_path = 'qrcodes/'.$qr_img_name_with_format;
                $user->status = 3; // mail code verified case
                $user->one_time_token = str_random(18);
                $user->update();

                return response()->json(['type'=>'success',
                    'message'=>'Mail Code verification is passed and qr code is generated',
                    'data' => [
                        'userID'=> $user->id,
                        'email' => $user->email,
                        'one_time_token' => $user->one_time_token,
                        'qr_path'=> asset('storage/'.$user->qr_path),
                        'qr_code' => $user->qr_code
                    ],
                ], $this->successStatus);
            } catch (\Exception $e1){

                return \Response::json(['type'=>'error',
                    'message' => 'User mail code verification is failed',
                ], $this->dangerStatus);
            }
        } else {

            return \Response::json(['type'=>'error',
                'message' => 'Wrong mail code!',
            ], $this->dangerStatus);
        }
    }

    public function verifyQrCode(Request $request)
    {
        $rules = [
            'one_time_token' => 'required',
            'qr_code' => 'required|size:8',
            'email' => 'required|email',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
        }

        $params = array('email'=>$request->input('email'),'qr_code'=>$request->input('qr_code'),'one_time_token'=>$request->input('one_time_token'),'status'=>3);

        $user = User::get_user($params);

        if($user && isset($user->id)){

            try {
                $user->status = 4; // qr code verified case
                $user->one_time_token = str_random(18);
                $user->update();

                return response()->json(['type'=>'success',
                    'message'=>'User QR code verification is passed',
                    'data' => [
                        'user_wallet' => $user->wallet_address,
                        'one_time_token' => $user->one_time_token,
                        //'qr_code' => $user->qr_code
                    ],
                ], $this->successStatus);

            } catch (\Exception $e){

                return \Response::json(['type'=>'error',
                    'message' => 'User QR code verification is failed',
                ], $this->dangerStatus);
            }

        } else {

            return \Response::json(['type'=>'error',
                'message' => 'User not found',
            ], $this->dangerStatus);
        }


    }

// register routes -------------------------------------------end

// mnemonic routes -------------------------------------------start
    public function setMnemonic(Request $request)
    {
        if (Auth::check()) {
            $rules = [
                'security_word' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
            }

            $security_word = $request->input('security_word');
            $security_word_array = explode(',',$security_word);

            if(is_array($security_word_array) && count($security_word_array) == 12){

                $user = Auth::user();
                $res = UserMnemonic::where('user_id',$user->id)->exists();
                if(!$res){
                    try {
                        UserMnemonic::add_row($user->id,$security_word);

                        return response()->json(['type'=>'success',
                            'message'=>'Mnemonic phrase is set successfuly',
                            'data' => [
                                'userID' =>$user->id,
                                'email' =>$user->email,
                            ],
                        ], $this->successStatus);

                    } catch (\Exception $e){

                        return \Response::json(['type'=>'error',
                            'message' => 'Mnemonic phrase is not set',
                        ], $this->dangerStatus);
                    }
                } else {
                    return \Response::json(['type'=>'error',
                        'message' => 'Current user has already set mnemonic phrase',
                    ], $this->dangerStatus);
                }
            }
        }
    }  

    /*public function checkMnemonic(Request $request)
    {
        if (Auth::check()) {

            $user = Auth::user();

            $words_arr = $request->json()->all();

            $result = UserMnemonic::get_mnemonic($user->id);
            $user_mnemonic_string = $result->mnemonic;

            $mnemonic = explode(' ',$user_mnemonic_string);  // 0 - 11 keys

            if(is_array($words_arr) && $words_arr && is_array($mnemonic) && $mnemonic){

                foreach ($words_arr as $key => $item){

                    //$key = array_search($item,$words_arr);

                    if(!isset($mnemonic[$key]) || $mnemonic[$key] != $item ){

                        return \Response::json(['type'=>'error',
                            'message' => 'Failed mnemonic verification',
                            'data' => [
                                'email' => $user->email
                            ]
                        ], $this->dangerStatus);

                    }
                }

                return response()->json(['type'=>'success',
                    'message'=>'Passed mnemonic verification',
                    'data' => [
                        'userID' => $user->id,
                        'user_wallet' => $user->wallet_address,
                        'one_time_token' => $user->one_time_token,
                        'email' => $user->email,
                    ]
                ], $this->successStatus);

            } else {

                return \Response::json(['type'=>'error',
                    'message' => 'Something went wrong with checking mnemonic',
                    'data' => [
                        'email' => $user->email
                    ]
                ], $this->dangerStatus);
            }
        }
    }*/ // unnecessary, should be done by app developer
// mnemonic routes -------------------------------------------end


// recover account routes -------------------------------------------start
    public function recoverAccount(Request $request){    //checkMnemonic

        $rules = [
            'words' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
        }

        $words = $request->input('words');
        $words_array = explode(',',$words);

        if(is_array($words_array) && count($words_array) == 12) {

            $mnemonic_result = UserMnemonic::get_mnemonic_row($words);

            if ($mnemonic_result && isset($mnemonic_result->user_id)){

                $user = User::find($mnemonic_result->user_id);

                if($user && isset($user->id)){
                    try{
                        if(!$user->one_time_token){
                            $user->one_time_token = str_random(18);
                            $user->update();
                        }
                        return response()->json(['type'=>'success',
                            'message'=>'User is found',
                            'data' => [
                                'userID' =>$user->id,
                                'user_wallet' =>$user->wallet_address,
                                'email' =>$user->email,
                                'one_time_token' => $user->one_time_token
                            ],
                        ], $this->successStatus);

                    } catch (\Exception $e){
                        return \Response::json(['type'=>'error',
                            'message' => 'User mnemonic checking is failed',
                        ], $this->dangerStatus);
                    }
                } else {
                    return \Response::json(['type'=>'error',
                        'message' => 'User with this mnemonic have not been found',
                    ], $this->dangerStatus);
                }
            } else{
                return \Response::json(['type'=>'error',
                    'message' => 'Wrong mnemonic phrase',
                ], $this->dangerStatus);
            }
        } else{
            return \Response::json(['type'=>'error',
                'message' => 'Mnemonic phrase should contain 12 worlds',
            ], $this->dangerStatus);
        }
    } // checkMnemonic for recover //+++

    public function recoverAccountForm(Request $request){ //updateEmail

        $rules = [
            'one_time_token' => 'required',
            'password' => 'required|min:6|max:40',
            'email' => 'required|email|unique:users,email',
            //'c_password' => 'required|same:password|min:6|max:40',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
        }

        $input = $request->all();

        $arr = array('one_time_token'=>$input['one_time_token']);
        $user = User::get_user($arr);

        if($user && isset($user->id)){

            $user_email = $user->email;
            try {
                $user->email = $input['email'];
                $user->password = bcrypt($input['password']);
                $user->status = 2 ;
                if(!$user->one_time_token){
                   $user->one_time_token = str_random(18);
                }
                $user->mail_code = str_random(6);
                $user->update();


                $arr = array();
                $arr['name'] = $user->name;
                $arr['email'] = $user->email;
                $arr['mail_code'] = $user->mail_code;

                $obj = new MailController();
                $result = $obj->sendEmail($arr);

                if($result) {

                    return response()->json(['type'=>'success',
                        'message'=>'Email is sent to user',
                        'data' => [
                            'userID' => $user->id,
                            'email' => $user->email,
                            'user_wallet' => $user->wallet_address,
                            'one_time_token' => $user->one_time_token
                        ]
                    ], $this->successStatus);

                } else {

                    return \Response::json(['type'=>'error',
                        'message' => 'Email is not sent to user',
                    ], $this->dangerStatus);
                }

            } catch (\Exception $e){
                return \Response::json(['type'=>'error',
                    'message' => 'Seting new data is failed',
                ], $this->dangerStatus);
            }
        } else {
            return \Response::json(['type'=>'error',
                'message' => 'User is not found',
            ], $this->dangerStatus);
        }
    } //+++

    public function verifyEmailRecovery(Request $request){

        $rules = [
            'one_time_token' => 'required',
            'mail_code' => 'required|size:6',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            foreach($rules as $key => $val){
                if(isset($validator->errors()->toArray()[$key])){
                    $message = $validator->errors()->toArray()[$key][0];
                }
            }
            return \Response::json(['type'=>'error','message'=>$message], $this->validFailedStatus);
        }

        $arr = array('one_time_token'=>$request->input('one_time_token'), 'mail_code'=> $request->input('mail_code'));

        $user = User::get_user($arr);

        if ($user && isset($user->id)){

            $user_email = $user->email;
            try {
                if(!$user->one_time_token){
                    $user->one_time_token = str_random(18);
                }
                $user->status = 4; // mail_code verified and qr code has already verified case
                $user->update();

                return response()->json(['type'=>'success',
                    'message'=>'Email is verified',
                    'data' => [
                        'userID' => $user->id,
                        'email' => $user->email,
                        'user_wallet' => $user->wallet_address,
                        'one_time_token' => $user->one_time_token
                    ]
                ], $this->successStatus);

            } catch (\Exception $e){

                return \Response::json(['type'=>'error',
                    'message' => 'Email verification failed',
                ], $this->dangerStatus);
            }

        } else {
            return \Response::json(['type'=>'error',
                'message' => 'User is not found',
            ], $this->dangerStatus);
        }


    } //+++
// recover account routes ----------------------------------------------end

// sms routes -------------------------------------------start
    public function sendSms(Request $request)
    {
        // application developer should get registration id and send it to sendSms endpoint with parameter reg_id

        if(Auth::check()){

            $rules = [
                'reg_id' => 'required',
                'phone_number' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            //$user = User::find(Auth::user()->id);
            $user = Auth::user();

            if($user && isset($user->id)){

                $user_email = $user->email;

                #API access key from Google API's Console

                $API_ACCESS_KEY = 'AAAAvTWtI0E:APA91bFVvwMD3lmGBySghHZgsDxLmKT4J44EwkodTJUNf9-mh4kKuYEfLd62hCuWVp7dAhO7loQR9Fvz7QCn0EB7lvkGFQzGK80313-bPNeX5aDC1cjdLVrZX43qVdfgSUd7PD8C-fbD';

                //$registrationIds = $_GET['id'];
                $registrationIds = $request->input('reg_id');
                #prep the bundle
                $sms_code = str_random(6);
                $msg = array
                (
                    'body' 	=> $sms_code,
                    'title'	=> 'Your sms code from JET',
                    'icon'	=> 'myicon',/*Default Icon*/
                    'sound' => 'mySound'/*Default sound*/
                );
                $fields = array
                (
                    'to'		=> $registrationIds,
                    'notification'	=> $msg
                );


                $headers = array
                (
                    'Authorization: key='.$API_ACCESS_KEY,
                    'Content-Type: application/json'
                );
                #Send Reponse To FireBase Server
                try{

                    $ch = curl_init();
                    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
                    curl_setopt( $ch,CURLOPT_POST, true );
                    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
                    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
                    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
                    $result = curl_exec($ch );
                    curl_close( $ch );

                    $arr_result = json_decode($result,true);

                    if($arr_result['failure'] == 1){
                        return \Response::json(['type'=>'error',
                            'message' => 'Sms sending failed',
                            'data' => array(
                                'email' => $user_email,
                                'success' => $arr_result['success'],
                                'failure' => $arr_result['failure'],
                                'error' => $arr_result['results'][0]['error'],
                                'multicast_id' => $arr_result['multicast_id'],
                                'canonical_ids' => $arr_result['canonical_ids'],
                            )
                        ], $this->dangerStatus);
                    }

                    DB::beginTransaction();

                    UserSms::delete_row($user->id);
                    UserSms::add_row($user->id, $sms_code,$registrationIds,$request->input('phone_number'));

                    DB::commit();

                    return response()->json(['type'=>'success',
                        'message'=>'SMS code is successfuly sent to user',
                        'data' => [
                            'userID' =>$user->id,
                            'email' =>$user->email,
                        ],
                    ], $this->successStatus);

                } catch (\Exception $e){

                    DB::rollBack();

                    return \Response::json(['type'=>'error',
                        'message' => $e->getMessage(),
                    ], $this->dangerStatus);
                    //var_dump($e->getMessage());die;
                }

            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'User not found',
                ], $this->dangerStatus);
            }
        }
    }

    public function reSendSms(Request $request)
    {
        // resend sms (the same as sendSms)

        if(Auth::check()){

            $rules = [
                'reg_id' => 'required',
                'phone_number' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            //$user = User::find(Auth::user()->id);
            $user = Auth::user();

            if($user && isset($user->id)){

                $user_email = $user->email;

                #API access key from Google API's Console

                $API_ACCESS_KEY = 'AAAAvTWtI0E:APA91bFVvwMD3lmGBySghHZgsDxLmKT4J44EwkodTJUNf9-mh4kKuYEfLd62hCuWVp7dAhO7loQR9Fvz7QCn0EB7lvkGFQzGK80313-bPNeX5aDC1cjdLVrZX43qVdfgSUd7PD8C-fbD';

                //$registrationIds = $_GET['id'];
                $registrationIds = $request->input('reg_id');
                #prep the bundle
                $sms_code = str_random(6);
                $msg = array
                (
                    'body' 	=> $sms_code,
                    'title'	=> 'Your sms code from JET',
                    'icon'	=> 'myicon',/*Default Icon*/
                    'sound' => 'mySound'/*Default sound*/
                );
                $fields = array
                (
                    'to'		=> $registrationIds,
                    'notification'	=> $msg
                );


                $headers = array
                (
                    'Authorization: key='.$API_ACCESS_KEY,
                    'Content-Type: application/json'
                );
                #Send Reponse To FireBase Server
                try{

                    $ch = curl_init();
                    curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
                    curl_setopt( $ch,CURLOPT_POST, true );
                    curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
                    curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
                    curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
                    curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
                    $result = curl_exec($ch );
                    curl_close( $ch );

                    $arr_result = json_decode($result,true);

                    if($arr_result['failure'] == 1){
                        return \Response::json(['type'=>'error',
                            'message' => 'Sms sending failed',
                            'data' => array(
                                'email' => $user_email,
                                'success' => $arr_result['success'],
                                'failure' => $arr_result['failure'],
                                'error' => $arr_result['results'][0]['error'],
                                'multicast_id' => $arr_result['multicast_id'],
                                'canonical_ids' => $arr_result['canonical_ids'],
                            )
                        ], $this->dangerStatus);
                    }

                    DB::beginTransaction();

                    UserSms::delete_row($user->id);
                    UserSms::add_row($user->id, $sms_code,$registrationIds,$request->input('phone_number'));

                    DB::commit();

                    return response()->json(['type'=>'success',
                        'message'=>'SMS code is successfuly sent to user',
                        'data' => [
                            'userID' =>$user->id,
                            'email' =>$user->email,
                        ],
                    ], $this->successStatus);

                } catch (\Exception $e){

                    DB::rollBack();

                    return \Response::json(['type'=>'error',
                        'message' => $e->getMessage(),
                    ], $this->dangerStatus);
                    //var_dump($e->getMessage());die;
                }

            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'User not found',
                ], $this->dangerStatus);
            }
        }
    }

    public function checkSms(Request $request)
    {
        if(Auth::check()) {

            $rules = [
                'sms_code' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $result = UserSms::get_row(Auth::user()->id,$request->input('sms_code'));

            if($result && isset($result->id)){

                try {
                    $result->verify = 1;
                    $result->update();

                    return response()->json(['type'=>'success',
                        'message'=>'User phone number is successfuly verified',
                        'data' => [
                            'userID' =>Auth::user()->id,
                            'email' =>Auth::user()->email,
                        ],
                    ], $this->successStatus);

                } catch (\Exception $e){
                    return \Response::json(['type'=>'error',
                        'message' => 'Phone number verification failed',
                    ], $this->dangerStatus);
                }

            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Wrong sms code',
                ], $this->dangerStatus);
            }
        }
    }
// sms routes -------------------------------------------end


// jet routes -------------------------------------------start
    public function getUserJetBalance(Request $request)
    {
        if(Auth::check()){

            $user = Auth::user();

            return response()->json(['type'=>'success',
                'message' => 'Current user jet balance',
                'data' => array(
                    'userID' => $user->id,
                    'jet_balance' => $user->wallet,
                )
            ], $this->successStatus);
        }
    } //+++

    public function getJetValue (Request $request)
    {
        if(Auth::check()){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_URL => 'https://api.coinmarketcap.com/v1/ticker/jetcoin/',
            ));
            $resp = curl_exec($curl);
            $result = json_decode($resp,true);
            curl_close($curl);

            return response()->json([
                'type'=>'success',
                'message'=>'Current Jet value in USD',
                'data'=>$result
            ]);
        }
    }  //+++

    public function sendJet(Request $request)
    {
        if(Auth::check()){
            $rules = [
                'amount' => 'required',
                'partner_wallet' => 'required',
                'user_wallet' => 'required',
                //'fee' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $amount = $request->input('amount');
            if(Auth::user()->wallet < $amount){
                return \Response::json(['type'=>'error',
                    'message' => 'Insufficient funds',
                ], $this->dangerStatus);
            }

            $params1 = array('wallet_address' => $request->input('partner_wallet'));
            $params2 = array('wallet_address' => $request->input('user_wallet'), 'id' => Auth::user()->id);

            $partner = User::get_user($params1);
            $user = User::get_user($params2);

            if($partner && isset($partner->id) && $user && isset($user->id)){
                DB::beginTransaction();
                try {
                    $user->wallet = $user->wallet - $amount;
                    $user->update();

                    $partner->wallet = $partner->wallet + $amount;
                    $partner->update();

                    $transaction_fee = GeneralSetting::get_setting_value('transaction_fee');
                    if(!$transaction_fee){
                        $transaction_fee = 0;
                    }

                    // saving history about transaction
                    $arr = array();
                    $arr['case'] = 'transfer';
                    $arr['amount'] = $request->input('amount');
                    $arr['fee'] = $transaction_fee;
                    $arr['comment'] = 'Send Jet';
                    $arr['sender_id'] = $user->id;
                    $arr['receiver_id'] = $partner->id;
                    $arr['sender_wallet_address'] = $user->wallet_address;
                    $arr['partner_wallet_address'] = $partner->wallet_address;

                    $transfer_history = Transaction::add_row($arr);

                    if(!$transfer_history){
                        DB::rollBack();
                        return \Response::json(['type'=>'error',
                            'message' => 'Transfer of Jet failed',
                        ], $this->dangerStatus);
                    }

                    DB::commit();

                    return response()->json(['type'=>'success',
                        'message'=>'Jet is sent successfuly',
                        'data' => [
                            'userID' => $user->id,
                            'jet_balance' => $user->wallet,
                            'sent_balance' => $amount,
                            'transaction_fee' => $transaction_fee,
                        ],
                    ], $this->successStatus);
                    //
                } catch (\Exception $e){
                    DB::rollBack();
                    return \Response::json(['type'=>'error',
                        'message' => 'Transfer of Jet failed',
                    ], $this->dangerStatus);
                }
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Wrong values for parameters',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function requestJet(Request $request)
    {
        if(Auth::check()){
            $rules = [
                'amount' => 'required',
                'partner_wallet' => 'required',
                'user_wallet' => 'required',
                //'fee' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $amount = $request->input('amount');

            $params1 = array('wallet_address' => $request->input('partner_wallet'));
            $params2 = array('wallet_address' => $request->input('user_wallet'), 'id' => Auth::user()->id);

            $partner = User::get_user($params1);
            $user = User::get_user($params2);

            if($partner && isset($partner->id) && $user && isset($user->id)){

                try {
                    $transaction_fee = GeneralSetting::get_setting_value('transaction_fee');
                    if(!$transaction_fee){
                        $transaction_fee = 0;
                    }
                    $arr = array();
                    $arr['amount'] = $amount;
                    $arr['fee'] = $transaction_fee;
                    $arr['request_owner_id'] = $user->id;
                    $arr['request_owner_wallet_address'] = $user->wallet_address;
                    $arr['partner_id'] = $partner->id;
                    $arr['partner_wallet_address'] = $partner->wallet_address;

                    JetRequest::add_row($arr);

                    return response()->json(['type'=>'success',
                        'message'=>'Request is successfuly sent',
                        'data' => [
                            'userID' => $user->id,
                            'jet_balance' => $user->wallet,
                            'request_balance' => $amount,
                            'transaction_fee' => $transaction_fee,
                        ],
                    ], $this->successStatus);

                } catch (\Exception $e){
                    return \Response::json(['type'=>'error',
                        'message' => 'Jet Request process failed',
                    ], $this->dangerStatus);
                }
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Wrong values for parameters',
                ], $this->dangerStatus);
            }
        }
    } //+++
    
// jet routes -------------------------------------------end


// talents routes -------------------------------------------start

    public function searchTalent(Request $request)
    {
        if(Auth::check()){
            $rules = [
                'name' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $talent = Talent::search_talent('name',$request->input('name'));

            if($talent && isset($talent->id)){

                return response()->json(['type'=>'success',
                    'message' => 'Talent is found',
                    'data' => array(
                        'talent' => $talent
                    )
                ], $this->successStatus);
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Talent not found',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function showAllTalents(Request $request)
    {
        if(Auth::check()){

            $rules = [
                'is_active' => 'numeric|required|in:0,1',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $is_active = (int)$request->input('is_active');

            $arr = array('is_active'=>$is_active);
            $talents = Talent::get_talents($arr);

            return response()->json(['type'=>'success',
                'message' => 'Talents are found',
                'data' => array(
                    'talents' => $talents
                )
            ], $this->successStatus);

        }
    } //+++

    public function showSingleTalent(Request $request)
    {
        if(Auth::check()) {

            $rules = [
                'talent_id' => 'numeric|required|exists:talents,id',
                //'from_menu' => 'required|in:talents,rewards'
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $talent = Talent::get_talentByID($request->input('talent_id'));

            if($talent && isset($talent->id)){

                return response()->json(['type'=>'success',
                    'message' => 'Talent is found',
                    'data' => array(
                        'talent' => $talent
                    )
                ], $this->successStatus);
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Talent not found',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function purchaseTalent(Request $request)
    {
        if(Auth::check()){

            $rules = [
                'talent_id' => 'required|numeric|exists:talents,id',
                'amount' => 'required|digits_between:1,20',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            if(Auth::user()->wallet < $request->input('amount')){
                return \Response::json(['type'=>'error',
                    'message' => 'Insufficient funds',
                ], $this->dangerStatus);
            }

            $talent = Talent::get_talentByID($request->input('talent_id'));

            if($talent && isset($talent->id)){

                if($talent->available >= $request->input('amount')){

                    DB::beginTransaction();
                    try {
                        $new_available = $talent->available - $request->input('amount');
                        $talent->available = $new_available;
                        $talent->sold = $talent->sold + $request->input('amount');
                        $talent->update();

                        $user = User::find(Auth::user()->id);
                        $user->wallet = $user->wallet - $request->input('amount');
                        $user->update();

                        $res = UserTalent::add_or_update_row(Auth::user()->id,$talent->id,$request->input('amount'));

                        // saving history about transaction
                        $arr = array();
                        $arr['case'] = 'talent';
                        $arr['amount'] = $request->input('amount');
                        $arr['comment'] = 'Purchase Talent';
                        $arr['talent_id'] = $talent->id;

                        $t_history = Transaction::add_row($arr);

                        if(!$t_history){
                            DB::rollBack();

                            return \Response::json(['type'=>'error',
                                'message' => 'Talent owning process is failed',
                            ], $this->dangerStatus);
                        }

                        DB::commit();

                        return response()->json(['type'=>'success',
                            'message' => 'User successfuly owned part of the tallent',
                            'data' => array(
                                'userID' => $res->user_id,
                                'talent_id' => $res->talent_id,
                                'amount' => $res->amount,
                                'email' => Auth::user()->email
                            )
                        ], $this->successStatus);

                    } catch (\Exception $e){
                        DB::rollBack();

                        return \Response::json(['type'=>'error',
                            'message' => 'Talent owning process is failed',
                        ], $this->dangerStatus);
                    }
                } else {
                    return \Response::json(['type'=>'error',
                        'message' => 'Please try get smaller amount then available amount is',
                    ], $this->dangerStatus);
                }
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Talent not found',
                ], $this->dangerStatus);
            }
        }
    } //+++ //getTalent

    public function myTeam(Request $request)
    {
        if(Auth::check()){
            $user_talents = UserTalent::get_user_talentsByUserID(Auth::user()->id);

            return response()->json(['type'=>'success',
                'message' => 'Talents are found',
                'data' => array(
                    'talents' => $user_talents
                )
            ], $this->successStatus);
        }
    } //+++


// talents routes -------------------------------------------end

// rewards routes -------------------------------------------start

    public function getAllRewardsByJet()
    {
        if(Auth::check()){

            $rewards = Reward::show_rewards();

            return response()->json(['type'=>'success',
                'message' => 'Rewards are found',
                'data' => array(
                    'rewards' => $rewards
                )
            ], $this->successStatus);

        }
    } //+++ // =jetRewards

    public function getRewardInfo(Request $request)
    {
        if(Auth::check()){

            $rules = [
                'reward_id' => 'numeric|required|exists:rewards,id',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $reward = Reward::get_rewardByID($request->input('reward_id'));

            if($reward && isset($reward)){

                return response()->json(['type'=>'success',
                    'message' => 'Reward is found',
                    'data' => array(
                        'reward' => $reward
                    )
                ], $this->successStatus);

            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Reward not found',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function purchaseReward(Request $request)
    {
        if(Auth::check()){

            $rules = [
                'reward_id' => 'required|numeric|exists:rewards,id',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $reward = Reward::get_rewardByID($request->input('reward_id'));

            if($reward && isset($reward->id)){

                if(Auth::user()->wallet < $reward->price){
                    return \Response::json(['type'=>'error',
                        'message' => 'Insufficient funds',
                    ], $this->dangerStatus);
                }

                if((int)$reward->quantity > 0){

                    DB::beginTransaction();
                    try {

                        $res = UserReward::add_or_update_row(Auth::user()->id, $reward->id, $reward->talent_id);

                        $user = User::find(Auth::user()->id);
                        $user->wallet = $user->wallet - $reward->price;
                        $user->update();

                        $reward->quantity = $reward->quantity - 1;
                        $reward->update();

                        // saving history about transaction
                        $arr = array();
                        $arr['case'] = 'reward';
                        $arr['amount'] = $reward->price;
                        $arr['comment'] = 'Purchase Reward';
                        $arr['reward_id'] = $reward->id;

                        $r_history = Transaction::add_row($arr);
                        if(!$r_history){
                            DB::rollBack();
                            return \Response::json(['type'=>'error',
                                'message' => 'Reward bying process is failed',
                            ], $this->dangerStatus);
                        }

                        DB::commit();

                        return response()->json(['type'=>'success',
                            'message' => 'User successfuly bought the reward',
                            'data' => array(
                                'userID' => $res->user_id,
                                'reward_id' => $res->reward_id,
                                'quantity' => $reward->quantity,
                                'email' => Auth::user()->email
                            )
                        ], $this->successStatus);

                    } catch (\Exception $e){
                        DB::rollBack();

                        return \Response::json(['type'=>'error',
                            'message' => 'Reward bying process is failed',
                        ], $this->dangerStatus);
                    }
                } else {

                    return \Response::json(['type'=>'error',
                        'message' => 'No rewards are available',
                    ], $this->dangerStatus);
                }
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Reward not found',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function getAllRewardsByTalent(Request $request)
    {
        if(Auth::check()){
            $rules = [
                'talent_id' => 'required|numeric|exists:talents,id',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $talent = Talent::get_talentByID($request->input('talent_id'));

            if($talent && isset($talent->id)){

                $rewards = $talent->rewards;

                return response()->json(['type'=>'success',
                    'message' => 'Rewards are found',
                    'data' => array(
                        'talent_id' => $talent->id,
                        'rewards' => $rewards
                    )
                ], $this->successStatus);
            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Rewards are not found',
                ], $this->dangerStatus);
            }
        }
    } //+++

    public function getAllRewardsDetailsByTeam(Request $request) //+++
    {
        if(Auth::check()){
            $rules = [
                'team_id' => 'required|numeric|exists:user_talents,id',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                foreach($rules as $key => $val){
                    if(isset($validator->errors()->toArray()[$key])){
                        $message = $validator->errors()->toArray()[$key][0];
                    }
                }
                return \Response::json(['type' => 'error', 'message' => $message], $this->validFailedStatus);
            }

            $team = UserTalent::find($request->input('team_id'));
            if($team && isset($team->id)){

                $talent = Talent::get_talentByID($team->talent_id);

                if($talent && isset($talent->id)){

                    $rewards = $talent->rewards;

                    return response()->json(['type'=>'success',
                        'message' => 'Rewards are found',
                        'data' => array(
                            'team_id' => $request->input('team_id'),
                            'talent_id' => $talent->id,
                            'rewards' => $rewards
                        )
                    ], $this->successStatus);
                } else {
                    return \Response::json(['type'=>'error',
                        'message' => 'Talent and Rewards not found',
                    ], $this->dangerStatus);
                }

            } else {
                return \Response::json(['type'=>'error',
                    'message' => 'Team not found',
                ], $this->dangerStatus);
            }

        }
    }

// rewards routes -------------------------------------------end


// transaction routes -------------------------------------------start

    public function getUserTransactionHistory(Request $request)
    {
        if(Auth::check()){
            $user_transaction_history = Transaction::get_user_transaction_history(Auth::user()->id);

            return response()->json(['type'=>'success',
                'message' => 'Transaction history',
                'data' => array(
                    'userID' => Auth::user()->id,
                    'transactions_count' => $user_transaction_history->count(),
                    'transactions' => $user_transaction_history
                )
            ], $this->successStatus);
        }
    } //+++
// transaction routes -------------------------------------------end


    /**
     * details api
     *
     * @return \Illuminate\Http\Response
     */

    public function logoutApi()
    {
        if (Auth::check()) {
            $user_email = Auth::user()->email;
            $user_id = Auth::user()->id;

            try {
                UserPin::delete_row(Auth::user()->id);

                Auth::user()->AauthAcessToken()->delete();

                return response()->json(['type' => 'success',
                    'message' => 'User is loged out',
                    'data' => [
                        'userID' => $user_id,
                        'email' => $user_email,
                    ]
                ], $this->successStatus);

            } catch (\Exception $e){

                return \Response::json(['type'=>'error',
                    'message' => 'Logout process failed',
                ], $this->dangerStatus);
            }


        }
    } //+++

//test routes ----------------------------------start

    public function details()
    {
        if(Auth::check()){
            $user = Auth::user();
            return response()->json(['success' => $user], $this->successStatus);
        }
    }






}
