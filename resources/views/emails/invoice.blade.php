<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html;"/>
    <meta charset="UTF-8">
    <title>{{ get_setting('site_name') }}</title>
    <style media="all">
        @font-face {
            font-family: 'Roboto';
            src: url("{{ static_asset('fonts/Roboto-Regular.ttf') }}") format("truetype");
            font-weight: normal;
            font-style: normal;
        }
        * { margin: 0; padding: 0; line-height: 1.3; font-family: 'Roboto'; color: #333542; }
        body { font-size: .875rem; }
        .gry-color *, .gry-color { color: #878f9c; }
        table { width: 100%; }
        table th { font-weight: normal; }
        table.padding th { padding: .5rem .7rem; }
        table.padding td { padding: .7rem; }
        table.sm-padding td { padding: .2rem .7rem; }
        .border-bottom td, .border-bottom th { border-bottom: 1px solid #eceff4; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .small { font-size: .85rem; }
    </style>
</head>
<body>
@php $logo = get_setting('header_logo'); @endphp

<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#e8ebef">
    <tr>
        <td align="center" valign="top" style="padding: 50px 10px;">
            <table width="650" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td bgcolor="#ffffff" style="width:650px; min-width:650px; line-height:0pt; padding:0; margin:0;">

                        <!-- Header : logo + nom du site -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#f8fafa">
                            <tr>
                                <td style="padding: 30px 30px;">
                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                        <tr>
                                            <td>
                                                @if($logo != null)
                                                    <img src="{{ uploaded_asset($logo) }}" height="28" border="0" alt="" style="display:inline-block;">
                                                @else
                                                    <img src="{{ static_asset('assets/img/logo.png') }}" height="28" border="0" alt="" style="display:inline-block;">
                                                @endif
                                            </td>
                                            <td style="text-align:right; color:#000000; font-family:'Roboto',sans-serif; font-size:14px; font-weight:500;">
                                                {{ get_setting('site_name') }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                        <!-- END Header -->

                        <!-- Infos boutique + N° commande -->
                        <div style="background:#eceff4; padding: 1.2rem 1.5rem;">
                            <table>
                                <tr>
                                    <td style="font-size:1rem; font-weight:600;">{{ get_setting('site_name') }}</td>
                                    <td class="text-right"></td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">{{ get_setting('contact_address') }}</td>
                                    <td class="text-right"></td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">{{ translate('Email') }}: {{ get_setting('contact_email') }}</td>
                                    <td class="text-right small">
                                        <span class="gry-color small">{{ translate('Order ID') }}:</span>
                                        <span style="font-weight:600;">{{ $order->code }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="gry-color small">{{ translate('Phone') }}: {{ get_setting('contact_phone') }}</td>
                                    <td class="text-right small">
                                        <span class="gry-color small">{{ translate('Order Date') }}:</span>
                                        <span style="font-weight:600;">{{ date('d-m-Y', $order->date) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Adresse de livraison -->
                        <div style="padding: 1.5rem 1.5rem 0 1.5rem;">
                            @php $shipping_address = json_decode($order->shipping_address); @endphp
                            <table>
                                <tr><td class="small gry-color" style="font-weight:600;">{{ translate('Bill to') }}:</td></tr>
                                <tr><td style="font-weight:600;">{{ $shipping_address->name }}</td></tr>
                                <tr><td class="gry-color small">{{ $shipping_address->address }}, {{ $shipping_address->city }}, {{ $shipping_address->country }}</td></tr>
                                <tr><td class="gry-color small">{{ translate('Email') }}: {{ $shipping_address->email }}</td></tr>
                                <tr><td class="gry-color small">{{ translate('Phone') }}: {{ $shipping_address->phone }}</td></tr>
                            </table>
                        </div>

                        <!-- Tableau produits -->
                        <div style="padding: 1.5rem;">
                            <table class="padding text-left small border-bottom">
                                <thead>
                                    <tr class="gry-color" style="background:#eceff4;">
                                        <th width="35%">{{ translate('Product Name') }}</th>
                                        <th width="15%">{{ translate('Delivery Type') }}</th>
                                        <th width="10%">{{ translate('Qty') }}</th>
                                        <th width="15%">{{ translate('Unit Price') }}</th>
                                        <th width="10%">{{ translate('Tax') }}</th>
                                        <th width="15%" class="text-right">{{ translate('Total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($order->orderDetails as $key => $orderDetail)
                                        @if ($orderDetail->product != null)
                                            <tr>
                                                <td>{{ $orderDetail->product->getTranslation('name') }}@if($orderDetail->variation != null) ({{ $orderDetail->variation }})@endif</td>
                                                <td>
                                                    @if ($order->shipping_type == 'home_delivery')
                                                        {{ translate('Home Delivery') }}
                                                    @elseif ($order->shipping_type == 'pickup_point')
                                                        @if ($order->pickup_point != null)
                                                            {{ $order->pickup_point->getTranslation('name') }} ({{ translate('Pickip Point') }})
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="gry-color">{{ $orderDetail->quantity }}</td>
                                                <td class="gry-color currency">{{ single_price($orderDetail->price / $orderDetail->quantity) }}</td>
                                                <td class="gry-color currency">{{ single_price($orderDetail->tax / $orderDetail->quantity) }}</td>
                                                <td class="text-right currency">{{ single_price($orderDetail->price + $orderDetail->tax) }}</td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Totaux -->
                        <div style="padding: 0 1.5rem 2.5rem 1.5rem;">
                            <table style="width:40%; margin-left:auto;" class="text-right sm-padding small">
                                <tbody>
                                    <tr>
                                        <th class="gry-color text-left">{{ translate('Sub Total') }}</th>
                                        <td class="currency">{{ single_price($order->orderDetails->sum('price')) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="gry-color text-left">{{ translate('Shipping Cost') }}</th>
                                        <td class="currency">{{ single_price($order->orderDetails->sum('shipping_cost')) }}</td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <th class="gry-color text-left">{{ translate('Total Tax') }}</th>
                                        <td class="currency">{{ single_price($order->orderDetails->sum('tax')) }}</td>
                                    </tr>
                                    <tr class="border-bottom">
                                        <th class="gry-color text-left">{{ translate('Coupon') }}</th>
                                        <td class="currency">{{ single_price($order->coupon_discount) }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-left" style="font-weight:700;">{{ translate('Grand Total') }}</th>
                                        <td class="currency" style="font-weight:700;">{{ single_price($order->grand_total) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
