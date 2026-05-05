@extends('backend.layouts.app')

@section('content')
<div class="row">

    {{-- ── Paramètres généraux ──────────────────────────────────────────── --}}
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0 h6">
                    <i class="las la-cog text-warning mr-1"></i>
                    {{ translate('Paramètres poids & volume') }}
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('gps_shipping.config.update') }}" method="POST">
                    @csrf

                    {{-- Mode de calcul --}}
                    <div class="form-group row">
                        <label class="col-md-4 col-form-label">{{ translate('Mode de calcul') }}</label>
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mt-1">
                                <label class="mr-3 mb-0">
                                    <input type="radio" name="gps_weight_mode" value="weight_only"
                                        {{ get_setting('gps_weight_mode', 'weight_only') == 'weight_only' ? 'checked' : '' }}>
                                    <span class="ml-1">{{ translate('Poids uniquement') }}</span>
                                </label>
                                <label class="mb-0">
                                    <input type="radio" name="gps_weight_mode" value="weight_volume"
                                        {{ get_setting('gps_weight_mode', 'weight_only') == 'weight_volume' ? 'checked' : '' }}>
                                    <span class="ml-1">{{ translate('Poids + Volume') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <p class="font-weight-bold text-muted mb-2" style="font-size:13px;">
                        {{ translate('Surcharge poids') }}
                    </p>

                    {{-- Seuil poids --}}
                    <div class="form-group row">
                        <label class="col-md-4 col-form-label">{{ translate('Seuil (kg)') }}</label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="number" name="gps_weight_threshold_kg" class="form-control"
                                    step="0.1" min="0"
                                    value="{{ get_setting('gps_weight_threshold_kg', '5') }}">
                                <div class="input-group-append">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                            <small class="text-muted">{{ translate('Poids inclus gratuitement dans le frais de base.') }}</small>
                        </div>
                    </div>

                    {{-- Surcharge par kg --}}
                    <div class="form-group row">
                        <label class="col-md-4 col-form-label">{{ translate('Surcharge / kg excédentaire') }}</label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <input type="number" name="gps_weight_surcharge_fcfa" class="form-control"
                                    step="1" min="0"
                                    value="{{ get_setting('gps_weight_surcharge_fcfa', '200') }}">
                                <div class="input-group-append">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section volume (conditionnelle) --}}
                    <div id="volume-section" style="{{ get_setting('gps_weight_mode', 'weight_only') == 'weight_only' ? 'display:none' : '' }}">
                        <hr class="my-3">
                        <p class="font-weight-bold text-muted mb-2" style="font-size:13px;">
                            {{ translate('Surcharge volume') }}
                        </p>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label">{{ translate('Seuil (litres)') }}</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="number" name="gps_volume_threshold_l" class="form-control"
                                        step="0.1" min="0"
                                        value="{{ get_setting('gps_volume_threshold_l', '20') }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">L</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-4 col-form-label">{{ translate('Surcharge / litre excédentaire') }}</label>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="number" name="gps_volume_surcharge_fcfa" class="form-control"
                                        step="1" min="0"
                                        value="{{ get_setting('gps_volume_surcharge_fcfa', '100') }}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right mt-3">
                        <button type="submit" class="btn btn-primary">{{ translate('Enregistrer') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Paliers de distance ───────────────────────────────────────────── --}}
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">
                    <i class="las la-route text-warning mr-1"></i>
                    {{ translate('Paliers de distance') }}
                </h5>
                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#addTierModal">
                    <i class="las la-plus"></i> {{ translate('Ajouter') }}
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('Distance') }}</th>
                            <th>{{ translate('Frais') }}</th>
                            <th>{{ translate('Mode') }}</th>
                            <th class="text-right">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiers as $tier)
                        <tr>
                            <td>
                                @if($tier->is_manual_review)
                                    <span class="text-danger font-weight-bold">&gt; {{ $tier->min_km }} km</span>
                                @else
                                    {{ $tier->min_km }} – {{ $tier->max_km }} km
                                @endif
                            </td>
                            <td>
                                @if($tier->is_manual_review)
                                    <span class="badge badge-secondary">—</span>
                                @else
                                    <strong>{{ number_format($tier->price, 0, ',', ' ') }} FCFA</strong>
                                @endif
                            </td>
                            <td>
                                @if($tier->is_manual_review)
                                    <span class="badge badge-warning">Révision admin</span>
                                @else
                                    <span class="badge badge-success">Auto</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary btn-edit-tier"
                                    data-id="{{ $tier->id }}"
                                    data-min="{{ $tier->min_km }}"
                                    data-max="{{ $tier->max_km }}"
                                    data-price="{{ $tier->price }}"
                                    data-manual="{{ $tier->is_manual_review ? '1' : '0' }}"
                                    data-toggle="modal" data-target="#editTierModal">
                                    <i class="las la-edit"></i>
                                </button>
                                <form action="{{ route('gps_shipping.tier.destroy', $tier) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ translate('Supprimer ce palier ?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">{{ translate('Aucun palier défini.') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-muted" style="font-size:12px;">
                <i class="las la-info-circle"></i>
                {{ translate('Point de départ : coordonnées configurées dans "Pickup Location For Delivery Boy".') }}
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-center">
                <a href="{{ route('gps_shipping.pending') }}" class="btn btn-warning">
                    <i class="las la-clock"></i>
                    {{ translate('Commandes en attente de supplément') }}
                    @php
                        $pendingCount = \App\Models\Order::where('gps_shipping_pending', true)->count();
                    @endphp
                    @if($pendingCount > 0)
                        <span class="badge badge-light ml-1">{{ $pendingCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ajouter palier --}}
<div class="modal fade" id="addTierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gps_shipping.tier.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Ajouter un palier') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('backend.setup_configurations.gps_shipping._tier_form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Ajouter') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Modifier palier --}}
<div class="modal fade" id="editTierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTierForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Modifier le palier') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('backend.setup_configurations.gps_shipping._tier_form', ['edit' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ translate('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Enregistrer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Toggle section volume
    $('input[name="gps_weight_mode"]').on('change', function () {
        if ($(this).val() === 'weight_volume') {
            $('#volume-section').show();
        } else {
            $('#volume-section').hide();
        }
    });

    // Pré-remplir modal édition
    $('.btn-edit-tier').on('click', function () {
        var id     = $(this).data('id');
        var min    = $(this).data('min');
        var max    = $(this).data('max');
        var price  = $(this).data('price');
        var manual = $(this).data('manual');

        var form = $('#editTierForm');
        form.attr('action', '/admin/gps-shipping/tiers/' + id);
        form.find('[name="min_km"]').val(min);
        form.find('[name="max_km"]').val(max || '');
        form.find('[name="price"]').val(price);
        form.find('[name="is_manual_review"]').prop('checked', manual == '1');

        // Toggle champ prix
        if (manual == '1') {
            form.find('.price-row').hide();
            form.find('.max-row').hide();
        } else {
            form.find('.price-row').show();
            form.find('.max-row').show();
        }
    });

    // Toggle champ prix/max selon révision manuelle
    $(document).on('change', '[name="is_manual_review"]', function () {
        var form = $(this).closest('form');
        if ($(this).is(':checked')) {
            form.find('.price-row').hide();
            form.find('.max-row').hide();
        } else {
            form.find('.price-row').show();
            form.find('.max-row').show();
        }
    });
</script>
@endsection
