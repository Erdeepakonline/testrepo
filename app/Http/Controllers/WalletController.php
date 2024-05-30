<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{

    public function getWallet($uid)
    {
        $user = DB::table('users')->select('wallet')->where('uid', $uid)->first();
        $userWallet = $user->wallet;
        if ($user) {
            $transac = Transaction::where('advertiser_code', $uid)->where('status', 1)->orderBy('id', 'DESC')->first();
            if ($transac) {
                $date = date('d M Y', strtotime($transac->created_at));
                $transadata = array('amount' => $transac->amount, 'date' => $date);
                $return['code']     = 200;
                $wltAmt = getWalletAmount();
                $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($userWallet, 3, '.', '');
                $return['transaction']   = $transadata;
                $return['message']  = 'Wallet data successfully retrieved';
            } else {
                $return['code']      = 200;
                $wltAmt = getWalletAmount();
                $return['wallet']        = ($wltAmt) > 0 ? $wltAmt : number_format($userWallet, 3, '.', '');
                $return['message']   = 'Transaction Not Found !';
            }
        } else {
            $return['code']      = 101;
            $return['message']   = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function getUsersAll()
    {
        $user = DB::table('users')->select('*')->get();
        $rows = $user->count();
        if ($user) {
            $return['code']     = 200;
            $return['count']     = $rows;
            $return['data']   = $user;
            $return['message']  = 'User data successfully retrieved';
        } else {
            $return['code']      = 101;
            $return['message']   = 'Something went wrong!';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function index()
    {
        $wallet = Wallet::all();
        if ($wallet) {
            $return['code']    = 200;
            $return['data']    = $wallet;
            $return['message'] = 'Data Retrieved successfully';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong!';
        }
    }

    public function show($id)
    {
        $wallet = Wallet::where('id', $id)->first();
        if ($wallet) {
            $return['code']    = 200;
            $return['data']    = $wallet;
            $return['message'] = 'User wallet data retrieved';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Wallet data retrieved';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'advertiser_id'     => 'required',
                'advertiser_code'   => 'required',
                'amount'            => 'required',
                'payment_mode'      => 'required',

            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $wallet = new Wallet();

        $wallet->advertiser_id      = $request->advertiser_id;
        $wallet->advertiser_code    = $request->advertiser_code;
        $wallet->payment_id         = 'PAYID' . strtoupper(uniqid());
        $wallet->transaction_id     = $request->transaction_id;
        $wallet->amount             = $request->amount;
        $wallet->payment_mode       = $request->payment_mode;

        if ($wallet->save()) {
            $return['code']    = 200;
            $return['data']    = $wallet;
            $return['message'] = 'Wallet data successfully added';
        } else {
            $return['code']    = 101;
            $return['message'] = 'Something went wrong';
        }

        return json_encode($return, JSON_NUMERIC_CHECK);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'transaction_id'    => 'required',
                'status'            => 'required'

            ]
        );

        if ($validator->fails()) {
            $return['code']    = 100;
            $return['error']   = $validator->errors();
            $return['message'] = 'Validation Error';
            return json_encode($return);
        }

        $wallet                 = Wallet::find($id);
        $wallet->transaction_id = $request->transaction_id;
        $wallet->status         = $request->status;
        if ($wallet->update()) {
            $return['code']    = 200;
            $return['data']    = $wallet;
            $return['message'] = 'Wallet data updated successfully';
        } else {
            $return['code'] = 101;
            $return['message'] = 'Something went wrong';
        }
        return json_encode($return, JSON_NUMERIC_CHECK);
    }
}