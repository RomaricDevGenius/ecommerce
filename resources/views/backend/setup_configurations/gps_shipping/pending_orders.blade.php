@extends('backend.layouts.app')

@section('styles')
<style>
    /* ── Badges tableau ── */
    .gps-badge-dist {
        display: inline-flex; align-items: center; gap: 4px;
        background: #eef2ff; color: #4f46e5;
        border: 1px solid #c7d2fe; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600; white-space: nowrap;
    }
    .gps-badge-pending {
        display: inline-flex; align-items: center; gap: 6px;
        background: #fff7ed; color: #c2410c;
        border: 1px solid #fed7aa; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600;
    }
    .gps-badge-pending::before {
        content: ''; display: inline-block;
        width: 7px; height: 7px; border-radius: 50%; background: #fb923c;
    }
    .gps-badge-confirmed {
        display: inline-flex; align-items: center; gap: 6px;
        background: #f0fdf4; color: #15803d;
        border: 1px solid #bbf7d0; border-radius: 20px;
        padding: 3px 10px; font-size: 12px; font-weight: 600;
    }
    .gps-badge-confirmed::before {
        content: ''; display: inline-block;
        width: 7px; height: 7px; border-radius: 50%; background: #22c55e;
    }

    /* ── Timeline trajet dans le modal ── */
    #supplementModal .modal-dialog { max-width: 480px; }

    .rt-wrap {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 18px;
    }
    .rt-step {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 14px 18px;
        background: #fff;
    }
    .rt-step + .rt-step { border-top: 1px solid #f3f4f6; }

    .rt-icon {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }
    .rt-icon.store  { background: #dcfce7; color: #16a34a; }
    .rt-icon.client { background: #fee2e2; color: #dc2626; }

    .rt-label {
        font-size: 10px; font-weight: 700; letter-spacing: .6px;
        text-transform: uppercase; color: #9ca3af; margin-bottom: 3px;
    }
    .rt-name  { font-size: 14px; font-weight: 700; color: #111827; line-height: 1.3; }
    .rt-addr  { font-size: 12px; color: #6b7280; margin-top: 3px; line-height: 1.4; }
    .rt-addr.loading { color: #d1d5db; font-style: italic; }

    .rt-middle {
        display: flex; align-items: center; gap: 0;
        background: #f9fafb; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;
        padding: 0 18px;
    }
    .rt-vline { width: 2px; height: 20px; background: #d1d5db; margin-left: 17px; flex-shrink: 0; }
    .rt-dist-pill {
        margin-left: 14px;
        display: inline-flex; align-items: center; gap: 5px;
        background: #eff6ff; color: #1d4ed8;
        border: 1px solid #bfdbfe; border-radius: 20px;
        padding: 4px 14px; font-size: 12px; font-weight: 700;
    }
    .rt-connector {
        display: flex; flex-direction: column; align-items: flex-start;
        padding: 0 18px; background: #f9fafb;
        border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6;
    }
    .rt-connector-inner { display: flex; align-items: center; gap: 14px; width: 100%; padding: 6px 0; }
    .rt-vlines { display: flex; flex-direction: column; align-items: center; width: 36px; flex-shrink: 0; }
    .rt-vline-seg { width: 2px; height: 14px; background: #d1d5db; }
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
                                <th>{{ translate('Supplément') }}</th>
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
                                        <span class="gps-badge-pending">{{ translate('En attente') }}</span>
                                    @elseif($quote->status === 'confirmed')
                                        <span class="gps-badge-confirmed">{{ translate('Devis envoyé') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span>{{ $quote->created_at->format('d/m/Y') }}</span><br>
                                    <small class="text-muted">{{ $quote->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    @if($quote->supplement_amount)
                                        <span class="text-success font-weight-bold">
                                            {{ number_format($quote->supplement_amount, 0, ',', ' ') }} FCFA
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button type="button"
                                        class="btn btn-soft-primary btn-sm btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-toggle="modal" data-target="#supplementModal">
                                        <i class="las la-money-bill-wave mr-1"></i>{{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer le devis') }}
                                    </button>
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

                    {{-- Timeline départ → arrivée --}}
                    <div class="rt-wrap">

                        {{-- Départ --}}
                        <div class="rt-step">
                            <div class="rt-icon store"><i class="las la-store"></i></div>
                            <div style="flex:1;min-width:0">
                                <div class="rt-label">{{ translate('Départ') }}</div>
                                <div class="rt-name">{{ get_setting('site_name', 'Boutique') }}</div>
                                <div class="rt-addr" id="modal-store-addr">{{ get_setting('delivery_pickup_address', '—') }}</div>
                            </div>
                        </div>

                        {{-- Connecteur distance --}}
                        <div class="rt-connector">
                            <div class="rt-connector-inner">
                                <div class="rt-vlines">
                                    <div class="rt-vline-seg"></div>
                                    <div class="rt-vline-seg" style="background:transparent"></div>
                                    <div class="rt-vline-seg"></div>
                                </div>
                                <div class="rt-dist-pill">
                                    <i class="las la-road"></i>
                                    <span id="modal-dist-label">—</span>
                                </div>
                            </div>
                        </div>

                        {{-- Arrivée --}}
                        <div class="rt-step">
                            <div class="rt-icon client"><i class="las la-map-marker"></i></div>
                            <div style="flex:1;min-width:0">
                                <div class="rt-label">{{ translate('Arrivée — Client') }}</div>
                                <div class="rt-name" id="modal-client-name">—</div>
                                <div class="rt-addr loading" id="modal-client-addr">{{ translate('Chargement de l\'adresse...') }}</div>
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

                    {{-- Montant --}}
                    <div class="form-group row mb-0">
                        <label class="col-md-5 col-from-label" style="padding-top:10px">
                            {{ translate('Frais de livraison') }}
                        </label>
                        <div class="col-md-7">
                            <div class="input-group">
                                <input type="number" name="supplement" id="modal-supplement-input"
                                    class="form-control" min="0" step="1"
                                    placeholder="{{ translate('Ex : 3 500') }}" required>
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

@section('script')
<script>
    var SUPP_URL  = "{{ route('gps_shipping.supplement', ':id') }}";
    var STORE_LAT = {{ (float) get_setting('delivery_pickup_latitude',  '12.3714') }};
    var STORE_LNG = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};

    /* Reverse geocoding via Nominatim (gratuit, sans clé API) */
    function reverseGeocode(lat, lng, callback) {
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/reverse',
            data: { format: 'json', lat: lat, lon: lng, 'accept-language': 'fr' },
            headers: { 'Accept-Language': 'fr' },
            success: function(data) {
                if (data && data.address) {
                    var a = data.address;
                    /* Construire une adresse lisible : quartier / rue, ville */
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

    $(document).on('click', '.btn-fix-quote', function () {
        var $b   = $(this);
        var lat  = parseFloat($b.data('lat'));
        var lng  = parseFloat($b.data('lng'));
        var dist = $b.data('distance') || '?';

        /* Infos de base */
        $('#modal-client-name').text($b.data('client') || '—');
        $('#modal-dist-label').text(dist + ' km');
        $('#modal-supplement-input').val($b.data('current') || '');
        $('#supplementForm').attr('action', SUPP_URL.replace(':id', $b.data('quote-id')));

        /* Lien Google Maps */
        if (!isNaN(lat) && !isNaN(lng)) {
            $('#btn-gmaps').attr(
                'href',
                'https://www.google.com/maps/dir/' + STORE_LAT + ',' + STORE_LNG + '/' + lat + ',' + lng
            );
        } else {
            $('#btn-gmaps').attr('href', '#');
        }

        /* Adresse client via reverse geocoding */
        $('#modal-client-addr').text("{{ translate('Chargement de l\'adresse...') }}").addClass('loading');
        if (!isNaN(lat) && !isNaN(lng)) {
            reverseGeocode(lat, lng, function(addr) {
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
