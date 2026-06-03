<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use Illuminate\Http\Request;

class MoovController extends Controller
{
    // Appelé par CheckoutController après sélection du mode de paiement Moov
    public function pay(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        return redirect()->route('moov.payment.form');
    }

    public function showPaymentForm(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        return view('frontend.moov.payment_form', compact('combined_order'));
    }

    // Étape 1 : envoie l'OTP par SMS (POST /moov/pay)
    public function generateOtp(Request $request)
    {
        $request->validate(['phone_number' => 'required|string']);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json(['success' => false, 'message' => translate('Session expired. Please try again.')]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        $phone  = preg_replace('/\D/', '', $request->phone_number);

        $result   = moovGenerateOtp((int) $combined_order->grand_total, $phone);
        $response = $result['response'];

        if (!$response) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')]);
        }

        if (isset($response->errorCode)) {
            return response()->json(['success' => false, 'message' => $response->errorMsg ?? translate('The service is temporarily unavailable, please try later')]);
        }

        if (!isset($response->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')]);
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
        ]);
    }

    // Étape 2 : valide l'OTP et finalise la commande (POST /moov/confirm)
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp'          => 'required|string',
            'trans_id'     => 'required|string',
            'request_id'   => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json(['success' => false, 'message' => translate('Session expired. Please try again.')]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        $response = moovCommitPayment(
            (int) $combined_order->grand_total,
            $request->phone_number,
            $request->otp,
            $request->trans_id,
            $request->request_id
        );

        if (!$response) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')]);
        }

        if (isset($response->errorCode)) {
            return response()->json(['success' => false, 'message' => $response->errorMsg ?? translate('The service is temporarily unavailable, please try later')]);
        }

        if (!isset($response->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')]);
        }

        if ((string) $response->status === '0') {
            $payment_details = json_encode([
                'transaction_id' => $response->{'trans-id'} ?? $request->trans_id,
                'phone'          => $request->phone_number,
                'method'         => 'Moov Money OTP',
            ]);

            checkout_done($combined_order->id, $payment_details);
            $request->session()->forget('club_point');

            return response()->json([
                'success' => true,
                'url'     => route('order_confirmed'),
                'message' => translate('Payment completed successfully.'),
            ]);
        }

        $errorMsg = match ((string) ($response->status ?? '')) {
            '12'    => translate('Payment failed. Please try again.'),
            '15'    => translate('Unknown error. Please try again.'),
            default => $response->message ?? translate('The service is temporarily unavailable, please try later'),
        };

        return response()->json(['success' => false, 'message' => $errorMsg]);
    }
}
