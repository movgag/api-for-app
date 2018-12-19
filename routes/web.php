<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    die;
    $curl = curl_init();
// Set some options - we are passing in a useragent too here
    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => 'https://api.coinmarketcap.com/v1/ticker/jetcoin/',
        // CURLOPT_USERAGENT => 'Codular Sample cURL Request'
    ));
// Send the request & save response to $resp
    $resp = curl_exec($curl);
    $resp = json_decode($resp,true);
    dd($resp);
});
