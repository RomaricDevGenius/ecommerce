<form action="{{ route('commissions.pay_to_seller') }}" method="POST">
    @csrf
    <input type="hidden" name="shop_id" value="{{ $shop->id }}">
    <div class="modal-header">
    	<h5 class="modal-title h6">{{translate('Pay to seller')}}</h5>
    	<button type="button" class="close" data-dismiss="modal">
    	</button>
    </div>
    <div class="modal-body">
      <table class="table table-striped table-bordered" >
          <tbody>
              <tr>
                  @if($shop->admin_to_pay >= 0)
                      <td>{{ translate('Due to seller') }}</td>
                      <td>{{ single_price($shop->admin_to_pay) }}</td>
                  @else
                      <td>{{ translate('Due to admin') }}</td>
                      <td>{{ single_price(abs($shop->admin_to_pay)) }}</td>
                  @endif
              </tr>
              @if ($shop->bank_payment_status == 1)
                  <tr>
                      <td>{{ translate('Bank Name') }}</td>
                      <td>{{ $shop->bank_name }}</td>
                  </tr>
                  <tr>
                      <td>{{ translate('Bank Account Name') }}</td>
                      <td>{{ $shop->bank_acc_name }}</td>
                  </tr>
                  <tr>
                      <td>{{ translate('Bank Account Number') }}</td>
                      <td>{{ $shop->bank_acc_no }}</td>
                  </tr>
                  <tr>
                      <td>{{ translate('Bank Routing Number') }}</td>
                      <td>{{ $shop->bank_routing_no }}</td>
                  </tr>
              @endif
          </tbody>
      </table>

      @if ($shop->admin_to_pay > 0)
          {{-- Paiement direct positif desactive : le versement passe par la demande de retrait du vendeur (moyen + numero fournis par le vendeur). "Clear due" (solde negatif) reste actif ci-dessous. --}}
          <div class="alert alert-info mb-0">
              {{ translate('Seller payments are now made from their withdrawal request.') }}
          </div>
          <div class="text-center mt-3">
              <a href="{{ route('withdraw_requests_all') }}" class="btn btn-primary">
                  {{ translate('View withdrawal requests') }}
              </a>
          </div>
      @else
          <div class="form-group row">
              <label class="col-md-3 col-from-label" for="amount">{{translate('Amount')}}</label>
              <div class="col-md-9">
                  <input type="number" lang="en" min="0" step="0.01" name="amount" id="amount" value="{{ abs($shop->admin_to_pay) }}" class="form-control" required>
              </div>
          </div>
          <div class="form-group row" id="txn_div">
              <label class="col-md-3 col-from-label" for="txn_code">{{translate('Txn Code')}}</label>
              <div class="col-md-9">
                  <input type="text" name="txn_code" id="txn_code" class="form-control">
              </div>
          </div>
      @endif
    </div>
    <div class="modal-footer">
      @if ($shop->admin_to_pay <= 0)
          <button type="submit" class="btn btn-primary">{{translate('Clear due')}}</button>
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
      $("#txn_div").hide();
      AIZ.plugins.bootstrapSelect('refresh');
  });
</script>
