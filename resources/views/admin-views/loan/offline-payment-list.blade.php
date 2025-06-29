@extends('layouts.back-end.app')

@section('title', 'Loan Installment Payment')

@section('content')
<div class="content container-fluid">

    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
            Loan Payment Request List

            <span class="badge badge-soft-dark radius-50 fz-14 ml-1">{{ $installments->total() }}</span>
        </h2>
    </div>

    <div class="row mt-20">
        <div class="col-md-12">
            <div class="card">
                <div class="table-responsive">
                    <table id="datatable" class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100 text-start">
                        <thead class="thead-light thead-50 text-capitalize">
                            <tr>
                                <th>#</th>
                                <th>User Name</th>
                                <th>Loan Amount</th>
                                <th>No. of Months</th>
                                <th>No. of EMIs</th>
                                <th>Request Date</th>
                                <th>Payment Collect Date</th>
                                <th>Request Type</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installments as $installment)
                            <tr>
                                <td>{{ $installment->id }}</td>
                                <td>{{ $installment->user->name ?? 'N/A' }}</td>
                                <td>₹ {{ number_format($installment->loan_amount, 2) }}</td>
                                <td>{{ $installment->no_of_months }}</td>
                                <td>{{ $installment->no_of_emi }}</td>
                                <td>{{ \Carbon\Carbon::parse($installment->request_date)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($installment->payment_collect_date)->format('d M Y') }}</td>
                                <td>{{ !empty($installment->request_type) ? ucfirst($installment->request_type) : 'EMI Request' }}</td>
                                <td>
                                    <span class="badge badge-{{ $installment->status === 'done' ? 'success' : 'warning' }}">
                                        {{ ucfirst($installment->status) }}
                                    </span>
                                </td>
                                <td>{{ $installment->remarks ?? '-' }}</td>
                                <td>
                                    @if($installment->status !== 'approved')
                                    <button class="btn btn-success accept-payment" data-id="{{ $installment->id }}">Accept</button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11">No installment payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div class="card-footer">
                    {!! $installments->links() !!}
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
                    fetch(`/admin/loan-requests/approve/${id}`, {
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
