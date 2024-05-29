<?php

namespace App\Http\Controllers\Publisher;

use App\Http\Controllers\Controller;
use App\Models\Publisher\PubUserPayoutMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PubUserPayoutModeController extends Controller
{
  /**
  * @OA\Post(
  *     path="/api/pub/user/payoutmodestore",
  *     summary="Store or update payout mode for a publisher",
  *     tags={"Payouts & Wallet"},
  *     @OA\RequestBody(
  *         required=true,
  *         @OA\MediaType(
  *             mediaType="multipart/form-data",
  *             @OA\Schema(
  *                 required={"pay_account_id", "payout_id", "payout_name", "publisher_id", "pub_withdrawl_limit"},
  *                 @OA\Property(property="pay_account_id", type="string", description="Account ID"),
  *                 @OA\Property(property="payout_id", type="integer", description="Payout ID"),
  *                 @OA\Property(property="payout_name", type="string", description="Payout name"),
  *                 @OA\Property(property="publisher_id", type="integer", description="Publisher ID"),
  *                 @OA\Property(property="pub_withdrawl_limit", type="number", format="float", description="Publisher withdrawal limit")
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
  *             type="object",
  *             @OA\Property(property="code", type="integer", example=200, description="Status code"),
  *             @OA\Property(property="message", type="string", description="Success message")
  *         )
  *     ),
  *     @OA\Response(
  *         response=100,
  *         description="Error response",
  *         @OA\JsonContent(
  *             type="object",
  *             @OA\Property(property="code", type="integer", example=100, description="Status code"),
  *             @OA\Property(property="error", type="object", description="Validation error details"),
  *             @OA\Property(property="message", type="string", description="Error message")
  *         )
  *     )
  * )
  */
  public function storePayoutMode (Request $request)
    {
      $validator = Validator::make($request->all(), [
        'payout_id'       	  => 'required',
        'payout_name'   	  => 'required',
        'publisher_id' 		  => 'required',
        'pub_withdrawl_limit' => 'required',
      ]);
      if ($validator->fails()) {
        $return['code'] = 100;
        $return['error'] = $validator->errors();
        $return['message'] = 'Validation error!';
        return json_encode($return);
      }
      DB::table('pub_user_payout_modes')->where('publisher_id', $request->publisher_id)->where('status', 1)->update(['status' => 0]);
      $check = PubUserPayoutMode::where('publisher_id', $request->publisher_id)->where('payout_id', $request->payout_id)->first();
      if(!empty($check))
      {
      	$check->payout_id 			= $request->payout_id;
        $check->pay_account_id 		= $request->pay_account_id;
        $check->payout_name 		= $request->payout_name;
        $check->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $check->status = 1;
        if($check->update())
        {
          $return['code'] = 200;
          $return['message'] = 'Updated Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
      }
      else
      {
      	$payoutmode = new PubUserPayoutMode;
        $payoutmode->payout_id 			= $request->payout_id;
        $payoutmode->pay_account_id 	= $request->pay_account_id;
        $payoutmode->publisher_id 		= $request->publisher_id;
        $payoutmode->payout_name 		= $request->payout_name;
        $payoutmode->pub_withdrawl_limit = $request->pub_withdrawl_limit;
        $payoutmode->status = 1;
        if($payoutmode->save())
        {
          $return['code'] = 200;
          $return['message'] = 'Added Successfully!';
        }
        else
        {
          $return['code'] = 101;
          $return['message'] = 'Something went wrong!';
        }
      }
      
      return json_encode($return, JSON_NUMERIC_CHECK);
      
    	
    }
}
