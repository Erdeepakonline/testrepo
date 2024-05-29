<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserDashboardController extends Controller
{
    /**
    * Get campaign intelligence analysis.
    *
    * @OA\Post(
    *     path="/api/user/dashboard/cia",
    *     summary="Get Campaign Intelligence Analysis",
    *     tags={"Dashboard"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *              required={"uid"},
    *              @OA\Property(property="uid", type="string", description="User ID"),
    *              @OA\Property(property="option", type="integer", description="Option"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [Advertiser]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="option", type="string", description="Selected option"),
    *             @OA\Property(property="graph", type="object",
    *                 @OA\Property(property="data", type="object",
    *                     @OA\Property(property="click", type="integer", example=100),
    *                     @OA\Property(property="impression", type="integer", example=1000),
    *                     @OA\Property(property="ctr", type="number", format="float", example=10.00),
    *                     @OA\Property(property="amount", type="number", format="float", example=100.00)
    *                 ),
    *                 @OA\Property(property="date", type="array",
    *                     @OA\Items(type="string", example="2024-03-23")
    *                 ),
    *                 @OA\Property(property="click", type="array",
    *                     @OA\Items(type="integer", example=100)
    *                 ),
    *                 @OA\Property(property="impression", type="array",
    *                     @OA\Items(type="integer", example=1000)
    *                 )
    *             ),
    *             @OA\Property(property="device", type="object",
    *                 @OA\Property(property="mobile", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=50.00)
    *                 ),
    *                 @OA\Property(property="desktop", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=30.00)
    *                 ),
    *                 @OA\Property(property="tablet", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=20.00)
    *                 )
    *             ),
    *             @OA\Property(property="os", type="object",
    *                 @OA\Property(property="linux", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=50.00)
    *                 ),
    *                 @OA\Property(property="windows", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=30.00)
    *                 ),
    *                 @OA\Property(property="android", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=15.00)
    *                 ),
    *                 @OA\Property(property="apple", type="object",
    *                     @OA\Property(property="impression", type="integer", example=100),
    *                     @OA\Property(property="click", type="integer", example=10),
    *                     @OA\Property(property="percent", type="number", format="float", example=5.00)
    *                 )
    *             ),
    *             @OA\Property(property="country", type="array",
    *                 @OA\Items(type="object",
    *                     @OA\Property(property="country", type="string", example="US"),
    *                     @OA\Property(property="total", type="integer", example=1000)
    *                 )
    *             ),
    *             @OA\Property(property="wallet", type="number", format="float", example=100.00),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Bad request",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=400),
    *             @OA\Property(property="error", type="object", example={"uid": {"The uid field is required."}}),
    *             @OA\Property(property="message", type="string", example="Valitation error!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="User not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=404),
    *             @OA\Property(property="message", type="string", example="User Not Found ! Please Valid User ID")
    *         )
    *     ),
    *     @OA\Response(
    *         response=201,
    *         description="No record found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="message", type="string", example="No record found !")
    *         )
    *     ),
    * )
    */
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
        $userdetail = User::where('uid', $uid)->first();
        if (empty($userdetail)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
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
        
        if (empty($userdata)) {
            $return['code'] = 200;
            $return['message'] = 'No record found ! ';
            $wltAmt = getWalletAmount();
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3, '.', '');
            return json_encode($return);
        }
        
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
       
        $todayfreport = [
                            'click' => $clks, 
                            'impression' => $imps, 
'ctr' => number_format($ctrs, 2),
                            'amount' => number_format($amts, 2)
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
            $totaldate[] = $xxdate;
            
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
      
        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
        
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
        $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3, '.', '');
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    
    public function ciaTest(Request $request)
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
        $userdetail = User::where('uid', $uid)->first();
        if (empty($userdetail)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found ! Please Valid User ID ';
            return json_encode($return);
        }
        $option =  $request->option;
        
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        
        $wlt = DB::table('users')->select('wallet')->where("uid", $uid)->first();
        
        $userdata = DB::select("SELECT date(created_at) as date, country, device_os, device_type, SUM(amount) im_amt, COUNT(id) impression FROM ss_ad_impressions imp WHERE imp.advertiser_code='".$uid."' 
                    AND DATE(imp.created_at) >= DATE('".$date."') GROUP BY imp.country, device_os, device_type, date(created_at)"); 
        $userdata2 = DB::select("SELECT date(created_at) as date, device_os, device_type, SUM(amount) im_amt, COUNT(id) click FROM ss_user_camp_click_logs imp WHERE imp.advertiser_code='".$uid."' 
                     AND DATE(imp.created_at) >= DATE('".$date."') GROUP BY device_os, device_type, date(created_at)"); 
        
        $dates = array_unique(array_column($userdata, 'date'));
        $country = array_unique(array_column($userdata, 'country'));
        $imps = array_sum(array_column($userdata, 'impression'));
        
        $imp = 0;
        $clk = 0;
        $amt = 0;
        
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
                    // echo $con['country'];
                    if(array_key_exists($con, $countries)) {
                        $countries[$con] = $countries[$con]+$udata->impression;
                    } else {
                        $countries[$con] = $udata->impression;
                    }
                }
                
            }
            
                if($udata->device_type == 'Mobile') {
                    $device['mobile']['impression'] = $device['mobile']['impression']+$udata->impression;
                } elseif($udata->device_type == 'Desktop') {
                    $device['desktop']['impression'] = $device['desktop']['impression']+$udata->impression;
                } elseif($udata->device_type == 'Tablet') {
                    $device['tablet']['impression'] = $device['tablet']['impression']+$udata->impression;
                }
                
                if($udata->device_os == 'linux') {
                    $os['linux']['impression'] = $os['linux']['impression']+$udata->impression;
                } elseif($udata->device_os == 'windows') {
                    $os['windows']['impression'] = $os['windows']['impression']+$udata->impression;
                } elseif($udata->device_os == 'android') {
                    $os['android']['impression'] = $os['android']['impression']+$udata->impression;
                } elseif($udata->device_os == 'apple') {
                    $os['apple']['impression'] = $os['apple']['impression']+$udata->impression;
                }
            $imp = $imp+$udata->impression;
            $amt = $amt+$udata->im_amt;
            
            foreach($dates as $date) {
                
                if($udata->date == $date) {
                    
                    if(array_key_exists($date, $gdata)) {
                        $gdata[$date] = [
                            "date" => $date,
                            "imps" => $gdata[$date]['imps']+$udata->impression,
                            "click" => 0
                        ];
                    } else {
                       $gdata[$date] = [
                            "date" => $date,
                            "imps" => $udata->impression,
                            "click" => 0
                        ];
                    }
                   
                    
                }
            }
                
        }
        
        
        
        foreach($userdata2 as $udata2) {
            
            foreach($dates as $date) {
                
                if($udata2->date == $date) {
                    
                    $gdata[$date]["click"] = $gdata[$date]['click']+$udata2->click;
                    
                }
            }
                
                if($udata2->device_type == 'Mobile') {
                    $device['mobile']['click'] = $device['mobile']['click']+$udata2->click;
                } elseif($udata2->device_type == 'Desktop') {
                    $device['desktop']['click'] = $device['desktop']['click']+$udata2->click;
                } elseif($udata2->device_type == 'Tablet') {
                    $device['tablet']['click'] = $device['tablet']['click']+$udata2->click;
                }
                
                if($udata2->device_os == 'linux') {
                    $os['linux']['click'] = $os['linux']['click']+$udata2->click;
                } elseif($udata2->device_os == 'windows') {
                    $os['windows']['click'] = $os['windows']['click']+$udata2->click;
                } elseif($udata2->device_os == 'android') {
                    $os['android']['click'] = $os['android']['click']+$udata2->click;
                } elseif($udata2->device_os == 'apple') {
                    $os['apple']['click'] = $os['apple']['click']+$udata2->click;
                }
            $clk = $clk+$udata2->click;
            $amt = $amt+$udata2->im_amt;
        }
        
        
        // print_r($gdata);
        // exit;
        
        if (empty($userdata)) {
            $return['code'] = 200;
            $return['message'] = 'No record found ! ';
            $wltAmt = getWalletAmount();
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3, '.', '');
            return json_encode($return);
        }
        
        
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
       
            
        $todayfreport = [
                            'click' => $clk, 
                            'impression' => $imp, 
                            'amount' => number_format($amt, 2)
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
            $totaldate[] = $xxdate;
            
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
      
        $return['code'] = 200;
        if ($option == 0) {
            $return['option'] = "Today";
        } else {
            $return['option'] = "$option days";
        }
        $return['graph'] = $maindata;
        
        $return['device'] = $device;
        $return['os'] = $os;
        
        foreach($countries as $key => $val) {
            $cont[] = [
                    "country" => $key,
                    "total" => $val
                ];
        }
        
        $return['country'] = $cont;
        $wltAmt = getWalletAmount();
        $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($wlt->wallet, 3, '.', '');
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    

    /**
    * Get dashboard campaign data.
    *
    * @OA\Post(
    *     path="/api/user/dashboard/campdata",
    *     summary="Get Dashboard Campaign Data",
    *     tags={"Dashboard"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                required={"uid"},
*                    @OA\Property(property="uid", type="string", description="User ID"),
    *                @OA\Property(property="option", type="integer", description="Option"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(name="x-api-key", in="header", required=true, description="x-api-key [Advertiser]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="topcamp", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="campaign_id", type="integer", example=1),
    *                     @OA\Property(property="campaign_name", type="string", example="Campaign 1"),
    *                     @OA\Property(property="totalimp", type="integer", example=100),
    *                     @OA\Property(property="impamt", type="integer", example=1000),
    *                     @OA\Property(property="totalclick", type="integer", example=50),
    *                     @OA\Property(property="clickamt", type="integer", example=0),
    *                 )
    *             ),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Bad request",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=400),
    *             @OA\Property(property="error", type="object", example={"uid": {"The uid field is required."}}),
    *             @OA\Property(property="message", type="string", example="Valitation error!")
    *         )
    *     ),
    * )
    */
    public function dashboardCampdata(Request $request)
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
        
        if ($option == 0) {
            $date = \Carbon\Carbon::today()->subDays($option);
        } else {
            $ddds =  $option - 1;
            $date = \Carbon\Carbon::today()->subDays($ddds);
        }
        
 
 
        // ======================================================================//
        // =====================================================================//
        
        $sql = "Select cmp.campaign_id, cmp.campaign_name, IFNULL(SUM(imp.impressions),0) as totalimp, IFNULL(SUM(imp.amount),0) as impamt, IFNULL(SUM(imp.clicks),0) as totalclick, (0) as clickamt
        from ss_campaigns cmp LEFT JOIN ss_adv_stats imp ON cmp.campaign_id=imp.camp_id AND Date(imp.udate) >= '$date' WHERE cmp.status ='2' 
        AND cmp.advertiser_code = '$uid' AND cmp.trash = '0' GROUP BY cmp.campaign_id ORDER BY impamt DESC LIMIT 5";
        
        $datas = DB::select($sql);
        
        
        if ($datas) {
            $return['topcamp'] = $datas;
        } else {
            $arrayName = array('campaign_id' => 0, 'campaign_name' => 0, 'totalimp' => 0, 'impamt' => 0, 'totalclick' => 0, 'clickamt' => 0);
            $return['topcamp'] = $arrayName;
        }
        

    //   	$return['wallet'] = number_format($wlt->wallet, 3, '.', '');
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
