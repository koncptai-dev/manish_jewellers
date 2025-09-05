@extends('layouts.back-end.app')

@section('title', 'Transaction List')

@section('content')

<div class="content container-fluid">

    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
            Users Transaction List
            <span class="badge badge-soft-dark radius-50 fz-14 ml-1">{{ $transactions->total() }}</span>
        </h2>
    </div>

    <div class="row mt-20">
        <div class="col-md-12">
            <div class="card">
                <div class="px-3 py-4">
                    <div class="row align-items-center mb-3">
                        <div class="col-lg-10">
                            {{-- Search & Filter --}}
                            <form action="{{ url()->current() }}" method="GET" class="row g-2">
                                {{-- Text Search --}}
                                <div class="col-md-3">
                                    <div class="input-group input-group-custom input-group-merge">
                                        <div class="input-group-prepend">
                                            <div class="input-group-text">
                                                <i class="tio-search"></i>
                                            </div>
                                        </div>
                                        <input type="search" name="searchValue" class="form-control"
                                            placeholder="{{ translate('Search by user name or transaction ref') }}"
                                            value="{{ request('searchValue') }}">
                                    </div>
                                </div>

                                {{-- Date Filter --}}
                                <div class="col-md-3">
                                    <input type="date" name="date" class="form-control"
                                        value="{{ request('date') }}">
                                </div>

                                {{-- Filter Button --}}
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn--primary w-100">
                                        {{ translate('Search') }}
                                    </button>
                                </div>

                                {{-- Reset Button --}}
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-secondary w-100" onclick="window.location='{{ url()->current() }}'">
                                        {{ translate('Reset') }}
                                    </button>
                                </div>

                            </form>
                        </div>

                        {{-- Export Button --}}
                        <div class="col-lg-2 text-lg-end mt-2 mt-lg-0">
                            <form action="{{ route('admin.export.csv') }}" method="GET">
                                <input type="hidden" name="searchValue" value="{{ request('searchValue') }}">
                                <input type="hidden" name="date" value="{{ request('date') }}">
                                <button type="submit" class="btn btn--primary">
                                    Export to CSV
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable"
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">

                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                <th>Plan Code</th>
                                <th>Plan Category</th>
                                <th>Payment ID</th>
                                <th>Paid Amount</th>
                                <th>Plan Status</th>
                                <th>Transaction Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($transactions as $transaction)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ optional(optional($transaction->installmentPayment)->user)->name }}</td>
                                    <td>{{ optional($transaction->installmentPayment)->plan_code }}</td>
                                    <td>{{ optional($transaction->installmentPayment)->plan_category }}</td>
                                    <td>{{ $transaction->transaction_ref }}</td>
                                    <td>₹ {{ $transaction->monthly_payment }}</td>
                                    <td>
                                        @if ($transaction->payment_status == 'paid')
                                            <span class="badge badge-success">Paid</span>
                                        @elseif ($transaction->payment_status == 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @else
                                            <span class="badge badge-danger"
                                                data-toggle="tooltip"
                                                data-placement="top"
                                                title="{{ $transaction->failure_reason ?? 'No reason provided' }}">
                                                {{ ucfirst($transaction->payment_status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $transaction->created_at->format('d-m-Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">{{ translate('No transactions found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                </div>

                {{-- Pagination --}}
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $transactions->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
