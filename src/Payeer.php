<?php

namespace Haris\Payeer;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class Payeer
{
    const HANDLE_PARAMS = [
        'm_operation_id',
        'm_operation_ps',
        'm_operation_date',
        'm_operation_pay_date',
        'm_shop',
        'm_orderid',
        'm_amount',
        'm_curr',
        'm_desc',
        'm_status'
    ];

    const REQUEST_PARAMS = [
        'm_shop',
        'm_orderid',
        'm_amount',
        'm_curr',
        'm_desc'
    ];

    const HANDLE_TYPE = 'handle';
    const REQUEST_TYPE = 'request';
    const TYPES = [ 
        self::HANDLE_TYPE => self::HANDLE_PARAMS,
        self::REQUEST_TYPE => self::REQUEST_PARAMS
    ];

    public function __construct()
    {
        //
    }

    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'm_operation_id' => 'required',
            'm_operation_ps' => 'required',
            'm_operation_date' => 'required',
            'm_operation_pay_date' => 'required',
            'm_shop' => 'required',
            'm_orderid' => 'required',
            'm_amount' => 'required',
            'm_curr' => 'required',
            'm_desc' => 'string|nullable',
            'm_status' => 'required|in:success',
            'm_sign' => 'required'
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function validateSignature(Request $request)
    {
        $sign = $this->getSignature(self::HANDLE_TYPE, $request);
        if (!$sign) return false;
        return $request->get('m_sign') == $sign;
    }

    public function allowIP($ip)
    {
        if ($ip == '127.0.0.1' && config('payeer.locale') === true) {
            return true;
        }

        return in_array($ip, config('payeer.allowed_ips'));
    }

    public function handleTypeSignature(array &$hash, Request $request)
    {

    }

    public function requestTypeSignature(array &$hash, Request $request)
    {
        if ($request->has('m_params'))
        {
            $hash[] = $request->get('m_params');
        }
    }

    public function getSignature($type, Request $request)
    {
        $key = config('payeer.secret_key');

        if (!array_key_exists($type, self::TYPES)) return null;

        $hash = $request->only(self::TYPES[$type]);

        $method = $type . 'TypeSignature';
        if (method_exists($this, $method))$this->$method($hash, $request);

        $hash[] = $key;
        return strtoupper(hash('sha256', implode(':', $hash)));
    }

    public function getRedirectPaymentUrl($m_orderid, $amount, $description = '', $curr = null)
    {
        $m_shop = config('payeer.merchant_id');
        $m_amount = number_format($amount, 2, '.', '');
        $m_desc = base64_encode($description);
        $m_curr = $curr ?? config('payeer.currency');
        $m_key = config('payeer.secret_key');
        
        $params = collect(self::TYPES[self::REQUEST_TYPE]);
        $request = new \Illuminate\Http\Request(compact($params->all()));
        $m_sign = $this->getSignature(self::REQUEST_TYPE, $request);
        return config('payeer.url') . '?' . http_build_query(compact($params->except('m_key')->push('m_sign')->toArray()));
    }

    public function handle(Request $request)
    {
        if (!$this->allowIP($request->ip())) return $this->responseError($request->get('m_orderid'));
        if (!$this->validate($request)) return $this->responseError($request->get('m_orderid'));
        if (!$this->validateSignature($request)) return $this->responseError($request->get('m_orderid'));

        $order = $this->callSearchOrder($request);
        if (!$order) return $this->responseError($request->get('m_orderid'));

        if (Str::lower($order['status']) === 'paid') return $this->responseOK($request->get('m_orderid'));
        if (! $this->callPaidOrder($request, $order)) return $this->responseError($request->get('m_orderid'));
        return $this->responseOK($request->get('m_orderid'));
    }

    public function callSearchOrder(Request $request)
    {
        if (is_null(config('payeer.searchOrder'))) {
            throw new Exception("Search order handler not found", 500);
        }

        return App::call(config('payeer.searchOrder'), ['order_id' => $request->input('m_orderid')]);
    }

    public function callPaidOrder($request, $order)
    {
        if (is_null(config('payeer.paidOrder'))) {
            throw new Exception("Paid order handler not found", 500);
        }

        return App::call(config('payeer.paidOrder'), ['request' => $request, 'order' => $order]);
    }

    public function responseError($orderid)
    {
        return $orderid.'|error';
    }

    public function responseOK($orderid)
    {
        return $orderid.'|success';
    }
}