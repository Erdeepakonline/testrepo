<?php

use App\Models\Campaign;
use App\Models\Coupon;
use App\Models\User;
use App\Models\Agent;
use App\Models\Country;
use App\Models\IpStack;
use App\Models\UsedCoupon;
use App\Models\TransactionLog;
use App\Models\Notification;
use App\Models\UserNotification;
use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use PDF;

function randomuid($utype = '')
{ 
  $uid =  $utype . strtoupper(uniqid()); 
  $checkdata = User::where('uid', $uid)->count();
  if ($checkdata > 0) {
    randomuid($utype);
  } else {
    return $uid;
  }
}

function randomClientid($agttype = '')
{
    $characters = "0123456789";
    $random_chars = '';
    for ($i = 0; $i < 4; $i++) {
        $random_chars .= $characters[rand(0, strlen($characters) - 1)];
    }
    $agent_id = ($agttype . $random_chars);
    $checkdata = Agent::where('agent_id', $agent_id)->count();
    if ($checkdata > 0) {
        randomClientid($agttype);
    } else {
        return substr($agent_id, 0, 7); 
    }
}

function listNotificationMassages($cmpid, $advcode, $dbudget)
{
  $return = '';
  $date = date('Y-m-d');
  $endDlyBudget = DB::table('camp_budget_utilize')->selectRaw('sum(amount) as cur_budget,camp_id')->where('camp_id', $cmpid)->where('advertiser_code', $advcode)->whereDate('udate', $date)->first();
  if (!empty($endDlyBudget->camp_id)) {
    if (($dbudget - 1) <= $endDlyBudget->cur_budget) {
      $specificDate = date('Y-m-d');
      $existUsers = Notification::where('uid', $cmpid)->whereDate('created_at', $specificDate)->count();
      if (empty($existUsers)) {
        $noti_title = "Your campaign's daily budget has been exhausted.";
        $noti_desc  = "Dear advertiser, your campaign's (" . $cmpid . ") daily budget is being exhausted. update the daily budget to enjoy the benefits.";
        $notification = new Notification();
        $notification->notif_id = gennotificationuniq();
        $notification->title = $noti_title;
        $notification->noti_desc = $noti_desc;
        $notification->noti_type = 1;
        $notification->noti_for = 1;
        $notification->all_users = 0;
        $notification->status = 1;
        $notification->uid = $cmpid;
        if ($notification->save()) {
          $noti = new UserNotification();
          $noti->notifuser_id = gennotificationuseruniq();
          $noti->noti_id = $notification->id;
          $noti->user_id = $advcode;
          $noti->user_type = 1;
          $noti->view = 0;
          $noti->created_at = Carbon::now();
          $noti->updated_at = now();
          $noti->save();
        }
        $return = 'Send message successfully!';
      }
    } 
  }
  return $return;
}
function listNotificationMassagesFront($impData)
{
  $return = '';
  $date = date('Y-m-d');
  $endDlyBudget = DB::table('camp_budget_utilize')->selectRaw('sum(amount) as cur_budget,camp_id')->where('camp_id', $impData['campaign_id'])->where('advertiser_code', $impData['advertiser_code'])->whereDate('udate', $date)->first();
  if (!empty($endDlyBudget->camp_id)) {
    if (($impData['d_budget'] - 1) <= $endDlyBudget->cur_budget) {
      $specificDate = date('Y-m-d');
      $existUsers = Notification::where('uid', $impData['advertiser_code'])->whereDate('created_at', $specificDate)->count();
      if (empty($existUsers)) {
        $noti_title = "Your campaign's daily budget has been exhausted.";
        $noti_desc  = "Dear advertiser, your campaign's (" . $impData['campaign_id'] . ") daily budget is being exhausted. update the daily budget to enjoy the benefits.";
        $notification = new Notification();
        $notification->notif_id = gennotificationuniq();
        $notification->title = $noti_title;
        $notification->noti_desc = $noti_desc;
        $notification->noti_type = 1;
        $notification->noti_for = 1;
        $notification->all_users = 0;
        $notification->status = 1;
        $notification->uid = $impData['advertiser_code'];
        if ($notification->save()) {
          $noti = new UserNotification();
          $noti->notifuser_id = gennotificationuseruniq();
          $noti->noti_id = $notification->id;
          $noti->user_id = $impData['advertiser_code'];
          $noti->user_type = 1;
          $noti->view = 0;
          $noti->created_at = Carbon::now();
          $noti->updated_at = now();
          $noti->save();
        }
        $return = 'Send message successfully!';
      }
    }
  }
  return $return;
}
function gennotificationuniq()

{

  $notigen = 'NOTIF';

  $unqid =  $notigen . strtoupper(uniqid());

  $checkdata = Notification::where('notif_id', $unqid)->count();

  if ($checkdata > 0) {

    gennotificationuniq();
  } else {

    return $unqid;
  }
}



function gennotificationuseruniq()

{

  $notigen = 'NOTIFU';

  $unqid =  $notigen . strtoupper(uniqid());

  $checkdata = UserNotification::where('notifuser_id', $unqid)->count();

  if ($checkdata > 0) {

    gennotificationuseruniq();
  } else {

    return $unqid;
  }
}



function real_ip()

{

  /*

      $header_checks = array(

        'HTTP_CLIENT_IP',

        'HTTP_PRAGMA',

        'HTTP_XONNECTION',

        'HTTP_CACHE_INFO',

        'HTTP_XPROXY',

        'HTTP_PROXY',

        'HTTP_PROXY_CONNECTION',

        'HTTP_VIA',

        'HTTP_X_COMING_FROM',

        'HTTP_COMING_FROM',

        'HTTP_X_FORWARDED_FOR',

        'HTTP_X_FORWARDED',

        'HTTP_X_CLUSTER_CLIENT_IP',

        'HTTP_FORWARDED_FOR',

        'HTTP_FORWARDED',

        'ZHTTP_CACHE_CONTROL',

        'REMOTE_ADDR'

      );

      foreach ($header_checks as $key) {

        if (array_key_exists($key, $_SERVER) === true) {

          foreach (explode(',', $_SERVER[$key]) as $ip) {

            $ip = trim($ip);

            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {

              return $ip;

            }

          }

         }

         } */



  if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {

    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];

    $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
  }

  $client  = @$_SERVER['HTTP_CLIENT_IP'];

  $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

  $remote  = $_SERVER['REMOTE_ADDR'];

  if (filter_var($client, FILTER_VALIDATE_IP)) {

    $ip = $client;
  } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {

    $ip = $forward;
  } else {

    $ip = $remote;
  }

  return $ip;
}





function getliveconvert($from, $to)

{

  /* API key  */

  $apikey = 'a3f649319bffc5655b3dd8b8e77bd823';

  $url = "http://apilayer.net/api/live?access_key=" . $apikey . "&currencies=" . $to . "&source=" . $from . "&format=1";

  $ch = curl_init($url);

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  $json = curl_exec($ch);

  curl_close($ch);

  $api_result = json_decode($json, true);

  return $api_result;
}


function ipaddressconr($ip)

{

  $curl = curl_init();

  curl_setopt_array($curl, array(

    CURLOPT_URL => 'http://ip-api.com/json/' . $ip,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'GET',

  ));

  $response = curl_exec($curl);

  $getCodes =  json_decode($response);

  //   print_r($getCodes); exit;

  $getCode = $getCodes->countryCode;

  $getdeatil = Country::where('iso', $getCode)->first()->toArray();

  if ($getdeatil) {

    if ($getdeatil['currency_code'] != 'USD') {

      $custCurrency =  $getdeatil['currency_code'];

      $usdcurrency = 'USD';

      $currencyData = getliveconvert($usdcurrency, $custCurrency);

      $finalamt = (float) filter_var($currencyData['quotes']['USD' . $custCurrency], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

      $return['data'] = array('famt' => $finalamt, 'currency' => $custCurrency);
    } else {

      $custCurrencyusd = 'USD';

      $return['data'] = array('famt' => 1, 'currency' => $custCurrencyusd);
    }
  } else {

    $return['code']    = 101;

    $return['message'] = 'Something went wrong!';
  }

  return $return;
}

function ipaddressconrTaza($ip)

{

  $curl = curl_init();

  curl_setopt_array($curl, array(

    CURLOPT_URL => 'http://ip-api.com/json/' . $ip,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'GET',

  ));

  $response = curl_exec($curl);
  $getCodes =  json_decode($response);

  //   print_r($getCodes); exit;

  $getCode = $getCodes->countryCode;

  $getdeatil = Country::where('iso', $getCode)->first()->toArray();

  if ($getdeatil) {

    if ($getdeatil['currency_code'] != 'USD') {

      $custCurrency =  $getdeatil['currency_code'];

      $country =  $getdeatil['iso'];

      $usdcurrency = 'USD';

      $currencyData = getliveconvert($usdcurrency, $custCurrency);

      $finalamt = (float) filter_var($currencyData['quotes']['USD' . $custCurrency], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

      // $return['data'] = array('famt' => $finalamt, 'currency' => $custCurrency, 'country' => $country);
      $return['data'] = array('famt' => 1, 'currency' => 'USD', 'country' => 'US');
    } else {

      $custCurrencyusd = 'USD';

      $return['data'] = array('famt' => 1, 'currency' => $custCurrencyusd, 'country' => 'US');
    }
  } else {

    $return['code']    = 101;

    $return['message'] = 'Something went wrong!';
  }

  return $return;
}


function ipaddressconrPayu($ip)

{

  $curl = curl_init();

  curl_setopt_array($curl, array(

    CURLOPT_URL => 'http://ip-api.com/json/' . $ip,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'GET',

  ));

  $response = curl_exec($curl);
  $getCodes =  json_decode($response);

  $getCode = $getCodes->countryCode;

  $getdeatil = Country::where('iso', $getCode)->first();

  if ($getdeatil) {

    //   $custCurrency=  $getdeatil['currency_code']; 

    $custCurrency =  'INR';

    $usdcurrency = 'USD';

    $currencyData = getliveconvert($usdcurrency, $custCurrency);

    $finalamt = (float) filter_var($currencyData['quotes']['USD' . $custCurrency], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $return['data'] = array('famt' => $finalamt, 'currency' => $custCurrency);
  } else {

    $return['code']    = 101;

    $return['message'] = 'Something went wrong!';
  }

  return $return;
}



function ipaddressconrAirpay($ip)

{

  $curl = curl_init();

  curl_setopt_array($curl, array(

    CURLOPT_URL => 'http://ip-api.com/json/' . $ip,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'GET',

  ));

  $response = curl_exec($curl);

  $getCodes =  json_decode($response);

  $getCode = $getCodes->countryCode;

  $getdeatil = Country::where('iso', $getCode)->first();

  if ($getdeatil) {

    //   $custCurrency=  $getdeatil['currency_code']; 

    $custCurrencyNumcode =  $getdeatil['numcode'];

    $custCountry =  $getdeatil['nicename'];



    $custCurrency =  'INR';

    $usdcurrency = 'USD';

    $currencyData = getliveconvert($usdcurrency, $custCurrency);

    $finalamt = (float) filter_var($currencyData['quotes']['USD' . $custCurrency], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $return['data'] = array('famt' => $finalamt, 'currency' => $custCurrency, 'numcode' => $custCurrencyNumcode, 'nicename' => $custCountry);
  } else {

    $return['code']    = 101;

    $return['message'] = 'Something went wrong!';
  }

  return $return;
}


function getCampPrefix($type)

{

  if ($type == 'text') {

    $aType = 'CMPT';
  } elseif ($type == 'banner') {

    $aType = 'CMPB';
  } elseif ($type == 'native') {

    $aType = 'CMPN';
  } elseif ($type == 'video') {

    $aType = 'CMPV';
  } elseif ($type == 'popup') {

    $aType = 'CMPP';
  } elseif ($type == 'social') {

    $aType = 'CMPS';
  } else {

    $aType = '';
  }

  return $aType;
}



function getCountryName($ip)

{

  $curl = curl_init();

  curl_setopt_array($curl, array(

    CURLOPT_URL => 'http://ip-api.com/json/' . $ip,

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'GET',

  ));

  $response = curl_exec($curl);

  curl_close($curl);

  return json_decode($response);
}



// function getCountryNameAdScript ($ip)

// {

//     // 'http://api.ipstack.com/'.$ip.'?access_key=1b7a39bca9bb90210c5f17de484faf7d';

//     $data  = file_get_contents('http://api.ipstack.com/'.$ip.'?access_key=73edfcf302ecac3b68b27d0aee4ba152');

//     return json_decode($data, true);

// }
/*
function getCountryNameAdScript($ip)
{
  $data = getCountryIpLocal('49.36.49.6');
  // print_r($data);
  $alldata = json_decode($data, true);
  if (count($alldata) > 0) {
    // $list = $alldata[0];
    // return ($list);
    return ($alldata);
  } else {
    $data  = file_get_contents('http://api.ipstack.com/' . $ip . '?access_key=73edfcf302ecac3b68b27d0aee4ba152');
    // insertCountryIpLocal(json_decode($data));
    return json_decode($data, true);
  }
}

function getCountryIpLocal($ip)
{
  // $data = IpStack::where('ip_addrs',$ip)->get();
  $data = IpStack::inRandomOrder()->first();
  return json_encode($data, true);
}

*/

function getCountryNameAdScript($ip)
{
  $data = getCountryIpLocal($ip);
  $alldata = json_decode($data, true);
  if (count($alldata) > 0) {
    $list = $alldata[0];
    return ($list);
  } else {
    $data  = file_get_contents('http://api.ipstack.com/' . $ip . '?access_key=73edfcf302ecac3b68b27d0aee4ba152');
    insertCountryIpLocal(json_decode($data));
    return json_decode($data, true);
  }
}

function getCountryIpLocal($ip)
{
  $data = IpStack::where('ip_addrs', $ip)->get();
  return json_encode($data, true);
}

function insertCountryIpLocal($data)
{

  $post = new IpStack();
  $post->ip_addrs = $data->ip;
  $post->continent_code = $data->continent_code;
  $post->continent_name = $data->continent_name;
  $post->country_code = $data->country_code;
  $post->country_name = $data->country_name;
  $post->region_code = $data->region_code;
  $post->region_name = $data->region_name;
  $post->city = $data->city;
  $post->zip = $data->zip;
  $post->time_zone = $data->time_zone->id;
  $post->save();
}



function campaignStatusCntryUpdate($camp_id, $status)

{

  $camp = Campaign::where('id', $camp_id)->first();

  $camp->status = $status;

  $camp->update();
}







function getCouponCal($userid, $couponcode, $amoumt,$couponid)
{
    $getcmpdata  = Coupon::where('coupon_id',$couponid)->where('trash',0)->where('status',1)->first();
    if(empty($getcmpdata))
    {
        $return ['code']    = 101;
        $return ['message'] = 'Please Enter Coupon Code!';
        return $return;  
    }else {
        $datenow = date('Y-m-d');
        $enddate = $getcmpdata->end_date;
        $date_now = new DateTime($datenow);
        $enddate1    = new DateTime($enddate);
        if($date_now <= $enddate1)
        {
            $coupontype = $getcmpdata->coupon_type;
            if($getcmpdata->coupon_limit_type === 'Limited'){
              //$limitedUse = DB::table('transactions')->where('advertiser_code',$userid)->where('cpn_id',$couponid)->Count();
              $usedcoupon = UsedCoupon::where('advertiser_code', $userid)->where('coupon_id', $couponid)->count();
              if($getcmpdata->coupon_limit_value <= $usedcoupon){
                  $return ['code']    = 101;
                  $return ['message'] = 'Coupon Limit is Expired!';
                  return $return;  
              }else{
                $return = getCouponTypeCodition($coupontype,$getcmpdata,$amoumt,$couponcode,$couponid,$userid);
                return $return;  
              }
            }
            if($getcmpdata->coupon_limit_type === 'Unlimited'){
                $return = getCouponTypeCodition($coupontype,$getcmpdata,$amoumt,$couponcode,$couponid,$userid);
                return $return;  
            }
        }else{
            $return ['code']    = 101;
            $return ['message'] = 'Coupon Expired!';
        }
    }
    return $return;  
}

 function getCouponTypeCodition($coupontype,$getcmpdata,$amoumt,$couponcode,$couponid,$userid){
  if($coupontype == 'Percent')
  {
     $cmpid= $getcmpdata->id;
     $coupontypes  = Coupon::where('id',$cmpid)->where('user_ids',0)->first();
     if($coupontypes){
             $minamoutcmp = $getcmpdata->min_bil_amt;
              if($amoumt >= $minamoutcmp ){
                  $valueamt = $getcmpdata->coupon_value;
                  $finalamt = $amoumt*$valueamt/100;  
                  $valuedisamt = $getcmpdata->max_disc; 
                  if($valuedisamt > $finalamt){
                        $cmpamtnew1 =$amoumt - $finalamt;
                        $return ['code']            = 200;
                        $return ['amount']          = $amoumt;
                        $return ['bonus_amount']    = $finalamt;
                        $return ['total_amount']    = $amoumt+$finalamt;
                        $return ['coupon_code']     = $couponcode;
                        $return ['coupon_id']       = $couponid;
                        return $return;
                  }else {
                          $cmpamtnew=$amoumt - $valuedisamt;
                          $return ['code']            = 200;
                          $return ['amount']          = $amoumt;
                          $return ['bonus_amount']    = $valuedisamt;
                          $return ['total_amount']    = $amoumt+$valuedisamt;
                          $return ['coupon_code']     = $couponcode;
                          $return ['coupon_id']       = $couponid;
                          return $return;
                  }
              }else{
              $return ['code']    = 101;
              $return ['message'] = "Minimum $$minamoutcmp required for this coupon.";
              return $return;
          }
     } else{
            $userdid = User::where('uid',$userid)->first();
            $useridn = $userdid->id;
            $getids = explode(",",$getcmpdata->user_ids);
            if (in_array($useridn, $getids))
            {
              $minamoutcmp = $getcmpdata->min_bil_amt;
              if($amoumt >= $minamoutcmp )
              {
                  $valueamt = $getcmpdata->coupon_value;
                  $finalamt = $amoumt*$valueamt/100;
                  $valuedisamt = $getcmpdata->max_disc;
                  if($valuedisamt > $finalamt){
                        $cmpamtnew1 =$amoumt - $finalamt;
                        $return ['code']    = 200;
                        $return ['amount']    = $amoumt;
                        $return ['bonus_amount']    = $finalamt;
                        $return ['total_amount']    = $amoumt+$finalamt;
                        $return ['coupon_code']    = $couponcode;
                        $return ['coupon_id']    = $couponid;
                        return $return;
                  }else{
                      $cmpamtnew=$amoumt - $valuedisamt;
                      $return ['code']    = 200;
                      $return ['amount']    = $amoumt;
                      $return ['bonus_amount']    = $finalamt;
                      $return ['total_amount']    = $amoumt+$finalamt;
                      $return ['coupon_code']    = $couponcode;
                      $return ['coupon_id']    = $couponid;
                      return $return;
                  }
              }else{
                  $return ['code']    = 101;
                  $return ['message'] = "Minimum $$minamoutcmp required for this coupon.";
                  return $return;
              }
            }else{
              $return ['code']    = 101;
              $return ['message'] = 'No Eligible Coupon!';
              return $return;
            }
     }
  }else{
    $cmpid= $getcmpdata->id;
     $coupontypes  = Coupon::where('id',$cmpid)->where('user_ids',0)->first();
     if($coupontypes){
              $minamoutcmp = $getcmpdata->min_bil_amt;
              if($amoumt >= $minamoutcmp)
              {
                  $valueamt = $getcmpdata->coupon_value;
                  $finalamt = $valueamt;
                  $valuedisamt = $getcmpdata->max_disc;
                  if($valuedisamt > $finalamt)
                  {
                        $cmpamtnew1 =$amoumt - $finalamt;
                        $return ['code']    = 200;
                        $return ['amount']    = $amoumt;
                        $return ['bonus_amount']    = $finalamt;
                        $return ['total_amount']    = $amoumt+$finalamt;
                        $return ['coupon_code']    = $couponcode;
                        $return ['coupon_id']    = $couponid;
                        return $return;
                  }else{
                      $cmpamtnew  =   $amoumt - $valuedisamt;
                      $return ['code']    = 200;
                      $return ['amount']    = $amoumt;
                      $return ['bonus_amount']    = $valuedisamt;
                      $return ['total_amount']    = $amoumt+$valuedisamt;
                      $return ['coupon_code']    = $couponcode;
                      $return ['coupon_id']    = $couponid;
                      return $return;
                  }
              }else{
              $return ['code']    = 101;
              $return ['message'] = "Minimum $$minamoutcmp required for this coupon.";
              return $return;
          }
     }else{
            $getids = explode(",",$getcmpdata->user_ids);
            $userdidn = User::where('uid',$userid)->first();
            $useridnews = $userdidn->id;
            if (in_array($useridnews, $getids)){
              $minamoutcmp = $getcmpdata->min_bil_amt;
              if($amoumt >= $minamoutcmp ){
                  $valueamt = $getcmpdata->coupon_value;
                  $finalamt = $amoumt*$valueamt/100;
                  $valuedisamt = $getcmpdata->max_disc;
                  if($valuedisamt > $finalamt){
                        $cmpamtnew1 =$amoumt - $finalamt;
                        $return ['code']    = 200;
                        $return ['amount']    = $amoumt;
                        $return ['bonus_amount']    = $finalamt;
                        $return ['total_amount']    = $amoumt+$finalamt;
                        $return ['coupon_code']    = $couponcode;
                        $return ['coupon_id']    = $couponid;
                        return $return;
                  } else{
                      $cmpamtnew=$amoumt - $valuedisamt;
                      $return ['code']    = 200;
                      $return ['amount']    = $amoumt;
                      $return ['bonus_amount']    = $valuedisamt;
                      $return ['total_amount']    = $amoumt+$valuedisamt;
                      $return ['coupon_code']    = $couponcode;
                      $return ['coupon_id']    = $couponid;
                      return $return;
                  }
              }else{
                  $return ['code']    = 101; 
                  $return ['message'] = "Minimum $$minamoutcmp required for this coupon.";
                  return $return;
              }
            }else{
              $return ['code']    = 101;
              $return ['message'] = 'No Eligible Coupon!';
              return $return;
            }
        }
     }
  }



function sendmailUser($subject, $body, $email)
{
  $isHTML = true;
  $mail = new PHPMailer();
  $mail->IsSMTP();
  $mail->CharSet = 'UTF-8';
  $mail->Host       = env('MAIL_HOST', "");
  $mail->SMTPDebug  = 0;
  $mail->SMTPAuth   = true;
  $mail->Port       = env('MAIL_PORT', "");
  $mail->Username   = env('mail_username', "");
  $mail->Password   = env('MAIL_PASSWORD', "");
  $mail->setFrom(env('mail_from_address', ""), "7Search PPC");
  $mail->addAddress($email);
  $mail->SMTPSecure = 'ssl';
  $mail->isHTML($isHTML);
  $mail->Subject = $subject;
  $mail->Body    = $body;
  if ($mail->send()) {
    return 1;
  } else {
    return 0;
  }
}

function sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2)
{
  $isHTMLAdmin = true;
  $mailadmin = new PHPMailer();
  $mailadmin->IsSMTP();
  $mailadmin->CharSet = 'UTF-8';
  $mailadmin->Host       = env('MAIL_HOST', "");
  $mailadmin->SMTPDebug  = 0;
  $mailadmin->SMTPAuth   = true;
  $mailadmin->Port       = env('MAIL_PORT', "");
  $mailadmin->Username   = env('mail_username', "");
  $mailadmin->Password   = env('MAIL_PASSWORD', "");
  $mailadmin->setFrom(env('mail_from_address', ""), "7Search PPC");
  $mailadmin->addAddress($adminmail1);
  $mailadmin->AddCC($adminmail2);
  $mailadmin->SMTPSecure = 'ssl';
  $mailadmin->isHTML($isHTMLAdmin);
  $mailadmin->Subject = $subjectadmin;
  $mailadmin->Body    = $bodyadmin;
  if ($mailadmin->send()) {
    return 1;
  } else {
    return 0;
  }
}


// function sendmailpaymentupdate($subject, $body, $emails)
// {
//     $isHTML = true;
//     $mail = new PHPMailer(true);
//     try {
//         $mail->isSMTP();
//         $mail->CharSet = 'UTF-8';
//         $mail->Host = env('MAIL_HOST', "");
//         $mail->SMTPAuth = true;
//         $mail->Port = env('MAIL_PORT', "");
//         $mail->Username = env('mail_username', "");
//         $mail->Password = env('MAIL_PASSWORD', "");
//         $mail->SMTPSecure = 'ssl';
//         $mail->setFrom('noreply@7searchppc.com', '7Search PPC');
//         foreach ($emails as $email) {
//             $mail->addAddress($email);
//             $mail->isHTML($isHTML);
//             $mail->Subject = $subject;
//             $mail->Body = $body;
//             $mail->send();
//             $mail->clearAddresses();
//         }
//         return 1;
//     } catch (Exception $e) {
//         return 0;
//     }
// }

function sendmailpaymentupdate($subject, $body, $emails)
{
    $isHTML = true;
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';
        $mail->Host = 'mail.7searchppc.com';
        $mail->SMTPAuth = true;
        $mail->Port = 465;
        $mail->Username = 'noreply@7searchppc.com';
        $mail->Password = 'vOO8vOvA*zwusbu3';
        $mail->SMTPSecure = 'ssl';
        // Sender
        $mail->setFrom('noreply@7searchppc.com', '7Search PPC');
        foreach ($emails as $email) {
            // Recipient
            $mail->addAddress($email);
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            // Send email
            $mail->send();
            // Clear addresses for next iteration
            $mail->clearAddresses();
        }
        return 1;
    } catch (Exception $e) {
        // Handle exceptions
        return 0;
    }
}


function sendFcmNotification($title, $msg)
{



  $adm = DB::table('admin_login_logs')->get()->toArray();

  $tokens = array_filter(array_column($adm, 'noti_token'));

  $tks = [];

  foreach ($tokens as $token) {

    $tks[] = $token;
  }



  $curl = curl_init();

  $data = json_encode([

    "registration_ids" => $tks,

    "notification" => [

      "body" => $msg,

      "title" => $title

    ]

  ]);



  curl_setopt_array($curl, array(

    CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'POST',

    CURLOPT_POSTFIELDS => $data,

    CURLOPT_HTTPHEADER => array(

      'Authorization: key=AAAAVYh9IkY:APA91bHbyuOioazKL-_Jhwy7kpZ0vzq9wkIzYHeeUZN2H_9a2fCQK92cp7Ywm4Yg0ERmsVRsZep_KAw2YvpIE-6XXAW1igs4KJXFir6Uf-PEytCQCb3_WGGgbeJA1qKqbroFUqnMOi1p',

      'Content-Type: application/json'

    ),

  ));



  $response = curl_exec($curl);



  curl_close($curl);

  $response;
}



function sendFcmPubNotification($title, $msg, $tks)
{



  $curl = curl_init();

  $data = json_encode([

    "registration_ids" => $tks,

    "notification" => [

      "body" => $msg,

      "title" => $title

    ]

  ]);



  curl_setopt_array($curl, array(

    CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',

    CURLOPT_RETURNTRANSFER => true,

    CURLOPT_ENCODING => '',

    CURLOPT_MAXREDIRS => 10,

    CURLOPT_TIMEOUT => 0,

    CURLOPT_FOLLOWLOCATION => true,

    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

    CURLOPT_CUSTOMREQUEST => 'POST',

    CURLOPT_POSTFIELDS => $data,

    CURLOPT_HTTPHEADER => array(

      'Authorization: key=AAAAV4lzPQs:APA91bHnkz0wjVPC4U3UPDD0tceeimP0-S5grV3HSg_O6vAksfJi36VptX1O1AN-7_20PbVinddNjBiDLW1rJe9OsqftzavRtazap6ozR9X-VT8CEjq85KExbz53imH1StRJ-A_eaH0a',

      'Content-Type: application/json'

    ),

  ));



  $response = curl_exec($curl);



  curl_close($curl);

  $response;
}
function getImpClickData($imp, $clk, $os, $dv)
{

  $comb = [
    'windows' => [
      ['os' => 'windows', 'device' => 'Desktop']
    ],
    'android' => [
      ['os' => 'android', 'device' => 'Mobile'],
      ['os' => 'android', 'device' => 'Tablet']
    ],
    'apple' => [
      ['os' => 'apple', 'device' => 'Desktop'],
      ['os' => 'apple', 'device' => 'Mobile']
    ],
    'linux' => [
      ['os' => 'linux', 'device' => 'Desktop']
    ],
  ];



  $im = [];
  $ck = [];
  $i = 0;


  foreach ($comb as $key1 => $val1) {
    if (in_array($key1, $os)) {
      foreach ($comb[$key1] as $k1 => $v1) {
        if (!in_array($v1['device'], $dv)) {
          unset($comb[$key1][$k1]);
        }
      }
    } else {
      unset($comb[$key1]);
    }
  }


  // print_r($comb);
  $comb = $arr = array_filter(array_map('array_values', $comb));
  $co = count($comb);

  foreach ($comb as $key => $val) {
    if (in_array($key, $os)) {

      $i++;

      if ($key == 'windows') {
        if (!empty($comb[$key])) {
          if ($co > $i) {
            $imp2 = floor(($imp / 100) * rand(50, 60));
            $im[$key] = $imp2;
            $imp = ($imp - $imp2);

            $clk2 = floor(($clk / 100) * rand(50, 60));
            $ck[$key] = $clk2;
            $clk = ($clk - $clk2);
          } else {
            $im[$key] = $imp;
            $ck[$key] = $clk;
          }
        } else {
          unset($comb[$key]);
        }
      } elseif ($key == 'android') {

        if (!empty($comb[$key])) {
          if ($co > $i) {
            $imp2 = floor(($imp / 100) * rand(50, 70));
            $im[$key] = $imp2;
            $imp = ($imp - $imp2);

            $clk2 = floor(($clk / 100) * rand(50, 70));
            $ck[$key] = $clk2;
            $clk = ($clk - $clk2);
          } else {
            $im[$key] = $imp;
            $ck[$key] = $clk;
          }
        } else {
          unset($comb[$key]);
        }
      } elseif ($key == 'apple') {

        if (!empty($comb[$key])) {
          if ($co > $i) {
            $imp2 = floor(($imp / 100) * rand(60, 80));
            $im[$key] = $imp2;
            $imp = ($imp - $imp2);

            $clk2 = floor(($clk / 100) * rand(60, 80));
            $ck[$key] = $clk2;
            $clk = ($clk - $clk2);
          } else {
            $im[$key] = $imp;
            $ck[$key] = $clk;
          }
        } else {
          unset($comb[$key]);
        }
      } elseif ($key == 'linux') {
        if (!empty($comb[$key])) {
          if ($co > $i) {
            $imp2 = floor(($imp / 100) * rand(1, 15));
            $im[$key] = $imp2;
            $imp = ($imp - $imp2);

            $clk2 = floor(($clk / 100) * rand(1, 15));
            $ck[$key] = $clk2;
            $clk = ($clk - $clk2);
          } else {
            $im[$key] = $imp;
            $ck[$key] = $clk;
          }
        } else {
          unset($comb[$key]);
        }
      }
    } else {
      unset($comb[$key]);
    }
  }

  $cns = count($comb);

  foreach ($comb as $key2 => $val2) {

    $co2 = count($comb[$key2]);

    if ($co2 == 2) {

      $imv[] = floor(($im[$key2] / 100) * rand(50, 70));
      $imv[] = $im[$key2] - $imv[0];

      $clv[] = floor(($ck[$key2] / 100) * rand(50, 70));
      $clv[] = $ck[$key2] - $clv[0];
    } else {
      $imv[] = $im[$key2];
      $clv[] = $ck[$key2];
    }

    foreach ($comb[$key2] as $k2 => $v2) {
      $comb[$key2][$k2]['imp'] = $imv[$k2];
      $comb[$key2][$k2]['clk'] = $clv[$k2];
    }
    unset($imv);
    unset($clv);
  }

  return $comb;
}

function generate_serial()
{
  $getserial = TransactionLog::max('serial_no');
  $serial_no = $getserial + 1;
  return  $serial_no;
}

function paymentSuccessMail($subjects, $fullname, $emailname, $phone, $addressline1, $addressline2, $city, $state, $country, $createdat, $useridas, $transactionid, $paymentmode, $amount, $paybleamt, $fee, $gst, $remark)
{
  $data['details'] = ['subject' => $subjects, 'full_name' => $fullname, 'emails' => $emailname, 'phone' => $phone, 'addressline1' => $addressline1, 'addressline2' => $addressline2, 'city' => $city, 'state' => $state, 'country' => $country, 'createdat' => $createdat, 'user_id' => $useridas, 'transaction_id' => $transactionid, 'payment_mode' => $paymentmode, 'amount' => $amount, 'payble_amt' => $paybleamt, 'fee' => $fee, 'gst' => $gst, 'remark' => $remark];
  $data["email"] = $emailname;
  $data["title"] = $subjects;
  $pdf = PDF::loadView('emailtemp.pdf.pdf_stripe', $data);
  $postpdf = time() . '_' . $transactionid;
  $fileName =  $postpdf . '.' . 'pdf';
  $path = public_path('pdf/invoice');
  $finalpath = $path . '/' . $fileName;
  $pdf->save($finalpath);
  $body =  View('emailtemp.transactionrecipt', $data);
  $isHTML = true;
  $mail = new PHPMailer();
  $mail->IsSMTP();
  $mail->CharSet = 'UTF-8';
  $mail->Host       = env('MAIL_HOST', "");
  $mail->SMTPDebug  = 0;
  $mail->SMTPAuth   = true;
  $mail->Port       = env('MAIL_PORT', "");
  $mail->Username   = env('mail_username', "");
  $mail->Password   = env('MAIL_PASSWORD', "");
  $mail->setFrom(env('mail_from_address', ""), "7Search PPC");
  $mail->addAddress($emailname);
  $mail->SMTPSecure = 'ssl';
  $mail->isHTML($isHTML);
  $mail->Subject = $subjects;
  $mail->Body    = $body;
  $mail->addAttachment($finalpath);
  $mail->send();
  $msg = "Razorpay|$emailname|Success|$transactionid|$amount|usd";
  $msg1 = base64_encode($msg);
}
function getAdInfo($adid) {
  $data = json_decode(Redis::get('webdata'));
  $key = array_search($adid, array_column($data, 'ad_code'));
  return $data[$key];
}
function userUpdateProfile($getreq,$id,$utype){
  date_default_timezone_set('Asia/Kolkata');
  $timestamp = date("Y-m-d H:i:s");
  $profileLog = [];
  $uid = $id;
  $users = DB::table('users')->select('first_name','last_name','phone','phonecode','address_line1','address_line2','city','state','country','messenger_name','messenger_type','profile_lock','status','ac_verified','user_type')->where('uid',$uid)->where('status',0)->where('ac_verified',1)->first();
  if(!empty($getreq['password'])){
    $profileLog['reg_created']['previous'] = '-----';
    $profileLog['reg_created']['updated']  =  '-----';
    $profileLog['message'] =  "New User Profile Registered successfully.";
    ($utype == 1) ? $utype = 1 : $utype = 2;
    $data = json_encode($profileLog);
    DB::table('profile_logs')->insert(['uid' => $uid,'profile_data'=>$data,'user_type'=>$utype,'created_at'=>$timestamp]);
  }
  // elseif(!empty($getreq['uid'])){
  //   if($getreq['del_remark']){
  //     $profileLog['del_remark']['previous'] = $getreq['del_remark'];
  //     $profileLog['del_remark']['updated']  =  $getreq['del_remark'];
  //     $profileLog['message'] =  "Your account deletion request has been successfully processed.";
  //   }else{
  //     $profileLog['cancel_req']['previous'] = 'Account delete request.';
  //     $profileLog['cancel_req']['updated']  =  'Cancel account delete request.';
  //     $profileLog['message'] =  "Your account deletion request has been successfully withdrawn.";
  //   }
  //   ($utype == 1 ) ? $utype = 1 : $utype = 2;
  //   $data = json_encode($profileLog);
  //   DB::table('profile_logs')->insert(['uid' => $uid,'profile_data'=>$data,'user_type'=>$utype,'created_at'=>$timestamp]);
  // }
  else{
  if($users->first_name != $getreq['first_name']){
    $profileLog['first_name']['previous'] = $users->first_name;
    $profileLog['first_name']['updated']  =  $getreq['first_name'];
  }
  if($users->last_name != $getreq['last_name']){
    $profileLog['last_name']['previous'] = $users->last_name;
    $profileLog['last_name']['updated']  =  $getreq['last_name'];
  }
  if($users->phone != $getreq['phone']){
    $profileLog['phone']['previous'] = $users->phone;
    $profileLog['phone']['updated']  =  $getreq['phone'];
  }
  if($users->phonecode != $getreq['phonecode']){
    $profileLog['phonecode']['previous'] = $users->phonecode;
    $profileLog['phonecode']['updated']  =  $getreq['phonecode'];
  }
  if($users->address_line1 != $getreq['address_line1']){
    $profileLog['address_line1']['previous'] = $users->address_line1;
    $profileLog['address_line1']['updated']  =  $getreq['address_line1'];
  }
  if($users->address_line2 != $getreq['address_line2']){
    $profileLog['address_line2']['previous'] = $users->address_line2;
    $profileLog['address_line2']['updated']  =  $getreq['address_line2'];
  }if($users->city != $getreq['city']){
    $profileLog['city']['previous'] = $users->city;
    $profileLog['city']['updated']  =  $getreq['city'];
  }if($users->state != $getreq['state']){
    $profileLog['state']['previous'] = $users->state;
    $profileLog['state']['updated']  =  $getreq['state'];
  }if($users->country != $getreq['country']){
    $profileLog['country']['previous'] = $users->country;
    $profileLog['country']['updated']  =  $getreq['country'];
  }if($users->messenger_name != $getreq['messenger_name']){
    $profileLog['messenger_name']['previous'] = $users->messenger_name;
    $profileLog['messenger_name']['updated']  =  $getreq['messenger_name'];
  }if($users->messenger_type != $getreq['messenger_type']){
    $profileLog['messenger_type']['previous'] = $users->messenger_type;
    $profileLog['messenger_type']['updated']  =  $getreq['messenger_type'];
  }
  if(count($profileLog) > 0){
    $profileLog['message'] =  "Profile updated successfully.";
    $data = json_encode($profileLog);
    DB::table('profile_logs')->insert(['uid' => $uid,'profile_data'=>$data,'user_type'=>$utype,'created_at'=>$timestamp]);
  }
 }
}
function manageMinimumPayment(){
  // $data = DB::table('panel_customizations')->select('payment_min_amt')->first();
  // $minAmt = $data->payment_min_amt;
  $minAmt = 25;
  return $minAmt;
}

// manage wallet function
function getWalletAmount(){
  $walletAmt = 0;
  return $walletAmt;
}
function getPubWalletAmount(){
  $pubWalletAmt = 0;
  return $pubWalletAmt;
}