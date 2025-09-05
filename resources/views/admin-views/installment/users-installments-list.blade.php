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
                                        <input id="datatableSearch_" type="search" name="searchValue" class="form-control"
                                            placeholder="{{ translate('search_by_user_name') }}" aria-label="Search orders"
                                            value="{{ request('searchValue') }}">
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
                                    <th>Plan Code</th>
                                    <th>Plan Category</th>
                                    <th>Total Invested Amount</th>
                                    <th>Plan Status</th>
                                    <th>Withdrawn Request</th>
                                    <th>Action</th> {{-- Added Action Column --}}
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
                                        <td>
                                            @if ($installment->status == 1)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Canceled</span>
                                            @endif
                                        </td>
                                        <td>{{$installment->withdraw_request}}</td>
                                        <td>
                                            @if ($installment->status == 1)
                                            <button type="button" class="btn btn-warning btn-sm withdraw-btn"
                                                data-toggle="modal" data-target="#withdrawModal"
                                                data-user-id="{{ $installment->user->id }}"
                                                data-user-name="{{ $installment->user->name }}"
                                                data-plan-code="{{ $installment->plan_code }}"
                                                data-installment-id="{{ $installment->installment_id }}"
                                                data-plan-amount="{{ $installment->plan_amount }}"> 
                                                Withdraw<i class="tio-wallet-outlined nav-icon"></i>
                                            </button>
                                            @endif
                                            <button type="button" class="btn btn-info btn-sm view-details-btn ml-2"
                                                data-toggle="modal" data-target="#withdrawDetailsModal"
                                                data-installment-id="{{ $installment->installment_id }}"
                                                data-user-name="{{ $installment->user->name }}"
                                                data-plan-code="{{ $installment->plan_code }}"
                                                data-plan-amount="{{ $installment->plan_amount }}">
                                                <i class="tio-visible nav-icon"></i> 
                                            </button>
                                            @if ($installment->status == 1)
                                                <button type="button" class="btn btn-danger btn-sm cancel-plan-btn ml-2"
                                                    data-toggle="modal" data-target="#cancelPlanModal"
                                                    data-installment-id="{{ $installment->installment_id }}"
                                                    data-user-name="{{ $installment->user->name }}"
                                                    data-plan-code="{{ $installment->plan_code }}">
                                                    Cancel <i class="tio-remove-from-trash nav-icon"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6">No installment found.</td> {{-- Adjusted colspan --}}
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

    <div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog" aria-labelledby="withdrawModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawModalLabel">Withdraw Amount for <span id="modalUserName"></span>
                        (Plan: <span id="modalPlanCode"></span>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="withdrawForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="withdrawUserId" name="user_id">
                        <input type="hidden" id="withdrawPlanCode" name="plan_code">
                        <input type="hidden" id="installmentId" name="installment_id">
                        <input type="hidden" id="planAmountInput" name="plan_amount"> {{-- Added hidden input for
                        plan_amount --}}

                        <div class="form-group">
                            <label for="amount">Amount (₹)</label>
                            <input type="number" class="form-control" id="amount" name="amount" min="1"
                                step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Withdraw</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="withdrawDetailsModal" tabindex="-1" role="dialog"
        aria-labelledby="withdrawDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawDetailsModalLabel">Withdrawal Details for <span
                            id="detailsModalUserName"></span> (Plan: <span id="detailsModalPlanCode"></span>)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Total Invested Amount: ₹<span id="detailsModalPlanAmount">0.00</span></p>
                    <p>Total Withdrawn: ₹<span id="detailsModalTotalWithdrawn">0.00</span></p>
                    <p>Remaining Withdrawable: ₹<span id="detailsModalRemainingWithdrawable">0.00</span></p>
                    <hr>
                    <h5>Withdrawal History</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount (₹)</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="withdrawalHistoryTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Plan Confirmation Modal -->
    <div class="modal fade" id="cancelPlanModal" tabindex="-1" role="dialog" aria-labelledby="cancelPlanModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="cancelPlanModalLabel">Confirm Plan Cancellation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p>Are you sure you want to <strong class="text-danger">cancel this plan</strong> for:</p>
                    <ul class="mb-2">
                        <li><strong>User:</strong> <span id="cancelModalUserName"></span></li>
                        <li><strong>Plan Code:</strong> <span id="cancelModalPlanCode"></span></li>
                    </ul>
                    <p class="text-warning">This action cannot be undone.</p>
                    <input type="hidden" id="cancelInstallmentId">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">No, Keep Plan</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelPlanBtn">Yes, Cancel Plan</button>
                </div>
            </div>
        </div>
    </div>


    @push('script')
        {{-- Use @push and @endpush for scripts --}}
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Logic for the new "Withdraw" functionality
                const withdrawModal = $('#withdrawModal'); // Using jQuery for Bootstrap modal
                const withdrawUserIdInput = document.getElementById('withdrawUserId');
                const withdrawPlanCodeInput = document.getElementById('withdrawPlanCode');
                const installmentIdInput = document.getElementById('installmentId');
                const planAmountInput = document.getElementById('planAmountInput'); // Get the new input element
                const modalUserNameSpan = document.getElementById('modalUserName');
                const modalPlanCodeSpan = document.getElementById('modalPlanCode');
                const withdrawForm = document.getElementById('withdrawForm');

                document.querySelectorAll('.withdraw-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                        const userName = this.getAttribute('data-user-name');
                        const planCode = this.getAttribute('data-plan-code');
                        const installmentId = this.getAttribute('data-installment-id');
                        const planAmount = this.getAttribute('data-plan-amount'); // Get the plan amount

                        withdrawUserIdInput.value = userId;
                        withdrawPlanCodeInput.value = planCode;
                        modalUserNameSpan.textContent = userName;
                        modalPlanCodeSpan.textContent = planCode;
                        installmentIdInput.value = installmentId;
                        planAmountInput.value = planAmount; // Assign plan amount to the hidden input
                    });
                });

                withdrawForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const userId = withdrawUserIdInput.value;
                    const planCode = withdrawPlanCodeInput.value;
                    const installmentId = installmentIdInput.value;
                    const planAmount = planAmountInput.value; // Get plan amount from the hidden input
                    const amount = document.getElementById('amount').value;
                    const remarks = document.getElementById('remarks').value;

                    if (confirm(`Are you sure you want to withdraw ₹${amount} for this user?`)) {
                        fetch('/admin/withdraw-amount', { // This will be your new route
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                        .getAttribute('content'),
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    user_id: userId,
                                    plan_code: planCode,
                                    amount: amount,
                                    installment_id: installmentId,
                                    plan_amount: planAmount, // Include plan_amount in the AJAX request
                                    remarks: remarks
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert(data.message);
                                    withdrawModal.modal('hide'); // Hide the modal on success
                                    location.reload(); // Reload to reflect changes
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred during the withdrawal.');
                            });
                    }
                });
            });

            const withdrawDetailsModal = $('#withdrawDetailsModal');
            const detailsModalUserNameSpan = document.getElementById('detailsModalUserName');
            const detailsModalPlanCodeSpan = document.getElementById('detailsModalPlanCode');
            const detailsModalPlanAmountSpan = document.getElementById('detailsModalPlanAmount');
            const detailsModalTotalWithdrawnSpan = document.getElementById('detailsModalTotalWithdrawn');
            const detailsModalRemainingWithdrawableSpan = document.getElementById('detailsModalRemainingWithdrawable');
            const withdrawalHistoryTableBody = document.getElementById('withdrawalHistoryTableBody');

            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const installmentId = this.getAttribute('data-installment-id');
                    const userName = this.getAttribute('data-user-name');
                    const planCode = this.getAttribute('data-plan-code');
                    const planAmount = parseFloat(this.getAttribute('data-plan-amount'));

                    detailsModalUserNameSpan.textContent = userName;
                    detailsModalPlanCodeSpan.textContent = planCode;
                    detailsModalPlanAmountSpan.textContent = planAmount.toFixed(2);

                    // Fetch withdrawal history via AJAX
                    fetch(`{{ url('admin/installments/withdrawal-history') }}/${installmentId}`, { // Ensure this route exists and is correct
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            const fetchedPlanAmount = parseFloat(planAmount);
                            const fetchedTotalWithdrawn = parseFloat(data.total_withdrawn_amount_current);
                            const fetchedRemainingWithdrawable = fetchedPlanAmount - fetchedTotalWithdrawn;
                            detailsModalPlanAmountSpan.textContent = fetchedPlanAmount.toFixed(2);
                            detailsModalTotalWithdrawnSpan.textContent = fetchedTotalWithdrawn.toFixed(2);
                            detailsModalRemainingWithdrawableSpan.textContent = fetchedRemainingWithdrawable
                                .toFixed(2);

                            // Populate withdrawal history table
                            withdrawalHistoryTableBody.innerHTML = ''; // Clear previous data
                            if (data.history && data.history.length > 0) {
                                data.history.forEach(record => {
                                    const row = `<tr>
                                                <td>${new Date(record.created_at).toLocaleString()}</td>
                                                <td>₹ ${parseFloat(record.amount).toFixed(2)}</td>
                                                <td>${record.remarks || 'N/A'}</td>
                                            </tr>`;
                                    withdrawalHistoryTableBody.insertAdjacentHTML('beforeend', row);
                                });
                            } else {
                                withdrawalHistoryTableBody.innerHTML =
                                    `<tr><td colspan="3" class="text-center">No withdrawal history found.</td></tr>`;
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching withdrawal history:', error);
                            withdrawalHistoryTableBody.innerHTML =
                                `<tr><td colspan="3" class="text-center text-danger">Failed to load history.</td></tr>`;
                        });
                });
            });

            const cancelPlanModal = $('#cancelPlanModal');
            const cancelModalUserNameSpan = document.getElementById('cancelModalUserName');
            const cancelModalPlanCodeSpan = document.getElementById('cancelModalPlanCode');
            const cancelInstallmentIdInput = document.getElementById('cancelInstallmentId');
            const confirmCancelPlanBtn = document.getElementById('confirmCancelPlanBtn');
            // Open modal with data
            document.querySelectorAll('.cancel-plan-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const installmentId = this.getAttribute('data-installment-id');
                    const userName = this.getAttribute('data-user-name');
                    const planCode = this.getAttribute('data-plan-code');

                    cancelInstallmentIdInput.value = installmentId;
                    cancelModalUserNameSpan.textContent = userName;
                    cancelModalPlanCodeSpan.textContent = planCode;
                });
            });

            // Confirm cancel
            confirmCancelPlanBtn.addEventListener('click', function() {
                const installmentId = cancelInstallmentIdInput.value;

                fetch('/admin/installments/cancel-plan', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content'),
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            installment_id: installmentId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            cancelPlanModal.modal('hide');
                            location.reload(); // Reflect the update
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during plan cancellation.');
                    });
            });
        </script>
    @endpush

@endsection
