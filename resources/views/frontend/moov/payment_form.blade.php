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
                <form action="{{ route('moov.pay') }}" class="form-default" role="form" method="POST" id="moov-payment-form">
                    @csrf
                    <div class="card shadow-sm border-0 rounded">
                        <div class="card-header p-3">
                            <h3 class="fs-16 fw-600 mb-0">{{ translate('Pay by') }} Moov Money</h3>
                        </div>
                        <div class="card-body">
                            <p>{{ str_replace('{amount}', number_format($combined_order->grand_total, 0, '', ' '), translate('You owe {amount} FCFA')) }}</p>
                            <p style="color:red; font-size: 1.2em;">{{ translate("(If you don't have an account you can go to an Moov money agent)") }}</p>
                            <p class="font-weight-bold">{{ translate("You will receive a message with instructions to complete the payment on your phone.") }}</p>
                            <div class="alert alert-block alert-danger d-none" id="error-msg"></div>
                            <div class="form-group">
                                <label>{{ translate("Your Moov money phone number (no spaces or dashes)") }}</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control" placeholder="70123456">
                            </div>
                            <button type="submit" class="btn btn-primary fw-600" id="validate_button">{{ translate('Initiate transaction') }}</button>
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
    $('#validate_button').prop('disabled', true);
    function validate_code() {
        var invalidPhone = "{{ translate('Phone number is not valid') }}";
        var phone = $('#phone_number').val();
        if (!/^[0-9]{8,}$/.test(phone)) {
            $('#error-msg').text(invalidPhone).removeClass('d-none');
            $('#validate_button').prop('disabled', true);
        } else {
            $('#error-msg').addClass('d-none');
            $('#validate_button').prop('disabled', false);
        }
    }
    $('#phone_number').on('keyup', validate_code);
    $('#moov-payment-form').on('submit', function(e) {
        e.preventDefault();
        HoldOn.open({ theme: "sk-circle", message: "<h4>{{ translate('Please wait, do not close or refresh the page') }}</h4>" });
        $.post('{{ route('moov.pay') }}', $(this).serialize())
            .done(function(data) {
                HoldOn.close();
                if (data.success) {
                    if (data.message) Swal.fire({ title: '{{ translate("Success") }}', text: data.message, icon: 'info', confirmButtonText: 'OK' }).then(function() { window.location.href = data.url; });
                    else window.location.href = data.url;
                } else Swal.fire({ title: '{{ translate("Error") }}', text: data.message, icon: 'error', confirmButtonText: 'OK' });
            })
            .fail(function() {
                HoldOn.close();
                Swal.fire({ title: '{{ translate("Error") }}', text: "{{ translate('An unexpected error occurred, please try again later') }}", icon: 'error', confirmButtonText: 'OK' });
            });
    });
});
</script>
@endsection
