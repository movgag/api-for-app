<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});


//Route::post('register1', 'API\UserController@register1');

//Route::post('loginViaPin', 'API\UserController@loginViaPin');

Route::post('login', 'API\UserController@login');


Route::post('register', 'API\UserController@register');

Route::post('resendMailCode', 'API\UserController@resendMailCode');

Route::post('verifyMailCode', 'API\UserController@verifyMailCode');

Route::post('verifyQrCode', 'API\UserController@verifyQrCode');

Route::post('applyPin', 'API\UserController@applyPin');


//recover routes ----------------------------------------------- start
Route::post('checkMnemonic', 'API\UserController@recoverAccount');

Route::post('updateEmail', 'API\UserController@recoverAccountForm');

Route::post('verifyEmailRecovery', 'API\UserController@verifyEmailRecovery');
//recover routes ------------------------------------------------- end


Route::group(['middleware' => ['auth:api','check_status']], function() {

    //jet routes ---------------------------------------------------- start
    Route::post('getUserJetBalance','API\UserController@getUserJetBalance');
    Route::post('getJetValue','API\UserController@getJetValue');

    Route::post('sendJet','API\UserController@sendJet');
    Route::post('requestJet','API\UserController@requestJet');

    //jet routes ---------------------------------------------------- end

    Route::post('sendSms', 'API\UserController@sendSms');
    Route::post('reSendSms', 'API\UserController@reSendSms');
    Route::post('checkSms', 'API\UserController@checkSms');
    
    Route::post('checkPin', 'API\UserController@checkPin');

    Route::post('setMnemonic','API\UserController@setMnemonic');
   // Route::post('checkMnemonic','API\UserController@checkMnemonic');

    Route::post('getAllTalents','API\UserController@showAllTalents');
    Route::post('getTalentInfo','API\UserController@showSingleTalent');
    Route::post('searchTalent','API\UserController@searchTalent');
    Route::post('purchaseTalent','API\UserController@purchaseTalent');

    Route::post('getAllRewardsByJet','API\UserController@getAllRewardsByJet');
    Route::post('getRewardInfo','API\UserController@getRewardInfo');
    Route::post('getAllRewardsByTalent','API\UserController@getAllRewardsByTalent');
    Route::post('purchaseReward','API\UserController@purchaseReward');

    Route::post('getUserTransactionHistory','API\UserController@getUserTransactionHistory');

    Route::post('getMyTeam','API\UserController@myTeam');
    Route::post('getAllRewardsDetailsByTeam','API\UserController@getAllRewardsDetailsByTeam');

    Route::post('logout','API\UserController@logoutApi');

// test route ------------------------------------------------
    Route::post('details', 'API\UserController@details');

});
