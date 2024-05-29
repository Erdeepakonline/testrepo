<?php

namespace App\Http\Controllers\Advertisers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AppFeedbackUserController extends Controller
{
    /**
    * @OA\Post(
    *     path="/api/app/user_feedback_create",
    *     summary="Create Advertiser Feedback",
    *     tags={"App Feedback"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Provide user ID, subject, message, rating, type, and optional file attachment",
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *               required={"user_id", "subject", "message", "rating", "type"},
    *                 @OA\Property(property="user_id", type="integer", format="int64", example="1"),
    *                 @OA\Property(property="subject", type="string", example="Feedback Subject"),
    *                 @OA\Property(property="message", type="string", example="Feedback Message"),
    *                 @OA\Property(property="rating", type="integer", format="int32", example="5"),
    *                 @OA\Property(property="type", type="integer", format="int32", example="1"),
    *                 @OA\Property(property="file", type="string", format="base64", example="base64 encoded file data")
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
    *         description="Feedback Added Successfully"
    *     ),
    *     @OA\Response(
    *         response=400,
    *         description="Validation error or Something went wrong!"
    *     )
    * )
    */
    public function create_adv_feedback(Request $request){

        $validator = Validator::make(
            $request->all(),
            [
                'user_id'=> 'required',
                'subject'=>'required|max:50',
                'message'=> 'required|max:305',
                'rating'=> 'required|numeric|max:5|min:1',
                //'file'=> 'file|mimes:jpeg,png,pdf,jpg|max:2048',
                'type' => 'required|numeric|min:1|max:1'
            ]);

            if ($validator->fails()) {
                $return['code'] = 100;
                $return['error'] = $validator->errors();
                $return['message'] = 'Validation error!';
                return json_encode($return);
            }
            
            $uid = $request->user_id;
            $usersdata = User::where('uid', $uid)->first();
            // if(!empty($usersdata) && $request->file('attachment')){
            //     $image = $request->file('attachment');
            //     $attachment = 'adv-' . time() . '.' . $image->getClientOriginalExtension();
            //     $destinationPaths = base_path('public/images/feedback');
            //     $image->move($destinationPaths, $attachment);
            // }else{
            //     $attachment = '';
            // }
            if ($request->file) {
                $base_str = explode(';base64,', $request->file);
                if($base_str[0] == 'data:application/pdf'){
                    $ext = str_replace('data:application/', '', $base_str[0]);
                    $image = base64_decode($base_str[1]);
                    $imageName = Str::random(10) . '.' . $ext; ;
                    $path = public_path('images/feedback/' . $imageName);
                     file_put_contents($path, $image); 
                }else{
                    $ext = str_replace('data:image/', '', $base_str[0]);
                    $image = base64_decode($base_str[1]);
                    $imageName = Str::random(10) . '.' . $ext; ;
                    file_put_contents('images/feedback/'.$imageName, $image); 
                }
            } else {
                $imageName = '';
            }
            if(empty($usersdata)){
                $return['code'] = 101;
                $return['message'] = 'User Not Found !';
            }else{
                $feedback = new Feedback();
                $feedback->user_id = $request->user_id;
                $feedback->subject = $request->subject;
                $feedback->message = $request->message;
                $feedback->rating = $request->rating;
                $feedback->attachment = $imageName;
                $feedback->type = $request->type;

                if($feedback->save()){
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->user_id;
                $activitylog->type    = 'Advertiser Feedback';
                $activitylog->description    = $usersdata->first_name . ' ' . $usersdata->last_name . ' '. 'submitted the feedback form successfully';
                $activitylog->status    = '1';
                $activitylog->save();
                $return['code'] = 200;
                $return['message'] = 'Feedback Added Successfully.';
                } else {
                    $return['code'] = 101;
                    $return['message'] = 'Something went wrong !';
                }
            }

            return json_encode($return, JSON_NUMERIC_CHECK);
    }
}