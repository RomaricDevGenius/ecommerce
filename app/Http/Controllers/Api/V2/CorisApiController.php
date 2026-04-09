<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CombinedOrder;
use Illuminate\Http\Request;

class CorisApiController
{
    /**
     * Étape 1 : Envoie un code OTP au téléphone du client via Coris Money.
     */
    public function sendOTP(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'phone_number'      => 'required|string',
        ]);

        $combined_order = CombinedOrder::find($request->combined_order_id);
        if (!$combined_order) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ], 404);
        }

        $result = sendCorisOTP($request->phone_number);

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->message ?: translate('Failed to send OTP. Please try again.'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => translate('OTP sent successfully. Please check your phone.'),
        ]);
    }

    /**
     * Étape 2 : Confirme le paiement avec le code OTP reçu par le client.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'phone_number'      => 'required|string',
            'otp_code'          => 'required|string',
        ]);

        $combined_order = CombinedOrder::find($request->combined_order_id);
        if (!$combined_order) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ], 404);
        }

        $result = sendCorisPaiementBien(
            $request->phone_number,
            (int) $combined_order->grand_total,
            $request->otp_code
        );

        if (!$result->success) {
            return response()->json([
                'success' => false,
                'message' => $result->message ?: translate('Payment failed. Please try again.'),
            ], 400);
        }

        $payment_details = json_encode([
            'transaction_id' => $result->transactionId,
            'phone'          => preg_replace('/\D/', '', $request->phone_number),
            'method'         => 'Coris Money',
        ]);

        checkout_done($combined_order->id, $payment_details);

        return response()->json([
            'success'        => true,
            'message'        => translate('Payment completed successfully.'),
            'transaction_id' => $result->transactionId,
        ]);
    }
}
