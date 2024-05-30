<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PubTransactionsUserController extends Controller
{
    /**
    * Retrieve transaction list for a specific user.
    *
    * @OA\Post(
    *     path="/api/pub/user/transactions",
    *     summary="Retrieve Transaction List",
    *     tags={"Transactions"},
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
    *         description="Transaction list retrieved successfully",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=200),
    *             @OA\Property(property="data", type="array", @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="transaction_id", type="string"),
    *                 @OA\Property(property="amount", type="number", format="float"),
    *                 @OA\Property(property="payout_method", type="string"),
    *                 @OA\Property(property="payout_transaction_id", type="string"),
    *                 @OA\Property(property="status", type="integer"),
    *                 @OA\Property(property="release_date", type="string", format="date-time"),
    *                 @OA\Property(property="remark", type="string"),
    *                 @OA\Property(property="created_at", type="string", format="date-time")
    *             )),
    *             @OA\Property(property="message", type="string", example="Data successfully retrieved!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=101,
    *         description="No data found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=101),
    *             @OA\Property(property="message", type="string", example="Not Found Data!")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Validation error",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer", example=100),
    *             @OA\Property(property="error", type="object", example={"uid": {"The uid field is required."}}),
    *             @OA\Property(property="message", type="string", example="Validation error!")
    *         )
    *     )
    * )
    */    
  	public function transacList(Request $request)
    {
        $uid  = $request->uid;
      	$trasaclist = PubPayout::select('id', 'transaction_id', 'amount','payout_method', 'payout_transaction_id', 'status', 'release_date', 'remark', 'created_at')
            		->where('publisher_id', $uid)->get();
      	$row = $trasaclist->count();  	
      	if ($row != null) {
            $return['code']    = 200;
            $return['data']    = $trasaclist;
            $return['message'] = 'data successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Not Found Data !';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}
