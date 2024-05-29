<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\AdImpression;
use App\Models\PubWebsite;
use App\Models\PubAdunit;
use App\Models\User;
use App\Models\UserCampClickLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PubDashboardUserController extends Controller
{

    public function ciaPubTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',

        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid =  $request->uid;
        $option =  $request->option;
        $userdata = User::where('uid', $uid)->first();
        if (empty($userdata)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        $cacheKeyClick = 'click_daily_stats_pub' . $date.$uid;
        $userdata = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid,$date) {
        return DB::select("SELECT usr.uid, usr.pub_wallet, im.impression, ck.click, FORMAT(ck.cl_amt+im.im_amt, 5) as amount
                                FROM `ss_users` usr,
                                (SELECT SUM(pub_imp_credit) im_amt, COUNT(id) impression FROM ss_ad_impressions imp WHERE imp.publisher_code='".$uid."' AND DATE(imp.created_at) >= DATE('".$date."') ) im,
                                (SELECT SUM(pub_click_credit) cl_amt, COUNT(id) click FROM ss_user_camp_click_logs clk WHERE clk.publisher_code='".$uid."' AND DATE(clk.created_at) >=DATE('".$date."') ) ck
                                WHERE usr.uid='".$uid."' limit 1"); 
        });
        
        if (empty($userdata)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
       
        
        $todayfreport = [
                            'click' => $userdata[0]->click, 
                            'impression' => $userdata[0]->impression, 
                            'amount' => $userdata[0]->amount
                        ];
      	//dd($todayfreport); 
        $ndate = date('d-m-Y');
        if ($option == 0) {
            $newDate = date("d-m-Y", strtotime($ndate . "-$option day"));
        } else {
            $ddd =  $option - 1;
            $newDate = date("d-m-Y", strtotime($ndate . "-$ddd day"));
        }
        $startDate = strtotime($newDate);
        $endDate = strtotime($ndate);
        for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {
            $xxxdate[] = date('jS M', $currentDate);
            $xxdate = date('Y-m-d', $currentDate);
            $count = UserCampClickLog::where('publisher_code', $uid)->whereDate('created_at', $xxdate)->count();
            $totalcampclicks[] = $count;
            $impcount = AdImpression::where('publisher_code', $uid)->whereDate('created_at', $xxdate)->count();
            $totalimpclicks[] = $impcount;
          
          	$clickamtcount = UserCampClickLog::where('publisher_code', $uid)->whereDate('created_at', $xxdate)->sum('pub_click_credit');
          
          	$impamtcount = AdImpression::where('publisher_code', $uid)->whereDate('created_at', $xxdate)->sum('pub_imp_credit');
          	$totalamtimpclicks[] = number_format($clickamtcount + $impamtcount, 3);
        }
        $maindata = array('data' => $todayfreport, 'date' => $xxxdate, 'click' => $totalcampclicks, 'impression' => $totalimpclicks, 'rev' => $totalamtimpclicks);
      	//print_r($maindata); exit; 
        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
      
        /* Device impressions and clicks query */
        $cacheKeyClick = 'click_daily_stats_pub_sqlcideviceval'.$date.$uid;
        $sqlcideviceval = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid,$date) {
        $sqlcidevice = "SELECT COUNT(id) as adn, 
        (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE publisher_code = '$uid' AND device_type='Desktop' AND Date(created_at) >= '$date') as desktop_imp, 
        (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE publisher_code = '$uid' AND device_type='Mobile' AND Date(created_at) >= '$date') as mobile_imp, 
        (SELECT COUNT(id) FROM `ss_ad_impressions` WHERE publisher_code = '$uid' AND device_type='Tablet' AND Date(created_at) >= '$date') as tablet_imp,
        (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE publisher_code = '$uid' AND device_type='Desktop' AND Date(created_at) >= '$date') as desktop_clk, 
        (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE publisher_code = '$uid' AND device_type='Mobile' AND Date(created_at) >= '$date') as mobile_clk,
        (SELECT COUNT(id) FROM `ss_user_camp_click_logs` WHERE publisher_code = '$uid' AND device_type='Tablet' AND Date(created_at) >= '$date') as tablet_clk
        FROM `ss_pub_adunits` WHERE uid = '$uid'";
        return DB::select($sqlcidevice);
        });
        /* Device impressions get */
        $tatimp = $sqlcideviceval[0]->desktop_imp + $sqlcideviceval[0]->mobile_imp + $sqlcideviceval[0]->tablet_imp;
        if ($tatimp == 0) {
            $prdesk2 = 0;
        } else {
            $prdesk2 = ($sqlcideviceval[0]->desktop_imp / $tatimp) * 100;
        }

        $prdesk = number_format($prdesk2, 2);
        $desktop['desktop'] = array('impression' => $sqlcideviceval[0]->desktop_imp, 'percent' => $prdesk);
        if ($tatimp == 0) {
            $prmobile2 = 0;
        } else {
            $prmobile2 = ($sqlcideviceval[0]->mobile_imp / $tatimp) * 100;
        }

        $prmobile = number_format($prmobile2, 2);
        $desktop['mobile'] = array('impression' => $sqlcideviceval[0]->mobile_imp, 'percent' => $prmobile);
        if ($tatimp == 0) {
            $prtablate2 = 0;
        } else {
            $prtablate2 = ($sqlcideviceval[0]->tablet_imp / $tatimp) * 100;
        }
        $prtablate = number_format($prtablate2, 2);
        $desktop['tablet'] = array('impression' => $sqlcideviceval[0]->tablet_imp, 'percent' => $prtablate);
      
      	/* Device clicks get */
      
      	$tatclk = $sqlcideviceval[0]->desktop_clk + $sqlcideviceval[0]->mobile_clk + $sqlcideviceval[0]->tablet_clk;
        if ($tatclk == 0) {
            $prdesk2 = 0;
        } else {
            $prdesk2 = ($sqlcideviceval[0]->desktop_clk / $tatclk) * 100;
        }

        $prdesk = number_format($prdesk2, 2);
        $desktopclk['desktop'] = array('click' => $sqlcideviceval[0]->desktop_clk, 'percent' => $prdesk);
        if ($tatclk == 0) {
            $prmobile2 = 0;
        } else {
            $prmobile2 = ($sqlcideviceval[0]->mobile_clk / $tatclk) * 100;
        }

        $prmobile = number_format($prmobile2, 2);
        $desktopclk['mobile'] = array('click' => $sqlcideviceval[0]->mobile_clk, 'percent' => $prmobile);
        if ($tatclk == 0) {
            $prtablate2 = 0;
        } else {
            $prtablate2 = ($sqlcideviceval[0]->tablet_clk / $tatclk) * 100;
        }
        $prtablate = number_format($prtablate2, 2);
        $desktopclk['tablet'] = array('click' => $sqlcideviceval[0]->tablet_clk, 'percent' => $prtablate);
      	
      	if ($sqlcideviceval) {
            $return['device_imp'] = $desktop;
          	$return['device_click'] = $desktopclk;
        }
        /* Countries impressions get */
        $cacheKeyClick = 'click_daily_stats_pub_countimp'.$date.$uid;
        $countimp = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid,$date) {
                    return DB::table('ad_impressions')
                    ->select('country', DB::raw('count(*) as total'))
                    ->where('publisher_code', $uid)
                    ->whereDate('created_at', '>=', $date)
                    ->groupBy('country')
                    ->orderBy('total', 'DESC')
                    ->limit(10)
                    ->get()->toArray();
              });


      	if ($countimp) {
            $return['country_imp'] = [
              "countries" =>  array_column($countimp, 'country'),
              "data" => array_column($countimp, 'total')
            ];
        }
      	else
        {
        	$return['country_imp'] = [
              "countries" =>  [],
              "data" => []
            ];
        }
      	
      	/* Countries clicks get */
          $cacheKeyClick = 'click_daily_stats_pub_countclk'.$date.$uid;
          $countclk = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid,$date) {
            return DB::table('user_camp_click_logs')
            ->select('country', DB::raw('count(*) as total'))
            ->where('publisher_code', $uid)
            ->whereDate('created_at', '>=', $date)
            ->groupBy('country')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->toArray();
          });

        if ($countclk) {	
              $return['country_click'] = [
                "countries" =>  array_column($countclk, 'country'),
                "data" => array_column($countclk, 'total')
              ];
          }
      	 else
         {
              $return['country_click'] = [
                "countries" =>  [],
                "data" => []
              ];
         }
      /* Get number of websites */
      $cacheKeyClick = 'click_daily_stats_pub_totalwebsite'.$date.$uid;
      $totalwebsite = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid,$date) {
        return DB::table('pub_websites')->where('uid', $uid)->where('status', '!=', 3)->where('status', '!=', 5)->where('status', '!=', 1)->where('trash', 0)->count();
      });
      $cacheKeyClick = 'click_daily_stats_pub_activewebsite'.$date.$uid;
      $activewebsite = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid) {
        return  DB::table('pub_websites')->where('uid', $uid)->where('status', 4)->where('trash', 0)->count();
      });
      $cacheKeyClick = 'click_daily_stats_pub_pendingwebsite'.$date.$uid;
      $pendingwebsite = Cache::remember($cacheKeyClick, now()->addHours(1), function () use ($uid) {
        return DB::table('pub_websites')->where('uid', $uid)->where('status', 6)->where('trash', 0)->count();
      });
    
     $web[] = array('total_website' => $totalwebsite, 'approved' => $activewebsite, 'pending' => $pendingwebsite);
      if($web)
      {
      	$return['website'] = $web;
      }
      
      /* Get number of adunites */
      $aduni = DB::table('pub_adunits')
            ->select('ad_type',DB::raw('count(id) as ads'))
            ->where('uid', $uid)
        	->where('trash', 0)
            ->where('status', 2)	
            ->groupBy('ad_type')
            ->orderBy('ad_type', 'ASC')
            ->get()->toArray();
      $totalad = array_sum(array_column($aduni, 'ads'));
            
      if($aduni)
      {
      	$return['adunit'] = $aduni;
        $return['total_adunit'] = $totalad;
      }
      	
        $wltPubAmt = getPubWalletAmount();
        $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($userdata[0]->pub_wallet, 2);
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
    public function cia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',

        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid =  $request->uid;
        $option =  $request->option;
        $userdetail = User::where('uid', $uid)->first();
        if (empty($userdetail)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }

        
        $userdata = DB::select("SELECT date(udate) as date, country, device_os, device_type, SUM(amount) amt, SUM(impressions) impression, SUM(clicks) click 
      						FROM ss_pub_stats imp WHERE imp.publisher_code='".$uid."' 
            				AND DATE(imp.udate) >= DATE('".$date."') GROUP BY imp.country, device_os, device_type, date(udate)");

      
        $dates = array_unique(array_column($userdata, 'date'));
        // $country = array_unique(array_column($userdata, 'country'));
        $imps = array_sum(array_column($userdata, 'impression'));
      	$clks = array_sum(array_column($userdata, 'click'));
      	$amts = array_sum(array_column($userdata, 'amt'));
      
      //  $imp = 0;
       // $clk = 0;
       // $amt = 0;
        // $countries = [];
        $device = [
                    "desktop" => [
                        "impression" => 0,
                    ],
                    "mobile" => [
                        "impression" => 0,
                    ],
                    "tablet" =>[
                        "impression" => 0,
                    ]
                ];
        
        $device2 = [
            "desktop" => [
                "click" => 0
            ],
            "mobile" => [
                "click" => 0
            ],
            "tablet" =>[
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
            
            // foreach($country as $con) {
            //     if($udata->country == $con) {
            //         if(array_key_exists($con, $countries)) {
            //             $countries[$con] = $countries[$con]+$udata->impression;
            //         } else {
            //             $countries[$con] = $udata->impression;
            //         }
            //     }
                
            // }
            
                if($udata->device_type == 'Mobile') {
                    $device['mobile']['impression'] = $device['mobile']['impression']+$udata->impression;
                    $device2['mobile']['click'] = $device2['mobile']['click']+$udata->click;
                } elseif($udata->device_type == 'Desktop') {
                    $device['desktop']['impression'] = $device['desktop']['impression']+$udata->impression;
                    $device2['desktop']['click'] = $device2['desktop']['click']+$udata->click;
                } elseif($udata->device_type == 'Tablet') {
                    $device['tablet']['impression'] = $device['tablet']['impression']+$udata->impression;
                    $device2['tablet']['click'] = $device2['tablet']['click']+$udata->click;
                }
                
            
        //    $imp = $imp+$udata->impression;
          //  $amt = $amt+$udata->im_amt;
            
            foreach($dates as $date) {
                
                if($udata->date == $date) {
                    
                    if(array_key_exists($date, $gdata)) {
                        $gdata[$date] = [
                            "date" => $date,
                            "imps" => $gdata[$date]['imps']+$udata->impression,
                            "click" => $gdata[$date]['click']+$udata->click,
                            "amt" => $gdata[$date]['amt']+$udata->amt
                        ];
                    } else {
                       $gdata[$date] = [
                            "date" => $date,
                            "imps" => $udata->impression,
                            "click" => $udata->click,
                            "amt" => $udata->amt
                        ];
                    }
                   
                    
                }
            }
                
        }
  

      if($imps != 0 )
      {
          $prc1 = ($device['mobile']['impression']/$imps) * 100;
          $device['mobile']['percent']  = number_format($prc1,2);
          $prc2 = ($device['desktop']['impression']/$imps) * 100;
          $device['desktop']['percent']  = number_format($prc2,2);
          $prc3 = ($device['tablet']['impression']/$imps) * 100;
          $device['tablet']['percent']  = number_format($prc3,2);
      }  
      else
      {
           $device['mobile']['percent'] = 0;
           $device['desktop']['percent']  = 0;
           $device['tablet']['percent']  = 0;
           
           
      }
      if($clks != 0 )
      {
      
          $prc4 = ($device2['mobile']['click']/$clks) * 100;
          $device2['mobile']['percent']  = number_format($prc4,2);
          $prc5 = ($device2['desktop']['click']/$clks) * 100;
          $device2['desktop']['percent']  = number_format($prc5,2);
          $prc6 = ($device2['tablet']['click']/$clks) * 100;
          $device2['tablet']['percent']  = number_format($prc6,2);
      }
      else
      {
           $device2['mobile']['percent']  = 0;
           $device2['desktop']['percent']  = 0;
           $device2['tablet']['percent']  = 0;
           
      }
              $todayfreport = [
                            'click' => $clks, 
                            'impression' => $imps, 
                            'amount' => number_format($amts, 5)
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
        $totalamt = [];
        
        $startDate = strtotime($newDate);
        $endDate = strtotime($ndate);
        
        for ($currentDate = $startDate; $currentDate <= $endDate; $currentDate += (86400)) {
            
            $xxdate = date('Y-m-d', $currentDate);
            $totaldate[] = $xxdate;
            
            if(in_array($xxdate, $dates)) {
                $uclick = 0;
                $uimp = 0;
                foreach($gdata as $imp) {
                    // print_r($imp);
                    if($imp['date'] == $xxdate) {
                        $uclick = $imp['click'];
                        $uimp = $imp['imps'];
                        $uamt = number_format($imp['amt'],5);
                    }
                }
                $totalcampclicks[] = $uclick;
                $totalcampimp[] = $uimp;
                $totalamt[] = $uamt;
            } else {
                $totalcampclicks[] = 0;
                $totalcampimp[] = 0;
                $totalamt[] = 0;
            }
        }
     
        $maindata = array('data' => $todayfreport, 'date' => $totaldate, 'click' => $totalcampclicks, 'impression' => $totalcampimp, 'rev' => $totalamt);
      
        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
        
        $return['device_imp'] = $device;
        $return['device_click'] = $device2;

        if ($option == 0) {
            $filterdate = \Carbon\Carbon::today()->subDays($option);
        } else {
            $beforedate =  $option - 1;
            $filterdate = \Carbon\Carbon::today()->subDays($beforedate);
        }
        
        $countimp = DB::table('pub_stats')
                    ->select('country', DB::raw('SUM(impressions) as total'))
                    ->where('publisher_code', $uid)
                    ->whereDate('udate', '>=', $filterdate)
                    ->groupBy('country')
                    ->orderBy('total', 'DESC')
                    ->limit(10)
                    ->get()->toArray();

      	if ($countimp) {
            $return['country_imp'] = [
              "countries" =>  array_column($countimp, 'country'),
              "data" => array_column($countimp, 'total')
            ];
        }
      	else
        {
        	$return['country_imp'] = [
              "countries" =>  [],
              "data" => []
            ];
        }
      	
      	

          
        /* Countries clicks get */
          $countclk = DB::table('pub_stats')
            ->select('country','udate', DB::raw('SUM(clicks) as total'))
            ->where('publisher_code', $uid)
            ->whereDate('udate', '>=', $filterdate)
            ->groupBy('country')
            ->orderBy('total', 'DESC')
            ->limit(10)
            ->get()->toArray();
        if ($countclk) {	
              $return['country_click'] = [
                "countries" =>  array_column($countclk, 'country'),
                "data" => array_column($countclk, 'total')
              ];
          }
      	 else
         {
              $return['country_click'] = [
                "countries" =>  [],
                "data" => []
              ];
         }
            
               
    $totalwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('trash', 0)->count();
    $holdwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 3)->where('trash', 0)->count();
    $activewebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 4)->where('trash', 0)->count();
    $suspendwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 5)->where('trash', 0)->count();
    $Unverifiedwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 1)->where('trash', 0)->count();
    
    $rejectwebsite =  DB::table('pub_websites')->where('uid', $uid)->where('status', 6)->where('trash', 0)->count();

    $web[] = array('total_website' => $totalwebsite, 'approved' => $activewebsite, 'reject' => $rejectwebsite, 'hold' => $holdwebsite, 'suspend' => $suspendwebsite ,'inreview'=>$Unverifiedwebsite);
     
      if($web)
      {
      	$return['website'] = $web;
      }
      
      /* Get number of adunites */
      $aduni = DB::table('pub_adunits')
            ->select('ad_type',DB::raw('count(id) as ads'))
            ->where('uid', $uid)
        	->where('trash', 0)
            ->where('status', 2)	
            ->groupBy('ad_type')
            ->orderBy('ad_type', 'ASC')
            ->get()->toArray();
      $totalad = array_sum(array_column($aduni, 'ads'));
            
      if($aduni)
      {
      	$return['adunit'] = $aduni;
        $return['total_adunit'] = $totalad;
      }
      
        $wltPubAmt = getPubWalletAmount();
        $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($userdetail->pub_wallet, 2);
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    
   
  
    
  
}
