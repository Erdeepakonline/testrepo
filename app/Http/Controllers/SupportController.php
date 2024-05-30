<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Supports;
use App\Models\SupportLog;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\CreateSupportMail;
use Exception;


class SupportController extends Controller
{

    public function randomToken()
    {
        $ticketno =  'TK' . strtoupper(uniqid());
        $checkdata = Supports::where('ticket_no', $ticketno)->count();
        if ($checkdata > 0) {
            $this->randomToken();
        } else {
            return $ticketno;
        }
    }

    /**
    * @OA\Post(
    *     path="/api/user/support/create",
    *     summary="Create support ticket Advertiser",
    *     tags={"Advertiser - Support"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "category", "subject", "message"},
    *                 @OA\Property(
    *                     property="uid",
    *                     type="string",
    *                     description="User ID",
    *                 ),
    *                 @OA\Property(
    *                     property="category",
    *                     type="string",
    *                     description="Category",
    *                 ),
    *                 @OA\Property(
    *                     property="sub_category",
    *                     type="string",
    *                     description="Sub-category",
    *                 ),
    *                 @OA\Property(
    *                     property="support_type",
    *                     type="string",
    *                     description="Support type",
    *                 ),
    *                 @OA\Property(
    *                     property="subject",
    *                     type="string",
    *                     description="Subject",
    *                 ),
    *                 @OA\Property(
    *                     property="message",
    *                     type="string",
    *                     description="Message",
    *                 ),
    *                 @OA\Property(
    *                     property="file",
    *                     type="file",
    *                     description="Attachment file",
    *                 ),
    *                 @OA\Property(
    *                     property="priority",
    *                     type="string",
    *                     description="Priority",
    *                 ),
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
    *             @OA\Property(property="data", type="object", description="Support log details"),
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function create_support(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'category' => 'required',
            'subject' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        if ($request->file('file')) {
            $imagelogo = $request->file('file');
            $logos = time() . '.' . $imagelogo->getClientOriginalExtension();
            $destinationPaths = base_path('public/images/support/');
            $imagelogo->move($destinationPaths, $logos);
        } else {
            $logos = '';
        }
        $ticketno =  $this->randomToken();
        $uid = $request->input('uid');
        $usersdata = User::where('uid', $uid)->first();

        if (empty($usersdata)) {
            $return['code'] = 101;
            $return['message'] = 'User Not Found !';
        } else {
            $fullname =  "$usersdata->first_name  $usersdata->last_name";

            $support                   = new Supports();
            $support->uid              = $uid;
            $support->ticket_no        = $ticketno;
            $support->category         = $request->input('category');
            $support->sub_category     = $request->input('sub_category');
            $support->support_type     = $request->input('support_type');
            $support->subject          = $request->input('subject');
            $support->message          = $request->input('message');
            $support->file             = $logos;
            $support->status           = 1;
            $support->priority         = $request->input('priority');
            if ($support->save()) {

                $supportlog                    = new SupportLog();
                $supportlog->support_id        = $support->id;
                $supportlog->ticket_no         = $ticketno;
                $supportlog->message           = $support->message;
                $supportlog->file              = $support->file;
                $supportlog->status            = 0;
                $supportlog->created_by        = 'User';
                $supportlog->user_id           = $support->uid;
                $supportlog->user_name         = $fullname;
                $supportlog->save();
                /* Activity Log  */
                $activitylog = new Activitylog();
                $activitylog->uid    = $uid;
                $activitylog->type    = 'Support';
                $activitylog->description    = 'Support Ticket' . $ticketno . ' is Added Successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                $email = $usersdata->email;
                $useridas = $usersdata->uid;
                $ticketno = $support->ticket_no;
                $data['userfullname'] = $fullname;
                $data['useridadmn'] = $useridas;
                $data['usercmpdetils'] = $support->message;
                $data['details'] = array('subject' => 'Your complaint registered', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'token_no' => $ticketno);
                /* User Section */
                $subject = "Your complaint registered $ticketno - 7Search PPC";
                $body =  View('emailtemp.supportcreate', $data);
                /* User Mail Section */
                $sendmailUser =  sendmailUser($subject,$body,$email);
                if($sendmailUser == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                }
                /* Admin Section  */
                $adminmail1 = 'advertisersupport@7searchppc.com';
                $adminmail2 = 'info@7searchppc.com';
                $bodyadmin =   View('emailtemp.supportcreateadmin', $data);
                $subjectadmin ="Your complaint registered $ticketno - 7Search PPC";
                $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
                if($sendmailadmin == '1') 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['message']  = 'Mail Send & Data Inserted Successfully !';
                }
                else 
                {
                    $return['code'] = 200;
                    $return['data']    = $supportlog;
                    $return['message']  = 'Mail Not Send But Data Insert Successfully !';
                }
            } else {
                $return['code'] = 101;
                $return['message'] = 'Something went wrong !';
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * @OA\Post(
    *     path="/api/user/support/list",
    *     summary="List Support Tickets Advertiser",
    *     tags={"Advertiser - Support"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid"},
    *                 @OA\Property(property="lim", type="integer", description="Limit", example="10"),
    *                 @OA\Property(property="page", type="integer", description="Page number", example="1"),
    *                 @OA\Property(property="uid", type="string", description="User ID"),
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Advertiser]",
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
    *             @OA\Property(property="data", type="string"),
    *             @OA\Property(property="row", type="integer", description="Total number of rows"),
    *             @OA\Property(property="wallet", type="number", description="User's wallet balance"),
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function list_support(Request $request)
    {
        $limit = $request->lim;
        $page = $request->page;
        $uid = $request->uid;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $userdata = User::where('uid', $uid)->first();
      	if(empty($userdata))
        {
        	$return['code']    = 101;
            $return['message'] = 'User not found!';
          	return json_encode($return);
        }
        $support = DB::table('supports')
          		   ->where('support_type', 'User')
          		   ->where('uid', $uid)
          		   ->orderBy('id', 'DESC');
        $row = $support->count();
        $data = $support->offset($start)->limit($limit)->get();
        foreach ($data as $value) {
            $ticket_no = $value->id;
            $datamsg = SupportLog::where('support_id', $ticket_no)->orderBy('id', 'DESC')->first();
            $value->message = $datamsg->message;
            $value->message_by = $datamsg->user_name;
        }
      	if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']     = $row;
            $wltAmt = getWalletAmount();
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($userdata->wallet, 3, '.', '');
            $return['message'] = 'Support list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Get support ticket information.
    *
    * @OA\Post(
    *     path="/api/user/support/info",
    *     tags={"Advertiser - Support"},
    *     summary="Get Support Ticket Information Advertiser",
    *     operationId="getSupportInfo",
    *     @OA\RequestBody(
    *         required=true,
    *         description="Pass user ID and ticket number",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "ticket_no"},
    *                 @OA\Property(property="uid", type="string", format="string"),
    *                 @OA\Property(property="ticket_no", type="string", format="string")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Advertiser]",
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
    *             @OA\Property(property="support", type="object",
    *                 @OA\Property(property="category", type="string"),
    *                 @OA\Property(property="sub_category", type="string"),
    *                 @OA\Property(property="support_type", type="string"),
    *                 @OA\Property(property="subject", type="string"),
    *                 @OA\Property(property="message", type="string"),
    *                 @OA\Property(property="file", type="string"),
    *                 @OA\Property(property="status", type="integer")
    *             ),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="user_name", type="string"),
    *                     @OA\Property(property="created_by", type="string"),
    *                     @OA\Property(property="ticket_no", type="string"),
    *                     @OA\Property(property="user_id", type="string"),
    *                     @OA\Property(property="message", type="string"),
    *                     @OA\Property(property="file", type="string"),
    *                     @OA\Property(property="created_at", type="string", format="date-time"),
    *                     @OA\Property(property="status", type="integer")
    *                 )
    *             ),
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function info(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'ticket_no' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $uid = $request->uid;
        $ticketno = $request->ticket_no;
        $support = Supports::select('category', 'sub_category', 'support_type', 'subject', 'message', 'file', 'status')
            ->where('uid', $uid)->where('ticket_no', $ticketno)->orderBy('id', 'DESC')->first();
        if ($support) {
            $supportLog = SupportLog::select('user_name', 'created_by', 'ticket_no', 'user_id', 'message', 'file', 'created_at', 'status')
                ->where('user_id', $uid)->where('ticket_no', $ticketno)->orderBy('id', 'ASC')->get();
        }
        if ($supportLog) {
            $return['code']    = 200;
            $return['support']    = $support;
            $return['data']    = $supportLog;
            $return['message'] = 'Chat list retrieved successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Send a message in the support chat.
    *
    * @OA\Post(
    *     path="/api/user/support/chat",
    *     tags={"Advertiser - Support"},
    *     summary="Send Message In The Support Chat Advertiser",
    *     operationId="sendSupportMessage",
    *     @OA\RequestBody(
    *         required=true,
    *         description="Pass user ID, ticket number, and message",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "ticket_no", "message"},
    *                 @OA\Property(property="uid", type="string", format="string"),
    *                 @OA\Property(property="ticket_no", type="string", format="string"),
    *                 @OA\Property(property="message", type="string", format="string"),
    *                 @OA\Property(property="file", type="string", format="binary")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Advertiser]",
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
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="id", type="integer", description="Support log ID"),
    *                 @OA\Property(property="support_id", type="integer", description="ID of the associated support ticket"),
    *                 @OA\Property(property="ticket_no", type="string", description="Ticket number"),
    *                 @OA\Property(property="message", type="string", description="Message content"),
    *                 @OA\Property(property="file", type="string", description="File path"),
    *                 @OA\Property(property="status", type="integer", description="Message status"),
    *                 @OA\Property(property="created_by", type="string", description="Message creator"),
    *                 @OA\Property(property="user_id", type="string", description="User ID"),
    *                 @OA\Property(property="user_name", type="string", description="User name"),
    *                 @OA\Property(property="created_at", type="string", description="Message creation timestamp")
    *             ),
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function chat(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'uid' => 'required',
            'ticket_no' => 'required',
            'message' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        if ($request->file('file')) {
            $imagelogo = $request->file('file');
            $logos = time() . '.' . $imagelogo->getClientOriginalExtension();
            $destinationPaths = base_path('public/images/support/');
            $imagelogo->move($destinationPaths, $logos);
        } else {
            $logos = '';
        }

        $ticketno = $request->input('ticket_no');
        $uid = $request->input('uid');

        $usersdata = User::where('uid', $uid)->first();
        $fullname =  "$usersdata->first_name $usersdata->last_name";
        $support = Supports::where('uid', $uid)->where('ticket_no', $ticketno)->first();


        $supportlog                    = new SupportLog();
        $supportlog->support_id        = $support->id;
        $supportlog->ticket_no         = $ticketno;
        $supportlog->message           = $request->input('message');
        $supportlog->file              = $logos;
        $supportlog->status            = 0;
        $supportlog->created_by        = 'User';
        $supportlog->user_id           = $uid;
        $supportlog->user_name         = $fullname;
        if ($supportlog->save()) {
          
          $email = $usersdata->email;
          $useridas = $usersdata->uid;
          $ticketno = $support->ticket_no;
          $data['userfullname'] = $fullname;
          $data['useridadmn'] = $useridas;
          $data['usercmpdetils'] = $support->message;
          $data['details'] = array('subject' => 'Your complaint registered', 'email' => $email, 'user_id' => $useridas, 'full_name' => $fullname, 'token_no' => $ticketno);
          
          /* Admin Section  */
          $adminmail1 = 'advertisersupport@7searchppc.com';
          $adminmail2 = 'info@7searchppc.com';
          $bodyadmin =   View('emailtemp.supportcreateadmin', $data);
          $subjectadmin ="Your complaint registered $ticketno - 7Search PPC";
          $sendmailadmin =  sendmailAdmin($subjectadmin,$bodyadmin,$adminmail1,$adminmail2); 
          if($sendmailadmin == '1') 
          {
            $return['code'] = 200;
            $return['data']    = $supportlog;
            $return['message']  = 'Mail Send & Data Inserted Successfully !';
          }
          else 
          {
            $return['code'] = 200;
            $return['data']    = $supportlog;
            $return['message']  = 'Mail Not Send But Data Insert Successfully !';
          }
          
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }


    /**
    * Delete a support ticket.
    *
    * @OA\Post(
    *     path="/api/user/support/delete",
    *     tags={"Advertiser - Support"},
    *     summary="Delete Support Ticket Advertiser",
    *     operationId="deleteSupportTicket",
    *     @OA\RequestBody(
    *         required=true,
    *         description="Pass user ID and ticket number",
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 required={"uid", "ticket_no"},
    *                 @OA\Property(property="uid", type="string", format="string"),
    *                 @OA\Property(property="ticket_no", type="string", format="string")
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Advertiser]",
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
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ticket_no' => 'required',
            'uid' => 'required',
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Valitation error!';
            return json_encode($return);
        }
        $ticketno = $request->input('ticket_no');
        $uid = $request->input('uid');
        $support = Supports::where('uid', $uid)->where('ticket_no', $ticketno)->first();
        if ($support) {
            $delete =  $support->delete();
            if ($delete) {
                $return['code']    = 200;
                $return['message'] = 'Deleted successfully!';
            } else {
                $return['code']    = 101;
                $return['message'] = 'Something went wrong!';
            }
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
