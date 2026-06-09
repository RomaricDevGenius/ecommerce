<form class="form-horizontal" action="{{ route('commissions.pay_to_seller') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="modal-header">
    	<h5 class="modal-title h6">{{translate('Pay to seller')}}</h5>
    	<button type="button" class="close" data-dismiss="modal">
    	</button>
    </div>
    <div class="modal-body">
      <table class="table table-striped table-bordered" >
          <tbody>
                <tr>
                    @if($user->shop->admin_to_pay >= 0)
                        <td>{{ translate('Due to seller') }}</td>
                        <td>{{ single_price($user->shop->admin_to_pay) }}</td>
                    @endif
                </tr>
                <tr>
                    @if($seller_withdraw_request->amount > $user->shop->admin_to_pay)
                        <td>{{ translate('Requested Amount is ') }}</td>
                        <td>{{ single_price($seller_withdraw_request->amount) }}</td>
                    @endif
                </tr>
                @if (!empty($seller_withdraw_request->payment_method))
                    <tr>
                        <td>{{ translate('Requested Method') }}</td>
                        <td><strong>{{ ucwords(str_replace('_', ' ', $seller_withdraw_request->payment_method)) }}</strong></td>
                    </tr>
                    @if (!empty($seller_withdraw_request->account_number))
                        <tr>
                            <td>{{ translate('Account Number') }}</td>
                            <td><strong>{{ $seller_withdraw_request->account_number }}</strong></td>
                        </tr>
                    @endif
                @endif
                @if ($user->shop->bank_payment_status == 1)
                    <tr>
                        <td>{{ translate('Bank Name') }}</td>
                        <td>{{ $user->shop->bank_name }}</td>
                    </tr>
                    <tr>
                        <td>{{ translate('Bank Account Name') }}</td>
                        <td>{{ $user->shop->bank_acc_name }}</td>
                    </tr>
                    <tr>
                        <td>{{ translate('Bank Account Number') }}</td>
                        <td>{{ $user->shop->bank_acc_no }}</td>
                    </tr>
                    <tr>
                        <td>{{ translate('Bank Routing Number') }}</td>
                        <td>{{ $user->shop->bank_routing_no }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($user->shop->admin_to_pay > 0)
            <input type="hidden" name="shop_id" value="{{ $user->shop->id }}">
            <input type="hidden" name="payment_withdraw" value="withdraw_request">
            <input type="hidden" name="withdraw_request_id" value="{{ $seller_withdraw_request->id }}">
            <div class="form-group row">
                <label class="col-sm-3 col-from-label" for="amount">{{translate('Requested Amount')}}</label>
                <div class="col-sm-9">
                    @if ($seller_withdraw_request->amount > $user->shop->admin_to_pay)
                        <input type="number" lang="en" min="0" step="0.01" name="amount" id="amount" value="{{ $user->shop->admin_to_pay }}" class="form-control" required>
                    @else
                        <input type="number" lang="en" min="0" step="0.01" name="amount" id="amount" value="{{ $seller_withdraw_request->amount }}" class="form-control" required>
                    @endif
                </div>
            </div>

            @if (!empty($seller_withdraw_request->payment_method))
                {{-- Le vendeur a choisi un moyen précis : l'admin paie via ce moyen puis confirme --}}
                <input type="hidden" name="payment_option" value="{{ $seller_withdraw_request->payment_method }}">
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label">{{translate('Payment Method')}}</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" value="{{ ucwords(str_replace('_', ' ', $seller_withdraw_request->payment_method)) }}" readonly>
                    </div>
                </div>
            @else
                {{-- Ancien format (demande créée avant l'ajout des moyens) : menu cash/bank historique --}}
                <div class="form-group row">
                    <label class="col-sm-3 col-from-label" for="payment_option">{{translate('Payment Method')}}</label>
                    <div class="col-sm-9">
                        <select name="payment_option" id="payment_option" class="form-control demo-select2-placeholder" required>
                            <option value="">{{translate('Select Payment Method')}}</option>
                            @if($user->shop->cash_on_delivery_status == 1)
                                <option value="cash">{{translate('Cash')}}</option>
                            @endif
                            @if($user->shop->bank_payment_status == 1)
                                <option value="bank_payment">{{translate('Bank Payment')}}</option>
                            @endif
                        </select>
                    </div>
                </div>
            @endif
            
            <div class="form-group row" id="txn_div">
                <label class="col-md-3 col-from-label" for="txn_code">{{translate('Txn Code')}}</label>
                <div class="col-md-9">
                    <input type="text" name="txn_code" id="txn_code" class="form-control">
                </div>
            </div>
        @endif

    </div>
    <div class="modal-footer">
      @if ($user->shop->admin_to_pay > 0)
        <button type="submit" class="btn btn-primary">{{translate('Pay')}}</button>
      @endif
      <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Cancel')}}</button>
    </div>
</form>

<script>
$(document).ready(function(){
    $('#payment_option').on('change', function() {
      if ( this.value == 'bank_payment')
      {
        $("#txn_div").show();
      }
      else
      {
        $("#txn_div").hide();
      }
    });
    @if (!empty($seller_withdraw_request->payment_method) && $seller_withdraw_request->payment_method != 'cash')
        $("#txn_div").show(); // moyen mobile/banque demandé : référence de transaction utile
    @else
        $("#txn_div").hide();
    @endif
    AIZ.plugins.bootstrapSelect('refresh');
});
</script>
