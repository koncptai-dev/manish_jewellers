@extends('layouts.back-end.app')

@section('title', 'Installment Payment')

@section('content')
<div class="content container-fluid">

    <div class="mb-3">
        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
            Payment Request List

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
                                        placeholder="{{ translate('search_by_User_Name') }}"
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
                                <th>Plan Code</th>
                                <th>Plan Amount</th>
                                <th>Plan Category</th>
                                <th>Purchase Gold Weight</th>
                                <th>Total Yearly Payment</th>
                                <th>User Name</th>
                                <th>Total Installment Paid</th>
                                <th> Remark</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installments as $installment)
                            <tr>
                                <td>{{ $installment->id }}</td>
                                <td>{{ $installment->plan_code }}</td>
                                <td>₹ {{ $installment->plan_amount }}</td>
                                <td>{{ $installment->plan_category }}</td>
                                <td>{{ $installment->total_gold_purchase }}</td>
                                <td>₹ {{ $installment->total_yearly_payment }}</td>
                                <td>{{ $installment->user->name }}</td>
                                <td>{{ $installment->count() }}</td>
                                <td>
                                    @if($installment->remarks)
                                        <div style="
                                            max-height: 100px;
                                            overflow-y: auto;
                                            background: #f9f9ff;
                                            border-left: 4px solid #0d6efd;
                                            padding: 8px 12px;
                                            border-radius: 6px;
                                            font-size: 14px;
                                            line-height: 1.5;
                                            color: #333;
                                            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
                                        ">
                                            {!! nl2br(e($installment->remarks ?? '—')) !!}
                                        </div>
                                    @else
                                    —
                                    @endif
                                </td>
                                <td>
                                    @if($installment->status !== 'done')
                                    <button class="btn btn-success accept-payment" data-id="{{ $installment->id }}">
                                        Accept
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9">No installment payments found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Pagination Links --}}
                <div class="table-responsive mt-4">
                    <div class="px-4 d-flex justify-content-lg-end">
                        {{ $installments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Remark -->
<div class="modal fade" id="remarkModal" tabindex="-1" aria-labelledby="remarkModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="remarkModalLabel">Approve Payment</h5>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="paymentId">
        <div class="form-group">
            <label for="remarkInput">Remark</label>
            <textarea id="remarkInput" class="form-control" placeholder="Enter remark (optional)"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="submitRemark">Approve</button>
      </div>
    </div>
  </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let selectedPaymentId = null;

        // Open modal when clicking Accept
        document.querySelectorAll('.accept-payment').forEach(button => {
            button.addEventListener('click', function() {
                selectedPaymentId = this.getAttribute('data-id');
                document.getElementById('paymentId').value = selectedPaymentId;
                document.getElementById('remarkInput').value = ''; // clear old remark
                var remarkModal = new bootstrap.Modal(document.getElementById('remarkModal'));
                remarkModal.show();
            });
        });

        // Submit remark and approve
        document.getElementById('submitRemark').addEventListener('click', function() {
            let id = document.getElementById('paymentId').value;
            let remark = document.getElementById('remarkInput').value;

            fetch(`/admin/payment-request/approve/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ remark: remark })
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                location.reload();
            })
            .catch(error => console.error('Error:', error));
        });
    });
</script>
@endsection
