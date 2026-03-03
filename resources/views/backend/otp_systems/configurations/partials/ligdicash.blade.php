<div class="form-group row">
    <input type="hidden" name="types[]" value="LIGDICASH_API_KEY">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('Ligdicash API KEY') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text"
               class="form-control"
               name="LIGDICASH_API_KEY"
               value="{{ env('LIGDICASH_API_KEY') }}"
               placeholder="LIGDICASH API KEY"
               required>
    </div>
</div>

<div class="form-group row">
    <input type="hidden" name="types[]" value="LIGDICASH_TOKEN">
    <div class="col-lg-3">
        <label class="col-from-label">{{ translate('Ligdicash TOKEN') }}</label>
    </div>
    <div class="col-lg-6">
        <input type="text"
               class="form-control"
               name="LIGDICASH_TOKEN"
               value="{{ env('LIGDICASH_TOKEN') }}"
               placeholder="LIGDICASH TOKEN"
               required>
    </div>
</div>

<div class="form-group mb-0 text-right">
    <button type="submit" class="btn btn-sm btn-primary">{{ translate('Save') }}</button>
</div>

