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
     * Initie le paiement Moov (enregistre la transaction en attente, le cron confirmera plus tard).
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
        $result = initMoovMoneyPayment((int) $combined_order->grand_total, $phone);
        $resultJson = json_decode($result);

        if (!isset($resultJson->status)) {
            return response()->json(['success' => false, 'message' => translate('The service is temporarily unavailable, please try later')]);
        }

        if ((string) $resultJson->status === '0') {
            $transId = $resultJson->{'trans-id'} ?? ($resultJson->trans_id ?? '');
            WaitingTransaction::create([
                'combined_order_id' => $combined_order_id,
                'phone' => $phone,
                'transaction_id' => $transId,
            ]);
            $request->session()->forget('club_point');

            return response()->json([
                'success' => true,
                'url' => route('order_confirmed'),
                'message' => translate('The transaction has been initiated successfully. You can complete the order and leave this page.'),
            ]);
        }

        $message = isset($resultJson->message) && $resultJson->message === 'NOT SUBSCRIBED'
            ? translate("You don't have a Moov money account")
            : translate('The service is temporarily unavailable, please try later');
        return response()->json(['success' => false, 'message' => $message]);
    }
}
