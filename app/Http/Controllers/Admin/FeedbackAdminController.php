<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Feedback;

class FeedbackAdminController extends Controller
{
    /**
    * Retrieve a list of advertisers with feedback.
    *
    * @OA\Post(
    *     path="/api/admin/feedback/advertiser/list",
    *     tags={"Admin Feedback"},
    *     summary="Retrieve a list of advertisers with feedback",
    *     description="This endpoint retrieves a list of advertisers along with their feedback based on the provided parameters.",
    *     operationId="getAdvertiserList",
    *     @OA\RequestBody(
    *         required=false,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="lim",
    *                     type="integer",
    *                     description="Limit the number of results per page",
    *                     example="10"
    *                 ),
    *                 @OA\Property(
    *                     property="page",
    *                     type="integer",
    *                     description="Page number for pagination",
    *                     example="1"
    *                 ),
    *                 @OA\Property(
    *                     property="src",
    *                     type="string",
    *                     description="Search string to filter results by user ID or email"
    *                 ),
    *                 @OA\Property(
    *                     property="rating",
    *                     type="integer",
    *                     description="Filter results by feedback rating"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="x-api-key",
    *         in="header",
    *         required=true,
    *         description="x-api-key [Admin]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="auth",
    *         in="header",
    *         required=true,
    *         description="Authorization [Admin]",
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
    *                 property="data",
    *                 type="array",
    *                 description="List of advertisers with feedback",
    *                 @OA\Items()
    *             ),
    *             @OA\Property(
    *                 property="row",
    *                 type="integer",
    *                 description="Total number of rows"
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
    public function get_advertiser_list(Request $request){
    $limit = $request->lim;
    $page = $request->page;
    $src = $request->src;
    $rating = $request->rating;
    $pg = $page - 1;
    $start = ($pg > 0) ? $limit * $pg : 0;

    $result = Feedback::join('users','feedbacks.user_id','=', 'users.uid')
              ->where('type',1)
              ->select('feedbacks.*','users.email');
    if($rating){
    $result->where('feedbacks.rating',$rating);
   }

    if ($src) {
       $result->where('feedbacks.user_id', 'like', "%{$src}%");
       $result->orWhere('users.email', 'like', "%{$src}%");
    }

    $row = $result->count();
    $res = $result->offset($start)->limit($limit)->orderByDesc('id')->get();

    if ($res->isNotEmpty()) {
        $return['code'] = 200;
        $return['data'] = $res;
        $return['row'] = $row;
        $return['msg'] = 'Data Successfully found !';
    } else {
        $return['code'] = 100;
        $return['msg'] = 'Data Not found !';
    }
      return json_encode($return, JSON_NUMERIC_CHECK);
    }



    // fetch publisher feedback list
    public function get_publisher_list(Request $request){
        $limit = $request->lim;
        $page = $request->page;
        $src = $request->src;
        $pg = $page - 1;
        $rating = $request->rating;
        $start = ($pg > 0) ? $limit * $pg : 0;
    
        $result = Feedback::join('users','feedbacks.user_id','=', 'users.uid')
                  ->where('type',2)
                  ->select('feedbacks.*','users.email');
        if($rating){
            $result->where('feedbacks.rating',$rating);
        }

        if ($src) {
           $result->where('feedbacks.user_id', 'like', "%{$src}%");
           $result->orWhere('users.email', 'like', "%{$src}%");
        }
    
        $row = $result->count();
        $res = $result->offset($start)->limit($limit)->orderByDesc('id')->get();
    
        if ($res->isNotEmpty()) {
            $return['code'] = 200;
            $return['data'] = $res;
            $return['row'] = $row;
            $return['msg'] = 'Data Successfully found !';
        } else {
            $return['code'] = 100;
            $return['msg'] = 'Data Not found !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
