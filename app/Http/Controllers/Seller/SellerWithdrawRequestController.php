<?php

namespace App\Http\Controllers\Seller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PayoutNotification;
use App\Models\SellerWithdrawRequest;
use App\Models\User;
use App\Utility\EmailUtility;
use Auth;

class SellerWithdrawRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $seller_withdraw_requests = SellerWithdrawRequest::where('user_id', Auth::user()->id)->latest()->paginate(9);
        $withdraw_methods = self::activeWithdrawMethods();
        return view('seller.money_withdraw_requests.index', compact('seller_withdraw_requests', 'withdraw_methods'));
    }

    /**
     * Moyens de retrait disponibles, construits dynamiquement à partir des
     * passerelles de paiement actives (Orange / Moov / Coris) + Cash toujours dispo.
     * Même convention de valeurs que le retrait livreur, sans aucun couplage avec lui.
     */
    public static function activeWithdrawMethods(): array
    {
        $defs = [
            ['value' => 'orange_money', 'label' => 'Orange Money', 'pm' => 'orange'],
            ['value' => 'moov_money',   'label' => 'Moov Money',   'pm' => 'moov'],
            ['value' => 'coris_money',  'label' => 'Coris Money',  'pm' => 'coris'],
        ];

        $methods = [];
        foreach ($defs as $def) {
            $active = \App\Models\PaymentMethod::where('name', $def['pm'])->where('active', 1)->exists();
            if ($active) {
                $methods[] = [
                    'value'     => $def['value'],
                    'label'     => $def['label'],
                    'image_url' => static_asset('assets/img/cards/' . $def['pm'] . '.png'),
                ];
            }
        }

        $methods[] = [
            'value'     => 'cash',
            'label'     => translate('Cash'),
            'image_url' => static_asset('assets/img/cards/cod.png'),
        ];

        return $methods;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:orange_money,moov_money,coris_money,cash',
            'account_number' => 'required_unless:payment_method,cash|nullable|string|max:50',
            'message'        => 'nullable|string|max:500',
        ]);

        $seller = auth()->user();
        $seller_withdraw_request = new SellerWithdrawRequest;
        $seller_withdraw_request->user_id = $seller->id;
        $seller_withdraw_request->amount = $request->amount;
        $seller_withdraw_request->payment_method = $request->payment_method;
        $seller_withdraw_request->account_number = $request->payment_method === 'cash' ? null : $request->account_number;
        $seller_withdraw_request->message = $request->message;
        $seller_withdraw_request->status = '0';
        $seller_withdraw_request->viewed = '0';
        if ($seller_withdraw_request->save()) {

            // Seller payout request web notification to admin
            $users = User::findMany(User::where('user_type', 'admin')->first()->id);
            $data = array();
            $data['user'] = $seller;
            $data['amount'] = $request->amount;
            $data['status'] = 'pending';
            $data['notification_type_id'] = get_notification_type('seller_payout_request', 'type')->id;
            Notification::send($users, new PayoutNotification($data));

            // Seller payout request email to admin & seller
            $emailIdentifiers = ['seller_payout_request_email_to_admin','seller_payout_request_email_to_seller'];
            EmailUtility::seller_payout($emailIdentifiers, $seller, $request->amount,  null);

            flash(translate('Request has been sent successfully'))->success();
            return redirect()->route('seller.money_withdraw_requests.index');
        }
        else{
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }
}
