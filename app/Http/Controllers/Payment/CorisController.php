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
     * Affiche le formulaire de paiement Coris Money (étape 1 : téléphone).
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
     * Étape 1 : Envoie le code OTP au téléphone du client via l'API Coris.
     * (Section 5 - Étape 1 : send-code-otp)
     */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json([
                'success' => false,
                'message' => translate('Session expired. Please try again.'),
            ]);
        }

        // Sauvegarde du numéro en session pour l'étape 2
        $request->session()->put('coris_phone', $request->phone_number);

        $result = sendCorisOTP($request->phone_number);

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->message ?: translate('Failed to send OTP. Please try again.'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => translate('OTP sent successfully. Please check your phone.'),
        ]);
    }

    /**
     * Étape 2 : Traite le paiement avec le code OTP reçu.
     * (Section 5 - Étape 2 : paiementbien + Section 8 : vérification statut)
     */
    public function handlePayment(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string',
            'otp_code'     => 'required|string',
        ]);

        $combined_order_id = $request->session()->get('combined_order_id');
        if (!$combined_order_id) {
            return response()->json([
                'success' => false,
                'message' => translate('Session expired. Please try again.'),
            ]);
        }

        $combined_order = CombinedOrder::findOrFail($combined_order_id);

        // ÉTAPE 2 : Paiement de bien (Section 5 - Étape 2)
        $result = sendCorisPaiementBien(
            $request->phone_number,
            (int) $combined_order->grand_total,
            $request->otp_code
        );

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->message ?: translate('Payment failed. Please try again.'),
            ]);
        }

        // VÉRIFICATION DU STATUT (Section 8)
        sleep(1);
        $statusCheck = checkCorisTransactionStatus($result->transactionId);

        if (!$statusCheck->success) {
            return response()->json([
                'success' => false,
                'message' => translate('Payment verification failed. Please try again or contact support.'),
            ]);
        }

        // Paiement confirmé : on marque la commande comme payée
        $payment_details = json_encode([
            'transaction_id'  => $result->transactionId,
            'phone'           => preg_replace('/\D/', '', $request->phone_number),
            'method'          => 'Coris Money',
            'status_verified' => true,
        ]);

        checkout_done($combined_order_id, $payment_details);

        $request->session()->forget('combined_order_id');
        $request->session()->forget('payment_data');
        $request->session()->forget('payment_type');
        $request->session()->forget('coris_phone');

        return response()->json([
            'success'        => true,
            'url'            => route('order_confirmed'),
            'transaction_id' => $result->transactionId,
        ]);
    }
}
