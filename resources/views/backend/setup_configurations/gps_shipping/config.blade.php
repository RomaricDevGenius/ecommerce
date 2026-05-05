@extends('backend.layouts.app')

@section('content')
<div class="row">

    {{-- ── Paramètres poids & volume ────────────────────────────────────────── --}}
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0 h6">{{ translate('Paramètres poids & volume') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('gps_shipping.config.update') }}" method="POST">
                    @csrf

                    {{-- Mode de calcul --}}
                    <div class="form-group row">
                        <label class="col-md-5 col-from-label">{{ translate('Mode de calcul') }}</label>
                        <div class="col-md-7">
                            <div class="d-flex align-items-center mt-2">
                                <label class="aiz-radio-inline mr-4 mb-0">
                                    <input type="radio" name="gps_weight_mode" value="weight_only"
                                        {{ get_setting('gps_weight_mode', 'weight_only') == 'weight_only' ? 'checked' : '' }}>
                                    <span class="ml-1">{{ translate('Poids uniquement') }}</span>
                                </label>
                                <label class="aiz-radio-inline mb-0">
                                    <input type="radio" name="gps_weight_mode" value="weight_volume"
                                        {{ get_setting('gps_weight_mode', 'weight_only') == 'weight_volume' ? 'checked' : '' }}>
                                    <span class="ml-1">{{ translate('Poids + Volume') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-md-5 col-from-label">{{ translate('Poids inclus (kg)') }}</label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="number" name="gps_weight_threshold_kg" class="form-control"
                                    step="0.1" min="0"
                                    value="{{ get_setting('gps_weight_threshold_kg', '5') }}">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">kg</span>
                                </div>
                            </div>
                            <small class="text-muted">{{ translate('Poids inclus gratuitement dans le tarif de base.') }}</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-md-5 col-from-label">{{ translate('Surcharge par kg en plus') }}</label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="number" name="gps_weight_surcharge_fcfa" class="form-control"
                                    step="1" min="0"
                                    value="{{ get_setting('gps_weight_surcharge_fcfa', '200') }}">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section volume (conditionnelle) --}}
                    <div id="volume-section" style="{{ get_setting('gps_weight_mode', 'weight_only') == 'weight_only' ? 'display:none' : '' }}">
                        <div class="form-group row">
                            <label class="col-md-5 col-from-label">{{ translate('Volume inclus (litres)') }}</label>
                            <div class="col-md-7">
                                <div class="input-group">
                                    <input type="number" name="gps_volume_threshold_l" class="form-control"
                                        step="0.1" min="0"
                                        value="{{ get_setting('gps_volume_threshold_l', '20') }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">L</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-md-5 col-from-label">{{ translate('Surcharge par litre en plus') }}</label>
                            <div class="col-md-7">
                                <div class="input-group">
                                    <input type="number" name="gps_volume_surcharge_fcfa" class="form-control"
                                        step="1" min="0"
                                        value="{{ get_setting('gps_volume_surcharge_fcfa', '100') }}">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Paliers de distance ───────────────────────────────────────────────── --}}
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">{{ translate('Paliers de distance') }}</h5>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTierModal">
                    <i class="las la-plus"></i> {{ translate('Ajouter') }}
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ translate('Distance') }}</th>
                            <th>{{ translate('Tarif de base') }}</th>
                            <th class="text-center">{{ translate('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tiers as $tier)
                        <tr @if($tier->is_manual_review) style="background:#fffbf0;" @endif>
                            <td>
                                @if($tier->is_manual_review)
                                    <i class="las la-exclamation-circle text-warning mr-1"></i>
                                    <span class="font-weight-500">&gt; {{ $tier->min_km }} km</span>
                                @else
                                    <i class="las la-map-marker-alt text-primary mr-1"></i>
                                    {{ $tier->min_km }} – {{ $tier->max_km }} km
                                @endif
                            </td>
                            <td>
                                @if($tier->is_manual_review)
                                    <span class="text-warning font-weight-500">
                                        <i class="las la-user-cog mr-1"></i>{{ translate('Révision manuelle') }}
                                    </span>
                                @else
                                    <span class="font-weight-bold">{{ number_format($tier->price, 0, ',', ' ') }} FCFA</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-soft-primary btn-icon btn-circle btn-sm btn-edit-tier"
                                    data-id="{{ $tier->id }}"
                                    data-min="{{ $tier->min_km }}"
                                    data-max="{{ $tier->max_km }}"
                                    data-price="{{ $tier->price }}"
                                    data-manual="{{ $tier->is_manual_review ? '1' : '0' }}"
                                    data-toggle="modal" data-target="#editTierModal"
                                    title="{{ translate('Modifier') }}">
                                    <i class="las la-edit"></i>
                                </button>
                                <form action="{{ route('gps_shipping.tier.destroy', $tier) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('{{ translate('Supprimer ce palier ?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                        title="{{ translate('Supprimer') }}">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                <i class="las la-route" style="font-size:28px;"></i><br>
                                {{ translate('Aucun palier défini.') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white" style="font-size:12px;">
                <i class="las la-info-circle text-muted mr-1"></i>
                {{ translate('Point de départ : coordonnées configurées dans "Point de retrait du livreur".') }}
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body text-center">
                <a href="{{ route('gps_shipping.pending') }}" class="btn btn-primary">
                    <i class="las la-clock"></i>
                    {{ translate('Commandes en attente de supplément') }}
                    @php $pendingCount = \App\Models\Order::where('gps_shipping_pending', true)->count(); @endphp
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
                    <h5 class="modal-title h6">{{ translate('Ajouter un palier') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('backend.setup_configurations.gps_shipping._tier_form')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Annuler') }}</button>
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
                    <h5 class="modal-title h6">{{ translate('Modifier le palier') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    @include('backend.setup_configurations.gps_shipping._tier_form', ['edit' => true])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Annuler') }}</button>
                    <button type="submit" class="btn btn-primary">{{ translate('Enregistrer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    $('input[name="gps_weight_mode"]').on('change', function () {
        $('#volume-section').toggle($(this).val() === 'weight_volume');
    });

    $(document).on('click', '.btn-edit-tier', function () {
        var id     = $(this).data('id');
        var min    = $(this).data('min');
        var max    = $(this).data('max');
        var price  = $(this).data('price');
        var manual = $(this).data('manual') == '1';

        var form = $('#editTierForm');
        form.attr('action', '/admin/gps-shipping/tiers/' + id);
        form.find('[name="min_km"]').val(min);
        form.find('[name="max_km"]').val(max || '');
        form.find('[name="price"]').val(price);
        form.find('[name="is_manual_review"]').prop('checked', manual);
        form.find('.price-row, .max-row').toggle(!manual);
    });

    $(document).on('change', '[name="is_manual_review"]', function () {
        var checked = $(this).is(':checked');
        $(this).closest('form').find('.price-row, .max-row').toggle(!checked);
    });
</script>
@endsection
