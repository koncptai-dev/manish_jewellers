@extends('layouts.back-end.app')



@section('title', 'Installment Payment')



@section('content')

<div class="content container-fluid">



    <div class="mb-3">

        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">

            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">

            Users Installments List



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
                                        placeholder="{{ translate('search_by_user_name') }}"
                                        aria-label="Search orders"
                                        value="{{ request('searchValue') }}">
                                    <button type="submit" class="btn btn--primary">{{ translate('search') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">

                    <table id="datatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">

                        <thead class="thead-light thead-50 text-capitalize">

                            <tr>

                                <th>#</th>

                                <th>User Name</th>

                                <th>Plan Code</th>

                                <th>Plan Category</th>

                                <th>Total Invested Amount</th>

                            </tr>

                        </thead>

                        <tbody>

                            
                            @forelse($installments as $installment)

                            <tr>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $installment->user->name }}</td>

                                <td>{{ $installment->plan_code }}</td>

                                <td>{{ $installment->plan_category }}</td>

                                <td>₹ {{ $installment->plan_amount }}</td>

                            </tr>

                            @empty

                            <tr>

                                <td colspan="9">No installment found.</td>

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

            </div>

        </div>

    </div>

</div>



<script>

    document.addEventListener('DOMContentLoaded', function() {

        document.querySelectorAll('.accept-payment').forEach(button => {

            button.addEventListener('click', function() {

                let id = this.getAttribute('data-id');

                if (confirm('Are you sure you want to approve this payment?')) {

                    fetch(`/admin/payment-request/approve/${id}`, {

                            method: 'POST',

                            headers: {

                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

                                'Content-Type': 'application/json'

                            },

                            body: JSON.stringify({})

                        })

                        .then(response => response.json())

                        .then(data => {

                            alert(data.message);

                            location.reload();

                        })

                        .catch(error => console.error('Error:', error));

                }

            });

        });

    });

</script>

@endsection