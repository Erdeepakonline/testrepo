<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AppUserDashboardControllers extends Controller
{
    public function dashboardcia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['msg'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid =  $request->uid;
        $userdetail = User::where('uid', $uid)->first();
        if (empty($userdetail)) {
            $return['code'] = 101;
            $return['msg'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
        $firstLogin = DB::table('transaction_logs')->where('advertiser_code', $uid)->count();
        if($firstLogin == 0){
            $llogin = 1;
        }else{
            $llogin = 2;
        } 

        $option =  $request->option;
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        
        $wlt = DB::table('users')->select('wallet')->where("uid", $uid)->first();
         
        $cacheKey = 'user_data_' . $uid . '_' . $date;
      $userdata = DB::select("SELECT date(udate) as date, country, device_os, device_type, SUM(amount) amt, SUM(impressions) impression, SUM(clicks) click 
      						FROM ss_adv_stats imp WHERE imp.advertiser_code='".$uid."' 
            				AND DATE(imp.udate) >= DATE('".$date."') GROUP BY imp.country, device_os, device_type, date(udate)");

        $dates = array_unique(array_column($userdata, 'date'));
        $country = array_unique(array_column($userdata, 'country'));
        $imps = array_sum(array_column($userdata, 'impression'));
      	$clks = array_sum(array_column($userdata, 'click'));
      	$amts = array_sum(array_column($userdata, 'amt'));
          if($imps == 0 || $clks == 0)
          {
              $ctrs = 0;
          }
          else
          {
              $ctrs = ($clks/ $imps) * 100;
          }
      //  $imp = 0;
       // $clk = 0;
       // $amt = 0;
        $countries = [];
        $device = [
                    "desktop" => [
                        "impression" => 0,
                        "click" => 0
                    ],
                    "mobile" => [
                        "impression" => 0,
                        "click" => 0
                    ],
                    "tablet" =>[
                        "impression" => 0,
                        "click" => 0
                    ]
                ];
        
        $os = [
                "linux" => [
                    "impression" => 0,
                    "click" => 0
                ],
                "windows" =>  [
                    "impression" => 0,
                    "click" => 0
                ],
                "android" =>  [
                    "impression" => 0,
                    "click" => 0
                ],
                "apple" =>  [
                    "impression" => 0,
                    "click" => 0
                ]
            ];
        
        $gdata = [];
        
        foreach($userdata as $udata) {
            
            foreach($country as $con) {
                if($udata->country == $con) {
                    if(array_key_exists($con, $countries)) {
                        $countries[$con] = $countries[$con]+$udata->impression;
                    } else {
                        $countries[$con] = $udata->impression;
                    }
                }
                
            }
            
                if($udata->device_type == 'Mobile') {
                    $device['mobile']['impression'] = $device['mobile']['impression']+$udata->impression;
                  	$device['mobile']['click'] = $device['mobile']['click']+$udata->click;
                } elseif($udata->device_type == 'Desktop') {
                    $device['desktop']['impression'] = $device['desktop']['impression']+$udata->impression;
                  	$device['desktop']['click'] = $device['desktop']['click']+$udata->click;
                } elseif($udata->device_type == 'Tablet') {
                    $device['tablet']['impression'] = $device['tablet']['impression']+$udata->impression;
                  	$device['tablet']['click'] = $device['tablet']['click']+$udata->click;
                }
                
                if($udata->device_os == 'linux') {
                    $os['linux']['impression'] = $os['linux']['impression']+$udata->impression;
                  	$os['linux']['click'] = $os['linux']['click']+$udata->click;
                } elseif($udata->device_os == 'windows') {
                    $os['windows']['impression'] = $os['windows']['impression']+$udata->impression;
                  	$os['windows']['click'] = $os['windows']['click']+$udata->click;
                } elseif($udata->device_os == 'android') {
                    $os['android']['impression'] = $os['android']['impression']+$udata->impression;
                  	$os['android']['click'] = $os['android']['click']+$udata->click;
                } elseif($udata->device_os == 'apple') {
                    $os['apple']['impression'] = $os['apple']['impression']+$udata->impression;
                  	$os['apple']['click'] = $os['apple']['click']+$udata->click;
                }
        //    $imp = $imp+$udata->impression;
          //  $amt = $amt+$udata->im_amt;
            foreach($dates as $date) {
                if($udata->date == $date) {
                    if(array_key_exists($date, $gdata)) {
                        $gdata[$date] = [
                            "date" => $date,
                            "imps" => $gdata[$date]['imps']+$udata->impression,
                            "click" => $gdata[$date]['click']+$udata->click
                        ];
                    } else {
                       $gdata[$date] = [
                            "date" => $date,
                            "imps" => $udata->impression,
                            "click" => $udata->click
                        ];
                    }
                }
            }       
        }
        if(!empty($imps)){
            $prc1 = ($device['mobile']['impression']/$imps) * 100;
            $device['mobile']['percent']  = number_format($prc1,2);
            $prc2 = ($device['desktop']['impression']/$imps) * 100;
            $device['desktop']['percent']  = number_format($prc2,2);
            $prc3 = ($device['tablet']['impression']/$imps) * 100;
            $device['tablet']['percent']  = number_format($prc3,2);
            
            $prc4 = ($os['linux']['impression']/$imps) * 100;
            $os['linux']['percent']  = number_format($prc4,2);
            $prc5 = ($os['windows']['impression']/$imps) * 100;
            $os['windows']['percent']  = number_format($prc5,2);
            $prc6 = ($os['android']['impression']/$imps) * 100;
            $os['android']['percent']  = number_format($prc6,2);
            $prc7 = ($os['apple']['impression']/$imps) * 100;
            $os['apple']['percent']  = number_format($prc7,2);
        }else{
            $device['mobile']['percent']  = 0;
            $device['desktop']['percent']  = 0;
            $device['tablet']['percent']  = 0;
            $os['linux']['percent']  = 0;
            $os['windows']['percent']  = 0;
            $os['android']['percent']  = 0;
            $os['apple']['percent']  = 0;
        }
        $todayfreport = [
                            'click' => $clks, 
                            'impression' => $imps, 
                            'ctr' => number_format($ctrs, 2),
                            'amount' => $amts ? number_format($amts, 2) : 0
                        ];  
     
        $ndate = date('d-m-Y');
        
        if ($option == 0) {
            $newDate = date("d-m-Y", strtotime($ndate . "-$option day"));
            $sdate = date('Y-m-d', strtotime("-$option day"));
        } else {
            $ddd =  $option - 1;
            $newDate = date("d-m-Y", strtotime($ndate . "-$ddd day"));
            $sdate = date('Y-m-d', strtotime("-$ddd day"));
        }
        
        $totalcampclicks = [];
        $totalcampimp = [];
        $totaldate = [];
        
        $startDate = strtotime($newDate);
        $endDate = strtotime($ndate);
        
        for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {
            
            $xxdate = date('Y-m-d', $currentDate);
            $totaldate[] = date('d', $currentDate);;
            
            if(in_array($xxdate, $dates)) {
                $uclick = 0;
                $uimp = 0;
                foreach($gdata as $imp) {
                    // print_r($imp);
                    if($imp['date'] == $xxdate) {
                        $uclick = $imp['click'];
                        $uimp = $imp['imps'];
                    }
                }
                $totalcampclicks[] = $uclick;
                $totalcampimp[] = $uimp;
            } else {
                $totalcampclicks[] = 0;
                $totalcampimp[] = 0;
            }
        }
     
        $maindata = array('data' => $todayfreport, 'date' => $totaldate, 'click' => $totalcampclicks, 'impression' => $totalcampimp);

        $sql = "Select cmp.campaign_id, cmp.campaign_name, IFNULL(SUM(imp.impressions),0) as totalimp, IFNULL(SUM(imp.amount),0) as impamt, IFNULL(SUM(imp.clicks),0) as totalclick, (0) as clickamt
        from ss_campaigns cmp LEFT JOIN ss_adv_stats imp ON cmp.campaign_id=imp.camp_id AND Date(imp.udate) >= '$date' WHERE cmp.status ='2' AND cmp.advertiser_code = '$uid' AND cmp.trash = '0' GROUP BY cmp.campaign_id ORDER BY totalclick DESC, totalimp DESC LIMIT 5";

        $datas = DB::select($sql);
        $arrayName = array('campaign_id' => 0, 'campaign_name' => 0, 'totalimp' => 0, 'impamt' => 0, 'totalclick' => 0, 'clickamt' => 0);
        if (empty($userdata)) {
            $return['code'] = 200;
            $return['option'] = "$option days";
            $return['graph'] = $maindata;
            $return['topcamp'] = self::dashboardCampdata($request->uid,$request->option);
            $return['device'] = $device;
            $return['os'] = $os;           
            $return['country'] = [];
            $return['msg'] = 'No record found ! ';
            $wltAmt = getWalletAmount();
            $return['wallet']   = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3);
            $return['firstlogin']    = $llogin;
            return json_encode($return);
        }
        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
        $return['topcamp'] = self::dashboardCampdata($request->uid,$request->option);
        $return['device'] = $device;
        $return['os'] = $os;
        arsort($countries);
        foreach($countries as $key => $val) {
            $cont[] = [
                    "country" => $key,
                    "total" => $val
                ];
        }
        $return['country'] = array_slice($cont, 0, 6);
        $wltAmt = getWalletAmount();
        $return['wallet']   = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3);
          $return['firstlogin']    = $llogin;
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public static function dashboardCampdata($uid,$option)
    {
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        $sql = "Select cmp.campaign_id, cmp.campaign_name, IFNULL(SUM(imp.impressions),0) as totalimp, IFNULL(SUM(imp.amount),0) as impamt, IFNULL(SUM(imp.clicks),0) as totalclick, (0) as clickamt
        from ss_campaigns cmp LEFT JOIN ss_adv_stats imp ON cmp.campaign_id=imp.camp_id AND Date(imp.udate) >= '$date' WHERE cmp.status ='2' 
        AND cmp.advertiser_code = '$uid' AND cmp.trash = '0' GROUP BY cmp.campaign_id ORDER BY impamt DESC LIMIT 5";
        
        $datas = DB::select($sql);
        
        
        if ($datas) {
            return $datas;
        } else {
            $arrayName[] = array('campaign_id' => 0, 'campaign_name' => 0, 'totalimp' => 0, 'impamt' => 0, 'totalclick' => 0, 'clickamt' => 0);
            return $arrayName;
        }
    }
}
