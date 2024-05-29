<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserCampClickLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PubReportAdminController extends Controller
{
    public function adReportNewTesting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //'uid'       => 'required',
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
          $qry = " AND clk.website_id = ss_ad_impressions.website_id ";
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

        if ($grpby == 'domain') {
            $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'ad_impressions.website_id');
          	$sql->join('pub_adunits', 'ad_impressions.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->select(
                "ad_impressions.ad_type",
              	"ad_impressions.adunit_id",
              	"ad_impressions.device_type",
                "ad_impressions.device_os",
                "ad_impressions.country",
                "pub_websites.site_url AS web",
              	"pub_adunits.ad_name",
                DB::raw("DATE_FORMAT(ss_ad_impressions.created_at, '%d-%m-%Y') as created, count(ss_ad_impressions.id) as Imprs")
            );
        } else {
          	$sql->join('pub_adunits', 'ad_impressions.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->select("ad_impressions.ad_type", "ad_impressions.adunit_id", "ad_impressions.device_type", "ad_impressions.device_os", "ad_impressions.country", "pub_adunits.ad_name",
            DB::raw("DATE_FORMAT(ss_ad_impressions.created_at, '%d-%m-%Y') as created, count(ss_ad_impressions.id) as Imprs"));
        }

        if ($grpby == 'date') {
			$sql->selectRaw("(SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE clk.website_id = ss_ad_impressions.website_id AND clk.publisher_code != 'NULL' $con_qry $adunit_qry AND DATE(ss_ad_impressions.created_at) = DATE(clk.created_at)) as Clicks")
                ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.website_id = ss_ad_impressions.website_id $con_qry1 $adunit_qry1
     	  AND DATE(ss_ad_impressions.created_at) = DATE(clk2.created_at)) + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
        } elseif ($grpby == 'domain') {
            $sql->selectRaw("(SELECT COUNT(clk.id) FROM ss_user_camp_click_logs clk WHERE clk.publisher_code != 'NULL' $con_qry $adunit_qry AND clk.website_id = ss_pub_websites.web_code AND DATE(clk.created_at) >= DATE('" . $todate . "') AND DATE(clk.created_at) <= DATE('" . $nfromdate . "') ) as Clicks")
                ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 WHERE clk2.website_id = ss_ad_impressions.website_id $con_qry1 $adunit_qry1 
     	  					AND clk2.website_id = ss_pub_websites.web_code AND DATE(clk2.created_at) >= DATE('" . $todate . "') AND DATE(clk2.created_at) <= DATE('" . $nfromdate . "')) + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
        } else {
            $sql->selectRaw("(SELECT COUNT(id) FROM ss_user_camp_click_logs clk WHERE DATE(clk.created_at) >= DATE('" . $todate . "') AND DATE(clk.created_at) <= DATE('" . $nfromdate . "') ".$qry." $con_qry $adunit_qry AND ss_ad_impressions." . $grpby . " = clk." . $grpby . " ) as Clicks")
                ->selectRaw("(SELECT IF(SUM(clk2.pub_click_credit) != 'NULL', FORMAT(SUM(clk2.pub_click_credit),5), 0) FROM ss_user_camp_click_logs clk2 WHERE DATE(clk2.created_at) >= DATE('" . $todate . "') AND DATE(clk2.created_at) <= DATE('" . $nfromdate . "') ".$qry1." $con_qry1 $adunit_qry1 AND ss_ad_impressions." . $grpby . " = clk2." . $grpby . ") + FORMAT(SUM(ss_ad_impressions.pub_imp_credit),5) as Totals");
        }
        $sql->where('ad_impressions.publisher_code', '!=', 'NULL')
            ->whereBetween("ad_impressions.created_at", [$todate, $nfromdate]);

        if (strlen($country) > 0) {
            $sql->where('ad_impressions.country', $country);
        }

        if (strlen($dmn) > 0) {
            $sql->where('ad_impressions.website_id', $dmn);
        }
        if (strlen($placement) > 0) {
            $sql->where('ad_impressions.adunit_id', $placement);
        }

        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_ad_impressions.created_at)');
        } elseif ($grpby == 'domain') {
            $sql->groupByRaw('ss_pub_websites.site_url');
        } else {
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
                $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
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
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
          	
            $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
          //echo $totalavgcpc; exit;
            $asdsdas = array('total_impression' => round($totalimp, 3), 'total_click' => round($totalclk, 3), 'total_amount' => round($totalamt, 5), 'total_ctr' => round($totalctr, 3), 'total_avgcpc' => round($totalavgcpc, 3));
            $return['code']       = 200;
            $return['data']       = $datas;
            $return['total']      = $asdsdas;
            $return['row']        = $row;
            $return['message']    = 'Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function adReport(Request $request)
    {
        // $validator = Validator::make($request->all(), [
        //     'to_date'   => 'required|date_format:Y-m-d',
        //     'from_date' => 'required|date_format:Y-m-d',
        // ]);
        // if ($validator->fails()) {
        //     $return['code'] = 100;
        //     $return['error'] = $validator->errors();
        //     $return['message'] = 'Validation error!';
        //     return json_encode($return);
        // }
        // $todate = $request->to_date;
        // $fromdate = $request->from_date;
        // $grpby = $request->group_by;
        // $placement = $request->placement;
        // $country = $request->country;
        // $dmn = $request->domain;
        // $limit = $request->lim;
        // $page = $request->page;
        // $pg = $page - 1;
        // $start = ($pg > 0) ? $limit * $pg : 0;
        // $sql = DB::table('pub_stats');
      	// if($grpby == 'domain') {
        //   $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
        //   $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
        //   $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
        //   $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web","pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code","users.email","users.uid",
        //     DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs, 
        //     IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) != 'NULL', SUM(ss_pub_stats.amount), 0) as Totals")
        //     );
        // } else {
        //   	$sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
        //     $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
        // 	$sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country","pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code","users.email","users.uid",
        //               	DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs,
        //               	IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) != 'NULL', SUM(ss_pub_stats.amount), 0) as Totals"));
        // }
        // $sql->where('pub_stats.publisher_code', '!=', 'NULL')
        //     ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
        // if (strlen($country) > 0) {
        //     $sql->where('pub_stats.country', $country);
        // }
        // if (strlen($dmn) > 0) {
        //     $sql->where('pub_stats.website_id', $dmn);
        // }
        // if (strlen($placement) > 0) {
        //     $sql->where('pub_stats.adunit_id', $placement);
        // }
        // if ($grpby == 'date') {
        //     $sql->groupByRaw('DATE(ss_pub_stats.udate)');
        // } elseif ($grpby == 'domain') {
        //     $sql->groupByRaw('ss_pub_websites.site_url');
        // } else {
        //     $sql->groupByRaw($grpby);
        // }
        // $rows = count($sql->get());
        // $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
        // $row = count($datas);
        // if (!empty($datas)) {
        //     $totalclk = '0';
        //     $totalimp = '0';
        //     $totalamt = '0';
        //     $totalctr = '0';
        //     $totalavgcpc = '0';
        //     foreach ($datas as $vallue) {
        //         if ($vallue->Imprs == 0) {
        //             $vallue->CTR = 0;
        //         } else {
        //             $vallue->CTR = round($vallue->Clicks / $vallue->Imprs * 100, 2);
        //         }
        //         $newDate = $vallue->created;
        //         $vallue->created = $newDate;
        //         if ($vallue->Clicks == 0) {
        //             $vallue->AvgCPC = 0;
        //         } else {
        //             $vallue->AvgCPC = round($vallue->Totals / $vallue->Clicks, 2);
        //         }
        //         $totalimp += $vallue->Imprs;
        //         $totalclk += $vallue->Clicks;
        //         $totalamt += $vallue->Totals;
        //         $vallue->Total = $vallue->Totals;
        //         unset($vallue->Totals);
        //     }
        //     $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
        //     $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
        //     $asdsdas = array('total_impression' => round($totalimp, 3), 'total_click' => round($totalclk, 3), 'total_amount' => round($totalamt, 5), 'total_ctr' => round($totalctr, 3), 'total_avgcpc' => round($totalavgcpc, 3));
        //     $return['code']       = 200;
        //     $return['data']       = $datas;
        //     $return['total']      = $asdsdas;
        //     $return['row']        = $row;
        //     $return['rows']        = $rows;
            
        //     $return['message']    = 'Successfully'; 
        // } else {
        //     $return['code']    = 100;
        //     $return['message'] = 'Something went wrong!';
        // }
        // return json_encode($return, JSON_NUMERIC_CHECK);

        $validator = Validator::make($request->all(), [
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
        $placement = $request->placement;
        $country = $request->country;
        $dmn = $request->domain;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        
        $sql = DB::table('pub_stats');
        //     adunit_id pub_stats  pub_websites
        if ($grpby == 'domain') {
            $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs, 
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals")
            );
        } else {
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs,
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals"));
        }
        
        $sql->where('pub_stats.publisher_code', '!=', 'NULL')
            ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
        
        if (strlen($country) > 0) {
            $sql->where('pub_stats.country', $country);
        }
        
        if (strlen($dmn) > 0) {
            $sql->where('pub_stats.website_id', $dmn);
        }
        
        if (strlen($placement) > 0) {
            $sql->where('pub_stats.adunit_id', $placement);
        }
        
        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_pub_stats.udate)');
        } elseif ($grpby == 'domain') {
            $sql->groupByRaw('ss_pub_websites.site_url');
        } elseif ($grpby == 'adunit_id') {
            $sql->groupByRaw('ss_pub_stats.adunit_id');
        } else {
            $sql->groupByRaw($grpby);
        }
        
        $rows = $sql->get()->count();
        $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
        $row = count($datas);
        
        if (!empty($datas)) {
            $totalclk = 0;
            $totalimp = 0;
            $totalamt = 0;
            $totalctr = 0;
            $totalavgcpc = 0;
        
            foreach ($datas as $value) {
                if ($value->Imprs == 0) {
                    $value->CTR = 0;
                } else {
                    $value->CTR = round($value->Clicks / $value->Imprs * 100, 2);
                }
        
                $newDate = $value->created;
                $value->created = $newDate;
        
                if ($value->Clicks == 0) {
                    $value->AvgCPC = 0;
                } else {
                    $value->AvgCPC = round($value->Totals / $value->Clicks, 2);
                }
        
                $totalimp += $value->Imprs;
                $totalclk += $value->Clicks;
                $totalamt += $value->Totals;
                $value->Total = $value->Totals;
                unset($value->Totals);
            }
        
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
            $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
        
            $totals = [
                'total_impression' => round($totalimp, 3),
                'total_click' => round($totalclk, 3),
                'total_amount' => round($totalamt, 5),
                'total_ctr' => round($totalctr, 3),
                'total_avgcpc' => round($totalavgcpc, 3)
            ];
        
            $return['code'] = 200;
            $return['data'] = $datas;
            $return['total'] = $totals;
            $return['row'] = $row;
            $return['rows'] = $rows;
            $return['message'] = 'Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }



    public function adReportTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to_date'   => 'required|date_format:Y-m-d',
            'from_date' => 'required|date_format:Y-m-d',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
        $todate = $request->to_date;
        $fromdate = $request->from_date;
        $grpby = $request->group_by;
        $placement = $request->placement;
        $country = $request->country;
        $dmn = $request->domain;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        
        $sql = DB::table('pub_stats');
        //     adunit_id pub_stats  pub_websites pub_stats
        if ($grpby == 'domain') {
            $sql->leftJoin('pub_websites', 'pub_websites.web_code', '=', 'pub_stats.website_id');
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_websites.site_url AS web", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs, 
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals")
            );
        } else {
            $sql->join('pub_adunits', 'pub_stats.adunit_id', '=', 'pub_adunits.ad_code');
            $sql->join('users', 'pub_stats.publisher_code', '=', 'users.uid');
            $sql->select("pub_stats.device_type", "pub_stats.device_os", "pub_stats.country", "pub_adunits.ad_name", "pub_adunits.ad_type", "pub_adunits.ad_code", "users.email", "users.uid",
                DB::raw("DATE_FORMAT(ss_pub_stats.udate, '%d-%m-%Y') as created, IFNULL(SUM(ss_pub_stats.impressions),0) as Imprs,
                IFNULL(SUM(ss_pub_stats.clicks),0) as Clicks, IF(SUM(ss_pub_stats.amount) IS NOT NULL, SUM(ss_pub_stats.amount), 0) as Totals"));
        }
        
        $sql->where('pub_stats.publisher_code', '!=', 'NULL')
            ->whereBetween("pub_stats.udate", [$todate, $fromdate]);
        
        if (strlen($country) > 0) {
            $sql->where('pub_stats.country', $country);
        }
        
        if (strlen($dmn) > 0) {
            $sql->where('pub_stats.website_id', $dmn);
        }
        
        if (strlen($placement) > 0) {
            $sql->where('ss_pub_stats.adunit_id', $placement);
        }
        
        if ($grpby == 'date') {
            $sql->groupByRaw('DATE(ss_pub_stats.udate)');
        } elseif ($grpby == 'domain') {
            $sql->groupByRaw('ss_pub_websites.site_url');
        } elseif ($grpby == 'adunit_id') {
            $sql->groupByRaw('ss_pub_stats.adunit_id');
        } else {
            $sql->groupByRaw($grpby);
        }
        
        $rows = $sql->get()->count();
        $datas = $sql->offset($start)->limit($limit)->orderBy('pub_stats.udate', 'DESC')->get();
        $row = count($datas);
        
        if (!empty($datas)) {
            $totalclk = 0;
            $totalimp = 0;
            $totalamt = 0;
            $totalctr = 0;
            $totalavgcpc = 0;
        
            foreach ($datas as $value) {
                if ($value->Imprs == 0) {
                    $value->CTR = 0;
                } else {
                    $value->CTR = round($value->Clicks / $value->Imprs * 100, 2);
                }
        
                $newDate = $value->created;
                $value->created = $newDate;
        
                if ($value->Clicks == 0) {
                    $value->AvgCPC = 0;
                } else {
                    $value->AvgCPC = round($value->Totals / $value->Clicks, 2);
                }
        
                $totalimp += $value->Imprs;
                $totalclk += $value->Clicks;
                $totalamt += $value->Totals;
                $value->Total = $value->Totals;
                unset($value->Totals);
            }
        
            $totalctr = ($totalclk) ? $totalclk / $totalimp * 100 : 0;
            $totalavgcpc = ($totalamt) ? $totalamt / ($totalclk + $totalimp) : 0;
        
            $totals = [
                'total_impression' => round($totalimp, 3),
                'total_click' => round($totalclk, 3),
                'total_amount' => round($totalamt, 5),
                'total_ctr' => round($totalctr, 3),
                'total_avgcpc' => round($totalavgcpc, 3)
            ];
        
            $return['code'] = 200;
            $return['data'] = $datas;
            $return['total'] = $totals;
            $return['row'] = $row;
            $return['rows'] = $rows;
            $return['message'] = 'Successfully';
        } else {
            $return['code']    = 100;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function reportImprDetail(Request $request)
    {
        $adunit_id = base64_decode($request->adunit_id);
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate . ' - 1 days'));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        //dd($adunit_id,$startDate,$endDate);
        if ($startDate == '' && $endDate == '') {
            $impDetail = DB::table('ad_impressions')
                ->select('id', 'device_type', 'device_os', 'ip_addr', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id);
        } else {
            $impDetail = DB::table('ad_impressions')
                ->select('id', 'device_type', 'device_os', 'ip_addr', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id)
                ->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }

        $impDetail->orderBy('ad_impressions.id', 'DESC');

        $row1 = $impDetail->get();
        $row = $row1->count();
        $data = $impDetail->offset($start)->limit($limit)->get();

        if ($row != null) {
            $return['code']        = 200;
            $return['data']        = $data;
            $return['row']         = $row;
            $return['message']     = 'List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function reportClickDetail(Request $request)
    {
        $adunit_id = base64_decode($request->adunit_id);
        $startDate = $request->startDate;
        $nfromdate = date('Y-m-d', strtotime($startDate . ' - 1 days'));
        $endDate = $request->endDate;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        if ($startDate == '' && $endDate == '') {
            $impDetail = DB::table('user_camp_click_logs')
                ->select('id', 'device_type', 'device_os', 'ip_address', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id);
        } else {
            $impDetail = DB::table('user_camp_click_logs')
                ->select('id', 'device_type', 'device_os', 'ip_address', 'country', 'created_at', DB::raw("'Real' as ip_type"))
                ->where('adunit_id', $adunit_id)
                ->whereDate('created_at', '>=', $nfromdate)
                ->whereDate('created_at', '<=', $endDate);
        }

        $impDetail->orderBy('user_camp_click_logs.id', 'DESC');

        $row1 = $impDetail->get();
        $row = $row1->count();
        $data = $impDetail->offset($start)->limit($limit)->get();

        if ($row != null) {
            $return['code']        = 200;
            $return['data']        = $data;
            $return['row']         = $row;
            $return['message']     = 'List retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
