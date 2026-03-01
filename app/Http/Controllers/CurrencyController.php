<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Currency;

class CurrencyController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:currency_setup'])->only('currency','create','edit','destroy');
    }

    public function changeCurrency(Request $request)
    {
        $allowed = get_frontend_currencies()->keyBy('code');
        $code = $request->currency_code;
        if (!$code || !$allowed->has($code)) {
            flash(translate('Currency not available'))->error();
            return;
        }
        $currency = $allowed->get($code);
        $request->session()->put('currency_code', $currency->code);
        $request->session()->put('currency_symbol', $currency->symbol);
        $request->session()->put('currency_exchange_rate', $currency->exchange_rate);
        flash(translate('Currency changed to ').$currency->name)->success();
    }

    public function currency(Request $request)
    {
        $sort_search =null;
        $currencies = Currency::orderBy('created_at', 'desc');
        if ($request->has('search')){
            $sort_search = $request->search;
            $currencies = $currencies->where('name', 'like', '%'.$sort_search.'%');
        }
        $currencies = $currencies->paginate(10);

        $active_currencies = Currency::where('status', 1)->get();
        return view('backend.setup_configurations.currencies.index', compact('currencies', 'active_currencies','sort_search'));
    }

    public function updateYourCurrency(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = $currency->status;
        if($currency->save()){
            Cache::forget('system_default_currency');
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        }
        else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function create()
    {
        return view('backend.setup_configurations.currencies.create');
    }

    public function edit(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        return view('backend.setup_configurations.currencies.edit', compact('currency'));
    }

    public function store(Request $request)
    {
        $currency = new Currency;
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = '0';
        if($currency->save()){
            Cache::forget('system_default_currency');
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        }
        else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function update_status(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        if($request->status == 0){
            if (get_setting('system_default_currency') == $currency->id) {
                return 0;
            }
        }
        $currency->status = $request->status;
        $currency->save();
        Cache::forget('system_default_currency');
        return 1;
    }

    /**
     * Supprimer une devise. Impossible de supprimer la devise par défaut du système.
     */
    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        if ((int) get_setting('system_default_currency') === (int) $currency->id) {
            flash(translate('You cannot delete the system default currency.'))->error();
            return back();
        }
        $currency->delete();
        Cache::forget('system_default_currency');
        flash(translate('Currency deleted successfully.'))->success();
        return redirect()->route('currency.index');
    }
}
