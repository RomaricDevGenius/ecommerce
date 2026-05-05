@php $edit = $edit ?? false; @endphp

<div class="form-group row">
    <label class="col-md-4 col-form-label">{{ translate('Depuis (km)') }} <span class="text-danger">*</span></label>
    <div class="col-md-8">
        <input type="number" name="min_km" class="form-control" step="0.1" min="0"
            value="{{ old('min_km', 0) }}" required>
    </div>
</div>

<div class="form-group row max-row">
    <label class="col-md-4 col-form-label">{{ translate("Jusqu'à (km)") }}</label>
    <div class="col-md-8">
        <input type="number" name="max_km" class="form-control" step="0.1" min="0"
            value="{{ old('max_km') }}">
        <small class="text-muted">{{ translate('Laisser vide pour "révision manuelle" (pas de limite).') }}</small>
    </div>
</div>

<div class="form-group row price-row">
    <label class="col-md-4 col-form-label">{{ translate('Frais (FCFA)') }} <span class="text-danger">*</span></label>
    <div class="col-md-8">
        <div class="input-group">
            <input type="number" name="price" class="form-control" step="1" min="0"
                value="{{ old('price', 0) }}" required>
            <div class="input-group-append">
                <span class="input-group-text">FCFA</span>
            </div>
        </div>
    </div>
</div>

<div class="form-group row">
    <label class="col-md-4 col-form-label">{{ translate('Révision manuelle') }}</label>
    <div class="col-md-8 d-flex align-items-center">
        <label class="aiz-switch aiz-switch-success mb-0">
            <input type="checkbox" name="is_manual_review" value="1">
            <span></span>
        </label>
        <small class="text-muted ml-2">{{ translate('Le client passe commande, l\'admin fixe le supplément manuellement.') }}</small>
    </div>
</div>
