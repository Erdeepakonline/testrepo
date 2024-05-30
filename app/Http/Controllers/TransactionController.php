<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make([

            'advertiser_id'     => 'required',
            'advertiser_code'   => 'required',
            'payment_id'        => 'required',
            'payment_mode'      => 'required',
            'remark'            => 'required'
        ]);
        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error';

            return json_encode($return);
        }
        $transac = new Transaction();
        $transac->advertiser_id    = $request->advertiser_id;
        $transac->advertiser_code  = $request->advertiser_code;
        $transac->payment_id       = $request->payment_id;
        $transac->payment_mode     = $request->payment_mode;
        $transac->remark           = $request->remark;

        if ($transac->save()) {
            $return['code'] = 200;
            $return['data'] = $transac;
            $return['message'] = 'Transaction data retrieved successfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong!';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {
        $validator = Validator::make([
            'advertiser_id'     => 'required',
            'advertiser_code'   => 'required',
            'payment_id'        => 'required',
            'payment_mode'      => 'required',
            'remark'            => 'required',

        ]);

        if ($validator->fails()) {
            $return['code'] = 100;
            $return['error'] = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        // $transac = Transaction::where('id', $id)->first();
        // $transac->advertiser_id = $request->advertiser_id;
        // $transac->advertiser_code = $request->advertiser_code;
        // $transac->payment_id = $request->payment_id;
        // $transac->payment_mode = $request->payment_mode;
    }

    public function fetchtransaction_old(Request $request)
    {
        $uid = $request->input('uid');
        $users = User::where('uid', $uid)->first();
        if (empty($users)) {
            $return['code'] = 100;
            $return['message'] = 'Not Found User';
            return json_encode($return);
        } else {
            $gettrans = "SELECT tr.transaction_id, IF(tr.status = 1, tlog.amount, tr.amount) as amt, tr.status, tr.created_at, tlog.remark FROM `ss_transactions` tr LEFT JOIN ss_transaction_logs tlog ON tr.transaction_id=tlog.transaction_id WHERE tr.advertiser_code = '$uid' ORDER BY tr.id DESC LIMIT 0, 10 ";
            $gettrans = DB::select($gettrans);
            //  $gettrans = TransactionLog::where('advertiser_code',$uid)->orderBy('id','DESC')->limit(10)->get()->toArray();
            if (empty($gettrans)) {
                $return['code'] = 100;
                $return['message'] = 'No Transaction ';
                return json_encode($return);
            } else {
                foreach ($gettrans as $value) {

                    $date = date('d M Y', strtotime($value->created_at));
                    $paymentmode = $value->remark;
                    $transactionid = $value->transaction_id;
                    $amount = $value->amt;
                    $status = $value->status;
                    $data[] = array('transaction_id' => $transactionid, 'payment_mode' => $paymentmode, 'amount' => $amount, 'status' => $status, 'date' => $date);
                }
                $return['code'] = 200;
                $return['message'] = 'successfully';
                $return['data'] = $data;
            }
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
  
    public function fetchtransaction_new(Request $request)
      {
          $uid = $request->input('uid');
          $limit = $request->lim;
          $page = $request->page;
          $pg = $page - 1;
          $start = ($pg > 0) ? $limit * $pg : 0;

          $users = User::where('uid', $uid)->first();
          if (empty($users)) {
              $return['code'] = 100;
              $return['message'] = 'Not Found User';
              return json_encode($return);
          } else {
              /*
              $gettrans = "SELECT tr.transaction_id, IF(tr.status = 1, tlog.amount, tr.amount) as amt, tr.status, tr.created_at, tlog.remark FROM `ss_transactions` tr LEFT JOIN ss_transaction_logs tlog ON tr.transaction_id=tlog.transaction_id WHERE tr.advertiser_code = '$uid' ORDER BY tr.id DESC LIMIT 0, 10 ";
              $gettrans = DB::select($gettrans);
              */
             $gettrans = TransactionLog::where('advertiser_code',$uid);
             $row = $gettrans->count();
             if($page > 0 && $limit > 0)
             {
             	$data = $gettrans->offset($start)->limit($limit)->orderBy('id', 'desc')->get()->toArray();
             }
             else
             {
               $data = $gettrans->orderBy('id', 'desc')->get()->toArray();
             }
              if (empty($gettrans)) {
                  $return['code'] = 100;
                  $return['message'] = 'No Transaction ';
                  return json_encode($return);
              } else {

                  foreach ($data as $value) {
                      $date = date('d M Y', strtotime($value['created_at']));
                      $paymentmode = $value['remark'];
                      $transactionid = $value['transaction_id'];
                      $amount = $value['amount'];
                      $cpn_typ = $value['cpn_typ'];
                      $status = 1;
                      $datas[] = array('transaction_id' => $transactionid, 'payment_mode' => $paymentmode, 'amount' => $amount, 'status' => $status, 'date' => $date, 'cpn_typ' => $cpn_typ);
                  } 
                  $return['code'] = 200;
                  $return['message'] = 'successfully';
                  $return['data'] = $datas;
                  $return['row']    = $row;
              }
          }
          return json_encode($return, JSON_NUMERIC_CHECK);
      }
  
  public function fetchtransaction(Request $request)
    {
        $uid = $request->input('uid');
        $limit = $request->lim;
        $page = $request->page;
        $pg = $page - 1;
        $start = ($pg > 0) ? $limit * $pg : 0;

        $users = User::where('uid', $uid)->first();


        if (!empty($users)) {
            $trlog = DB::table('transactions')
                ->select('transactions.transaction_id', 'transactions.advertiser_code', 'transactions.payment_mode', 'transactions.status', 'transaction_logs.remark', 'transaction_logs.cpn_typ', DB::raw("IF(ss_transaction_logs.id>0, ss_transaction_logs.amount, ss_transactions.amount ) as amount"))
                ->selectRaw("DATE_FORMAT(ss_transactions.created_at, '%d %b %Y %h:%i %p' ) as date")
                ->leftJoin('transaction_logs', function ($join) {
                    $join->on('transaction_logs.transaction_id', '=', 'transactions.transaction_id');
                })
                ->where('transactions.advertiser_code', $uid)
                ->where('transactions.status', '<', 2)
                ->orderBy('transactions.id', 'desc');
            $row = $trlog->count();
            $datas = $trlog->offset($start)->limit($limit)->get();

            $return['code']     = 200;
            $return['message']  = 'successfully';
            $return['data']     = $datas;
            $wltAmt = getWalletAmount();
            $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($users->wallet, 3, '.', '');
            $return['row']      = $row;
        } else {

            $return['code'] = 100;
            $return['message'] = 'Not Found User';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    /**
    * @OA\POST(
    *     path="/api/user/gateway/list",
    *     summary="Get list of Payment Gateway",
    *     tags={"Manage Payment Gateway"},
      *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(property="uid", type="string", description="Name of the user id")
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
    *         description="Successful operation",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="data", type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="string"),
    *                     @OA\Property(property="value", type="string")
    *                 )
    *             ),
    *             @OA\Property(property="message", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response=100,
    *         description="Data Not found",
    *         @OA\JsonContent(
    *             @OA\Property(property="code", type="integer"),
    *             @OA\Property(property="message", type="string")
    *         )
    *     )
    * )
    */
    public function gatewayList(Request $request){
        $getList = DB::table('payment_gateways')->select('title','sub_title','image','order_no','status')->where('status',1);
        $row = $getList->count();
        $data = $getList->orderBy('order_no','ASC')->get();
        if ($data) {
            $return['code']    = 200;
            $return['data']    = $data;
            $return['row']    = $row;
            $return['message'] = 'Data Found Successfully!';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Data Not Found!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}