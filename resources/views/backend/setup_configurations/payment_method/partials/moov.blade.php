<form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST" autocomplete="off">
    @csrf
    <input type="hidden" name="payment_method" value="moov">
    <div class="form-group row">
        <input type="hidden" name="types[]" value="MOOV_MONEY_MERCHANT_ID">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Moov Money Merchant ID') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text" class="form-control" name="MOOV_MONEY_MERCHANT_ID"
                value="{{ env('MOOV_MONEY_MERCHANT_ID') ?? '' }}"
                placeholder="{{ translate('Merchant ID') }}" autocomplete="off" required>
        </div>
    </div>
    <div class="form-group row">
        <input type="hidden" name="types[]" value="MOOV_MONEY_MERCHANT_PASSWORD">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Moov Money Merchant Password') }}</label>
        </div>
        <div class="col-md-8">
            <input type="password" class="form-control" name="MOOV_MONEY_MERCHANT_PASSWORD"
                value="{{ env('MOOV_MONEY_MERCHANT_PASSWORD') ?? '' }}"
                placeholder="{{ translate('Merchant Password') }}" autocomplete="new-password" required>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Moov Money Sandbox Mode') }}</label>
        </div>
        <div class="col-md-8">
            <label class="aiz-switch aiz-switch-success mb-0">
                <input value="1" name="moov_sandbox" type="checkbox"
                    @if (get_setting('moov_sandbox') == 1) checked @endif>
                <span class="slider round"></span>
            </label>
        </div>
    </div>
    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
    </div>
</form>
