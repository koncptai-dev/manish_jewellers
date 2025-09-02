@php use Illuminate\Support\Str; @endphp
@extends('layouts.back-end.app')

@section('title', translate('customer_List'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-4">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/customer.png')}}" alt="">
                {{translate('customer_list')}}
                <span class="badge badge-soft-dark radius-50">{{ $totalCustomers }}</span>
            </h2>
        </div>

        {{-- Filter Card --}}
        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ translate('Order_Date') }}</label>
                            <div class="position-relative">
                                <span class="tio-calendar icon-absolute-on-right"></span>
                                <input type="text" name="order_date"
                                       class="js-daterangepicker-with-range form-control cursor-pointer"
                                       value="{{request('order_date')}}" placeholder="{{ translate('Select_Date') }}"
                                       autocomplete="off" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{translate('Customer_Joining_Date')}}</label>
                            <div class="position-relative">
                                <span class="tio-calendar icon-absolute-on-right cursor-pointer"></span>
                                <input type="text" name="customer_joining_date"
                                       class="js-daterangepicker-with-range form-control cursor-pointer"
                                       value="{{request('customer_joining_date')}}" placeholder="{{ translate('Select_Date') }}"
                                       autocomplete="off" readonly>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{translate('Sort_By') }}</label>
                            <select class="form-control js-select2-custom" name="sort_by">
                                <option disabled {{ is_null(request('sort_by')) ? 'selected' : '' }}>
                                    {{ translate('Select_Customer_sorting_order') }}
                                </option>
                                <option value="order_amount">{{ translate('Sort_By_Order_Amount') }}</option>
                                <option value="asc" {{ request('sort_by') === 'asc' ? 'selected' : '' }}>
                                    {{translate('Sort_By_Oldest')}}
                                </option>
                                <option value="desc" {{ request('sort_by') === 'desc' ? 'selected' : '' }}>
                                    {{translate('Sort_By_Newest')}}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{translate('Choose_First')}}</label>
                            <input type="number" class="form-control" min="1"
                                   value="{{ request('choose_first') }}"
                                   placeholder="{{translate('Ex')}} : {{translate('100')}}" name="choose_first">
                        </div>
                        <div class="col-md-4">
                            <label class="d-md-block">&nbsp;</label>
                            <div class="btn--container justify-content-end">
                                <a href="{{ route('admin.customer.list') }}"
                                   class="btn btn-secondary px-5">
                                    {{ translate('reset') }}
                                </a>
                                <button type="submit" class="btn btn--primary">{{translate('Filter')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabs for Active / Inactive Customers --}}
        <div class="card">
            <div class="card-header gap-3 align-items-center">
                <h5 class="mb-0 mr-auto">
                    {{translate('Customer_list')}}
                    <span class="badge badge-soft-dark radius-50 fz-14 ml-1">{{ $totalCustomers }}</span>
                </h5>

                {{-- Search --}}
                <form action="{{ url()->current() }}" method="GET">
                    <input type="hidden" name="order_date" value="{{request('order_date')}}">
                    <input type="hidden" name="customer_joining_date" value="{{request('customer_joining_date')}}">
                    <input type="hidden" name="sort_by" value="{{request('sort_by')}}">
                    <input type="hidden" name="choose_first" value="{{request('choose_first')}}">
                    <div class="input-group input-group-merge input-group-custom">
                        <div class="input-group-prepend">
                            <div class="input-group-text"><i class="tio-search"></i></div>
                        </div>
                        <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                               placeholder="{{ translate('search_by_Name_or_Email_or_Phone')}}" aria-label="Search orders"
                               value="{{ request('searchValue') }}">
                        <button type="submit" class="btn btn--primary">{{ translate('search')}}</button>
                    </div>
                </form>

                {{-- Export --}}
                <div class="dropdown">
                    <a type="button" class="btn btn-outline--primary text-nowrap"
                       href="{{route('admin.customer.export', [
                            'sort_by' => request('sort_by'),
                            'choose_first' => request('choose_first'),
                            'order_date' => request('order_date'),
                            'customer_joining_date' => request('customer_joining_date'),
                            'searchValue' => request('searchValue')
                       ])}}">
                        <img width="14" src="{{dynamicAsset(path: 'public/assets/back-end/img/excel.png')}}" alt="" class="excel">
                        <span class="ps-2">{{ translate('export') }}</span>
                    </a>
                </div>
            </div>

            {{-- Nav Tabs --}}
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#active-customers" role="tab">
                        {{ translate('Active Customers') }}
                        <span class="badge badge-soft-success">{{ $activeCustomers->total() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#inactive-customers" role="tab">
                        {{ translate('Inactive Customers') }}
                        <span class="badge badge-soft-danger">{{ $inactiveCustomers->total() }}</span>
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content">
                {{-- Active Customers --}}
                <div class="tab-pane fade show active" id="active-customers" role="tabpanel">
                    @include('admin-views.customer.partials.customer_table', ['customers' => $activeCustomers])
                </div>

                {{-- Inactive Customers --}}
                <div class="tab-pane fade" id="inactive-customers" role="tabpanel">
                    @include('admin-views.customer.partials.customer_table', ['customers' => $inactiveCustomers])
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script type="text/javascript">
        changeInputTypeForDateRangePicker($('input[name="order_date"]'));
        changeInputTypeForDateRangePicker($('input[name="customer_joining_date"]'));
    </script>
@endpush
