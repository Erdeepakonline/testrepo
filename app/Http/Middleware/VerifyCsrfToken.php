<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'ajax/registration/add',
        'payment/payu',
        'payment/response',
        'payment/phonepe',
        'phonepe/response',
        'payment/payment_tazapay',
        'tazapay/response',
        'payment/airpay',
        'airpay/response',
        'payment/bitcoin',
        'payment/upscreenshot',
        'payment/stripe',
        'stripe/response',
        'payment/bitcoin/online',
        'forget/user/password',
        'forget/admin/password',
        'forgetpassword/user/submit',
        'forgetpassword/admin/submit',
      	'payment/bitcoin/online/response',
        'app_payment_bitcoin_online',
        'chagepass',
        'image/banner-image',
        'payment_payment_tazapay',
        'tazapay_response',
        'app_payment_stripe',
        'app_payment_payu',
        'app_payment_phonepe',
        'app_payment_payment_tazapay',
        'app_payment_airpay',
        'app_razorpay/failed',
        'app_phonepe/failed',

    ];
}
