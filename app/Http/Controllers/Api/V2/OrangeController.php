<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CombinedOrder;
use Illuminate\Http\Request;

class OrangeController
{
    /**
     * Traite un paiement Orange Money pour l'API mobile.
     *
     * L’utilisateur a déjà lancé l’USSD côté téléphone, ici on valide via OTP.
     */
    public function pay(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'phone_number'      => 'required|string',
            'otp'               => 'required|string',
        ]);

        $combined_order = CombinedOrder::find($request->combined_order_id);
        if (!$combined_order) {
            return response()->json([
                'success' => false,
                'message' => translate('Order not found.'),
            ], 404);
        }

        $reference_number = 'ORD-' . $combined_order->id;
        $ext_txn_id = 'DAKWARI-' . $combined_order->id . '-' . time();

        $result = sendOrangeMoneyPayment(
            $request->phone_number,
            (int) $combined_order->grand_total,
            $request->otp,
            $reference_number,
            $ext_txn_id
        );

        if (isset($result->status) && (string) $result->status === '200') {
            $transId = isset($result->transID)
                ? $result->transID
                : (isset($result->TransID) ? $result->TransID : '');

            $payment_details = json_encode([
                'transaction_id' => $transId,
                'phone'          => $request->phone_number,
                'method'         => 'Orange Money',
            ]);

            checkout_done($combined_order->id, $payment_details);

            return response()->json([
                'success' => true,
                'message' => translate('Payment completed successfully.'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => translateOMErrors($result),
        ], 400);
    }
}

