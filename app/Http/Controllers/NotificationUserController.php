<?php



namespace App\Http\Controllers;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\Notification;

use App\Models\User;

use App\Models\UserNotification;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;



class NotificationUserController extends Controller

{


    /**
    * View notifications by user ID.
    *
    * @OA\Post(
    *     path="/api/user/notification/user_id",
    *     tags={"Notification"},
    *     summary="View Advertiser Notifications By User ID",
    *     operationId="viewNotifications",
    *     @OA\RequestBody(
    *         required=true,
    *         description="Pass user ID",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(property="user_id", type="string", format="string"),
    *                 @OA\Property(property="page", type="integer", format="int32", description="Page number", example="1"),
    *                 @OA\Property(property="lim", type="integer", format="int32", description="Limit per page", example="10")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key (Advertiser)",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="notifuser_id", type="integer", description="Notification user ID"),
    *                 @OA\Property(property="view", type="integer", description="View status"),
    *                 @OA\Property(property="title", type="string", description="Notification title"),
    *                 @OA\Property(property="noti_desc", type="string", description="Notification description"),
    *                 @OA\Property(property="noti_type", type="integer", description="Notification type"),
    *                 @OA\Property(property="display_url", type="string", description="Display URL"),
    *                 @OA\Property(property="created_at", type="string", description="Notification creation timestamp")
    *             )),
    *             @OA\Property(property="row", type="integer", description="Total number of rows"),
    *             @OA\Property(property="wallet", type="number", description="User's wallet balance")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Error message"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     )
    * )
    */
    public function view_notification_by_user_id(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $page   = $request->page;

        $limit  = $request->lim;

        $pg     = $page - 1;

        $start  = ($pg > 0) ? $limit * $pg : 0;

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        $userid = $usersdestils->uid;

        $users = DB::table('user_notifications')

        ->join('notifications', 'user_notifications.noti_id', '=', 'notifications.id')

        ->select('user_notifications.notifuser_id','user_notifications.view', 'notifications.title', 'notifications.noti_desc', 'notifications.noti_type', 'notifications.display_url', 'notifications.created_at')

        ->where('user_notifications.user_id',$userid)

        ->where('user_notifications.user_type','!=',2)

        ->where('user_notifications.trash',0)

        ->orderBy('user_notifications.id','desc');

        $row = $users->count();

        $queru =  $users->offset($start)->limit($limit)->get();

        if (count($queru)) {

            $return['code'] = 200;

            $return['msg'] = 'All Data  User Notification';

            $return['data']    = $queru;

            $return['row']     = $row;
              $wltAmt = getWalletAmount();
              $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($usersdestils->wallet, 3, '.', '');

            } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

    }

  
    /**
    * View publisher notifications by user ID.
    *
    * @OA\Post(
    *     path="/api/pub/user/notification/user_id",
    *     tags={"Notification"},
    *     summary="View Publisher Notifications By User ID",
    *     operationId="viewPubNotifications",
    *     @OA\RequestBody(
    *         required=true,
    *         description="Pass user ID",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(property="user_id", type="string", format="string"),
    *                 @OA\Property(property="page", type="integer", format="int32", description="Page number"),
    *                 @OA\Property(property="lim", type="integer", format="int32", description="Limit per page")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key (Publisher)",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="notifuser_id", type="integer", description="Notification user ID"),
    *                 @OA\Property(property="title", type="string", description="Notification title"),
    *                 @OA\Property(property="noti_desc", type="string", description="Notification description"),
    *                 @OA\Property(property="noti_type", type="integer", description="Notification type"),
    *                 @OA\Property(property="display_url", type="string", description="Display URL"),
    *                 @OA\Property(property="view", type="integer", description="View status"),
    *                 @OA\Property(property="created_at", type="string", description="Notification creation timestamp")
    *             )),
    *             @OA\Property(property="count", type="integer", description="Number of unread notifications"),
    *             @OA\Property(property="row", type="integer", description="Total number of rows"),
    *             @OA\Property(property="wallet", type="number", description="User's publisher wallet balance")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Error message"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     )
    * )
    */
  	public function view_pub_notification_by_user_id(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        $userid = $usersdestils->uid;
$page   = $request->page;
        $limit  = $request->lim;
        $pg     = $page - 1;
        $start  = ($pg > 0) ? $limit * $pg : 0;
        $notifications = DB::table('user_notifications as un')
                    ->select('un.notifuser_id', 'n.title', 'n.noti_desc', 'n.noti_type', 'n.display_url', 'un.view', 'n.created_at')
                    ->join('notifications as n', 'un.noti_id', '=', 'n.id')
                    ->where('un.user_id', $userid)
                    ->where('un.user_type', '!=', '1')
                    ->where('un.trash', '0')
                    ->orderBy('un.id', 'desc');
        $row = $notifications->count();
        $notificationslist =  $notifications->offset($start)->limit($limit)->get();
        if ($notificationslist) {
          	$sql1 = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.view = '0' AND un.user_type != '1' AND un.trash='0' ORDER BY un.id DESC";

        	$noticn = DB::select($sql1);

            $return['code'] = 200;

            $return['msg'] = 'All Data  User Notification';
            $return['data']    = $notificationslist;
          	$return['count']    = count($noticn);
            $return['row']    = $row;
            $wltPubAmt = getPubWalletAmount();
            $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($usersdestils->pub_wallet, 2);
              
              
              

        } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

    }


    /**
    * @OA\Post(
    *     path="/api/user/notification/count",
    *     summary="Advertiser Notifications Count",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="string",
    *                     description="User ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key (Advertiser)",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="integer", description="Number of unread notifications")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating data not found")
    *         )
    *     )
    * )
    */
    public function countNotif(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        $userid = $usersdestils->uid;

        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.user_type != '2' AND un.view=0 AND  un.trash='0'  ORDER BY un.id DESC";

        $queru = DB::select($sql);

        if (count($queru)) {

            $return['code'] = 200;

            $return['msg'] = 'User Notification Count';

            $return['data']    = count($queru);

        } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

    }

  
    /**
    * @OA\Post(
    *     path="/api/pub/user/notification/count",
    *     summary="Publisher Notifications Count",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="string",
    *                     description="User ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key (Publisher)",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="integer", description="Number of unread notifications")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating data not found")
    *         )
    *     )
    * )
    */
  	public function countPubNotif(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        $userid = $usersdestils->uid;

        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.user_type != '1' AND un.view=0 AND  un.trash='0'  ORDER BY un.id DESC";

        $queru = DB::select($sql);

        if (count($queru)) {

            $return['code'] = 200;

            $return['msg'] = 'User Notification Count';

            $return['data']    = count($queru);

        } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

    }


    /**
    * @OA\Post(
    *     path="/api/user/notification/unreadnoti",
    *     summary="Advertiser Unread Notifications",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="string",
    *                     description="User ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="notifuser_id", type="integer", description="Notification user ID"),
    *                 @OA\Property(property="title", type="string", description="Notification title"),
    *                 @OA\Property(property="noti_desc", type="string", description="Notification description"),
    *                 @OA\Property(property="noti_type", type="integer", description="Notification type"),
    *                 @OA\Property(property="display_url", type="string", description="Display URL"),
    *                 @OA\Property(property="view", type="integer", description="View status"),
    *                 @OA\Property(property="created_at", type="string", description="Notification creation timestamp")
    *             )),
    *             @OA\Property(property="count", type="integer", description="Number of unread notifications")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="User not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating user not found")
    *         )
    *     ),
    *     @OA\Response(
    *         response=102,
    *         description="Data not found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating data not found")
    *         )
    *     )
    * )
    */
    public function unreadNotif(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        if (empty($usersdestils)) {

            $return['code'] = 101;

            $return['msg'] = 'User Not Found';

            return response()->json($return);

        } else { 

        $userid = $usersdestils->uid;

        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.user_type != '2' AND un.trash= 0 ORDER BY un.id DESC LIMIT 3";

        $queru = DB::select($sql);

        if (count($queru)) {

            $sqld = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.view = '0' AND un.user_type != '2' AND un.trash=0 ORDER BY un.id  DESC";

            $querus = DB::select($sqld);

            $return['code'] = 200;

            $return['msg'] = 'User Notification Count';

            $return['data']    = $queru;

            $return['count']    = count($querus);

        } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

    }

    

		return json_encode($return);

    }

  
    /**
    * @OA\Post(
    *     path="/api/pub/user/notification/unreadnoti",
    *     summary="Publisher Unread Notifications",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="string",
    *                     description="User ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success or failure"),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="notifuser_id", type="integer", description="Notification user ID"),
    *                 @OA\Property(property="title", type="string", description="Notification title"),
    *                 @OA\Property(property="noti_desc", type="string", description="Notification description"),
    *                 @OA\Property(property="noti_type", type="integer", description="Notification type"),
    *                 @OA\Property(property="display_url", type="string", description="Display URL"),
    *                 @OA\Property(property="view", type="integer", description="View status"),
    *                 @OA\Property(property="created_at", type="string", description="Notification creation timestamp")
    *             )),
    *             @OA\Property(property="count", type="integer", description="Number of unread notifications")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="User Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating user not found")
    *         )
    *     )
    * )
    */
  	public function unreadPubNotif(Request $request)

    {

        $validator = Validator::make(

            $request->all(),

            [

                'user_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $userids =  $request->user_id;

        $usersdestils = User::where('uid', $userids)->first();

        if (empty($usersdestils)) {

            $return['code'] = 101;

            $return['msg'] = 'User Not Found';

            return response()->json($return);

        } else { 

        $userid = $usersdestils->uid;

        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.user_type != '1' AND un.trash= 0 ORDER BY un.id DESC LIMIT 3";

        $queru = DB::select($sql);

        if (count($queru)) {

            $sqld = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.view = '0' AND un.user_type != '1' AND un.trash=0 ORDER BY un.id  DESC";

            $querus = DB::select($sqld);

            $return['code'] = 200;

            $return['msg'] = 'User Notification Count';

            $return['data']    = $queru;

            $return['count']    = count($querus);

        } else {

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

    }

    

		return json_encode($return);

    }


    /**
    * @OA\Post(
    *     path="/api/user/notification/read",
    *     summary="Advertiser Notification Mark as Read",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"notifuser_id"},
    *                 @OA\Property(
    *                     property="notifuser_id",
    *                     type="integer",
    *                     description="Notification user ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success"),
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating data not found")
    *         )
    *     )
    * )
    */
    public function read(Request $request)

    {



        $validator = Validator::make(

            $request->all(),

            [

                'notifuser_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $notifuserid =  $request->notifuser_id;

        $notifdatau =  UserNotification ::where('notifuser_id',$notifuserid)->where('user_type', '!=', '2')->where('view',0)->first();

        if(!empty($notifdatau))

        { 

            $notifdatau->view = 1;

            $notifdatau->save();

            $return['code'] = 200;

            $return['msg'] = 'Notification Read Successfully';

        }

        else

        { 

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

      }

  
      /**
    * @OA\Post(
    *     path="/api/pub/user/notification/read",
    *     summary="Publisher Notification Mark as Read",
    *     tags={"Notification"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"notifuser_id"},
    *                 @OA\Property(
    *                     property="notifuser_id",
    *                     type="integer",
    *                     description="Notification user ID"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating success"),
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating the error"),
    *             @OA\Property(property="err", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Data Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="msg", type="string", description="Message indicating data not found")
    *         )
    *     )
    * )
    */
  	public function readPub(Request $request)

    {



        $validator = Validator::make(

            $request->all(),

            [

                'notifuser_id' => "required",

            ]

        );

        if ($validator->fails()) {

            $return['code'] = 100;

            $return['msg'] = 'error';

            $return['err'] = $validator->errors();

            return response()->json($return);

        }

        $notifuserid =  $request->notifuser_id;

        $notifdatau =  UserNotification ::where('notifuser_id',$notifuserid)->where('user_type','!=', '1')->where('view',0)->first();

        if(!empty($notifdatau))

        { 

            $notifdatau->view = 1;

            $notifdatau->save();

            $return['code'] = 200;

            $return['msg'] = 'Notification Read Successfully';

        }

        else

        { 

            $return['code'] = 101;

            $return['msg'] = 'Data Not Found';

        }

        return json_encode($return);

      }







}

