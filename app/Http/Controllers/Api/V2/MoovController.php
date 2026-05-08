<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CombinedOrder;
use Illuminate\Http\Request;

class MoovController extends Controller
{
    // POST /api/v2/moov/generate-otp
    public function generateOtp(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'phone_number'      => 'required|string',
        ]);

        $order = CombinedOrder::find($request->combined_order_id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => translate('Order not found.')], 404);
        }

        $phone = preg_replace('/\D/', '', $request->phone_number);
        if (empty($phone)) {
            return response()->json(['success' => false, 'message' => translate('Phone number is not valid')], 422);
        }

        $result   = moovGenerateOtp((int) $order->grand_total, $phone);
        $response = $result['response'];

        if (!$response || !isset($response->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')], 503);
        }

        if ((string) $response->status === '0') {
            return response()->json([
                'success'    => true,
                'trans_id'   => $response->{'trans-id'} ?? '',
                'request_id' => $result['request_id'],
                'message'    => translate('OTP sent to your phone. Valid for 10 minutes.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response->message ?? translate('The service is temporarily unavailable, please try later'),
        ], 400);
    }

    // POST /api/v2/moov/resend-otp
    public function resendOtp(Request $request)
    {
        $request->validate([
            'combined_order_id'  => 'required|integer',
            'phone_number'       => 'required|string',
            'original_request_id' => 'required|string',
        ]);

        $order = CombinedOrder::find($request->combined_order_id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => translate('Order not found.')], 404);
        }

        $result   = moovResendOtp((int) $order->grand_total, $request->phone_number, $request->original_request_id);
        $response = $result['response'];

        if (!$response || !isset($response->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')], 503);
        }

        if ((string) $response->status === '0') {
            return response()->json([
                'success'    => true,
                'request_id' => $result['request_id'],
                'message'    => translate('OTP resent successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response->message ?? translate('The service is temporarily unavailable, please try later'),
        ], 400);
    }

    // POST /api/v2/moov/confirm
    public function confirm(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'phone_number'      => 'required|string',
            'otp'               => 'required|string',
            'trans_id'          => 'required|string',
            'request_id'        => 'required|string',
        ]);

        $order = CombinedOrder::find($request->combined_order_id);
        if (!$order) {
            return response()->json(['success' => false, 'message' => translate('Order not found.')], 404);
        }

        $response = moovCommitPayment(
            (int) $order->grand_total,
            $request->phone_number,
            $request->otp,
            $request->trans_id,
            $request->request_id
        );

        if (!$response || !isset($response->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')], 503);
        }

        if ((string) $response->status === '0') {
            $payment_details = json_encode([
                'transaction_id' => $response->{'trans-id'} ?? $request->trans_id,
                'phone'          => $request->phone_number,
                'method'         => 'Moov Money OTP',
            ]);

            checkout_done($order->id, $payment_details);

            return response()->json([
                'success' => true,
                'message' => translate('Payment completed successfully.'),
            ]);
        }

        $errorMsg = match ((string) ($response->status ?? '')) {
            '12'    => translate('Payment failed. Please try again.'),
            '15'    => translate('Unknown error. Please try again.'),
            default => $response->message ?? translate('The service is temporarily unavailable, please try later'),
        };

        return response()->json(['success' => false, 'message' => $errorMsg], 400);
    }
}
