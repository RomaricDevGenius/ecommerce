@extends('frontend.layouts.app')

@section('content')

    <section class="my-4 gry-bg">
        <div class="container">
            <div class="row cols-xs-space cols-sm-space cols-md-space">
                <div class="col-lg-8 mx-auto">
                    <form class="form-default" data-toggle="validator" action="{{ route('payment.checkout') }}" role="form" method="POST" id="checkout-form">
                        @csrf

                        <div class="accordion" id="accordioncCheckoutInfo">

                            <!-- Shipping Info -->
                            <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem;">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingShippingInfo" type="button" data-toggle="collapse" data-target="#collapseShippingInfo" aria-expanded="true" aria-controls="collapseShippingInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Shipping Info') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapseShippingInfo" class="collapse show" aria-labelledby="headingShippingInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="shipping_info">
                                       @include('frontend.partials.cart.shipping_info', ['address_id' => $address_id])
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Location (GPS) -->
                            @if (get_setting('shipping_type') == 'gps_distance_shipping')
                            <div class="card rounded-0 border shadow-none" id="deliveryLocationCard" style="margin-bottom: 2rem;">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingDeliveryLocation">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Delivery Location') }}</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row gutters-5">
                                        <div class="col-sm-6 mb-2">
                                            <button type="button" class="btn btn-delivery-location btn-block fw-600" onclick="useCurrentLocation()">
                                                <i class="las la-location-arrow"></i> {{ translate('My current location') }}
                                            </button>
                                        </div>
                                        <div class="col-sm-6 mb-2">
                                            <button type="button" class="btn btn-delivery-location btn-block fw-600" onclick="openDeliveryMap()">
                                                <i class="las la-map"></i> {{ translate('Choose on map') }}
                                            </button>
                                        </div>
                                    </div>
                                    <div id="gps_location_status" class="mt-2 fs-13 text-muted">
                                        {{ translate('Please choose your delivery location to calculate the shipping cost.') }}
                                    </div>
                                    <div id="gps_quote_box" class="mt-3" style="display:none;"></div>
                                </div>
                            </div>
                            @endif

                            <!-- Delivery Info -->
                            <div class="card rounded-0 border shadow-none" style="margin-bottom: 2rem; overflow: visible !important;">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingDeliveryInfo" type="button" data-toggle="collapse" data-target="#collapseDeliveryInfo" aria-expanded="true" aria-controls="collapseDeliveryInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Delivery Info') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapseDeliveryInfo" class="collapse show" aria-labelledby="headingDeliveryInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="delivery_info">
                                        @include('frontend.partials.cart.delivery_info', ['carts' => $carts, 'carrier_list' => $carrier_list, 'shipping_info' => $shipping_info])
                                    </div>
                                </div>
                            </div>


                            <!-- Payment Info -->
                            <div class="card rounded-0 mb-0 border shadow-none">
                                <div class="card-header border-bottom-0 py-3 py-xl-4" id="headingPaymentInfo" type="button" data-toggle="collapse" data-target="#collapsePaymentInfo" aria-expanded="true" aria-controls="collapsePaymentInfo">
                                    <div class="d-flex align-items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20">
                                            <path id="Path_42357" data-name="Path 42357" d="M58,48A10,10,0,1,0,68,58,10,10,0,0,0,58,48ZM56.457,61.543a.663.663,0,0,1-.423.212.693.693,0,0,1-.428-.216l-2.692-2.692.856-.856,2.269,2.269,6-6.043.841.87Z" transform="translate(-48 -48)" fill="#9d9da6"/>
                                        </svg>
                                        <span class="ml-2 fs-19 fw-700">{{ translate('Payment') }}</span>
                                    </div>
                                    <i class="las la-angle-down fs-18"></i>
                                </div>
                                <div id="collapsePaymentInfo" class="collapse show" aria-labelledby="headingPaymentInfo" data-parent="#accordioncCheckoutInfo">
                                    <div class="card-body" id="payment_info">
                                        @include('frontend.partials.cart.payment_info', ['carts' => $carts, 'total' => $total])

                                        <!-- Agree Box -->
                                        <div class="pt-2rem fs-14">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" required id="agree_checkbox" onchange="stepCompletionPaymentInfo()">
                                                <span class="aiz-square-check"></span>
                                                <span>{{ translate('I agree to the') }}</span>
                                            </label>
                                            <a href="{{ route('terms') }}"
                                                class="fw-700">{{ translate('terms and conditions') }}</a>,
                                            <a href="{{ route('returnpolicy') }}"
                                                class="fw-700">{{ translate('return policy') }}</a> &
                                            <a href="{{ route('privacypolicy') }}"
                                                class="fw-700">{{ translate('privacy policy') }}</a>
                                        </div>

                                        <div class="row align-items-center pt-3 mb-4">
                                            <!-- Return to shop -->
                                            <div class="col-6">
                                                <a href="{{ route('home') }}" class="btn btn-link fs-14 fw-700 px-0">
                                                    <i class="las la-arrow-left fs-16"></i>
                                                    {{ translate('Return to shop') }}
                                                </a>
                                            </div>
                                            <!-- Complete Ordert -->
                                            <div class="col-6 text-right">
                                                <button type="button" onclick="submitOrder(this)" id="submitOrderBtn"
                                                    class="btn btn-primary fs-14 fw-700 rounded-0 px-4 d-inline-flex align-items-center justify-content-center">
                                                    <span id="spinner-complete-order" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                                    {{ translate('Complete Order') }}</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
                <!-- Cart Summary -->
                <div class="col-lg-4 mt-4 mt-lg-0" id="cart_summary">
                    @include('frontend.partials.cart.cart_summary', ['proceed' => 0, 'carts' => $carts])
                </div>
            </div>
        </div>
    </section>
@endsection

@section('modal')
    <!-- Address Modal -->
    @if(Auth::check())
        @include('frontend.partials.address.address_modal')
         @include('frontend.partials.address.billing_address_modal')
    @endif

    <!-- Delivery Location Map Modal (GPS) -->
    @if (get_setting('shipping_type') == 'gps_distance_shipping')
    <div class="modal fade" id="delivery_map_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Choose your location on the map') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-2">
                        <input type="text" id="delivery_map_search" class="form-control"
                            placeholder="{{ translate('Search a locality...') }}"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();searchDeliveryLocation();}">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" onclick="searchDeliveryLocation()">
                                <i class="las la-search"></i>
                            </button>
                        </div>
                    </div>
                    <div id="delivery_map_suggestions" class="list-group mb-2" style="max-height:180px; overflow-y:auto; display:none;"></div>
                    <p class="fs-13 text-muted mb-2">{{ translate('Tap on the map to place the marker on your exact location.') }}</p>
                    <div id="delivery_map" style="height: 360px; width: 100%; border-radius: 6px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <button type="button" class="btn btn-primary" onclick="confirmMapLocation()">{{ translate('Confirm location') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
<style>
#deliveryLocationCard { position: relative; }
#deliveryLocationCard::before,
#deliveryLocationCard::after {
    box-sizing: inherit; content: ''; position: absolute; z-index: 2;
    width: 0; height: 0; border: 2px solid transparent;
}
#deliveryLocationCard::before { top: 0; left: 0; }
#deliveryLocationCard::after  { top: 0; bottom: 0; left: 0; right: 0; }
#deliveryLocationCard.delivery-location-error::before,
#deliveryLocationCard.delivery-location-error::after { width: 100%; height: 100%; }
#deliveryLocationCard.delivery-location-error::before {
    border-top-color: #dc3545; border-right-color: #dc3545;
    transition: width 0.3s ease-out, height 0.3s ease-out 0.3s;
}
#deliveryLocationCard.delivery-location-error::after {
    border-bottom-color: #dc3545; border-left-color: #dc3545;
    transition: height 0.3s ease-out, width 0.3s ease-out 0.3s;
}
#deliveryLocationCard .btn-delivery-location {
    background-color: var(--primary); border-color: var(--primary); color: #fff;
}
#deliveryLocationCard .btn-delivery-location:hover,
#deliveryLocationCard .btn-delivery-location:focus {
    background-color: var(--primary); border-color: var(--primary); color: #fff; opacity: 0.85;
}
</style>
    <script type="text/javascript">
       var carrierCount=0;
        $(document).ready(function() {
            $(".online_payment").click(function() {
                $('#manual_payment_description').parent().addClass('d-none');
            });
            toggleManualPaymentData($('input[name=payment_option]:checked').data('id'));
        });

        var minimum_order_amount_check = {{ get_setting('minimum_order_amount_check') == 1 ? 1 : 0 }};
        var minimum_order_amount =
            {{ get_setting('minimum_order_amount_check') == 1 ? get_setting('minimum_order_amount') : 0 }};

        function use_wallet() {
            $('input[name=payment_option]').val('wallet');
            if ($('#agree_checkbox').is(":checked")) {
                ;
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var allIsOk = false;
                    var isOkShipping = stepCompletionShippingInfo();
                    var isOkDelivery = stepCompletionDeliveryInfo();
                    var isOkPayment = stepCompletionWalletPaymentInfo();
                    if(isOkShipping && isOkDelivery && isOkPayment) {
                        allIsOk = true;
                    }else{
                        var notifyMsg = !isOkDelivery ? 'Veuillez sélectionner votre lieu de livraison.' : '{{ translate("Please fill in all mandatory fields!") }}';
                        AIZ.plugins.notify('danger', notifyMsg);
                        if (!isOkDelivery) {
                            var $dlCard = $('#deliveryLocationCard');
                            $dlCard.removeClass('delivery-location-error');
                            setTimeout(function(){ $dlCard.addClass('delivery-location-error'); $dlCard[0] && $dlCard[0].scrollIntoView({behavior:'smooth',block:'center'}); }, 10);
                        }
                        $('#checkout-form [required]').each(function (i, el) {
                            if ($(el).val() == '' || $(el).val() == undefined) {
                                var is_trx_id = $('.d-none #trx_id').length;
                                if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                    $(el).focus();
                                    $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                    return false;
                                }
                            }
                        });
                    }

                    if (allIsOk) {
                        $('#checkout-form').submit();
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
            }
        }

        function submitOrder(el) {
            $(el).prop('disabled', true);
            if ($('#agree_checkbox').is(":checked")) {
                if (minimum_order_amount_check && $('#sub_total').val() < minimum_order_amount) {
                    AIZ.plugins.notify('danger',
                        '{{ translate('You order amount is less then the minimum order amount') }}');
                } else {
                    var offline_payment_active = '{{ addon_is_activated('offline_payment') }}';
                    if (offline_payment_active == '1' && $('.offline_payment_option').is(":checked") && $('#trx_id')
                        .val() == '') {
                        AIZ.plugins.notify('danger', '{{ translate('You need to put Transaction id') }}');
                        $(el).prop('disabled', false);
                    } else {
                        var allIsOk = false;
                        var isOkShipping = stepCompletionShippingInfo();
                        var isOkDelivery = stepCompletionDeliveryInfo();
                        var isOkPayment = stepCompletionPaymentInfo();
                        if(isOkShipping && isOkDelivery && isOkPayment) {
                            allIsOk = true;
                        }else{
                            var notifyMsg = !isOkDelivery ? 'Veuillez sélectionner votre lieu de livraison.' : '{{ translate("Please fill in all mandatory fields!") }}';
                            AIZ.plugins.notify('danger', notifyMsg);
                            if (!isOkDelivery) {
                                var $dlCard = $('#deliveryLocationCard');
                                $dlCard.removeClass('delivery-location-error');
                                setTimeout(function(){ $dlCard.addClass('delivery-location-error'); $dlCard[0] && $dlCard[0].scrollIntoView({behavior:'smooth',block:'center'}); }, 10);
                            }
                            $('#checkout-form [required]').each(function (i, el) {
                                if ($(el).val() == '' || $(el).val() == undefined) {
                                    var is_trx_id = $('.d-none #trx_id').length;
                                    if(($(el).attr('name') != 'trx_id') || is_trx_id == 0){
                                        $(el).focus();
                                        $(el).scrollIntoView({behavior: "smooth", block: "center"});
                                        return false;
                                    }
                                }
                            });
                        }

                        if (allIsOk) {
                            $('#spinner-complete-order').removeClass('d-none');
                            $('#checkout-form').submit();
                        }
                    }
                }
            } else {
                AIZ.plugins.notify('danger', '{{ translate('You need to agree with our policies') }}');
                $(el).prop('disabled', false);
            }
        }

        function toggleManualPaymentData(id) {
            if (typeof id != 'undefined') {
                $('#manual_payment_description').parent().removeClass('d-none');
                $('#manual_payment_description').html($('#manual_payment_info_' + id).html());
            }
        }
        // coupon apply
        $(document).on("click", "#coupon-apply", function() {
            @if (Auth::check())
                @if(Auth::user()->user_type != 'customer')
                    AIZ.plugins.notify('warning', "{{ translate('Please Login as a customer to apply coupon code.') }}");
                    return false;
                @endif

                var data = new FormData($('#apply-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.apply_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        AIZ.plugins.notify(data.response_message.response, data.response_message.message);
                        $("#cart_summary").html(data.html);
                    }
                });
            @else
                $('#login_modal').modal('show');
            @endif
        });

        // coupon remove
        $(document).on("click", "#coupon-remove", function() {
            @if (Auth::check() && Auth::user()->user_type == 'customer')
                var data = new FormData($('#remove-coupon-form')[0]);
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    method: "POST",
                    url: "{{ route('checkout.remove_coupon_code') }}",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data, textStatus, jqXHR) {
                        $("#cart_summary").html(data);
                    }
                });
            @endif
        });

        function updateDeliveryAddress(id, city_id = 0, area_id=0) {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryAddress') }}', {
                _token: AIZ.data.csrf,
                address_id: id,
                city_id: city_id,
                area_id: area_id
            }, function(data) {
                $('#delivery_info').html(data.delivery_info);
                $('#cart_summary').html(data.cart_summary);
                $('.aiz-refresh').removeClass('active');
                carrierCount = data.carrier_count;
                checkCarrerShippingInfo();
            });
           
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function updateBillingAddress(id) {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateBillingAddress') }}', {
                _token: AIZ.data.csrf,
                billing_address_id: id
            }, function(data) {
                $('.aiz-refresh').removeClass('active');
            });
        }

        function stepCompletionShippingInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            @if (Auth::check())
                var length = $('input[name="address_id"]:checked').length;
                if (length > 0) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            @else
                var count = 0;
                var length = $('#shipping_info [required]').length;
                $('#shipping_info [required]').each(function (i, el) {
                    if ($(el).val() != '' && $(el).val() != undefined && $(el).val() != null) {
                        count += 1;
                    }
                });
                if (count == length) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            @endif

            $('#headingShippingInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        $('#shipping_info [required]').each(function (i, el) {
            $(el).change(function(){
                if ($(el).attr('name') == 'address_id') {
                    updateDeliveryAddress($(el).val());
                    setDefaultshippingAddress();
                    setBillingAddress();
                }
                @if (get_setting('shipping_type') == 'area_wise_shipping')
                    if ($(el).attr('name') == 'city_id') {
                        let country_id = $('select[name="country_id"]').length? $('select[name="country_id"]').val() : $('input[name="country_id"]').val();
                        let city_id = $(this).val();
                        updateDeliveryAddress(country_id, city_id);
                    }
                @endif
                if ($(el).attr('name') == 'billing_address_id') {
                    setBillingAddress(el);
                }
                
                
                stepCompletionShippingInfo();
            });
        });

        $('select[name="area_id"].guest-checkout').change(function () {
            let country_id = $('select[name="country_id"]').length
                ? $('select[name="country_id"]').val()
                : $('input[name="country_id"]').val();
            let city_id = $('select[name="city_id"]').val();
            let area_id = $(this).val();

            if (area_id) {
                updateDeliveryAddress(country_id, city_id, area_id);
            } else {
                updateDeliveryAddress(country_id, city_id);
            }

            stepCompletionShippingInfo();
        });

        function stepCompletionDeliveryInfo() {
            // Mode livraison GPS : autorisé si (<=20km position choisie) OU (>20km devis accepté).
            if (typeof gps_required !== 'undefined' && gps_required) {
                var gpsOk = (!gps_manual && gps_chosen) || (gps_quote_status === 'accepted');
                if (!gpsOk) {
                    $('#headingDeliveryInfo svg *').css('fill', '#9d9da6');
                    $("#submitOrderBtn").prop('disabled', true);
                    return false;
                }
            }
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            var content = $('#delivery_info [required]');
            if (content.length > 0) {
                var content_checked = $('#delivery_info [required]:checked');
                if (content_checked.length > 0) {
                    content_checked.each(function (i, el) {
                        allOk = false;
                        if($(el).val() == 'carrier'){
                            var owner = $(el).attr('data-owner');
                            if ($('input[name=carrier_id_'+owner+']:checked').length > 0) {
                                allOk = true;
                            }
                        }else if($(el).val() == 'pickup_point'){
                            var owner = $(el).attr('data-owner');
                            if ($('select[name="pickup_point_id_'+owner+'"]').val() != '') {
                                allOk = true;
                            }
                        }else{
                            allOk = true;
                        }

                        if(allOk == false) {
                            return false;
                        }
                    });

                    if (allOk) {
                        headColor = '#15a405';
                        btnDisable = false;
                    }
                }
            }else{
                allOk = true
                headColor = '#15a405';
                btnDisable = false;
            }

            $('#headingDeliveryInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        function updateDeliveryInfo(shipping_type, type_id, user_id, country_id = 0, city_id = 0) {
            @if (get_setting('shipping_type') == 'area_wise_shipping' || get_setting('shipping_type') == 'carrier_wise_shipping')
                country_id = $('select[name="country_id"]').val() != null ? $('select[name="country_id"]').val() : 0;
                city_id = $('select[name="city_id"]').val() != null ? $('select[name="city_id"]').val() : 0;
            @endif
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.updateDeliveryInfo') }}', {
                _token: AIZ.data.csrf,
                shipping_type: shipping_type,
                type_id: type_id,
                user_id: user_id,
                country_id: country_id,
                city_id: city_id
            }, function(data) {
                $('#cart_summary').html(data);
                checkCarrerShippingInfo();
                stepCompletionDeliveryInfo();
                $('.aiz-refresh').removeClass('active');
            });
            AIZ.plugins.bootstrapSelect("refresh");
        }

        function show_pickup_point(el, user_id) {
        	var type = $(el).val();
        	var target = $(el).data('target');
            var type_id = null;

        	if(type == 'home_delivery' || type == 'carrier'){
                if(!$(target).hasClass('d-none')){
                    $(target).addClass('d-none');
                }
                $('.carrier_id_'+user_id).removeClass('d-none');
        	}else{
        		$(target).removeClass('d-none');
        		$('.carrier_id_'+user_id).addClass('d-none');
        	}

            if(type == 'carrier'){
                type_id = $('input[name=carrier_id_'+user_id+']:checked').val();
            }else if(type == 'pickup_point'){
                type_id = $('select[name=pickup_point_id_'+user_id+']').val();
            }
            updateDeliveryInfo(type, type_id, user_id);
        }

        function stepCompletionPaymentInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var payment = false;
            var agree = false;
            var allOk = false;
            var length = $('input[name="payment_option"]:checked').length;
            if(length > 0){
                if ($('input[name="payment_option"]:checked').hasClass('offline_payment_option')) {
                    if ($('#trx_id').val() != '' && $('#trx_id').val() != undefined && $('#trx_id').val() != null) {
                        payment = true;
                    }
                } else {
                    payment = true;
                }

                if ($('#agree_checkbox').is(":checked")){
                    agree = true;
                }

                if (payment && agree) {
                    headColor = '#15a405';
                    btnDisable = false;
                    allOk = true;
                }
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        function stepCompletionWalletPaymentInfo() {
            var headColor = '#9d9da6';
            var btnDisable = true;
            var allOk = false;
            if ($('#agree_checkbox').is(":checked")){
                headColor = '#15a405';
                btnDisable = false;
                allOk = true;
            }

            $('#headingPaymentInfo svg *').css('fill', headColor);
            $("#submitOrderBtn").prop('disabled', btnDisable);
            return allOk;
        }

        $('input[name="payment_option"]').change(function(){
            stepCompletionPaymentInfo();
        });

        function checkCarrerShippingInfo(){
           const shippingType = @json(get_setting('shipping_type'));
            const isDisabled = carrierCount === 0;
            let carrierSelected = false;
            let pickupSelected = false;
            $('.shipping-type-radio').each(function () {
                if ($(this).is(':checked') && $(this).val() === 'carrier') {
                    carrierSelected = true;
                }
            });
            $('.shipping-type-radio').each(function () {
                if ($(this).is(':checked') && $(this).val() === 'pickup_point') {
                    pickupSelected = true;
                }
            });
                if(shippingType == 'carrier_wise_shipping' && carrierSelected){
                    if (carrierCount === 0) {
                        if( (carrierSelected && pickupSelected) || (carrierSelected && !pickupSelected) ){
                            $('#submitOrderBtn').prop('disabled', true);
                            $('#agree_checkbox').prop('checked', false).prop('disabled', true);
                            $('.online_payment, .offline_payment_option').prop('checked', false).prop('disabled', true);
                        }
                    } else {
                        $('#agree_checkbox').prop('disabled', false);
                        $('.online_payment, .offline_payment_option').prop('disabled', false);
                    }
                }else{
                    $('#agree_checkbox').prop('disabled', false);
                    $('.online_payment, .offline_payment_option').prop('disabled', false);
                }
        }

        $(document).ready(function(){
            carrierCount = parseInt(document.getElementById('carrierCount')?.value || 0);
            checkCarrerShippingInfo();
            stepCompletionShippingInfo();
            stepCompletionDeliveryInfo();
            stepCompletionPaymentInfo();
            
        });

        function changeShippingAddress(){
            $('#choose-address-modal').modal('hide');
        }

        function setDefaultshippingAddress() {
            let checkedAddress = $('input[name="address_id"]:checked');

            if (checkedAddress.length) {

                let selectedText = checkedAddress.closest('label').find('.address-text').html();
                $('#choose-default').html(selectedText);
                $('#default-address-change-btn').attr('onclick', "edit_address('" + checkedAddress.val() + "')");
                $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                let $box = $('#default-address-box');
                if ($box.length) {
                    $box.removeClass('border-danger');
                    checkedAddress.prop('checked', true);
                    checkedAddress.prop('disabled', false);
                    $box.find('#hide-no-longer-div').remove();
                    
                }
            }
        }

        function setBillingAddress(el) {
            let type = $(el).data('type');
            let checkedAddress = $(el);
           if(type === 'billing'){
                let checkedAddress = $('input[name="billing_address_id"]:checked');
                if (checkedAddress.length) {

                    let selectedText = checkedAddress.closest('label').find('.address-text').html();
                    $('#choose-default-billing').html(selectedText);
                    $('#default-address-change-btn').attr('onclick', "edit_billing_address('" + checkedAddress.val() + "')");
                    $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                    let $box = $('#default-billing-address-box');
                    if ($box.length) {
                        $box.removeClass('border-danger');
                        checkedAddress.prop('checked', true);
                        checkedAddress.prop('disabled', false);
                        $box.find('#hide-no-valid-div').remove();
                        
                    }
                }
            } else{
                let checkedAddress = $('input[name="address_id"]:checked');
                if (checkedAddress.length) {
                    let selectedText = checkedAddress.closest('label').find('.address-text').html();
                    $('#choose-default-billing').html(selectedText);
                    $('input[name="billing_address_id"]').first().val(checkedAddress.val());
                }
            }
            updateBillingAddress(checkedAddress.val());
        }


    </script>

    @include('frontend.partials.address.address_js')

    @if(get_active_countries()->count() == 1)
    <script>
        $(document).ready(function() {
            @if(get_setting('has_state') == 1)
                get_states(@json(get_active_countries()[0]->id));
                @if(get_setting('billing_address_required') == 1)
                  get_billing_states(@json(get_active_countries()[0]->id));
                @endif
            @else
                get_city_by_country(@json(get_active_countries()[0]->id));
                @if(get_setting('billing_address_required') == 1)
                  get_billing_city_by_country(@json(get_active_countries()[0]->id));
                @endif
            @endif
        });
         @if(get_setting('shipping_type') == 'carrier_wise_shipping' && !Auth::check() )
            updateDeliveryAddress({{ get_active_countries()[0]->id }});
         @endif
    </script>
    @endif

    @if (get_setting('google_map') == 1)
        @include('frontend.partials.google_map')
    @endif

    {{-- Sélection de la position de livraison (GPS / carte OSM) — checkout web --}}
    @if (get_setting('shipping_type') == 'gps_distance_shipping')
    @php
        // Devis GPS en cours pour ce client (permet de reprendre où il en était au retour).
        $activeGpsQuote = null;
        if (auth()->check()) {
            $activeGpsQuote = \App\Models\GpsQuoteRequest::where('user_id', auth()->id())
                ->whereIn('status', ['pending', 'confirmed', 'accepted'])
                ->latest()->first();
        }
    @endphp
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script type="text/javascript">
        var gps_required = true;
        var gps_chosen   = {{ (session('checkout_delivery_lat') !== null) ? 'true' : 'false' }};
        // Devis GPS (zone >20km) — repris depuis un éventuel devis actif
        var gps_quote_status = '{{ $activeGpsQuote->status ?? 'none' }}';
        var gps_quote_id     = {{ $activeGpsQuote->id ?? 'null' }};
        var gps_quote_amount = {{ $activeGpsQuote ? (float) $activeGpsQuote->total_amount : 0 }};
        var gps_last_distance = {{ $activeGpsQuote ? (float) $activeGpsQuote->distance_km : 0 }};
        var gps_manual       = ({{ $activeGpsQuote ? 'true' : 'false' }});
        var _deliveryMap = null;
        var _deliveryMarker = null;
        var _pickedLat = {{ session('checkout_delivery_lat') !== null ? (float) session('checkout_delivery_lat') : 'null' }};
        var _pickedLng = {{ session('checkout_delivery_lng') !== null ? (float) session('checkout_delivery_lng') : 'null' }};
        var _defaultLat = {{ (float) get_setting('delivery_pickup_latitude', '12.3714') }};
        var _defaultLng = {{ (float) get_setting('delivery_pickup_longitude', '-1.5197') }};

        // Option 1 : position actuelle via le navigateur
        function useCurrentLocation() {
            if (!navigator.geolocation) {
                AIZ.plugins.notify('danger', '{{ translate('Geolocation is not supported by your browser.') }}');
                return;
            }
            $('.aiz-refresh').addClass('active');
            navigator.geolocation.getCurrentPosition(function(pos) {
                setDeliveryLocation(pos.coords.latitude, pos.coords.longitude);
            }, function() {
                $('.aiz-refresh').removeClass('active');
                AIZ.plugins.notify('danger', '{{ translate('Unable to get your position. Please allow location access or choose on the map.') }}');
            }, { enableHighAccuracy: true, timeout: 10000 });
        }

        // Option 2 : choisir un point sur la carte OpenStreetMap
        function openDeliveryMap() {
            $('#delivery_map_modal').modal('show');
            setTimeout(function() {
                var startLat = _pickedLat !== null ? _pickedLat : _defaultLat;
                var startLng = _pickedLng !== null ? _pickedLng : _defaultLng;
                if (_deliveryMap === null) {
                    _deliveryMap = L.map('delivery_map').setView([startLat, startLng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(_deliveryMap);
                    _deliveryMap.on('click', function(e) {
                        placeMapMarker(e.latlng.lat, e.latlng.lng);
                    });
                }
                _deliveryMap.invalidateSize();
                _deliveryMap.setView([startLat, startLng], 13);
                if (_pickedLat !== null) { placeMapMarker(_pickedLat, _pickedLng); }
            }, 300);
        }

        function placeMapMarker(lat, lng) {
            _pickedLat = lat;
            _pickedLng = lng;
            if (_deliveryMarker === null) {
                _deliveryMarker = L.marker([lat, lng]).addTo(_deliveryMap);
            } else {
                _deliveryMarker.setLatLng([lat, lng]);
            }
        }

        // Paramètres Nominatim filtrés Burkina Faso (comme l'app mobile)
        function _nominatimParams(q, limit) {
            return {
                q: q,
                format: 'json',
                limit: limit,
                countrycodes: 'bf',
                viewbox: '-5.5,15.1,2.4,9.4',
                bounded: 1,
                addressdetails: 1
            };
        }

        // Suggestions en temps réel pendant la frappe (anti-rebond 600 ms, dès 3 caractères)
        var _searchDebounce = null;
        $(document).on('input', '#delivery_map_search', function() {
            var q = $(this).val();
            if (_searchDebounce) { clearTimeout(_searchDebounce); }
            if (!q || q.trim().length < 3) {
                $('#delivery_map_suggestions').html('').hide();
                return;
            }
            _searchDebounce = setTimeout(function() { fetchDeliverySuggestions(q.trim()); }, 600);
        });

        function fetchDeliverySuggestions(q) {
            $.ajax({
                url: 'https://nominatim.openstreetmap.org/search',
                data: _nominatimParams(q, 5),
                dataType: 'json',
                success: function(res) {
                    var box = $('#delivery_map_suggestions');
                    box.html('');
                    if (res && res.length > 0) {
                        res.forEach(function(item) {
                            var lat = parseFloat(item.lat);
                            var lng = parseFloat(item.lon);
                            var name = item.display_name;
                            var a = $('<a href="javascript:void(0)" class="list-group-item list-group-item-action fs-13 py-2"></a>').text(name);
                            a.on('click', function() {
                                $('#delivery_map_search').val(name);
                                box.html('').hide();
                                if (_deliveryMap) {
                                    _deliveryMap.setView([lat, lng], 15);
                                    placeMapMarker(lat, lng);
                                }
                            });
                            box.append(a);
                        });
                        box.show();
                    } else {
                        box.hide();
                    }
                },
                error: function() { $('#delivery_map_suggestions').hide(); }
            });
        }

        // Recherche au clic sur le bouton (Burkina Faso uniquement)
        function searchDeliveryLocation() {
            var q = $('#delivery_map_search').val();
            if (!q || q.trim() === '') return;
            $('.aiz-refresh').addClass('active');
            $.ajax({
                url: 'https://nominatim.openstreetmap.org/search',
                data: _nominatimParams(q.trim(), 1),
                dataType: 'json',
                success: function(res) {
                    $('.aiz-refresh').removeClass('active');
                    $('#delivery_map_suggestions').html('').hide();
                    if (res && res.length > 0 && _deliveryMap) {
                        var lat = parseFloat(res[0].lat);
                        var lng = parseFloat(res[0].lon);
                        _deliveryMap.setView([lat, lng], 15);
                        placeMapMarker(lat, lng);
                    } else {
                        AIZ.plugins.notify('warning', '{{ translate('No result found for this search.') }}');
                    }
                },
                error: function() {
                    $('.aiz-refresh').removeClass('active');
                    AIZ.plugins.notify('danger', '{{ translate('Search failed. Please try again.') }}');
                }
            });
        }

        function confirmMapLocation() {
            if (_pickedLat === null || _pickedLng === null) {
                AIZ.plugins.notify('danger', '{{ translate('Please tap on the map to choose your location.') }}');
                return;
            }
            $('#delivery_map_modal').modal('hide');
            setDeliveryLocation(_pickedLat, _pickedLng);
        }

        // Envoie la position au serveur, recalcule les frais et rafraîchit le récapitulatif
        function setDeliveryLocation(lat, lng) {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.set_delivery_location') }}', {
                _token: AIZ.data.csrf,
                delivery_lat: lat,
                delivery_lng: lng
            }, function(data) {
                $('#cart_summary').html(data.cart_summary);
                _pickedLat = lat;
                _pickedLng = lng;
                gps_last_distance = data.distance_km;
                $('#headingDeliveryLocation svg *').css('fill', '#15a405');
                $('#deliveryLocationCard').removeClass('delivery-location-error');
                $('#gps_location_status').html('<i class="las la-check-circle text-success"></i> ' +
                    '{{ translate('Location set') }} (' + Number(lat).toFixed(5) + ', ' + Number(lng).toFixed(5) + ')');

                if (data.manual_review === true) {
                    // Zone > 20 km : un devis est nécessaire, la commande reste bloquée.
                    // Tout changement de position invalide le devis précédent → on repart à zéro.
                    gps_manual = true;
                    gps_chosen = false;
                    gps_quote_status = 'none';
                    gps_quote_id = null;
                    checkExistingQuote();
                } else {
                    // Zone <= 20 km : frais auto, commande autorisée.
                    gps_manual = false;
                    gps_chosen = true;
                    gps_quote_status = 'none';
                    gps_quote_id = null;
                    $('#gps_quote_box').hide().html('');
                }
                renderQuoteBox();
                stepCompletionDeliveryInfo();
                $('.aiz-refresh').removeClass('active');
            }).fail(function() {
                $('.aiz-refresh').removeClass('active');
                AIZ.plugins.notify('danger', '{{ translate('Could not save the location. Please try again.') }}');
            });
        }

        // ── Devis GPS (>20 km) ─────────────────────────────────────────────
        function format_amount(v) {
            return Number(v).toLocaleString('fr-FR') + ' FCFA';
        }

        function checkExistingQuote() {
            $.get('{{ route('checkout.gps_quote.status') }}', function(resp) {
                if (resp && resp.status && ['pending','confirmed','accepted'].indexOf(resp.status) !== -1) {
                    gps_quote_id     = resp.quote_id;
                    gps_quote_status = resp.status;
                    gps_quote_amount = resp.amount || 0;
                }
                renderQuoteBox();
                stepCompletionDeliveryInfo();
            });
        }

        function renderQuoteBox() {
            var box = $('#gps_quote_box');
            if (!gps_manual) { box.hide().html(''); return; }
            var html = '';
            if (gps_quote_status === 'none') {
                html = '<div class="alert alert-warning mb-2">{{ translate('Remote area') }} (' + Number(gps_last_distance).toFixed(1) + ' km). {{ translate('Shipping cost requires a quote.') }}</div>' +
                    '<button type="button" class="btn btn-primary btn-block fw-600" onclick="submitQuote()">{{ translate('Request a quote') }}</button>';
            } else if (gps_quote_status === 'pending') {
                html = '<div class="alert alert-info mb-2">{{ translate('Quote requested — waiting for validation.') }}</div>' +
                    '<button type="button" class="btn btn-soft-primary btn-block fw-600" onclick="refreshQuoteStatus()"><i class="las la-sync"></i> {{ translate('Refresh') }}</button>';
            } else if (gps_quote_status === 'confirmed') {
                html = '<div class="alert alert-success mb-2">{{ translate('Proposed shipping cost') }} : <strong>' + format_amount(gps_quote_amount) + '</strong></div>' +
                    '<div class="row gutters-5"><div class="col-6"><button type="button" class="btn btn-primary btn-block fw-600" onclick="acceptQuote()">{{ translate('Accept') }}</button></div>' +
                    '<div class="col-6"><button type="button" class="btn btn-light btn-block fw-600" onclick="refuseQuote()">{{ translate('Refuse') }}</button></div></div>';
            } else if (gps_quote_status === 'accepted') {
                html = '<div class="alert alert-success mb-0"><i class="las la-check-circle"></i> {{ translate('Quote accepted') }}' + (gps_quote_amount ? ' (' + format_amount(gps_quote_amount) + ')' : '') + '</div>';
            }
            box.html(html).show();
        }

        function submitQuote() {
            $('.aiz-refresh').addClass('active');
            $.post('{{ route('checkout.gps_quote.submit') }}', {
                _token: AIZ.data.csrf,
                delivery_lat: _pickedLat,
                delivery_lng: _pickedLng,
                distance_km: gps_last_distance
            }, function(resp) {
                $('.aiz-refresh').removeClass('active');
                if (resp && resp.result) {
                    gps_quote_id = resp.quote_id;
                    gps_quote_status = 'pending';
                    renderQuoteBox();
                    AIZ.plugins.notify('success', '{{ translate('Votre demande de devis a été soumise.') }}');
                }
            }).fail(function() {
                $('.aiz-refresh').removeClass('active');
                AIZ.plugins.notify('danger', '{{ translate('Could not submit the quote. Please try again.') }}');
            });
        }

        function refreshQuoteStatus() {
            $('.aiz-refresh').addClass('active');
            $.get('{{ route('checkout.gps_quote.status') }}', function(resp) {
                $('.aiz-refresh').removeClass('active');
                if (!resp) return;
                if (resp.status === 'confirmed') {
                    gps_quote_id = resp.quote_id;
                    gps_quote_amount = resp.amount || 0;
                    gps_quote_status = 'confirmed';
                } else if (resp.status === 'pending') {
                    gps_quote_status = 'pending';
                    AIZ.plugins.notify('info', '{{ translate('Quote still pending.') }}');
                } else {
                    gps_quote_status = 'none';
                    gps_quote_id = null;
                }
                renderQuoteBox();
                stepCompletionDeliveryInfo();
            }).fail(function() { $('.aiz-refresh').removeClass('active'); });
        }

        function acceptQuote() {
            if (!gps_quote_id) return;
            $('.aiz-refresh').addClass('active');
            $.post('{{ url('checkout/gps-quote/accept') }}/' + gps_quote_id, {
                _token: AIZ.data.csrf
            }, function(resp) {
                if (resp && resp.result) {
                    gps_quote_status = 'accepted';
                    gps_quote_amount = resp.amount || gps_quote_amount;
                    $.get('{{ route('checkout.cart_summary') }}', function(html) {
                        $('#cart_summary').html(html);
                        $('.aiz-refresh').removeClass('active');
                        renderQuoteBox();
                        stepCompletionDeliveryInfo();
                    });
                } else {
                    $('.aiz-refresh').removeClass('active');
                }
            }).fail(function() {
                $('.aiz-refresh').removeClass('active');
                AIZ.plugins.notify('danger', '{{ translate('Could not accept the quote. Please try again.') }}');
            });
        }

        function refuseQuote() {
            if (!gps_quote_id) return;
            $('.aiz-refresh').addClass('active');
            $.post('{{ url('checkout/gps-quote/refuse') }}/' + gps_quote_id, {
                _token: AIZ.data.csrf
            }, function(resp) {
                $('.aiz-refresh').removeClass('active');
                gps_quote_status = 'none';
                gps_quote_id = null;
                renderQuoteBox();
                stepCompletionDeliveryInfo();
            }).fail(function() { $('.aiz-refresh').removeClass('active'); });
        }

        $(document).ready(function() {
            // Position restaurée (depuis un devis en cours) ou déjà choisie → repère + icône verte
            if (_pickedLat !== null && _pickedLng !== null) {
                $('#headingDeliveryLocation svg *').css('fill', '#15a405');
                $('#gps_location_status').html('<i class="las la-check-circle text-success"></i> ' +
                    '{{ translate('Location set') }} (' + Number(_pickedLat).toFixed(5) + ', ' + Number(_pickedLng).toFixed(5) + ')');
            }
            if (gps_quote_status !== 'none') {
                gps_manual = true;   // un devis est en cours → zone lointaine
            }
            renderQuoteBox();
            stepCompletionDeliveryInfo();
        });
    </script>
    @endif

@endsection
