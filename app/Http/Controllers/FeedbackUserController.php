<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\User;
use App\Models\Activitylog;
use Illuminate\Support\Facades\Validator;

class FeedbackUserController extends Controller
{
    /**
    * @OA\Post(
    *     path="/api/user/feedback/create",
    *     summary="Create advertiser feedback",
    *     tags={"Feedback"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id", "subject", "message", "rating", "type"},
    *                 @OA\Property(property="user_id", type="string", description="User ID"),
    *                 @OA\Property(property="subject", type="string", description="Subject of the feedback"),
    *                 @OA\Property(property="message", type="string", description="Message of the feedback"),
    *                 @OA\Property(property="rating", type="number", format="float", description="Rating of the feedback (between 1 and 5)"),
    *                 @OA\Property(property="attachment", type="string", format="binary", description="Attachment file (JPEG, PNG, PDF, JPG; max size 2MB)"),
    *                 @OA\Property(property="type", type="string", description="Type of feedback (1 for advertiser feedback)")
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
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation Error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="message", type="string", description="Message indicating validation error")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="User Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     ),
    *     @OA\Response(
    *         response=102,
    *         description="Something went wrong",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
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
                'attachment'=> 'file|mimes:jpeg,png,pdf,jpg|max:2048',
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
            if(!empty($usersdata) && $request->file('attachment')){
                $image = $request->file('attachment');
                $attachment = 'adv-' . time() . '.' . $image->getClientOriginalExtension();
                $destinationPaths = base_path('public/images/feedback');
                $image->move($destinationPaths, $attachment);
            }else{
                $attachment = '';
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
                $feedback->attachment = $attachment;
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



    /**
    * @OA\Post(
    *     path="/api/pub/user/feedback/create",
    *     summary="Create publisher feedback",
    *     tags={"Feedback"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 required={"user_id", "subject", "message", "rating", "type"},
    *                 @OA\Property(property="user_id", type="string", description="User ID"),
    *                 @OA\Property(property="subject", type="string", description="Subject of the feedback"),
    *                 @OA\Property(property="message", type="string", description="Message of the feedback"),
    *                 @OA\Property(property="rating", type="number", format="float", description="Rating of the feedback (between 1 and 5)"),
    *                 @OA\Property(property="attachment", type="string", format="binary", description="Attachment file (JPEG, PNG, PDF, JPG; max size 2MB)"),
    *                 @OA\Property(property="type", type="string", description="Type of feedback (2 for publisher feedback)")
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
    *             @OA\Property(property="message", type="string", description="Message indicating success")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation Error",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="error", type="object", description="Validation errors"),
    *             @OA\Property(property="message", type="string", description="Message indicating validation error")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="User Not Found",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     ),
    *     @OA\Response(
    *         response=102,
    *         description="Something went wrong",
    *         @OA\JsonContent(
    *             type="object",
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function create_pub_feedback(Request $request){
         $validator = Validator::make(
            $request->all(),
            [
                'user_id'=> 'required', 
                'subject'=>'required|max:50',
                'message'=> 'required|max:305',
                'rating'=> 'required|numeric|max:5|min:1',
                'attachment'=> 'file|mimes:jpeg,png,pdf,jpg|max:2048',
                'type' => 'required|numeric|min:2|max:2'
            ]);

            if ($validator->fails()) {
                $return['code'] = 100;
                $return['error'] = $validator->errors();
                $return['message'] = 'Validation error!';
                return json_encode($return);
            }
            $uid = $request->user_id;
            $usersdata = User::where('uid', $uid)->first();
            if(!empty($usersdata) && $request->file('attachment')){
                $image = $request->file('attachment');
                $attachment = 'pub-' . time() . '.' . $image->getClientOriginalExtension();
                $destinationPaths = base_path('public/images/feedback');
                $image->move($destinationPaths, $attachment);
            }else{
                $attachment = '';
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
                $feedback->attachment = $attachment;
                $feedback->type = $request->type;

                if($feedback->save()){
                $activitylog = new Activitylog();
                $activitylog->uid    = $request->user_id;
                $activitylog->type    = 'Publisher Feedback';
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
