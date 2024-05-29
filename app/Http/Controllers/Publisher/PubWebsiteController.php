<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\PubAdunit;
use Illuminate\Http\Request;
use App\Models\PubWebsite;
use App\Models\User;
use App\Models\Activitylog;
use App\Models\Notification;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class PubWebsiteController extends Controller
{
    public function websiteCodeGenerator()
    {
        $webcode = '7SWB' . strtoupper(uniqid(10));
        $checkdata = PubWebsite::where('web_code', $webcode)->count();
        if ($checkdata > 0) {
            $this->websiteCodeGenerator();
        } else {
            return $webcode;
        }
    }
    public function adCodeGenerator()
    {
        $adcode = '7SAD' . strtoupper(uniqid(15));
        $checkdata = PubAdunit::where('ad_code', $adcode)->count();
        if ($checkdata > 0) {
            $this->adCodeGenerator();
        } else {
            return $adcode;
        }
    }

    public function websiteStore(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid' => 'required',
                'website_category' => 'required',
                //'site_url' => 'required|url|regex:' . $regex . '|unique:pub_websites',
                'site_url' => 'required',
                'ad_name' => 'required',
                'ad_type' => 'required',
            ],
            [
                'uid.required' => 'Please Enter Publisher Code',
                'website_category.required' => 'Please Select Website Category',
                'site_url.required' => 'Please Enter Website URL',
                'ad_name.required' => 'Please Enter Ad Name',
                'ad_type.required' => 'Please Select Ad Type',
            ]
        );

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error!';
            return json_encode($return);
        }
      $url = str_replace('https://', '', str_replace ('http://', '', str_replace ('http://www.', '', str_replace ('https://www.', '', str_replace ('www.', '', $request->site_url))))); 
        $mataTagValidation = self::checkMeta($request->site_url,$request->code);
        if($mataTagValidation == false){
        $return['code'] = 102;
        $return['message'] = 'Please verify your website ownership first!';
        return json_encode($return);
        }
        
        $user_email = User::where('uid', $request->uid)->first();
        $category = Category::where('id', $request->website_category)->first();
        $webnumber = $this->websiteCodeGenerator();
        $website = new PubWebsite();
        $website->uid = $request->uid;
        $website->u_email = $user_email->email;
        $website->website_category = $request->website_category;
        // $website->category_name = $category->cat_name;
        $website->site_url = $url;
      	$website->status = 1;
        $website->web_code = $webnumber;
        if ($website->save()) {
            
            $noti_title = 'Website Under Moderation - 7Search PPC ';
            $noti_desc  = 'Your request to add a new website is currently under review. Our moderation team will evaluate your website shortly and take appropriate action within 3 to 5 business days. In the meantime, please cooperate.';
            $notification = new Notification();
            $notification->notif_id = gennotificationuniq();
            $notification->title = $noti_title;
            $notification->noti_desc = $noti_desc;
            $notification->noti_type = 1;
            $notification->noti_for = 2;
            $notification->all_users = 0;
            $notification->status = 1;
            if ($notification->save()) {
                $noti = new UserNotification();
                $noti->notifuser_id = gennotificationuseruniq();
                $noti->noti_id = $notification->id;
                $noti->user_id = $request->uid;
                $noti->user_type = 2;
                $noti->view = 0;
                $noti->created_at = Carbon::now();
                $noti->updated_at = now();
                $noti->save();
            }

          /* Website Activity Add & Generate Notification */
          	$activitylog = new Activitylog();
            $activitylog->uid    = $request->uid;
            $activitylog->type    = 'Website Added';
            $activitylog->description    = '' . $website->web_code . ' is added Successfully';
            $activitylog->status    = '1';
            $activitylog->save();
          	/* Send real time notification to admin */
          	sendFcmNotification($activitylog->type, $activitylog->description);
          	
          
            $adnumber = $this->adCodeGenerator();
            $adunit = new PubAdunit();
            $adunit->ad_code            = $adnumber;
            $adunit->uid                = $request->uid;
            $adunit->web_code           = $website->web_code;
            $adunit->ad_name            = $request->ad_name;
            $adunit->ad_type            = $request->ad_type;
          	$adunit->grid_type          = $request->grid_type;
            $adunit->ad_size            = $request->ad_size;
            $adunit->site_url           = $url;
            $adunit->website_category   = $request->website_category;
            // $adunit->category_name      = $category->cat_name;
            $adunit->erotic_ads         = $request->erotic_ads;
          	$adunit->alert_ads         = $request->alert_ads;
          	$adunit->status      		= 2;
            if ($adunit->save()) {
              /* Adunit Activity Add & Generate Notification */
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->uid;
                $activitylog->type    = 'Adunit Added';
                $activitylog->description    = '' . $adunit->ad_code . ' is added Successfully';
                $activitylog->status    = '1';
                $activitylog->save();
              	/* Send real time notification to admin */
          		sendFcmNotification($activitylog->type, $activitylog->description);
                $return['code']          = 200;
                $return['message']       = 'Website added successfully!';
              	/* Admin Section  */
              	$email = $user_email->email;
                $fullname = $user_email->first_name . ' ' . $user_email->last_name;
                $useridas = $request->uid;
                $webcode = $website->web_code;
              	$website_url = $website->site_url;
                $webcategory =   $website->website_category;
                
              
              	$data['details'] = array('subject' => 'Website Under Moderation - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas, 'websiteid' => $webcode, 'website_url' => $website_url, 'webcategory' => $webcategory);
              	
              	$subject = 'Website Under Moderation - 7Search PPC';
                $body =  View('emailtemp.websitecreateuser', $data);
                sendmailUser($subject,$body,$email); 
                             
                $adminmail1 = 'advertisersupport@7searchppc.com';
            	$adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.websitecreateadmin', $data);
                $subjectadmin = 'Website Addition Request - 7Search PPC';
                $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 


                if($sendmailadmin == '1') 
                {
                    $return['code'] = 200;
                    $return['message']  = 'Mail Send & Website added successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                }
            } else {

                $return['code']    = 101;
                $return['message'] = 'Ad Unit not added!';
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Website not added!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Retrieve a list of websites associated with a specific user.
    *
    * @OA\Post(
    *     path="/api/pub/website/list",
    *     summary="Get List of Websites",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid"},
    *                 @OA\Property(property="uid", type="string", description="User ID"),
    *                 @OA\Property(property="status", type="string", description="Status"),
    *                 @OA\Property(property="src", type="string", description="src"),
    *                 @OA\Property(property="lim", type="integer", description="lim", example="10"),
    *                 @OA\Property(property="page", type="number", description="page", example="1")
    *             )
    *         )
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
    *         description="List of websites retrieved successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="object"),
    *             @OA\Property(property="row", type="integer", example=10),
    *             @OA\Property(property="wallet", type="string", example="1000.00"),
    *             @OA\Property(property="message", type="string", example="Data retrieved successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="Not Found Data !")
    *         )
    *     )
    * )
    */
    public function websiteList(Request $request)
    {
        $limit  = $request->lim;
        $uid  = $request->uid;
      	$status = $request->status;
      	$src = $request->src;
        $page   = $request->page;
        $pg     = $page - 1;
        $start  = ($pg > 0) ? $limit * $pg : 0;
        $weblist = PubWebsite::selectRaw("ss_pub_websites.id,ss_pub_websites.web_code,ss_pub_websites.site_url,ss_pub_websites.status, ss_pub_websites.verify, ss_pub_websites.website_category, ss_pub_websites.remark, ss_pub_websites.created_at,(select count(id) from ss_pub_adunits ad_unit where ad_unit.web_code = ss_pub_websites.web_code AND ad_unit.trash = 0) as adunits, ss_categories.cat_name")
            ->join('categories', 'pub_websites.website_category', '=', 'categories.id');
        $weblist->where('pub_websites.uid', $uid)->where('pub_websites.trash', 0);
      	if($status != '')
        {
        	$weblist->where('pub_websites.status', $status);
        }
      	if($src)
        {
        	$weblist->whereRaw( 'concat(ss_pub_websites.site_url,ss_pub_websites.web_code) like ?', "%{$src}%" );
        } 
      	$row        = $weblist->count();
        $data       = $weblist->offset($start)->limit($limit)->orderBy('id', 'DESC')->get()->toArray();
       	$wres = [];
        foreach($data as $website)
        {
            $currentDate = Carbon::now();
        	$webadlist = PubAdunit::selectRaw("ss_pub_adunits.id,ss_pub_adunits.ad_size,ss_pub_adunits.web_code,ss_pub_adunits.erotic_ads,ss_pub_adunits.ad_code, ss_pub_adunits.ad_name, ss_pub_adunits.ad_type,ss_pub_adunits.site_url,ss_pub_adunits.status, ss_pub_adunits.website_category, ss_pub_adunits.created_at,ss_pub_adunits.grid_type,
            			(IF(DATEDIFF( '".$currentDate."', created_at) < 8, 1, 0)) as badge")
        				->where('pub_adunits.web_code', $website['web_code'])->where('pub_adunits.trash', 0)->orderBy('id', 'DESC')->get()->toArray();
                              
          $website['adunit_list'] = $webadlist;
          $wres[] = $website;
        }
      	$userdata = User::where('uid', $uid)->first();
      	if ($wres) {
            $return['code']    = 200;
            $return['data']    = $wres;
            $return['row']     = $row;
            $wltPubAmt = getPubWalletAmount();
            $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($userdata->pub_wallet, 2);
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Retrieve a dropdown list of websites associated with a specific user.
    *
    * @OA\Post(
    *     path="/api/pub/website/dropdown",
    *     summary="Get Dropdown List of Websites",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid"},
    *                 @OA\Property(property="uid", type="integer")
    *             )
    *         )
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
    *         description="Dropdown list of websites retrieved successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="id", type="integer", example=1),
    *                 @OA\Property(property="web_code", type="string", example="ABC123"),
    *                 @OA\Property(property="webname", type="string", example="example.com")
    *             )),
    *             @OA\Property(property="message", type="string", example="Data retrieved successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="Not Found Data !")
    *         )
    *     )
    * )
    */
  	public function webDropdownList(Request $request)
    {
        $uid  = $request->uid;
      	$weblist = PubWebsite::select('id','web_code','site_url as webname')
            		->where('uid', $uid)->where('trash', 0)->get();
      	$row = $weblist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $weblist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Retrieve a list of ad units associated with a specific website.
    *
    * @OA\Post(
    *     path="/api/pub/website/adlist",
    *     summary="Get Ad Unit List For a Website",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "web_code"},
    *                 @OA\Property(property="uid", type="string"),
    *                 @OA\Property(property="web_code", type="string"),
    *                 @OA\Property(property="lim", type="integer", example=10),
    *                 @OA\Property(property="page", type="integer", example=1)
    *             )
    *         )
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
    *         description="Ad unit list retrieved successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="id", type="integer", example=1),
    *                 @OA\Property(property="web_code", type="string", example="ABC123"),
    *                 @OA\Property(property="erotic_ads", type="integer", example=0),
    *                 @OA\Property(property="ad_code", type="string", example="AD001"),
    *                 @OA\Property(property="ad_name", type="string", example="Banner Ad"),
    *                 @OA\Property(property="ad_type", type="string", example="Banner"),
    *                 @OA\Property(property="site_url", type="string", example="example.com"),
    *                 @OA\Property(property="status", type="integer", example=1),
    *                 @OA\Property(property="website_category", type="string", example="Technology"),
    *                 @OA\Property(property="created_at", type="string", example="2024-03-23 10:15:00"),
    *                 @OA\Property(property="cat_name", type="string", example="Category Name")
    *             )),
    *             @OA\Property(property="row", type="integer", example=10),
    *             @OA\Property(property="message", type="string", example="Data Retrieved successfully!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="No data found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="No Data Found !")
    *         )
    *     )
    * )
    */
    public function websiteAdunitList(Request $request)
    {
        $web_code 	= $request->web_code;
      	$uid 		= $request->uid;
        $limit  	= $request->lim;
        $page   	= $request->page;
        $pg     	= $page - 1;
        $start  	= ($pg > 0) ? $limit * $pg : 0;
      	$webadlist = PubAdunit::selectRaw("ss_pub_adunits.id,ss_pub_adunits.web_code,ss_pub_adunits.erotic_ads,ss_pub_adunits.ad_code, ss_pub_adunits.ad_name, ss_pub_adunits.ad_type,ss_pub_adunits.site_url,ss_pub_adunits.status, ss_pub_adunits.website_category, ss_pub_adunits.created_at, ss_categories.cat_name")
            ->join('categories', 'pub_adunits.website_category', '=', 'categories.id');
        $webadlist->where('pub_adunits.uid', $uid)->where('pub_adunits.web_code', $web_code)->where('pub_adunits.trash', 0);
        //$webadlist  = PubAdunit::select('*')->where('web_code', $web_code)->where('uid', $uid)->where('trash', 0);
        $row        = $webadlist->count();
        $data       = $webadlist->offset($start)->limit($limit)->orderBy('id', 'DESC')->get();
        // print_r($data);
        if (empty($row)) {
            $return['code']    = 101;
            $return['message'] = 'No Data Found !';
        } else {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'Data Retrieved successfully!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function websiteListverfy(Request $request)
    {
        $limit  = $request->lim;
        $page   = $request->page;
        $pg     = $page - 1;
        $start  = ($pg > 0) ? $limit * $pg : 0;
        // $weblist = PubWebsite::select('id','web_code','web_name','site_url','auth_code', 'status','verify','created_at','updated_at')->where('trash', 0)->where('verify', 1);    
        $weblist = PubWebsite::selectRaw('id,web_code,web_name,site_url,auth_code,status,verify,created_at,updated_at,(select count(id) from ss_pub_adunits adunits where adunits.web_code = ss_pub_websites.web_code) as counts')->where('trash', 0)->where('verify', 1)->orderBy('id', 'desc');
        $row        =     $weblist->count();
        $data       =     $weblist->offset($start)->limit($limit)->orderBy('id', 'DESC')->get();
        if (empty($weblist)) {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
            return json_encode($return);
        } else {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $return['message'] = 'data successfully!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Move a website to trash.
    *
    * @OA\Post(
    *     path="/api/pub/website/delete",
    *     summary="Move Website to Trash",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"id"},
    *                 @OA\Property(property="id", type="integer", description="Website ID", example=1)
    *             )
    *         )
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
    *         description="Website deleted successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="message", type="string", example="Website deleted successfully")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Something went wrong",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="Something went wrong!")
    *         )
    *     )
    * )
    */
    public function websiteTrash(Request $request)
    {
        $id = $request->id;
        $website = PubWebsite::where('id', $id)->first();
        $website->trash = 1;
        if ($website->update()) {
            $return['code']    = 200;
            $return['message'] = 'Website deleted successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function verifyfileWeb(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'auth_code' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error !';
            return json_encode($return);
        }
        $authcode = $request->auth_code;
        $detail = PubWebsite::where('auth_code', $authcode)->first();

        if ($detail) {
            $remoteFile = $detail->site_url . '/7searchppc-verification.json';
            $handle = @fopen($remoteFile, 'r');
            if (!$handle) {
                $return['code'] = 101;
                $return['message'] = 'File does not exist, please upload the file !';
            } else {
                $handles =  fgets($handle);
                if ($authcode == $handles) {
                    if ($detail->verify == 1) {
                        $return['code'] = 101;
                        $return['message'] = 'Already Verified !';
                    } else {
                        $detail->verify = 1;
                        $detail->status = 2;
                        $detail->save();
                        $return['code'] = 200;
                        $return['message'] = 'Verified successfully !';
                    }
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'File not matched !';
                }
            }
        } else {
            $return['code'] = 101;
            $return['message'] = 'Detail not found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Get details of a specific website.
    *
    * @OA\Post(
    *     path="/api/pub/website/detail",
    *     summary="Get website detail",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"id"},
    *                 @OA\Property(property="id", type="integer", example=1)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Website detail fetched successfully",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="object"),
    *             @OA\Property(property="message", type="string", example="Website detail fetched successfully !")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Website detail not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="Website detail not found !")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", example=100),
    *             @OA\Property(property="error", type="object", example={"id": {"The id field is required."}}),
    *             @OA\Property(property="message", type="string", example="Validation error !")
    *         )
    *     ),
    * )
    */
    public function websiteDetail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation error !';
            return json_encode($return);
        }
        $detail = PubWebsite::select('*')->where('id', $request->id)->first();
        if ($detail) {
            $return['code'] = 200;
            $return['data'] = $detail;
            $return['message'] = 'Website detail fetched successfully !';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Website detail not found !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Re-submit website for review.
    *
    * @OA\Post(
    *     path="/api/user/pub/website/re-submit",
    *     summary="Re-submit Website for Review",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "web_id", "action"},
    *                 @OA\Property(property="uid", type="integer", example=1),
    *                 @OA\Property(property="web_id", type="string", example="7SHU1065FCA6A021545A"),
    *                 @OA\Property(property="action", type="string", example="resubmit")
    *             )
    *         )
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
    *         description="Website resubmitted successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="message", type="string", example="Updated Website Status Successfully!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Error occurred while processing the request",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="User Id and Website Id Something went wrong!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=102,
    *         description="User not found or inactive",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=102),
    *             @OA\Property(property="message", type="string", example="User Not Active Status!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=103,
    *         description="User is already active",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=103),
    *             @OA\Property(property="message", type="string", example="Users Active not found!")
    *         )
    *     )
    * )
    */
    public function reSubmit(Request $request){
       $existuser =  User::where('uid', $request->uid)->where('ac_verified',1)->where('status',0)->where('trash',0)->first();
       $resubmit_date = date('Y-m-d h:i:s A');
      if(!empty($existuser)){
          if($request->action === 'resubmit'){
            $remark = "Congratulations! Your website has been re-submitted successfully.";
            $detail = PubWebsite::where('uid',$request->uid)->where('web_code',$request->web_id)->where('trash',0)->update(['status'=> 1,'remark' => $remark, 'resubmit_date'=>$resubmit_date]);
            if($detail){
                $email = $existuser->email;
                $fullname = $existuser->first_name . ' ' . $existuser->last_name;
                $useridas = $existuser->uid;
                $noti_title = 'Website Re-submit - 7Search PPC ';
                $noti_desc  = 'Thank you for your resubmission. Our team is conducting a thorough review of your website to ensure compliance with our guidelines. We appreciate your cooperation.';
                $notification = new Notification();
                $notification->notif_id = gennotificationuniq();
                $notification->title = $noti_title;
                $notification->noti_desc = $noti_desc;
                $notification->noti_type = 1;
                $notification->noti_for = 2;
                $notification->all_users = 0;
                $notification->status = 1;
                if ($notification->save()) {
                    $noti = new UserNotification();
                    $noti->notifuser_id = gennotificationuseruniq();
                    $noti->noti_id = $notification->id;
                    $noti->user_id = $request->uid;
                    $noti->user_type = 2;
                    $noti->view = 0;
                    $noti->created_at = Carbon::now();
                    $noti->updated_at = now();
                    $noti->save();
                }
                 /* Website Activity Add & Generate Notification */
                $website = PubWebsite::where('uid',$request->uid)->where('web_code',$request->web_id)->first();
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->uid;
                $activitylog->type    = 'Website Re-submitted';
                $activitylog->description    = '' . $website->web_code . ' is re-submitted successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                /* Send real time notification to admin */
                sendFcmNotification($activitylog->type, $activitylog->description);
                $data['details'] = array('subject' => 'Website Under Moderation - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
                $subject = 'Website Under Moderation - 7Search PPC';
                $body =  View('emailtemp.pubwebsiteresubmit', $data);
                sendmailUser($subject,$body,$email); 

                $dataAdmin['details'] = array('subject' => 'Resubmission to Add Website - 7Search PPC ', 'fullname' => $fullname,  'usersid' => $useridas);
                  /* Admin Section  */
                  $adminmail1 = 'advertisersupport@7searchppc.com';
                  $adminmail2 = 'info@7searchppc.com';
                  $bodyadmin =   View('emailtemp.useradminwebsiteresubmit', $dataAdmin);
                  $subjectadmin = 'Resubmission to Add Website - 7Search PPC';
                  sendmailAdmin($subjectadmin, $bodyadmin, $adminmail1, $adminmail2);
                $return['code'] = 200;
                $return['message'] = 'Updated Website Status Successfully!';
            }else{
                $return['code'] = 101;
                $return['message'] = 'User Id and Website Id Something went wrong!';
            }
          }else{
            $return['code'] = 101;
            $return['message'] = 'Users Active not found!';
          }
      }else{
        $return['code'] = 101;
        $return['message'] = 'User Not Active Status!';
      }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }

    static function checkMeta($url,$code)
    {
        $return = false;
    try {
        $currenturl = 'https://'.$url;
        $html = file_get_contents($currenturl);
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_use_internal_errors(false);
        $xpath = new DOMXPath($doc);
        $query = '//meta[@name="7searchppc"]';
        $metaDescription = $xpath->query($query)->item(0);
        if ($metaDescription) {
            $content = $metaDescription->getAttribute('content');
            if ($content == $code) {
                $return = true;
            }
        }
    } catch (\Exception $e) {
        $return = false;
    }
    return $return;
  }
  
  static function checkMetaFront(Request $request)
  {
      if(empty($request->url)){
        $data['code'] = 102;
        $data['message'] = 'url not found!';
        return json_encode($data, JSON_NUMERIC_CHECK);
      }
      if(empty($request->code)){
        $data['code'] = 103;
        $data['message'] = 'Meta Code is not found!';
        return json_encode($data, JSON_NUMERIC_CHECK);
      }
      $return = false;
  try {
      $currenturl = 'https://'.$request->url;
      $html = file_get_contents($currenturl);
      $doc = new DOMDocument();
      libxml_use_internal_errors(true);
      $doc->loadHTML($html);
      libxml_use_internal_errors(false);
      $xpath = new DOMXPath($doc);
      $query = '//meta[@name="7searchppc"]';
      $metaDescription = $xpath->query($query)->item(0);
      if ($metaDescription) {
          $content = $metaDescription->getAttribute('content');
          if ($content == $request->code) {
              $return = true;
          }
      }
  } catch (\Exception $e) {
      $return = false;
  }
    if($return === true){
        $data['code'] = 200;
        $data['message'] = 'Congratulations! Your website has been re-submitted successfully.!';
    }else{
        $data['code'] = 101;
        $data['message'] = 'Meta Code is not found!';
    }
    return json_encode($data, JSON_NUMERIC_CHECK);
  }


  /**
    * Check if a website exists and if it already exists in the database.
    *
    * @OA\Post(
    *     path="/api/pub/website/check-site-url",
    *     summary="Check Website Existence",
    *     tags={"Websites"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"site_url"},
    *                 @OA\Property(property="site_url", type="string", example="https://example.com")
    *             )
    *         )
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
    *         description="Website is valid",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="message", type="string", example="Website is valid!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Website does not exist or already exists in the database",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="The website does not exist or already exists in the database")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=100),
    *             @OA\Property(property="error", type="object", example={"site_url": {"The site_url field is required."}}),
    *             @OA\Property(property="message", type="string", example="Validation error!")
    *         )
    *     )
    * )
    */
    public function chuckWebsiteExit(Request $request){
    $validator = Validator::make($request->all(), [
        'site_url' => 'required',
    ]);
    if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error !';
        return json_encode($return);
    }
    $url = str_replace('https://', '', str_replace ('http://', '', str_replace ('http://www.', '', str_replace ('https://www.', '', str_replace ('www.', '', $request->site_url))))); 
    $websiteFetch = PubWebsite::where('site_url', $url)->count();
    if($websiteFetch > 0)
    {
      $return['code'] = 101;
      $return['message'] = 'This website already exists!';
      return json_encode($return);
    }
    try {
        $response = Http::withOptions(['verify' => false])->get('http://' . $url);
        if ($response->successful()) {
            $return['code'] = 200;
            $return['message'] = 'Website is valid!';
        } else {
            $return['code'] = 101;
            $return['message'] = "The website does not exist";
        }
    } catch (ConnectionException $e) {
        $return['code'] = 101;
        $return['message'] = 'The website does not exist';
        return response()->json($return);
    }
    return json_encode($return);
  }
}
