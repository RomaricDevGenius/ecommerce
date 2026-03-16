<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use App\Models\WaitingTransaction;
use Illuminate\Http\Request;
use Session;

class MoovController extends Controller
{
    /**
     * Appelé après soumission du checkout : redirige vers le formulaire Moov Money.
     */
    public function pay(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        return redirect()->route('moov.payment.form');
    }

    /**
     * Affiche le formulaire de paiement Moov Money (téléphone).
     */
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

    /**
     * Étape 1 : génération OTP (Online Merchant Payment with OTP).
     */
    public function initPayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json(['success' => false, 'message' => translate('Session expired. Please try again.')]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        $phone = preg_replace('/\D/', '', $request->phone_number);
        $amount = (int) $combined_order->grand_total;

        $raw = moovOtpGenerate($amount, '226' . $phone);
        $resultJson = json_decode($raw);

        if (!isset($resultJson->status)) {
            return response()->json([
                'success' => false,
                'message' => translate('The service is temporarily unavailable, please try later'),
            ]);
        }

        if ((string) $resultJson->status === '0') {
            $transId = $resultJson->{'trans-id'} ?? ($resultJson->trans_id ?? '');
            $requestId = $resultJson->{'request-id'} ?? ($resultJson->request_id ?? '');

            $request->session()->put('moov_otp_context', [
                'combined_order_id' => $combined_order_id,
                'phone' => $phone,
                'amount' => $amount,
                'trans_id' => $transId,
                'request_id' => $requestId,
            ]);

            return response()->json([
                'success' => true,
                'step' => 'otp_sent',
                'message' => translate('An OTP has been sent to your phone. Please enter it to confirm the payment.'),
            ]);
        }

        $message = isset($resultJson->message) && $resultJson->message === 'NOT SUBSCRIBED'
            ? translate("You don't have a Moov money account")
            : (isset($resultJson->message) ? $resultJson->message : translate('The service is temporarily unavailable, please try later'));

        return response()->json(['success' => false, 'message' => $message]);
    }

    /**
     * Étape 1 bis : renvoi de l'OTP.
     */
    public function resendOtp(Request $request)
    {
        $context = $request->session()->get('moov_otp_context');
        if (!$context) {
            return response()->json([
                'success' => false,
                'message' => translate('Session expired. Please try again.'),
            ]);
        }

        $raw = moovOtpResend(
            (int) $context['amount'],
            '226' . $context['phone'],
            $context['request_id']
        );

        $resultJson = json_decode($raw);
        if (!isset($resultJson->status)) {
            return response()->json([
                'success' => false,
                'message' => translate('The service is temporarily unavailable, please try later'),
            ]);
        }

        if ((string) $resultJson->status === '0') {
            return response()->json([
                'success' => true,
                'message' => translate('The OTP has been resent to your phone.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => isset($resultJson->message) ? $resultJson->message : translate('The service is temporarily unavailable, please try later'),
        ]);
    }

    /**
     * Étape 2 : confirmation du paiement avec l'OTP.
     */
    public function confirmOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $context = $request->session()->get('moov_otp_context');
        if (!$context) {
            return response()->json([
                'success' => false,
                'message' => translate('Session expired. Please try again.'),
            ]);
        }

        $combined_order = CombinedOrder::find($context['combined_order_id']);
        if (!$combined_order) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ]);
        }

        $raw = moovOtpCommitPayment(
            (int) $context['amount'],
            '226' . $context['phone'],
            $request->otp,
            $context['trans_id'],
            $context['request_id']
        );

        $resultJson = json_decode($raw);
        if (!isset($resultJson->status)) {
            return response()->json([
                'success' => false,
                'message' => translate('The service is temporarily unavailable, please try later'),
            ]);
        }

        if ((string) $resultJson->status === '0') {
            $payment_details = json_encode([
                'provider' => 'Moov Money',
                'transaction_id' => $context['trans_id'],
                'phone' => '226' . $context['phone'],
                'method' => 'OTP',
            ]);

            checkout_done($combined_order->id, $payment_details);

            $request->session()->forget('combined_order_id');
            $request->session()->forget('payment_data');
            $request->session()->forget('payment_type');
            $request->session()->forget('moov_otp_context');

            return response()->json([
                'success' => true,
                'url' => route('order_confirmed'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => isset($resultJson->message) ? $resultJson->message : translate('Payment failed'),
        ]);
    }
}
