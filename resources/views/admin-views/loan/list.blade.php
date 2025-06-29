@extends('layouts.back-end.app')

@section('title', 'Installment Payment')

@section('content')
<div class="content container-fluid">

    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
            Installment Payment List

            <span class="badge badge-soft-dark radius-50 fz-14 ml-1">{{ $installments->total() }}</span>
        </h2>
    </div>
    <div class="row mt-20">
        <div class="col-md-12">
            <div class="card">
                <div class="px-3 py-4">
                    <div class="row align-items-center">
                        <div class="col-lg-4">
                            <form action="{{ url()->current() }}" method="GET">
                                <div class="input-group input-group-custom input-group-merge">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">
                                            <i class="tio-search"></i>
                                        </div>
                                    </div>
                                    <input id="datatableSearch_" type="search" name="searchValue"
                                        class="form-control"
                                        placeholder="{{ translate('search_by_Product_Name') }}"
                                        aria-label="Search orders"
                                        value="{{ request('searchValue') }}">
                                    <input type="hidden" value="{{ request('status') }}" name="status">
                                    <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                                </div>
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
                                <th>No Of Months</th>
                                
                                <th>Plan Category</th>
                                <th>{{ translate('Purchase Gold Weight') }}</th>
                                <th>{{ translate('Total Yearly Payment') }}</th>
                               
                                <th>Total Installment Paid</th>
                                <th>Bonus Amount</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installments as $installment)
                            <tr>
                                <td>{{ $installment->id }}</td>
                                <td>{{ $installment->user?->name ?? 'N/A' }}</td>
                                <td>{{ $installment->no_of_months }}</td>
                               
                                <td>{{ $installment->plan_category }}</td>
                                <td>{{ $installment->total_gold_purchase }}</td>
                                <td>₹ {{ $installment->total_yearly_payment }}</td>
                                
                                <td>{{ $installment->details->count() }}</td>
                                <td>₹ {{ $installment->credit_bonus }}</td>
                                <td>
                                    <a href="{{ route('installments.show', $installment->id) }}" class="btn btn-primary btn-sm">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">{{ translate('No installment payments found.') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $installments->links() }}
                    </div>
                </div>

                @if(count($installments) == 0)
                @include('layouts.back-end._empty-state',['text'=>'no_product_found'],['image'=>'default'])
                @endif
            </div>
        </div>
    </div>
</div>
<span id="message-select-word" data-text="{{ translate('select') }}"></span>
@endsection
