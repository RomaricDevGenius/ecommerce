@extends('frontend.layouts.app')

@section('content')
<section class="pt-5 mb-4">
    <div class="container">
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="row aiz-steps arrow-divider">
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-shopping-cart"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('1. My Cart') }}</h3></div></div>
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-map"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('2. Shipping info') }}</h3></div></div>
                    <div class="col done"><div class="text-center text-success"><i class="la-3x mb-2 las la-truck"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('3. Delivery info') }}</h3></div></div>
                    <div class="col active"><div class="text-center text-primary"><i class="la-3x mb-2 las la-credit-card"></i><h3 class="fs-14 fw-600 d-none d-lg-block">{{ translate('4. Payment') }}</h3></div></div>
                    <div class="col"><div class="text-center"><i class="la-3x mb-2 opacity-50 las la-check-circle"></i><h3 class="fs-14 fw-600 d-none d-lg-block opacity-50">{{ translate('5. Confirmation') }}</h3></div></div>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="mb-4">
    <div class="container text-left">
        <div class="row">
            <div class="col-lg-12">
                <form action="{{ route('orange.pay') }}" class="form-default" method="POST" id="orange-payment-form">
                    @csrf
                    <div class="card shadow-sm border-0 rounded">
                        <div class="card-header p-3">
                            <h3 class="fs-16 fw-600 mb-0">{{ translate('Pay by') }} Orange Money</h3>
                        </div>
                        <div class="card-body">
                            <p>{{ str_replace('{amount}', number_format($combined_order->grand_total, 0, '', ' '), translate('You owe {amount} FCFA, pay by Orange money by doing:')) }}</p>
                            <p class="font-weight-bold" style="color: red; font-size: 1.2em;">
                                @if (get_setting('orange_sandbox') == 1)
                                    *866*4*6*{{ (int) $combined_order->grand_total }}#
                                @else
                                    *144*4*6*{{ (int) $combined_order->grand_total }}#
                                @endif
                            </p>
                            <p style="color:red; font-size: 1.2em;">{{ translate("If you don't have an account you can go to an Orange money agent") }}</p>
                            <p class="font-weight-bold">{{ translate("You will receive an OTP by SMS. Enter it and the phone number below.") }}</p>
                            <div class="alert alert-block alert-danger d-none" id="error-msg"></div>
                            <div class="form-group">
                                <label>{{ translate('OTP code (received by SMS)') }}</label>
                                <input type="password" name="otp" id="otp" class="form-control" maxlength="6">
                            </div>
                            <div class="form-group">
                                <label>{{ translate("Phone number used for OTP (no spaces or dashes)") }}</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="70123456">
                            </div>
                            <button type="submit" class="btn btn-primary fw-600 d-inline-flex align-items-center justify-content-center" id="validate_button">
                                <span id="validate_button_spinner" class="spinner-border spinner-border-sm mr-2 d-none" role="status" aria-hidden="true"></span>
                                <span id="validate_button_text">{{ translate('Complete order') }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="row align-items-center pt-3">
                        <div class="col-6">
                            <a href="{{ route('home') }}" class="link link--style-3"><i class="las la-arrow-left"></i> {{ translate('Return to shop') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</section>
@endsection

@section('script')
<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(function() {
    function showLoading() {
        $('#validate_button').prop('disabled', true);
        $('#validate_button_spinner').removeClass('d-none');
        $('#validate_button_text').text("{{ translate('Processing...') }}");
    }

    function hideLoading() {
        $('#validate_button').prop('disabled', false);
        $('#validate_button_spinner').addClass('d-none');
        $('#validate_button_text').text("{{ translate('Complete order') }}");
    }

    $('#validate_button').prop('disabled', true);
    function validate_code() {
        var invalidOtp = "{{ translate('OTP is invalid') }}";
        var invalidPhone = "{{ translate('Phone number is not valid') }}";
        var otp = $('#otp').val();
        var phone = $('#phone_number').val();
        var error = null;
        if (!/^[0-9]{6}$/.test(otp)) error = invalidOtp;
        else if (!/^[0-9]{8,}$/.test(phone)) error = invalidPhone;
        else error = null;
        if (error) {
            $('#error-msg').text(error).removeClass('d-none');
            $('#validate_button').prop('disabled', true);
        } else {
            $('#error-msg').addClass('d-none');
            $('#validate_button').prop('disabled', false);
        }
    }
    $('#otp, #phone_number').on('keyup', validate_code);
    $('#orange-payment-form').on('submit', function(e) {
        e.preventDefault();
        showLoading();
        $.post('{{ route('orange.pay') }}', $(this).serialize())
            .done(function(data) {
                hideLoading();
                if (data.success) {
                    var p = data.payment || {};
                    Swal.fire({
                        icon: 'success',
                        title: "{{ translate('Payment successful') }}",
                        html:
                            '<p>{{ translate('Payment method') }} : <strong>' + (p.method || 'Orange Money') + '</strong></p>' +
                            '<p>{{ translate('Phone') }} : <strong>' + (p.phone || '') + '</strong></p>' +
                            '<p>{{ translate('Transaction ID') }} : <strong>' + (p.transaction_id || '') + '</strong></p>',
                        confirmButtonText: "{{ translate('Continue') }}"
                    }).then(function() {
                        window.location.href = data.url;
                    });
                } else {
                    Swal.fire({ title: 'Error', text: data.message, icon: 'error', confirmButtonText: 'OK' });
                }
            })
            .fail(function() {
                hideLoading();
                Swal.fire({ title: 'Error', text: "{{ translate('An unexpected error occurred') }}", icon: 'error', confirmButtonText: 'OK' });
            });
    });
});
</script>
@endsection
