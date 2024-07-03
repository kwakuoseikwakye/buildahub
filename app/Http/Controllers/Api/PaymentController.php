<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function initiatePayment()
    {
        try {
            $validator = Validator::make($this->request->all(), [
                "plan_code" =>  'required|exists:plans,plan_code',
                "model_id" => "required",
                'payment_method' => 'required|in:card,momo,both',
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Initiating payment failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $user = extractUserToken($this->request);

            $planAmount =  Plans::where('plan_code', $this->request->plan_code)->first();
            if (!empty($planAmount)) {
                $amt = $planAmount->amount;
            } else {
                return apiResponse('error', "Plan not found", null, 404);
            }

            if ($amt < 1) {
                return apiResponse('error', "Amount cannot be less than 1", null, 400);
            }
            $amount = match (true) {
                is_numeric($amt) && ($number = (int)($amt * 100)) >= 0 && $number <= 999999999999 =>
                str_pad($number, 12, '0', STR_PAD_LEFT),
                is_string($amt) && strlen($amt) === 12 && ctype_digit($amt) =>
                $amt,
                default => '',
            };

            $transactionId = '';
            for ($i = 0; $i < 12; $i++) {
                $transactionId .= random_int(0, 9);
            }

            $username = env("API_USER");
            $key = env("API_KEY");
            $url = "https://buildahub.net";

            Payment::create([
                "transaction_id" => $transactionId,
                "amount_paid" => $amt,
                "order_id" => $this->request->model_id,
                "userid" => $user->user_id,
                "status" => Payment::PENDING,
                "payment_mode" => "online",
            ]);

            $credentials = base64_encode($username . ':' . $key);
            $payload = json_encode([
                "merchant_id" => "TTM-00009286",
                "transaction_id" => $transactionId,
                "desc" => "Payment Using Checkout Page",
                "amount" => $amount,
                "redirect_url" => $url,
                "email" => $user->email,
                'payment_method' => $this->request->payment_method,
                'currency' => 'GHS'
            ]);

            $curl = curl_init("https://checkout-test.theteller.net/initiate");//TODO:MUST CHANGE TO LIVE URL
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Basic " . $credentials,
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ],
                CURLOPT_POSTFIELDS => $payload,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                return apiResponse('error', $err, null, 400);
            }

            return apiResponse('success', 'Payment link generated successfully', json_decode($response, true), 200);
        } catch (\Exception $e) {
            return internalServerErrorResponse('generating payment link failed', $e);
        }
    }

    public function verifyPayment($transactionId)
    {
        try {
            $validator = Validator::make($this->request->all(), [
                "payment_status_code" => "required",
            ], [
                "payment_status_code.required" => "Status code is required",
            ]);

            if ($validator->fails()) {
                return apiResponse('error', "Verifying payment failed. " . join(". ", $validator->errors()->all()), null, 422);
            }

            $payment  = Payment::where('transaction_id', $transactionId)->first();

            if (empty($payment)) {
                return apiResponse('error', 'TransactionID not found', null, 404);
            }

            if ($this->request->payment_status_code == "000") {
                if (empty($payment)) {
                    return apiResponse('error', 'Payment failed', null, 400);
                }
                Payment::where('transaction_id', $transactionId)->update(['status' => Payment::SUCCESS]);
            } else {
                return apiResponse('error', 'Payment failed', null, 400);
            }
            return apiResponse('success', 'Payment verified successfully', null, 200);
        } catch (\Exception $e) {
            return internalServerErrorResponse(' verifying payment', $e);
        }
    }
}
