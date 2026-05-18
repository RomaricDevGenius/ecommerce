@extends('backend.layouts.app')

@section('styles')
<style>
    /* ── Badge distance ── */
    .gps-badge-dist {
        display: inline-flex; align-items: center; gap: 4px;
        background: #eef2ff; color: #4f46e5;
        border: 1px solid #c7d2fe; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600; white-space: nowrap;
    }

    /* ── Badges statut unifiés ── */
    .gps-status {
        display: inline-flex; align-items: center; gap: 5px;
        border-radius: 20px; padding: 4px 12px;
        font-size: 12px; font-weight: 600; white-space: nowrap;
    }
    .gps-status.pending   { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
    .gps-status.confirmed { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .gps-status.accepted  { background: #ecfdf5; color: #065f46; border: 1px solid #6ee7b7; }
    .gps-status.refused   { background: #fef2f2; color: #dc2626; border: 1px solid #fca5a5; }
    .gps-status.expired   { background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; }
    .gps-status.pending::before, .gps-status.confirmed::before {
        content: ''; display: inline-block;
        width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
    }
    .gps-status.pending::before   { background: #fb923c; }
    .gps-status.confirmed::before { background: #22c55e; }

    /* ── Timeline trajet dans le modal ── */
    #supplementModal .modal-dialog { max-width: 480px; }

    .rt-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 16px 18px;
        margin-bottom: 18px;
        background: #fafafa;
    }

    /* Layout horizontal : [départ] ─── dist ─── [arrivée] */
    .rt-horizontal { display: flex; align-items: flex-start; gap: 10px; }

    /* Colonne point + texte */
    .rt-col { flex: 1; min-width: 0; }

    .rt-dot-row { display: flex; align-items: center; gap: 7px; margin-bottom: 5px; }
    .rt-dot {
        width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
    }
    .rt-dot.store  { background: #16a34a; box-shadow: 0 0 0 3px #dcfce7; }
    .rt-dot.client { background: #dc2626; box-shadow: 0 0 0 3px #fee2e2; }

    .rt-label {
        font-size: 10px; font-weight: 700; letter-spacing: .5px;
        text-transform: uppercase; color: #9ca3af;
    }
    .rt-name  { font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 2px; }
    .rt-addr  { font-size: 11px; color: #6b7280; line-height: 1.4; }
    .rt-addr.loading { color: #d1d5db; font-style: italic; }

    /* Colonne centrale : séparateur + distance */
    .rt-middle {
        display: flex; flex-direction: column; align-items: center;
        flex-shrink: 0; padding-top: 2px; gap: 4px;
    }
    .rt-hline { width: 1px; height: 16px; background: #d1d5db; }
    .rt-dist-pill {
        display: inline-flex; align-items: center; gap: 4px;
        background: #eff6ff; color: #1d4ed8;
        border: 1px solid #bfdbfe; border-radius: 20px;
        padding: 3px 10px; font-size: 11px; font-weight: 700; white-space: nowrap;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0 h6">
                    <i class="las la-clock text-warning mr-1"></i>
                    {{ translate('Devis GPS en attente') }}
                </h5>
                <a href="{{ route('gps_shipping.config') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="las la-arrow-left mr-1"></i>{{ translate('Retour configuration') }}
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table aiz-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ translate('Client') }}</th>
                                <th>{{ translate('Distance') }}</th>
                                <th>{{ translate('Statut') }}</th>
                                <th>{{ translate('Date') }}</th>
                                <th>{{ translate('Base') }}</th>
                                <th>{{ translate('Supplément') }}</th>
                                <th>{{ translate('Total') }}</th>
                                <th class="text-right">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotes as $quote)
                            <tr>
                                <td>
                                    <span class="font-weight-600">{{ $quote->user ? $quote->user->name : '—' }}</span><br>
                                    <small class="text-muted">{{ $quote->user ? $quote->user->email : '' }}</small>
                                </td>
                                <td>
                                    <span class="gps-badge-dist">
                                        <i class="las la-route" style="font-size:13px"></i>
                                        {{ number_format($quote->distance_km, 1) }} km
                                    </span>
                                </td>
                                <td>
                                    @if($quote->status === 'pending')
                                        <span class="gps-status pending">{{ translate('En attente') }}</span>
                                    @elseif($quote->status === 'confirmed')
                                        <span class="gps-status confirmed">{{ translate('Envoyé') }}</span>
                                    @elseif($quote->status === 'accepted')
                                        <span class="gps-status accepted">{{ translate('Accepté') }}</span>
                                    @elseif($quote->status === 'refused')
                                        <span class="gps-status refused">{{ translate('Refusé') }}</span>
                                    @elseif($quote->status === 'expired')
                                        <span class="gps-status expired">{{ translate('Expiré') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{ $quote->created_at->format('d/m/Y') }}</span><br>
                                    <small class="text-muted">{{ $quote->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        {{ $quote->base_amount > 0 ? number_format($quote->base_amount, 0, ',', ' ').' FCFA' : '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        {{ $quote->supplement_amount > 0 ? number_format($quote->supplement_amount, 0, ',', ' ').' FCFA' : '—' }}
                                    </span>
                                </td>
                                <td>
                                    @if($quote->total_amount > 0)
                                        <span class="text-success font-weight-bold">
                                            {{ number_format($quote->total_amount, 0, ',', ' ') }} FCFA
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if(in_array($quote->status, ['pending', 'confirmed']))
                                    <button type="button"
                                        class="btn btn-soft-primary btn-sm btn-fix-quote mr-1"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-base="{{ $quote->base_amount }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-dep-lat="{{ $quote->departure_lat ?? (float)get_setting('delivery_pickup_latitude', '12.3714') }}"
                                        data-dep-lng="{{ $quote->departure_lng ?? (float)get_setting('delivery_pickup_longitude', '-1.5197') }}"
                                        data-dep-name="{{ ($quote->seller_id && $quote->shop) ? $quote->shop->name : get_setting('site_name', 'Boutique') }}"
                                        data-toggle="modal" data-target="#supplementModal">
                                        <i class="las la-money-bill-wave mr-1"></i>{{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer le devis') }}
                                    </button>
                                    @endif
                                    <form action="{{ route('gps_shipping.quote.destroy', $quote->id) }}" method="POST"
                                          class="d-inline" id="del-quote-{{ $quote->id }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-soft-danger btn-sm btn-delete-quote"
                                                data-form-id="del-quote-{{ $quote->id }}">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="las la-check-circle" style="font-size:48px;color:#ccc"></i>
                                    <p class="mt-2 mb-0">{{ translate('Aucun devis en attente.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($quotes->hasPages())
            <div class="card-footer">{{ $quotes->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- ══ Modal : Fixer le devis ══ --}}
<div class="modal fade" id="supplementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="mb-0 h6">
                    <i class="las la-route mr-1"></i>{{ translate('Fixer le devis de livraison') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal"></button>
            </div>

            <form id="supplementForm" method="POST" action="">
                @csrf
                <div class="modal-body" style="padding: 20px 24px">

                    {{-- Départ / Distance / Arrivée sur la même ligne --}}
                    <div class="rt-wrap">
                        <div class="rt-horizontal">

                            {{-- Départ --}}
                            <div class="rt-col">
                                <div class="rt-dot-row">
                                    <div class="rt-dot store"></div>
                                    <span class="rt-label">{{ translate('Départ') }}</span>
                                </div>
                                <div class="rt-name" id="modal-store-name">—</div>
                                <div class="rt-addr loading" id="modal-store-addr">{{ translate('Chargement...') }}</div>
                            </div>

                            {{-- Distance (centre) --}}
                            <div class="rt-middle">
                                <div class="rt-hline"></div>
                                <div class="rt-dist-pill">
                                    <i class="las la-road"></i>
                                    <span id="modal-dist-label">—</span>
                                </div>
                                <div class="rt-hline"></div>
                            </div>

                            {{-- Arrivée --}}
                            <div class="rt-col" style="text-align:right">
                                <div class="rt-dot-row" style="justify-content:flex-end">
                                    <span class="rt-label">{{ translate('Arrivée') }}</span>
                                    <div class="rt-dot client"></div>
                                </div>
                                <div class="rt-name" id="modal-client-name">—</div>
                                <div class="rt-addr loading" id="modal-client-addr">{{ translate('Chargement...') }}</div>
                            </div>

                        </div>
                    </div>

                    {{-- Bouton Google Maps --}}
                    <a id="btn-gmaps" href="#" target="_blank" rel="noopener"
                        class="btn btn-block btn-light mb-4"
                        style="border:1px solid #e5e7eb;font-size:13px;font-weight:600;color:#374151;border-radius:8px;padding:9px">
                        <i class="las la-external-link-alt mr-1" style="color:#1a73e8"></i>
                        {{ translate('Voir le trajet sur Google Maps') }}
                    </a>

                    {{-- Bandeau révision manuelle (visible uniquement si base = 0) --}}
                    <div id="modal-manual-banner" class="alert alert-warning py-2 px-3 mb-3" style="display:none;font-size:12.5px;border-radius:8px">
                        <i class="las la-exclamation-triangle mr-1"></i>
                        <strong>{{ translate('Zone hors paliers automatiques') }}</strong> —
                        {{ translate('Saisissez le montant total à facturer au client.') }}
                    </div>

                    {{-- Base calculée (lecture seule) --}}
                    <div id="modal-base-row" class="form-group row mb-2">
                        <label class="col-md-5 col-from-label" style="padding-top:8px;font-size:13px">
                            {{ translate('Base calculée') }}
                        </label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" id="modal-base-display"
                                    class="form-control bg-light text-muted" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <small class="text-muted" id="modal-base-note">{{ translate('Calculé automatiquement par le système') }}</small>
                        </div>
                    </div>

                    {{-- Supplément admin --}}
                    <div class="form-group row mb-2">
                        <label class="col-md-5 col-from-label" style="padding-top:8px" id="modal-supplement-label">
                            {{ translate('Supplément') }}
                        </label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="number" name="supplement" id="modal-supplement-input"
                                    class="form-control" min="0" step="1" value="0"
                                    placeholder="{{ translate('Ex : 500') }}" required>
                                <div class="input-group-append">
                                    <span class="input-group-text font-weight-600">FCFA</span>
                                </div>
                            </div>
                            <small class="text-muted" id="modal-supplement-note">{{ translate('Montant additionnel si zone difficile, etc.') }}</small>
                        </div>
                    </div>

                    {{-- Total = base + supplément --}}
                    <div class="form-group row mb-0">
                        <label class="col-md-5 col-from-label" style="padding-top:8px;font-weight:700">
                            {{ translate('Total facturé au client') }}
                        </label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="text" id="modal-total-display"
                                    class="form-control font-weight-bold text-success bg-light" readonly>
                                <div class="input-group-append">
                                    <span class="input-group-text font-weight-600">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">
                        {{ translate('Annuler') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Envoyer au client') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('script')
<script>
    /* ── Suppression via modal système ── */
    var _deleteFormId = null;
    $(document).on('click', '.btn-delete-quote', function () {
        _deleteFormId = $(this).data('form-id');
        $('#delete-modal').modal('show');
    });
    $(document).on('click', '#delete-link', function (e) {
        e.preventDefault();
        if (_deleteFormId) {
            $('#' + _deleteFormId).submit();
        }
    });

    var SUPP_URL       = "{{ route('gps_shipping.supplement', ':id') }}";
    var DEFAULT_DEP_LAT = {{ (float) get_setting('delivery_pickup_latitude',  '12.3714') }};
    var DEFAULT_DEP_LNG = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};
    var DEFAULT_DEP_NAME = {!! json_encode(get_setting('site_name', 'Boutique')) !!};
    var LOADING_TEXT   = "{{ translate('Chargement...') }}";

    /* Reverse geocoding via Nominatim (gratuit, sans clé API) */
    function reverseGeocode(lat, lng, callback) {
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/reverse',
            data: { format: 'json', lat: lat, lon: lng, 'accept-language': 'fr' },
            headers: { 'Accept-Language': 'fr' },
            success: function(data) {
                if (data && data.address) {
                    var a = data.address;
                    var parts = [];
                    var street = a.road || a.pedestrian || a.neighbourhood || a.suburb || '';
                    var city   = a.city || a.town || a.village || a.county || '';
                    if (street) parts.push(street);
                    if (city && city !== street) parts.push(city);
                    callback(parts.length ? parts.join(', ') : (data.display_name || null));
                } else {
                    callback(null);
                }
            },
            error: function() { callback(null); }
        });
    }

    /* Cache des adresses géocodées par coordonnées */
    var _geocodeCache = {};
    function cachedGeocode(lat, lng, callback) {
        var key = lat.toFixed(5) + ',' + lng.toFixed(5);
        if (_geocodeCache[key] !== undefined) {
            callback(_geocodeCache[key]);
            return;
        }
        reverseGeocode(lat, lng, function(addr) {
            _geocodeCache[key] = addr;
            callback(addr);
        });
    }

    /* Calcul du total en temps réel quand l'admin modifie le supplément */
    function updateTotal() {
        var base  = parseFloat($('#modal-base-display').data('raw') || 0);
        var supp  = parseFloat($('#modal-supplement-input').val() || 0);
        var total = base + (isNaN(supp) ? 0 : supp);
        $('#modal-total-display').val(Math.round(total).toLocaleString('fr-FR'));
    }
    $(document).on('input', '#modal-supplement-input', updateTotal);

    $(document).on('click', '.btn-fix-quote', function () {
        var $b          = $(this);
        var lat         = parseFloat($b.data('lat'));
        var lng         = parseFloat($b.data('lng'));
        var depLat      = parseFloat($b.data('dep-lat') || DEFAULT_DEP_LAT);
        var depLng      = parseFloat($b.data('dep-lng') || DEFAULT_DEP_LNG);
        var depName     = $b.data('dep-name') || DEFAULT_DEP_NAME;
        var dist        = $b.data('distance') || '?';
        var baseAmt     = parseFloat($b.data('base') || 0);
        var currentSupp = parseFloat($b.data('current') || 0);

        /* Infos client et distance */
        $('#modal-client-name').text($b.data('client') || '—');
        $('#modal-dist-label').text(dist + ' km');

        /* Point de départ — nom + géocodage */
        $('#modal-store-name').text(depName);
        $('#modal-store-addr').addClass('loading').text(LOADING_TEXT);
        cachedGeocode(depLat, depLng, function(addr) {
            $('#modal-store-addr')
                .removeClass('loading')
                .text(addr || (depLat.toFixed(4) + ', ' + depLng.toFixed(4)));
        });

        /* Base calculée (lecture seule) */
        var isManual = (baseAmt === 0);
        $('#modal-base-display')
            .val(isManual ? '—' : Math.round(baseAmt).toLocaleString('fr-FR'))
            .data('raw', baseAmt);
        $('#modal-manual-banner').toggle(isManual);
        $('#modal-base-row').toggle(!isManual);
        $('#modal-supplement-label').text(isManual ? '{{ translate("Prix total de livraison") }}' : '{{ translate("Supplément") }}');
        $('#modal-supplement-note').text(isManual ? '{{ translate("Montant total à facturer au client pour ce trajet") }}' : '{{ translate("Montant additionnel si zone difficile, etc.") }}');

        /* Supplément (valeur existante ou 0) */
        $('#modal-supplement-input').val(currentSupp > 0 ? currentSupp : 0);

        /* Total */
        updateTotal();

        $('#supplementForm').attr('action', SUPP_URL.replace(':id', $b.data('quote-id')));

        /* Lien Google Maps avec le bon point de départ */
        if (!isNaN(lat) && !isNaN(lng)) {
            $('#btn-gmaps').attr(
                'href',
                'https://www.google.com/maps/dir/' + depLat + ',' + depLng + '/' + lat + ',' + lng
            );
        } else {
            $('#btn-gmaps').attr('href', '#');
        }

        /* Adresse client via reverse geocoding */
        $('#modal-client-addr').addClass('loading').text(LOADING_TEXT);
        if (!isNaN(lat) && !isNaN(lng)) {
            cachedGeocode(lat, lng, function(addr) {
                $('#modal-client-addr')
                    .removeClass('loading')
                    .text(addr || (lat.toFixed(4) + ', ' + lng.toFixed(4)));
            });
        } else {
            $('#modal-client-addr').removeClass('loading').text('—');
        }
    });
</script>
@endsection
