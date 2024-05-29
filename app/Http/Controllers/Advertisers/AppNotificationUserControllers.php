<?php

namespace App\Http\Controllers\Advertisers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AppNotificationUserControllers extends Controller
{
    /**
    * Retrieve notifications for a specific user by their ID.
    *
    * @OA\Post(
    *     path="/api/app/notification_user_id",
    *     tags={"App Notifications"},
    *     summary="Retrieve Notifications For Specific User",
    *     description="This endpoint retrieves notifications for a specific user by their ID.",
    *     operationId="viewNotificationByUserId",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="integer",
    *                     description="User Id"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [App]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(
    *                 property="code",
    *                 type="integer",
    *                 description="Response code"
    *             ),
    *             @OA\Property(
    *                 property="msg",
    *                 type="string",
    *                 description="Message indicating success or failure"
    *             ),
    *             @OA\Property(
    *                 property="data",
    *                 type="array",
    *                 description="Array of notifications",
    *                 @OA\Items(
    *                     @OA\Property(
    *                         property="notifuser_id",
    *                         type="integer",
    *                         description="Notification user ID"
    *                     ),
    *                     @OA\Property(
    *                         property="title",
    *                         type="string",
    *                         description="Notification title"
    *                     ),
    *                     @OA\Property(
    *                         property="noti_desc",
    *                         type="string",
    *                         description="Notification description"
    *                     ),
    *                     @OA\Property(
    *                         property="noti_type",
    *                         type="integer",
    *                         description="Notification type"
    *                     ),
    *                     @OA\Property(
    *                         property="display_url",
    *                         type="string",
    *                         description="URL to display"
    *                     ),
    *                     @OA\Property(
    *                         property="view",
    *                         type="integer",
    *                         description="Notification view status"
    *                     ),
    *                     @OA\Property(
    *                         property="created_at",
    *                         type="string",
    *                         description="Notification creation date"
    *                     )
    *                 )
    *             ),
    *             @OA\Property(
    *                 property="count",
    *                 type="integer",
    *                 description="Count of unread notifications"
    *             ),
    *             @OA\Property(
    *                 property="wallet",
    *                 type="string",
    *                 description="User's wallet balance"
    *             )
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
        $userids =  $request->user_id;
        $usersdestils = User::where('uid', $userids)->first();
        $userid = $usersdestils->uid;
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.trash='0' ORDER BY un.id DESC";
        $queru = DB::select($sql);
        if (count($queru)) {
            $sql1 = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'AND un.view = '0' AND un.trash='0' ORDER BY un.id DESC";
        	$noticn = DB::select($sql1);
            $return['code'] = 200;
            $return['msg'] = 'All Data  User Notification';
            $return['data']    = $queru;
          	$return['count']    = count($noticn);
          	// $return['wallet']    = number_format($usersdestils->pub_wallet, 2);
            $wltPubAmt = getPubWalletAmount();
            $return['wallet']   = ($wltPubAmt) > 0 ? $wltPubAmt : number_format($usersdestils->pub_wallet, 2);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
     }

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
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid' AND un.view=0 AND  un.trash='0'  ORDER BY un.id DESC";
        $queru = DB::select($sql);
        if (count($queru)) {
            $return['code'] = 200;
            $return['msg'] = 'User Notification Count';
            $return['data']    = count($queru);
        } else {
            $return['code'] = 101;
            $return['msg'] = 'Data Not Found';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Get unread notifications for a user.
    *
    * @OA\Post(
    *     path="/api/app/notification/unreadnoti",
    *     tags={"App Notifications"},
    *     summary="Get Unread Notifications For User",
    *     description="This endpoint retrieves unread notifications for a user based on the provided user ID.",
    *     operationId="getUnreadNotifications",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"user_id"},
    *                 @OA\Property(
    *                     property="user_id",
    *                     type="integer",
    *                     description="User Id"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [App]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(
    *                 property="code",
    *                 type="integer",
    *                 description="Response code"
    *             ),
    *             @OA\Property(
    *                 property="msg",
    *                 type="string",
    *                 description="Message indicating success or failure"
    *             ),
    *             @OA\Property(
    *                 property="data",
    *                 type="array",
    *                 description="Array of unread notifications",
    *                 @OA\Items(
    *                     type="object",
    *                     @OA\Property(
    *                         property="notifuser_id",
    *                         type="integer"
    *                     ),
    *                     @OA\Property(
    *                         property="title",
    *                         type="string"
    *                     ),
    *                     @OA\Property(
    *                         property="noti_desc",
    *                         type="string"
    *                     ),
    *                     @OA\Property(
    *                         property="noti_type",
    *                         type="string"
    *                     ),
    *                     @OA\Property(
    *                         property="display_url",
    *                         type="string"
    *                     ),
    *                     @OA\Property(
    *                         property="view",
    *                         type="integer"
    *                     ),
    *                     @OA\Property(
    *                         property="created_at",
    *                         type="string"
    *                     )
    *                 )
    *             ),
    *             @OA\Property(
    *                 property="count",
    *                 type="integer",
    *                 description="Total count of unread notifications"
    *             )
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
        $sql = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'  AND un.trash= 0 ORDER BY un.id DESC LIMIT 3";
        $queru = DB::select($sql);
        if (count($queru)) {
            $sqld = "SELECT un.notifuser_id,n.title,n.noti_desc,n.noti_type,n.display_url,un.view,n.created_at FROM `ss_user_notifications` un INNER JOIN `ss_notifications` n ON (un.noti_id = n.id) WHERE un.user_id='$userid'  AND un.trash=0 ORDER BY un.id  DESC";
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
    


        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Mark a notification as read.
    *
    * @OA\Post(
    *     path="/api/app/notification_user_read",
    *     tags={"App Notifications"},
    *     summary="Mark a notification as read",
    *     description="This endpoint marks a notification as read based on the provided notification user ID.",
    *     operationId="markNotificationAsRead",
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"notifuser_id"},
    *                 @OA\Property(
    *                     property="notifuser_id",
    *                     type="integer",
    *                     description="Notification ID",
    *                     example="NOTIFU660FC1A85895E"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [App]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(
    *                 property="code",
    *                 type="integer",
    *                 description="Response code"
    *             ),
    *             @OA\Property(
    *                 property="msg",
    *                 type="string",
    *                 description="Message indicating success or failure"
    *             )
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
        $notifdatau =  UserNotification ::where('notifuser_id',$notifuserid)->where('view',0)->first();
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
        return json_encode($return, JSON_NUMERIC_CHECK);
      

      
       
      

    }



}
