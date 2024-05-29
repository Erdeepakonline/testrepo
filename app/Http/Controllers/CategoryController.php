<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    // public function index()
    // {
    //     $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
    //     if ($category) {
    //         $return = $category;
    //     } else {
    //         $return['code']    = 101;
    //         $return['message'] = 'Something went wrong!';
    //     }

    //     return json_encode($return, JSON_NUMERIC_CHECK);
    // }
    

    /**
    * @OA\Get(
    *     path="/api/category/index",
    *     summary="Get Categories for Advertiser",
    *     tags={"Categories"},
    *     @OA\Parameter(
    *         name="uid",
    *         in="query",
    *         required=true,
    *         description="User ID",
    *         @OA\Schema(
    *             type="string"
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
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="value", type="integer", description="Category ID"),
    *                 @OA\Property(property="label", type="string", description="Category Name")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Something went wrong!'",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function index()
    {
        $uid = $_GET['uid'];
        $userRecord = User::where('uid',$uid)->where('account_type', 0)->first();
        if(!empty($userRecord)){
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id','!=',113)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }else{
            $category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
        return json_encode($return);
    }
    

    /**
    * @OA\Post(
    *     path="/api/pub/category",
    *     summary="Get Categories for Publisher",
    *     tags={"Categories"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/x-www-form-urlencoded",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(
    *                     property="uid",
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
    *         description="x-api-key [Publisher]",
    *         @OA\Schema(
    *             type="string"
    *         )
    *     ),
    *     @OA\Response(
    *         response=200,
    *         description="Success response",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="value", type="integer", description="Category ID"),
    *                 @OA\Property(property="label", type="string", description="Category Name")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="Something went wrong!'",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", description="Status code"),
    *             @OA\Property(property="message", type="string", description="Error message")
    *         )
    *     )
    * )
    */
    public function pubCategoryList(Request $request)
    {
      	$user = User::select('account_type')->where('uid', $request->uid)->first();
      	if($user->account_type == 0 )
        {
        	$category = Category::select('id as value', 'cat_name as label')->where('id', '!=', 113)->where('id', '!=', 64)->where('status', 1)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
      	else{
        	$category = Category::select('id as value', 'cat_name as label')->where('status', 1)->where('id', '!=', 64)->where('trash', 0)->orderBy('label', 'asc')->get()->toArray();
        }
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function getCategoryList()
    {
        $category = Category::select('id as value', 'cat_name as label', 'status')
            ->where('trash', 0)
            ->orderBy('label', 'asc')->get()->toArray();
        if ($category) {
            $return = $category;
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
