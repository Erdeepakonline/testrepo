<?php

namespace App\Http\Controllers;

use App\Models\ProfileLogs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommanProfileController extends Controller
{
    /**
    * Retrieves a list of user profile logs.
    *
    *
    * @OA\Post(
    *     path="/api/profile/log-list",
    *     summary="Retrieves a list of user profile logs.",
    *     tags={"Profile"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="User ID and type",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"uid", "type"},
    *                 @OA\Property(property="uid", type="integer", description="User ID"),
    *                 @OA\Property(property="type", type="string", enum={"advertiser", "publisher"}, description="User type"),
    *                 @OA\Property(property="page", type="integer", format="int32", description="Page number", example="1"),
    *                 @OA\Property(property="lim", type="integer", format="int32", description="Limit per page", example="10")
    *             )
    *         ),
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="uid", type="integer", description="User ID"),
    *                 @OA\Property(property="profile_data", type="array", @OA\Items(type="object"), description="User profile data"),
    *                 @OA\Property(property="user_type", type="integer", description="User type"),
    *                 @OA\Property(property="createdat", type="string", description="Date and time of profile log creation")
    *             )),
    *             @OA\Property(property="row", type="integer", description="Total number of rows")
    *         )
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Bad Request",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message indicating a bad request"),
    *             @OA\Property(property="error", type="object", description="Validation errors")
    *         )
    *     ),
    *     @OA\Response(
    *         response=404,
    *         description="Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Message indicating that no profile logs were found for the user")
    *         )
    *     )
    * )
    */
    public function userProfileLogList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid'       => 'required',
            ],[
                'uid.required'=>'The User ID field is required.'
            ]
        );
        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;
        $profile_data = [];
        ($request->type == 'advertiser') ? $utype = 1 : $utype = 2;
        $data = ProfileLogs::select('uid','profile_data','user_type', DB::raw("DATE_FORMAT(created_at, '%d %b %Y %h:%i %p') as createdat"))->where('uid',$uid)
        ->where(function($query) use ($utype) {
            $query->where('user_type', $utype)
                  ->orWhere('user_type', 3);
        });
        //->where('user_type', $utype);
        $count = $data->count();
        $data = $data->offset($start)->limit($limit)->orderBy('id','DESC')->get();
        foreach ($data as $log) {
            $profile_data = [json_decode($log->profile_data)];
            $log->profile_data = $profile_data;
        }
        if ($data) {
            $return['data']    = $data;
            $return['row']     = $count;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }  
    public function adminUserProfileLogList(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'uid'       => 'required',
            ],[
                'uid.required'=>'The User ID field is required.'
            ]
        );

        if ($validator->fails()) {
            $return['code']      = 100;
            $return['message']   = 'Validation Error';
            $return['error']     = $validator->errors();
            return json_encode($return);
        }
        $uid = $request->uid;
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1; 
        $start = ($pg > 0) ? $limit * $pg : 0;
        $profile_data = [];
        $data = ProfileLogs::select('uid','profile_data','user_type','created_at','switcher_login')->where('uid',$uid);
        $count = $data->count();
        $data = $data->offset($start)->limit($limit)->orderBy('id','DESC')->get();
        foreach ($data as $log) {
            $profile_data = [json_decode($log->profile_data)];
            $log->profile_data = $profile_data;
        }

        if ($data) {
            $return['data']    = json_decode($data);
            $return['row']     = $count;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    } 
}
