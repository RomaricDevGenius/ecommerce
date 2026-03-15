<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\CombinedOrder;
use Illuminate\Http\Request;

class CorisController extends Controller
{
    /**
     * Appelé après soumission du checkout : redirige vers le formulaire Coris Money.
     */
    public function pay(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        return redirect()->route('coris.payment.form');
    }

    /**
     * Affiche le formulaire de paiement Coris Money (téléphone + code retrait).
     */
    public function showPaymentForm(Request $request)
    {
        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            flash(translate('Session expired. Please try again.'))->error();
            return redirect()->route('checkout');
        }
        $combined_order = CombinedOrder::findOrFail($combined_order_id);
        return view('frontend.coris.payment_form', compact('combined_order'));
    }

    /**
     * Traite le paiement Coris Money (soumission du formulaire).
     *
     * Le client doit d'abord initier un paiement internet dans l'application CorisMoney
     * pour obtenir un code de retrait, puis renseigner ce code et son téléphone ici.
     */
    public function handlePayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'withdraw_code' => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json([
                'success' => false,
                'message' => translate('Session expired. Please try again.'),
            ]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        $result = sendCorisInternetPayment(
            $request->phone_number,
            (int) $combined_order->grand_total,
            $request->withdraw_code
        );

        if ($result->success) {
            $payment_details = json_encode([
                'transaction_id' => $result->transactionId,
                'phone' => preg_replace('/\D/', '', $request->phone_number),
                'method' => 'Coris Money',
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

        $message = $result->message ?: translate('Payment failed');

        return response()->json([
            'success' => false,
            'message' => $message,
        ]);
    }
}

