<?php

namespace Haris\Payeer;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class Payeer
{
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
            'm_desc' => 'required',
            'm_status' => 'required,in:success',
            'm_sign' => 'required'
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function validateSignature(Request $request)
    {
        $sign = $this->getSignature($request);

        if ($request->get('m_sign') != $sign) {
            return false;
        }

        return true;
    }

    public function allowIP($ip)
    {
        if ($ip == '127.0.0.1' && config('payeer.locale') === true) {
            return true;
        }

        return in_array($ip, config('payeer.allowed_ips'));
    }

    public function getSignature(Request $request)
    {
        $key = config('payeer.secret_key');

        $hash = array(
            $request->get('m_operation_id'),
            $request->get('m_operation_ps'),
            $request->get('m_operation_date'),
            $request->get('m_operation_pay_date'),
            $request->get('m_shop'),
            $request->get('m_orderid'),
            $request->get('m_amount'),
            $request->get('m_curr'),
            $request->get('m_desc'),
            $request->get('m_status')
        );

        if ($request->has('m_params'))
        {
            $hash[] = $request->get('m_params');
        }

        $hash[] = $key;

        return strtoupper(hash('sha256', implode(':', $hash)));
    }

    public function handle(Request $request)
    {
        if (!$this->allowIP($request->ip())) return $this->responseError($request->get('m_orderid'));
        if (!$this->validate($request)) return $this->responseError($request->get('m_orderid'));
        if (!$this->validateSignature($request)) return $this->responseError($request->get('m_orderid'));

        $order = $this->callSearchOrder($request);
        if (!$order) return $this->responseError($request->get('m_orderid'));

        if (Str::lower($order['_orderStatus']) === 'paid') return $this->responseOK($request->get('m_orderid'));
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

    public function callPaidOrder($order)
    {
        if (is_null(config('payeer.paidOrder'))) {
            throw new Exception("Paid order handler not found", 500);
        }

        return App::call(config('payeer.paidOrder'), ['order' => $order]);
    }

    public function responseError($orderid)
    {
        return $orderid.'|success';
    }

    public function responseOK($orderid)
    {
        return $orderid.'|error';
    }
}