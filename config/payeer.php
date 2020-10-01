<?php

return [
    /**
     * Your merchant ID
     */
    'merchant_id' => env('PAYEER_MERCHANT_ID', ''),

    /**
     * Your secret key
     */
    'secret_key' => env('PAYEER_SECRET_KEY', ''),

    /**
     * Search order in the database and return order details
     */
    'searchOrder' => null, //  'App\Http\Controllers\PayeerController@searchOrder',

    /**
     * If current status != paid then call PaidOrderFilter
     * update order into DB & other actions
     */
    'paidOrder' => null, //  'App\Http\Controllers\PayeerController@paidOrder',

    /**
     * Default currency for payments
     */
    'currency' => 'USD',

    /**
     * Allowed IP's
     */
    'allowed_ips' => [
        '185.71.65.92', 
        '185.71.65.189', 
        '149.202.17.210'
    ],

    /**
     * Allow local?
     */
    'locale' => true,

    /**
     * Payeer merchant URL
     */
    'url' => 'https://payeer.com/merchant/'
];