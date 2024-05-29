<?php

namespace App\Http\Controllers\Publisher\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PubAdunit;
use App\Models\AdImpression;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PubAdUnitAdminController extends Controller {
    
    public function adUnitList(Request $request) {
      	$sort_order = $request->sort_order;
      	$col = $request->col;
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $start = ( $pg > 0 ) ? $limit * $pg : 0;
        $currentDate = Carbon::now();
      	$data = DB::table('pub_adunits')
        ->join('users', 'users.uid', '=', 'pub_adunits.uid')
        ->join('categories', 'categories.id', '=', 'pub_adunits.website_category')
        ->select('pub_adunits.id','pub_adunits.ad_name','pub_adunits.site_url','pub_adunits.ad_code','pub_adunits.ad_type','pub_adunits.status','pub_adunits.website_category', 
        'users.email as user_email' , 'users.uid as user_id', 'categories.cat_name as category',
        DB::raw('ss_pub_adunits.created_at as create_date, (IF(DATEDIFF( "'.$currentDate.'", ss_pub_adunits.created_at) < 8, 1, 0)) as badge,
        (select IFNULL(sum(impressions),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as impressions,
        (select IFNULL(sum(clicks),0) from ss_pub_stats adimp where adimp.adunit_id = ss_pub_adunits.ad_code) as clicks'));
      //dd($data);
        if ( $request->status != '' && $request->category == '' && $request->ad_type == '' ) {
           	$data->where( 'pub_adunits.status', $request->status );
        }
        if ( $request->website_category != '' && $request->website_status == '' && $request->ad_type == '' ) {
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }
        if ($request->ad_type != '' && $request->website_category == '' && $request->website_status == '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
        }
        if ( $request->website_category != '' && $request->status != '' ) {
            $data->where( 'pub_adunits.status', $request->status );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }if ( $request->website_category != '' && $request->ad_type != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }if ( $request->status != '' && $request->ad_type != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.status', $request->status );
        }if ( $request->status != '' && $request->ad_type != '' && $request->website_category != '' ) {
            $data->where( 'pub_adunits.ad_type', $request->ad_type );
            $data->where( 'pub_adunits.status', $request->status );
            $data->where( 'pub_adunits.website_category', $request->website_category );
        }
        
        if ( $src ) {
            $data->whereRaw( 'concat(ss_pub_adunits.site_url,ss_pub_adunits.status, ss_pub_adunits.ad_name, ss_users.email,ss_pub_adunits.uid,ss_pub_adunits.ad_code) like ?', "%{$src}%" );
        }
      	
      	$row        = $data->count();
      
      	if($col)
        {
          if($col == 'impressions')
          {
            $data = $data->offset( $start )->limit( $limit )->orderBy('impressions', $sort_order)->get();
          }
          elseif($col == 'clicks')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('clicks', $sort_order)->get();
          }
          elseif($col == 'email')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('user_email', $sort_order)->get();
          }
          elseif($col == 'category')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('category', $sort_order)->get();
          }
          elseif($col == 'created_at')
          {
              $data = $data->offset( $start )->limit( $limit )->orderBy('create_date', $sort_order)->get();
          }
          else
          {
              $data  = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.'.$col, $sort_order)->get();
          }
        }
        else
        {
          $data       = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.id', 'DESC')->get();
        }
        //$data       = $data->offset( $start )->limit( $limit )->orderBy('pub_adunits.id', 'DESC')->get();
        //       foreach($data as $value)
        //       {
        //           $date = Carbon::now()->subDays(7);
        // 		   $imprtblcount = AdImpression::where('adunit_id', $value->ad_code)->whereDate('created_at', '>=', $date)->count();
        //           $createdDate = Carbon::parse($value->create_date);
        //             $currentDate = Carbon::now();
        // 			// Calculate the difference in days
        //             $daysDifference = $createdDate->diffInDays($currentDate);
        //           	if ($daysDifference < 7) {
        //               // Return the new badge
        //               $value->badge = 'New';
        //             } else {
        //               // Return a different response if the condition is not met
        //                 $value->badge = '';
        //             }
        //       }
       
      	if ( count( $data ) > 0 ) {
            $return[ 'code' ] = 200;
            $return[ 'data' ] = $data;
          	$return[ 'row' ]  = $row;
          	$return[ 'message' ] = 'Data Successfully found!';
        } else {
            $return[ 'code' ] = 101;
            $return[ 'message' ] = 'Data Not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
    public function dailyInactiveAdunit(Request $request)
    {
        $inactiveStatus = 1;
        $lastDay = Carbon::now()->subDays(7);
        $adUnits = PubAdunit::where('status', '=', 2)->whereDate('created_at','<', $lastDay)->get();
        foreach ($adUnits as $adUnit) {
            $impressionCount = DB::table('pub_stats')->where('adunit_id', $adUnit->ad_code)
            ->whereDate('udate', '>', $lastDay)
            ->count();
            if ($impressionCount == 0) {
              $adUnit->status = $inactiveStatus;
              $adUnit->save();
            }
       }
   }
  
  	public function adUnitStatusUpdate(Request  $request)
    {
        $adunit = PubAdunit::where('id', $request->id)->first();
        $adunit->status = $request->status;
        if ($adunit->update()) {
            $return['code'] = 200;
            $return['message'] = 'Ad Unit updated successfully!';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
  	public function adAdminUnitList(Request $request)
    {
        $web_code  = $request->web_code;
      	$adlist = PubAdunit::select('id','ad_code','ad_name')->where('web_code', $web_code)->where('trash', 0)->get();
      	
      	//print_r($adlist); exit;
      	$row = $adlist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $adlist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
