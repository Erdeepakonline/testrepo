<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Publisher\PubPayoutMethod;
use App\Models\Publisher\PubUserPayoutMode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PubReportUserController extends Controller
{
    public function ad_reportTest(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'uid'       => 'required',
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }

        $uid = $request->uid;
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
      	$placement = $request->placement;
      	$country = $request->country;
      	$dmn = $request->domain;
        $nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));
		
      	$sql = DB::table('ad_impressions');
      
      	$qry = '';
        $qry1 = '';
        if(strlen($dmn) > 0)
        {
          $qry = " AND clk.website_id = ss_ad_impressions.website_id";
          $qry1 = " AND clk2.website_id = ss_ad_impressions.website_id ";
        }
      
      	$con_qry = '';
      	$con_qry1 = '';
        if(strlen($country) > 0)
        {
          $con_qry = " AND clk.country = ss_ad_impressions.country";
          $con_qry1 = " AND clk2.country = ss_ad_impressions.country ";
        }
      
      	$adunit_qry = '';
      	$adunit_qry1 = '';
        if(strlen($placement) > 0)
        {
          $adunit_qry = " AND clk.adunit_id = ss_ad_impressions.adunit_id";
          $adunit_qry1 = " AND clk2.adunit_id = ss_ad_impressions.adunit_id ";
        }
     
      	if($grpby == 'domain') {
           //echo $grpby; exit;
          $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'ad_impressions.website_id');
          $sql->join('pub_adunits', 'ad_impressions.adunit_id', '=', 'pub_adunits.ad_code');
          $sql->select("ad_impressions.ad_type", "ad_impressions.device_type", "ad_impressions.device_os", "ad_impressions.country", "pub_websites.site_url AS web","pub_adunits.ad_name","pub_adunits.ad_code",
                         DB::raw("DATE_FORMAT(ss_ad_impressions.created_at, '%d-%m-%Y') as created, count(ss_ad_impressions.id) as Imprs")
                         );
          
        } else {
          	$sql->join('pub_adunits', 'ad_impressions.adunit_id', '=', 'pub_adunits.ad_code');
        	$sql->select("ad_impressions.ad_type", "ad_impressions.device_type", "ad_impressions.device_os", "ad_impressions.country","pub_adunits.ad_name","pub_adunits.ad_code",
                      	DB::raw("DATE_FORMAT(ss_ad_impressions.created_at, '%d-%m-%Y') as created, count(ss_ad_impressions.id) as Imprs"));
        }
      
      	if($grpby == 'date') {
          
         $sql->selectRaw("(SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.publisher_code = '$uid' ".$con_qry." ".$adunit_qry." AND DATE(ss_ad_impressions.created_at) = DATE(clk.created_at)) as Clicks")
          ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.publisher_code = '$uid' $con_qry1 $adunit_qry1 
     	  AND DATE(ss_ad_impressions.created_at) = DATE(clk2.created_at)) + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
        }
       elseif($grpby == 'domain') {
         $sql->selectRaw("(SELECT COUNT(clk.id) FROM ss_user_camp_click_logs clk WHERE clk.publisher_code = '$uid' ".$con_qry." ".$adunit_qry." AND clk.website_id = ss_pub_websites.web_code AND DATE(clk.created_at) >= DATE('".$todate."') AND DATE(clk.created_at) <= DATE('".$nfromdate."') ) as Clicks")
          ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.publisher_code = '$uid' $con_qry1 $adunit_qry1 
     	  					AND clk2.website_id = ss_pub_websites.web_code AND DATE(clk2.created_at) >= DATE('".$todate."') AND DATE(clk2.created_at) <= DATE('".$nfromdate."')) + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
       }
       else {
          
          $sql->selectRaw("(SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.publisher_code = '$uid'
          AND DATE(clk.created_at) >= DATE('".$todate."') AND DATE(clk.created_at) <= DATE('".$nfromdate."')
          ".$qry." ".$con_qry." ".$adunit_qry." AND ss_ad_impressions.".$grpby." = clk.".$grpby." ) as Clicks")
          ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 
          WHERE clk2.publisher_code = '$uid' $qry1 $con_qry1 $adunit_qry1
          AND DATE(clk2.created_at) >= DATE('".$todate."') AND DATE(clk2.created_at) <= DATE('".$nfromdate."') 
          AND ss_ad_impressions.".$grpby." = clk2.".$grpby.") + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
      }
      
      
      $sql->where("ad_impressions.publisher_code", $uid)
        ->whereBetween("ad_impressions.created_at", [$todate, $nfromdate]);
      
      if(strlen($country) > 0 )
      {
          $sql->where('ad_impressions.country', $country);
      }
      
      if(strlen($dmn) > 0 )
      {
          $sql->where('ad_impressions.website_id', $dmn);
      }
      if(strlen($placement) > 0 )
      {
          $sql->where('ad_impressions.adunit_id', $placement);
      }
          		
      if($grpby == 'date') {
        $sql->groupByRaw('DATE(ss_ad_impressions.created_at)');
      }
      elseif($grpby == 'domain') {
        $sql->groupByRaw('ss_pub_websites.site_url');
      }
      else {
        $sql->groupByRaw($grpby);
      }
       
      $datas = $sql->orderBy('ad_impressions.created_at', 'DESC')->get();
      //print_r($datas); exit;
      $row = count($datas);
      
      if (!empty($datas)) {
        $totalclk = '0';
        $totalimp = '0';
        $totalamt = '0';
        $totalctr = '0';
        $totalavgcpc = '0';
        foreach ($datas as $vallue) {
          if ($vallue->Imprs == 0) {
            $vallue->CTR = 0;
          } else {
            $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
          }
          
          $newDate = $vallue->created;
          $vallue->created = $newDate;
          if ($vallue->Clicks == 0) {
            $vallue->AvgCPC = 0;
          } else {
            $vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);
          }
          $totalimp += $vallue->Imprs;
          $totalclk += $vallue->Clicks;
          $totalamt += $vallue->Totals;
          $vallue->Total = $vallue->Totals;
          unset($vallue->Totals);
        }
        //print_r($datas); exit;
        $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
        $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp): 0;
        $asdsdas = array('total_impression' => round($totalimp, 2), 'total_click' => round($totalclk, 2), 'total_amount' => round($totalamt, 5), 'total_ctr' => round($totalctr, 2), 'total_avgcpc' => round($totalavgcpc, 2));
        $userdata = User::where('uid', $uid)->first();
        $return['code']    		= 200;
        $return['data']    		= $datas;
        $return['total']    	= $asdsdas;
        $return['row']     		= $row;
        $wltPubAmt = getPubWalletAmount();
        $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($userdata->pub_wallet, 2);
        $return['message'] 		= 'Successfully';
      } else {
        $return['code']    = 100;
        $return['message'] = 'Something went wrong!';
      }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
    

    /**
    * Get ad report.
    *
    * @OA\Post(
    *     path="/api/pub/user/report",
    *     summary="Get ad report",
    *     tags={"Reports"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"uid", "to_date", "from_date", "group_by"},
    *                 @OA\Property(property="uid", type="string", description="User ID"),
    *                 @OA\Property(property="to_date", type="string", format="date", example="2024-03-23", description="To date"),
    *                 @OA\Property(property="from_date", type="string", format="date", example="2024-03-01", description="From date"),
    *                 @OA\Property(property="group_by", type="string", description="Group by"),
    *                 @OA\Property(property="placement", type="string", description="Placement"),
    *                 @OA\Property(property="country", type="string", example="USA", description="Country"),
    *                 @OA\Property(property="domain", type="string", example="example.com", description="Domain"),
    *                 @OA\Property(property="lim", type="integer", example=10, description="Limit"),
    *                 @OA\Property(property="page", type="integer", example=1, description="Page"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Publisher]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="device_type", type="string", example="Mobile"),
    *                     @OA\Property(property="device_os", type="string", example="Android"),
    *                     @OA\Property(property="country", type="string", example="USA"),
    *                     @OA\Property(property="web", type="string", example="example.com"),
    *                     @OA\Property(property="ad_name", type="string", example="Banner Ad"),
    *                     @OA\Property(property="ad_type", type="string", example="Banner"),
    *                     @OA\Property(property="ad_code", type="string", example="xyz123"),
    *                     @OA\Property(property="created", type="string", example="23-03-2024"),
    *                     @OA\Property(property="Imprs", type="integer", example=100),
    *                     @OA\Property(property="Clicks", type="integer", example=50),
    *                     @OA\Property(property="Total", type="number", format="float", example=1000.00),
    *                 ),
    *             ),
    *             @OA\Property(property="total", type="object",
    *                 @OA\Property(property="total_impression", type="integer", example=1000),
    *                 @OA\Property(property="total_click", type="integer", example=500),
    *                 @OA\Property(property="total_amount", type="number", format="float", example=5000.00),
    *                 @OA\Property(property="total_ctr", type="number", format="float", example=50.00),
    *                 @OA\Property(property="total_avgcpc", type="number", format="float", example=10.00),
    *             ),
    *             @OA\Property(property="row", type="integer", example=10),
    *             @OA\Property(property="wallet", type="number", format="float", example=1000.00),
    *             @OA\Property(property="message", type="string", example="Successfully"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Bad request",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=100),
    *             @OA\Property(property="error", type="object", example={"to_date": {"The to date field is required."}}),
    *             @OA\Property(property="message", type="string", example="Validation error!")
    *         )
    *     )
    * )
    */
    public function ad_report(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'uid'       => 'required',
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }

        $uid = $request->uid;
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
      	$placement = $request->placement;
      	$country = $request->country;
      	$dmn = $request->domain;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
        // $nfromdate = date('Y-m-d', strtotime($fromdate . ' + 1 days'));
		
      	$sql = DB::table('pub_stats');
      	if($grpby == 'domain') {
          $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
          $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
          $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web","pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code",
                        DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, SUM(ss_pub_stats.impressions) as Imprs, 
                        SUM(ss_pub_stats.clicks) as Clicks, IF(SUM(ss_pub_stats.amount) != 'NULL', FORMAT(SUM(ss_pub_stats.amount), 5), 0) as Totals")
                        );
          
        } else {

          $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
          $sql->select(
              "pub_stats.device_type", "pub_stats.device_os", "pub_stats.country",
              "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code",
              DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created"),
              DB::raw("SUM(ss_pub_stats.impressions) as Imprs"),
              DB::raw("SUM(ss_pub_stats.clicks) as Clicks"),
              DB::raw("IF(SUM(ss_pub_stats.amount) IS NOT NULL, FORMAT(SUM(ss_pub_stats.amount), 5), 0) as Totals"));
        }
      
      
      
      $sql->where("pub_stats.publisher_code", $uid)
        ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
      
      if(strlen($country) > 0 )
      {
          $sql->where('pub_stats.country', $country);
      }
      
      if(strlen($dmn) > 0 )
      {
          $sql->where('pub_stats.website_id', $dmn);
      }
      if(strlen($placement) > 0 )
      {
          $sql->where('pub_stats.adunit_id', $placement);
      }
          		
      if($grpby == 'date') {
        $sql->groupByRaw('DATE(ss_pub_stats.udate)');
      }
      elseif($grpby == 'domain') {
        $sql->groupByRaw('ss_pub_websites.site_url');
      }
      else {
        $sql->groupByRaw($grpby);
      }
      //$row   = $sql->count();
      $datascount = $sql->orderBy('pub_stats.udate', 'DESC')->get();
      $row   = count($datascount);
      $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
     
      //print_r($datas); exit;
      
      
      if (!empty($datas)) {
        $totalclk = '0';
        $totalimp = '0';
        $totalamt = '0';
        $totalctr = '0';
        $totalavgcpc = '0';
        foreach ($datas as $vallue) {
          if ($vallue->Imprs == 0) {
            $vallue->CTR = 0;
          } else {
            $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
          }
          
          $newDate = $vallue->created;
          $vallue->created = $newDate;
          if ($vallue->Clicks == 0) {
            $vallue->AvgCPC = 0;
          } else {
            $vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);
          }
          $totalimp += $vallue->Imprs;
          $totalclk += $vallue->Clicks;
          $totalamt += $vallue->Totals;
          $vallue->Total = $vallue->Totals;
          unset($vallue->Totals);
        }
        //print_r($datas); exit;
        // $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
        if($totalimp == 0)
        {
          $totalctr = 0;
        }
        else
        {
          $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
        }
        $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp): 0;
        $asdsdas = array('total_impression' => round($totalimp, 2), 'total_click' => round($totalclk, 2), 'total_amount' => round($totalamt, 5), 'total_ctr' => round($totalctr, 2), 'total_avgcpc' => round($totalavgcpc, 2));
        $userdata = User::where('uid', $uid)->first();
        $return['code']    		= 200;
        $return['data']    		= $datas;
        $return['total']    	= $asdsdas;
        $return['row']     		= $row;
        $wltPubAmt = getPubWalletAmount();
        $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($userdata->pub_wallet, 2);
        $return['message'] 		= 'Successfully';
      } else {
        $return['code']    = 100;
        $return['message'] = 'Something went wrong!';
      }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  

    /**
    * Get payout method list.
    *
    * @OA\Post(
    *     path="/api/pub/user/payoutmethodlist",
    *     summary="Get payout method list",
    *     tags={"Payout"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"uid"},  
    *                 @OA\Property(property="uid", type="string", description="User ID"),
    *             ),
    *         ),
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Publisher]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="integer", example=1, description="Payout method ID"),
    *                     @OA\Property(property="method_name", type="string", example="Bank Transfer", description="Payout method name"),
    *                     @OA\Property(property="image", type="string", example="http://example.com/images/bank_transfer.png", description="URL to the payout method image"),
    *                     @OA\Property(property="processing_fee", type="number", format="float", example=1.5, description="Processing fee for the payout method"),
    *                     @OA\Property(property="min_withdrawl", type="number", format="float", example=10.00, description="Minimum withdrawal amount for the payout method"),
    *                     @OA\Property(property="description", type="string", example="Transfer funds directly to your bank account", description="Description of the payout method"),
    *                     @OA\Property(property="display_name", type="string", example="Bank Transfer", description="Display name of the payout method"),
    *                     @OA\Property(property="user_opt", type="integer", example=0, description="User option for the payout method (0 - Not selected, 1 - Selected)"),
    *                 ),
    *             ),
    *             @OA\Property(property="wid_limit", type="string", example="Weekly withdrawal limit", description="Publisher's weekly withdrawal limit"),
    *             @OA\Property(property="pay_account_id", type="string", example="123456789", description="Publisher's payment account ID"),
    *             @OA\Property(property="payout_name", type="string", example="Bank Account", description="Publisher's payout account name"),
    *             @OA\Property(property="payout_id", type="integer", example=1, description="Publisher's payout method ID"),
    *             @OA\Property(property="bank_name", type="string", example="ABC Bank", description="Publisher's bank name"),
    *             @OA\Property(property="account_holder_name", type="string", example="John Doe", description="Publisher's account holder name"),
    *             @OA\Property(property="account_number", type="string", example="1234567890", description="Publisher's account number"),
    *             @OA\Property(property="ifsc_code", type="string", example="ABCD123456", description="Publisher's IFSC code"),
    *             @OA\Property(property="swift_code", type="string", example="SWFT123456", description="Publisher's SWIFT code"),
    *             @OA\Property(property="iban_code", type="string", example="IBAN123456", description="Publisher's IBAN code"),
    *             @OA\Property(property="minimum_amount", type="number", format="float", example=50.00, description="Publisher's minimum withdrawal amount"),
    *             @OA\Property(property="message", type="string", example="List fetched successfully!", description="Response message"),
    *         ),
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Bad request",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=100),
    *             @OA\Property(property="error", type="object", example={"uid": {"The uid field is required."}}),
    *             @OA\Property(property="message", type="string", example="Validation error!")
    *         )
    *     )
    * )
    */
  	public function payoutMethodList(Request $request)
    {

      $validator = Validator::make($request->all(), [
        'uid'       => 'required',
      ]);
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
      }
      
      $user = User::where('uid', $request->uid)->count();
      if(empty($user))
      {
      	$return['code'] = 102;
        $return['message'] = 'User not found!';
        return json_encode($return);
      }
      
      $listmode = PubUserPayoutMode::select('payout_id', 'payout_name', 'pay_account_id', 'pub_withdrawl_limit','bank_name','account_holder_name','account_number','ifsc_code','swift_code','iban_code','minimum_amount','status')->where('publisher_id', $request->uid)->where('status',1)->first();
      
      $listmethod = PubPayoutMethod::select('id', 'method_name', 'image', 'processing_fee', 'min_withdrawl', 'description', 'display_name')->where('status',0)->get();
      foreach($listmethod as $list)
      {
      	$list->image = config('app.url').'payout_methos'. '/' .$list->image;
        if(!empty($listmode))
        {
          if($listmode->payout_id == $list->id)
          {
          	$list->user_opt = 1;
          }
          else
          {
            $list->user_opt = 0;
          }
        }
        else
        {
        	$list->user_opt = 0;
        }
      	$data[] = $list;
      }
      
      
      if(!empty($data))
      {
      	$return['code'] = 200;
        $return['data'] = $data;
        $return['wid_limit'] = ($listmode) ? $listmode->pub_withdrawl_limit : '';
        $return['pay_account_id'] = ($listmode) ? $listmode->pay_account_id : '';
        $return['payout_name'] = ($listmode) ? $listmode->payout_name : '';
        $return['payout_id'] = ($listmode) ? $listmode->payout_id : '';
        $return['bank_name'] = ($listmode) ? $listmode->bank_name : '';
        $return['account_holder_name'] = ($listmode) ? $listmode->account_holder_name : '';
        $return['account_number'] = ($listmode) ? $listmode->account_number : '';
        $return['ifsc_code'] = ($listmode) ? $listmode->ifsc_code : '';
        $return['swift_code'] = ($listmode) ? $listmode->swift_code : '';
        $return['iban_code'] = ($listmode) ? $listmode->iban_code : '';
        $return['minimum_amount'] = ($listmode) ? $listmode->minimum_amount : '';
        $return['message'] = 'List fetched successfully!';
      }
      else
      {
      	$return['code'] = 101;
        $return['message'] = 'Data not found!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }
    public function wireTransferGatewayAdd(Request $request){
      $validator = Validator::make($request->all(), [
        'bank_name'       => 'required',
        'account_holder_name'   => 'required',
        'account_number' => 'required',
        'ifsc_code' => 'required',
        'minimum_amount' => 'required',
    ]);
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
    }
    DB::table('pub_user_payout_modes')->where('publisher_id', $request->publisher_id)->where('status', 1)->update(['status' => 0]);
    $updateData  =  PubUserPayoutMode::Where('publisher_id',$request->publisher_id)->where('payout_id', $request->payout_id)->first();
    if($updateData){
      $date = Carbon::now();
      $formatedDate = $date->format('Y-m-d H:i:s');
        $updateData->payout_id 			= $request->payout_id;
        $updateData->pay_account_id 		= $request->pay_account_id;
        $updateData->payout_name 		= $request->payout_name;
        $updateData->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $updateData->bank_name 			= $request->bank_name;
        $updateData->account_holder_name 			= $request->account_holder_name;
        $updateData->account_number 			= $request->account_number;
        $updateData->ifsc_code 			= $request->ifsc_code;
        $updateData->swift_code 			= $request->swift_code;
        $updateData->iban_code 			= $request->iban_code;
        $updateData->minimum_amount 			= $request->minimum_amount;
        $updateData->created_at 			= $request->formatedDate;
        $updateData->updated_at 			= $request->formatedDate;
        $updateData->status = 1;
        if($updateData->update())
        {
          $return['code'] = 200;
          $return['message'] = 'Updated Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
    }else{
      $date = Carbon::now();
      $formatedDate = $date->format('Y-m-d H:i:s');
      $values = array(
        'bank_name' => $request->bank_name,
        'payout_id' => $request->payout_id,
        'publisher_id' => $request->publisher_id,
        'payout_name' => $request->payout_name,
        'pay_account_id' => $request->pay_account_id,
        'pub_withdrawl_limit' => $request->pub_withdrawl_limit,
        'account_holder_name' => $request->account_holder_name,
        'account_number' => $request->account_number,
        'ifsc_code' => $request->ifsc_code,
        'swift_code' => $request->swift_code,
        'iban_code' => $request->iban_code,
        'minimum_amount' => $request->minimum_amount,
        'created_at' => $formatedDate,
        'updated_at' => $formatedDate,
         'status' =>1,
      );
      $datainsert  =  DB::table('pub_user_payout_modes')->insert($values);
      $msg = 'Insert Data Successfully!';
      if($datainsert){
        $return['code'] = 200;
        $return['message'] = $msg;
      }else{
        $return['code'] = 101;
        $return['message'] = 'Something went wrong!';
      }
    }
      return json_encode($return, JSON_NUMERIC_CHECK);
   }
}
