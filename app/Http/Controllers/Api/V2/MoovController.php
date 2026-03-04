<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CombinedOrder;
use App\Models\WaitingTransaction;
use Illuminate\Http\Request;

class MoovController
{
    /**
     * Initie un paiement Moov Money pour l'API mobile.
     *
     * Reçoit un combined_order_id et un numéro de téléphone,
     * appelle l'API Moov via la fonction helper initMoovMoneyPayment
     * et enregistre une WaitingTransaction en cas de succès.
     */
    public function init(Request $request)
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

        // On ne garde que les chiffres dans le numéro envoyé par l’utilisateur
        $phone = preg_replace('/\D/', '', $request->phone_number);
        if (empty($phone)) {
            return response()->json([
                'success' => false,
                'message' => translate('Phone number is not valid'),
            ], 422);
        }

        $result = initMoovMoneyPayment((int) $combined_order->grand_total, $phone);
        $resultJson = json_decode($result);

        if (!isset($resultJson->status)) {
            return response()->json([
                'success' => false,
                'message' => translate('The service is temporarily unavailable, please try later'),
            ], 503);
        }

        // Selon la documentation actuelle, status "0" signifie succès
        if ((string) $resultJson->status === '0') {
            $transId = $resultJson->{'trans-id'} ?? ($resultJson->trans_id ?? '');

            WaitingTransaction::create([
                'combined_order_id' => $combined_order->id,
                'phone'             => $phone,
                'transaction_id'    => $transId,
            ]);

            return response()->json([
                'success' => true,
                'message' => translate('The transaction has been initiated successfully. You can complete the order and leave this page.'),
                'transaction_id' => $transId,
            ]);
        }

        $message = isset($resultJson->message) && $resultJson->message === 'NOT SUBSCRIBED'
            ? translate("You don't have a Moov money account")
            : translate('The service is temporarily unavailable, please try later');

        return response()->json([
            'success' => false,
            'message' => $message,
        ], 400);
    }
}

