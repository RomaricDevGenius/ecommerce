<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use Illuminate\Http\Request;
use Session;

class OrangeController extends Controller
{
    /**
     * Appelé après soumission du checkout : redirige vers le formulaire Orange Money.
     */
    public function pay(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        return redirect()->route('orange.payment.form');
    }

    /**
     * Affiche le formulaire de paiement Orange Money (téléphone + OTP).
     */
    public function showPaymentForm(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        return view('frontend.orange.payment_form', compact('combined_order'));
    }

    /**
     * Traite le paiement Orange Money (soumission du formulaire).
     */
    public function handlePayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp' => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json(['success' => false, 'message' => translate('Session expired. Please try again.')]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        $result = sendOrangeMoneyPayment(
            $request->phone_number,
            (int) $combined_order->grand_total,
            $request->otp
        );

        if (isset($result->status) && (string) $result->status === '200') {
            $transId = isset($result->transID) ? $result->transID : (isset($result->TransID) ? $result->TransID : '');
            $payment_details = json_encode([
                'transaction_id' => $transId,
                'phone' => $request->phone_number,
                'method' => 'Orange Money',
            ]);
            checkout_done($combined_order_id, $payment_details);
            $request->session()->forget('combined_order_id');
            $request->session()->forget('payment_data');
            $request->session()->forget('payment_type');

            return response()->json([
                'success' => true,
                'url' => route('order_confirmed'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => translateOMErrors($result),
        ]);
    }
}
