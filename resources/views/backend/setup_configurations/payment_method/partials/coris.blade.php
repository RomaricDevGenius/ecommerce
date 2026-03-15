<form class="form-horizontal" action="{{ route('payment_method.update') }}" method="POST" autocomplete="off">
    @csrf
    <input type="hidden" name="payment_method" value="coris">

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_CLIENT_ID">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Client ID') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text"
                   class="form-control"
                   name="CORIS_CLIENT_ID"
                   value="{{ env('CORIS_CLIENT_ID') ?? '' }}"
                   placeholder="{{ translate('Client ID') }}"
                   autocomplete="off"
                   required>
        </div>
    </div>

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_CLIENT_SECRET">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Client Secret') }}</label>
        </div>
        <div class="col-md-8">
            <input type="password"
                   class="form-control"
                   name="CORIS_CLIENT_SECRET"
                   value="{{ env('CORIS_CLIENT_SECRET') ?? '' }}"
                   placeholder="{{ translate('Client Secret') }}"
                   autocomplete="new-password"
                   required>
        </div>
    </div>

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_CODE_PV">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Shop Code (Code PV)') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text"
                   class="form-control"
                   name="CORIS_CODE_PV"
                   value="{{ env('CORIS_CODE_PV') ?? '' }}"
                   placeholder="{{ translate('Code PV fourni par Coris') }}"
                   autocomplete="off"
                   required>
        </div>
    </div>

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_COUNTRY_CODE">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Country phone code (codePays)') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text"
                   class="form-control"
                   name="CORIS_COUNTRY_CODE"
                   value="{{ env('CORIS_COUNTRY_CODE', '226') }}"
                   placeholder="{{ translate('Ex: 226') }}"
                   autocomplete="off"
                   required>
        </div>
    </div>

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_BASE_URL_TEST">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Test Base URL') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text"
                   class="form-control"
                   name="CORIS_BASE_URL_TEST"
                   value="{{ env('CORIS_BASE_URL_TEST', 'https://testbed.corismoney.com/external/v1/api') }}"
                   placeholder="https://testbed.corismoney.com/external/v1/api"
                   autocomplete="off">
        </div>
    </div>

    <div class="form-group row">
        <input type="hidden" name="types[]" value="CORIS_BASE_URL_PROD">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Live Base URL') }}</label>
        </div>
        <div class="col-md-8">
            <input type="text"
                   class="form-control"
                   name="CORIS_BASE_URL_PROD"
                   value="{{ env('CORIS_BASE_URL_PROD') ?? '' }}"
                   placeholder="{{ translate('Production base URL (fourni par Coris)') }}"
                   autocomplete="off">
        </div>
    </div>

    <div class="form-group row">
        <div class="col-md-4">
            <label class="col-from-label">{{ translate('Coris Money Sandbox Mode') }}</label>
        </div>
        <div class="col-md-8">
            <label class="aiz-switch aiz-switch-success mb-0">
                <input value="1" name="coris_sandbox" type="checkbox"
                    @if (get_setting('coris_sandbox') == 1) checked @endif>
                <span class="slider round"></span>
            </label>
            <small class="text-muted d-block mt-1">
                {{ translate('When sandbox is ON, the test base URL will be used.') }}
            </small>
        </div>
    </div>

    <div class="form-group mb-0 text-right">
        <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
    </div>
</form>

