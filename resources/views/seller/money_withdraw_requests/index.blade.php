@extends('seller.layouts.app')

@section('panel_content')

@php
    $hasPendingRequest = \App\Models\SellerWithdrawRequest::where('user_id', Auth::user()->id)
        ->where('status', 0)
        ->exists();
@endphp

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

    <div class="aiz-titlebar mt-2 mb-4">
      <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Money Withdraw') }}</h1>
        </div>
      </div>
    </div>

    <div class="row gutters-10">
        <div class="col-md-4 mb-3 ml-auto" >
            <div class="bg-grad-1 text-white rounded-lg overflow-hidden">
              <span class="size-30px rounded-circle mx-auto bg-soft-primary d-flex align-items-center justify-content-center mt-3">
                  <i class="las la-dollar-sign la-2x text-white"></i>
              </span>
              <div class="px-3 pt-3 pb-3">
                  <div class="h4 fw-700 text-center">{{ single_price(Auth::user()->shop->admin_to_pay) }}</div>
                  <div class="opacity-50 text-center">{{ translate('Pending Balance') }}</div>
              </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mr-auto" >
          @if($hasPendingRequest)
              <div class="p-3 rounded mb-3 text-center bg-warning shadow-sm">
                  <span class="size-60px rounded-circle mx-auto bg-white d-flex align-items-center justify-content-center mb-3">
                      <i class="las la-clock la-3x text-warning"></i>
                  </span>
                  <div class="fs-18 text-white fw-700">{{ translate('You already have a pending withdraw request') }}</div>
              </div>
          @else
              <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition" onclick="show_request_modal()">
                  <span class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                      <i class="las la-plus la-3x text-white"></i>
                  </span>
                  <div class="fs-18 text-primary">{{ translate('Send Withdraw Request') }}</div>
              </div>
          @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Withdraw Request History')}}</h5>
        </div>
          <div class="card-body">
              <table class="table aiz-table mb-0">
                  <thead>
                      <tr>
                          <th>#</th>
                          <th>{{ translate('Date') }}</th>
                          <th>{{ translate('Amount')}}</th>
                          <th data-breakpoints="lg">{{ translate('Payment Method')}}</th>
                          <th data-breakpoints="lg">{{ translate('Status')}}</th>
                          <th data-breakpoints="lg">{{ translate('Message')}}</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach ($seller_withdraw_requests as $key => $seller_withdraw_request)
                          <tr>
                              <td>{{ ($key+1) + ($seller_withdraw_requests->currentPage() - 1) * $seller_withdraw_requests->perPage() }}</td>
                              <td>{{ date('d-m-Y', strtotime($seller_withdraw_request->created_at)) }}</td>
                              <td>{{ single_price($seller_withdraw_request->amount) }}</td>
                              <td>
                                  @if($seller_withdraw_request->payment_method)
                                      {{ translate(ucfirst(str_replace('_', ' ', $seller_withdraw_request->payment_method))) }}
                                      @if($seller_withdraw_request->account_number)
                                          <br><small class="text-muted">{{ $seller_withdraw_request->account_number }}</small>
                                      @endif
                                  @else
                                      —
                                  @endif
                              </td>
                              <td>
                                  @if ($seller_withdraw_request->status == 1)
                                      <span class="badge badge-inline badge-success">{{ translate('Paid')}}</span>
                                  @else
                                      <span class="badge badge-inline badge-info">{{ translate('Pending')}}</span>
                                  @endif
                              </td>
                              <td>{{ $seller_withdraw_request->message ?? '—' }}</td>
                          </tr>
                      @endforeach
                  </tbody>
              </table>
              <div class="aiz-pagination">
                  {{ $seller_withdraw_requests->links() }}
              </div>
          </div>
    </div>
@endsection

@section('modal')
    <div class="modal fade" id="request_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ translate('Send A Withdraw Request') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                @if (Auth::user()->shop->admin_to_pay > 5) 
                    <form class="" action="{{ route('seller.money_withdraw_request.store') }}" method="post">
                        @csrf
                        <div class="modal-body gry-bg px-3 pt-3">
                            <div class="row">
                                <div class="col-md-3">
                                    <label>{{ translate('Amount')}} <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-9">
                                    <input type="number" lang="en" class="form-control mb-3" name="amount" min="{{ get_setting('minimum_seller_amount_withdraw') }}" max="{{ Auth::user()->shop->admin_to_pay }}" placeholder="{{ translate('Amount') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <label>{{ translate('Payment Method')}} <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-9">
                                    <select name="payment_method" id="withdraw_payment_method" class="form-control mb-3" required onchange="toggle_account_number()">
                                        <option value="">{{ translate('Select Payment Method') }}</option>
                                        @foreach ($withdraw_methods as $method)
                                            <option value="{{ $method['value'] }}">{{ $method['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row" id="account_number_row" style="display:none;">
                                <div class="col-md-3">
                                    <label>{{ translate('Account Number')}} <span class="text-danger">*</span></label>
                                </div>
                                <div class="col-md-9">
                                    <input type="text" class="form-control mb-3" name="account_number" id="withdraw_account_number" placeholder="{{ translate('Phone / Account number') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <label>{{ translate('Message')}}</label>
                                </div>
                                <div class="col-md-9">
                                    <textarea name="message" rows="8" class="form-control mb-3"></textarea>
                                </div>
                            </div>
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-sm btn-primary">{{translate('Send')}}</button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="modal-body gry-bg px-3 pt-3">
                        <div class="p-5 heading-3">
                            {{ translate('You do not have enough balance to send withdraw request') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script type="text/javascript">
        function show_request_modal(){
            toggle_account_number();
            $('#request_modal').modal('show');
        }

        // Affiche le champ "Numéro de compte" sauf si le moyen choisi est Cash
        function toggle_account_number(){
            var method = document.getElementById('withdraw_payment_method');
            var row    = document.getElementById('account_number_row');
            var input  = document.getElementById('withdraw_account_number');
            if (!method || !row || !input) return;
            if (method.value === 'cash' || method.value === '') {
                row.style.display = 'none';
                input.required = false;
            } else {
                row.style.display = '';
                input.required = true;
            }
        }

    </script>
@endsection
