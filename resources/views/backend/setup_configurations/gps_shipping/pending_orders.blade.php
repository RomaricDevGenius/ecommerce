@extends('backend.layouts.app')

@section('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #quote-map { height: 220px; border-radius: 6px; margin-bottom: 12px; z-index: 0; }
    .quote-distance-badge { white-space: nowrap; }
    .btn-fix-quote { white-space: nowrap; }
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
                    <i class="las la-arrow-left"></i> {{ translate('Retour configuration') }}
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
                                <th class="text-right" style="min-width:100px">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($quotes as $quote)
                            <tr>
                                <td>
                                    <span class="font-weight-bold">{{ $quote->user ? $quote->user->name : '—' }}</span><br>
                                    <small class="text-muted">{{ $quote->user ? $quote->user->email : '' }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-info quote-distance-badge">
                                        {{ number_format($quote->distance_km, 1) }} km
                                    </span>
                                </td>
                                <td>
                                    @if($quote->status === 'pending')
                                        <span class="badge badge-warning">{{ translate('En attente') }}</span>
                                    @elseif($quote->status === 'confirmed')
                                        <span class="badge badge-success">{{ translate('Devis envoyé') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $quote->created_at->format('d/m/Y H:i') }}</small>
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
                                        class="btn btn-soft-warning btn-sm btn-fix-quote"
                                        data-quote-id="{{ $quote->id }}"
                                        data-client="{{ $quote->user ? $quote->user->name : '?' }}"
                                        data-distance="{{ $quote->distance_km }}"
                                        data-current="{{ $quote->supplement_amount }}"
                                        data-lat="{{ $quote->delivery_lat }}"
                                        data-lng="{{ $quote->delivery_lng }}"
                                        data-toggle="modal" data-target="#supplementModal"
                                        title="{{ translate('Fixer le devis') }}">
                                        <i class="las la-money-bill-wave"></i>
                                        {{ $quote->supplement_amount ? translate('Modifier') : translate('Fixer') }}
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="las la-check-circle" style="font-size:40px;"></i><br>
                                    {{ translate('Aucun devis en attente.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($quotes->hasPages())
            <div class="card-footer">
                {{ $quotes->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal supplément --}}
<div class="modal fade" id="supplementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="supplementForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Fixer le devis de livraison') }}</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- Carte Leaflet --}}
                    <div id="quote-map"></div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted d-block">{{ translate('Client') }}</small>
                            <strong id="modal-client">—</strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">{{ translate('Distance') }}</small>
                            <strong><span id="modal-distance">—</span> km</strong>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label>{{ translate('Frais de livraison (FCFA)') }}</label>
                        <div class="input-group">
                            <input type="number" name="supplement" id="modal-supplement-input"
                                class="form-control" min="0" step="1" placeholder="Ex: 3500" required>
                            <div class="input-group-append">
                                <span class="input-group-text">FCFA</span>
                            </div>
                        </div>
                        <small class="text-muted">
                            {{ translate('Ce montant sera proposé au client via notification push et email.') }}
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Annuler') }}</button>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var storeLat = {{ (float) get_setting('delivery_pickup_latitude', '12.3714') }};
    var storeLng = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};
    var supplementBaseUrl = "{{ route('gps_shipping.supplement', ':id') }}";
    var quoteMap = null;

    // Event delegation : fonctionne même si DataTables re-render le DOM
    $(document).on('click', '.btn-fix-quote', function () {
        var quoteId  = $(this).data('quote-id');
        var client   = $(this).data('client');
        var distance = $(this).data('distance');
        var current  = $(this).data('current');
        var lat      = parseFloat($(this).data('lat'));
        var lng      = parseFloat($(this).data('lng'));

        $('#modal-client').text(client || '—');
        $('#modal-distance').text(distance ? parseFloat(distance).toFixed(1) : '?');
        $('#modal-supplement-input').val(current || '');
        $('#supplementForm').attr('action', supplementBaseUrl.replace(':id', quoteId));

        // Initialiser la carte après ouverture du modal (délai pour que le DOM soit visible)
        setTimeout(function () {
            if (quoteMap) { quoteMap.remove(); quoteMap = null; }

            quoteMap = L.map('quote-map').setView([storeLat, storeLng], 10);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(quoteMap);

            var storeIcon = L.divIcon({ className: '', html: '<div style="background:#28a745;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.5)"></div>' });
            var clientIcon = L.divIcon({ className: '', html: '<div style="background:#e74c3c;width:14px;height:14px;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.5)"></div>' });

            L.marker([storeLat, storeLng], { icon: storeIcon })
                .addTo(quoteMap)
                .bindPopup('<strong>Boutique (départ)</strong>');

            if (!isNaN(lat) && !isNaN(lng)) {
                L.marker([lat, lng], { icon: clientIcon })
                    .addTo(quoteMap)
                    .bindPopup('<strong>' + client + '</strong><br>Lieu de livraison');

                L.polyline([[storeLat, storeLng], [lat, lng]], {
                    color: '#007bff', weight: 2, dashArray: '6'
                }).addTo(quoteMap);

                quoteMap.fitBounds([[storeLat, storeLng], [lat, lng]], { padding: [25, 25] });
            }
        }, 350);
    });

    $('#supplementModal').on('hidden.bs.modal', function () {
        if (quoteMap) { quoteMap.remove(); quoteMap = null; }
    });
</script>
@endsection
