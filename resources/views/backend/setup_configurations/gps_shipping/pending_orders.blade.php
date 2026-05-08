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

    /* ── Boutons ── */
    .btn-map-quote, .btn-fix-quote { white-space: nowrap; font-size: 12px; }
    .btn-map-quote.active-map { outline: 2px solid #1a73e8; outline-offset: 2px; }

    /* ── Panneau trajet inline ── */
    #route-panel { display: none; margin-top: 20px; }

    .route-step {
        display: flex; align-items: flex-start; gap: 14px;
        padding: 14px 18px;
    }
    .route-dot {
        width: 16px; height: 16px; border-radius: 50%;
        flex-shrink: 0; margin-top: 2px;
        border: 3px solid #fff;
        box-shadow: 0 0 0 2px currentColor;
    }
    .route-dot.store  { color: #22c55e; background: #22c55e; }
    .route-dot.client { color: #ef4444; background: #ef4444; }
    .route-connector {
        display: flex; flex-direction: column; align-items: center; gap: 0;
        padding: 0 18px; margin-left: 25px;
    }
    .route-connector-line {
        width: 2px; height: 40px;
        background: repeating-linear-gradient(
            to bottom, #1a73e8 0, #1a73e8 6px, transparent 6px, transparent 12px
        );
    }
    .route-connector-dist {
        background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 20px;
        padding: 3px 12px; font-size: 12px; font-weight: 700; color: #1a73e8;
        display: flex; align-items: center; gap: 4px;
    }

    /* ── Modal formulaire ── */
    #supplementModal.modal         { padding-left: 0 !important; }
    #supplementModal .modal-dialog { max-width: 440px; margin: 80px auto; }
    #supplementModal .modal-content{ border: none; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
    #supplementModal .modal-close-btn {
        background: rgba(255,255,255,.2); border: none; border-radius: 6px;
        color: #fff; width: 30px; height: 30px; display: flex; align-items: center;
        justify-content: center; cursor: pointer; font-size: 18px; line-height: 1;
        padding: 0;
    }
    #supplementModal .modal-close-btn:hover { background: rgba(255,255,255,.35); }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-12">

        {{-- ── Tableau des devis ── --}}
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
                                <th class="text-right" style="min-width:160px">{{ translate('Actions') }}</th>
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
                                        class="btn btn-soft-info btn-sm btn-map-quote mr-1"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        title="{{ translate('Voir le trajet') }}">
                                        <i class="las la-map-marked-alt mr-1"></i>{{ translate('Trajet') }}
                                    </button>
                                    <button type="button"
                                        class="btn btn-soft-warning btn-sm btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ number_format($quote->distance_km, 1) }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-toggle="modal" data-target="#supplementModal"
                                        title="{{ translate('Fixer le devis') }}">
                                        <i class="las la-money-bill-wave mr-1"></i>{{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer') }}
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

        {{-- ── Panneau trajet (s'affiche sous le tableau au clic) ── --}}
        <div id="route-panel" class="card">
            <div class="card-header d-flex justify-content-between align-items-center" style="background:#1a73e8;padding:14px 20px">
                <h5 class="mb-0 h6 text-white">
                    <i class="las la-route mr-2"></i>{{ translate('Trajet de livraison') }}
                </h5>
                <button type="button" id="btn-close-route" class="btn btn-sm btn-light">
                    <i class="las la-times mr-1"></i>{{ translate('Fermer') }}
                </button>
            </div>
            <div class="card-body" style="padding:8px 0">

                {{-- Étape 1 : Boutique --}}
                <div class="route-step">
                    <div class="route-dot store"></div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;font-weight:600">{{ translate('Départ — Boutique') }}</div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-top:2px">{{ get_setting('delivery_pickup_address', translate('Point de retrait')) }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px">
                            {{ (float) get_setting('delivery_pickup_latitude','—') }},
                            {{ (float) get_setting('delivery_pickup_longitude','—') }}
                        </div>
                    </div>
                </div>

                {{-- Connecteur avec distance --}}
                <div class="route-connector">
                    <div class="route-connector-line"></div>
                    <div class="route-connector-dist">
                        <i class="las la-road" style="font-size:13px"></i>
                        <span id="route-dist-label">— km</span>
                    </div>
                    <div class="route-connector-line"></div>
                </div>

                {{-- Étape 2 : Client --}}
                <div class="route-step">
                    <div class="route-dot client"></div>
                    <div>
                        <div style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#888;font-weight:600">{{ translate('Arrivée — Client') }}</div>
                        <div style="font-size:14px;font-weight:700;color:#1e293b;margin-top:2px" id="route-client-name">—</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:2px" id="route-client-coords">—</div>
                    </div>
                </div>

                {{-- Bouton Google Maps --}}
                <div class="px-4 pb-4 pt-2">
                    <a id="btn-open-gmaps" href="#" target="_blank" rel="noopener"
                        class="btn btn-block"
                        style="background:#1a73e8;color:#fff;border-radius:8px;font-weight:600;font-size:13px;padding:10px">
                        <i class="las la-external-link-alt mr-1"></i>
                        {{ translate('Voir le trajet sur Google Maps') }}
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════
     MODAL FORMULAIRE — bleu, propre
═══════════════════════════════════════════════ --}}
<div class="modal fade" id="supplementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="supplementForm" method="POST" action="">
                @csrf

                <div class="modal-header d-flex align-items-center justify-content-between" style="background:#1a73e8;border:none;padding:16px 22px">
                    <div>
                        <h5 class="modal-title h6 text-white mb-0">
                            <i class="las la-money-bill-wave mr-2"></i>{{ translate('Fixer le devis') }}
                        </h5>
                        <small class="text-white" style="opacity:.85" id="form-header-info"></small>
                    </div>
                    <button type="button" class="modal-close-btn" data-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>

                <div class="modal-body" style="padding:24px">
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;margin-bottom:20px">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">{{ translate('Client') }}</small>
                                <strong id="form-client">—</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">{{ translate('Distance') }}</small>
                                <strong><span id="form-distance">—</span> km</strong>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-1">
                        <label class="font-weight-600" style="font-size:13px;color:#374151">
                            {{ translate('Montant des frais de livraison') }}
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" name="supplement" id="modal-supplement-input"
                                class="form-control" min="0" step="1"
                                placeholder="Ex: 3500" required
                                style="border-radius:8px 0 0 8px;font-size:18px;font-weight:700;border-color:#bfdbfe">
                            <div class="input-group-append">
                                <span class="input-group-text"
                                    style="border-radius:0 8px 8px 0;background:#1a73e8;color:#fff;font-weight:700;border-color:#1a73e8;font-size:14px">
                                    FCFA
                                </span>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="las la-paper-plane mr-1"></i>{{ translate('Envoyé au client par push et email.') }}
                    </small>
                </div>

                <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:14px 24px">
                    <button type="button" class="btn btn-light" data-dismiss="modal" style="border-radius:8px">
                        {{ translate('Annuler') }}
                    </button>
                    <button type="submit" class="btn btn-primary" style="border-radius:8px;padding:8px 24px;font-weight:600;background:#1a73e8;border-color:#1a73e8">
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
    var STORE_LAT = {{ (float) get_setting('delivery_pickup_latitude',  '12.3714') }};
    var STORE_LNG = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};
    var SUPP_URL  = "{{ route('gps_shipping.supplement', ':id') }}";

    /* ── Bouton "Trajet" : affiche le panneau de trajet ── */
    $(document).on('click', '.btn-map-quote', function () {
        var $b     = $(this);
        var client = $b.data('client') || '?';
        var dist   = $b.data('distance') || '?';
        var lat    = parseFloat($b.data('lat'));
        var lng    = parseFloat($b.data('lng'));

        // Remplir les infos du panneau
        $('#route-client-name').text(client);
        $('#route-dist-label').text(dist + ' km');
        if (!isNaN(lat) && !isNaN(lng)) {
            $('#route-client-coords').text(lat.toFixed(5) + ', ' + lng.toFixed(5));
            $('#btn-open-gmaps').attr(
                'href',
                'https://www.google.com/maps/dir/' + STORE_LAT + ',' + STORE_LNG + '/' + lat + ',' + lng
            );
        } else {
            $('#route-client-coords').text('—');
            $('#btn-open-gmaps').attr('href', '#');
        }

        // Afficher et scroller
        $('#route-panel').show();
        $('html, body').animate({ scrollTop: $('#route-panel').offset().top - 20 }, 400);

        // Surbrillance du bouton actif
        $('.btn-map-quote').removeClass('active-map');
        $b.addClass('active-map');
    });

    /* ── Fermer le panneau ── */
    $(document).on('click', '#btn-close-route', function () {
        $('#route-panel').hide();
        $('.btn-map-quote').removeClass('active-map');
    });

    /* ── Bouton "Fixer" : remplit le formulaire ── */
    $(document).on('click', '.btn-fix-quote', function () {
        var $b = $(this);
        $('#form-client').text($b.data('client') || '—');
        $('#form-distance').text($b.data('distance') || '?');
        $('#modal-supplement-input').val($b.data('current') || '');
        $('#form-header-info').text(($b.data('client') || '') + ' — ' + ($b.data('distance') || '?') + ' km');
        $('#supplementForm').attr('action', SUPP_URL.replace(':id', $b.data('quote-id')));
    });
</script>
@endsection
