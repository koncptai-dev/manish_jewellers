@extends('layouts.back-end.app')

@section('title', 'Installment Payment Details')

@section('content')
<div class="content container-fluid">
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <h2 class="h1 mb-0 text-capitalize d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/inhouse-product-list.png') }}" alt="">
            Installment Payment Details
        </h2>
        <a href="{{ route('admin.payment-history') }}" class="btn btn-primary">
            ← Back to Payment History
        </a>
    </div>
    
    <div class="row mt-20">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Installment Plan Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        
                        <tr>
                            <th>User Name:</th>
                            <td>{{ $installment->user->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Plan Category:</th>
                            <td>{{ $installment->plan_category }}</td>
                        </tr>
                        <tr>
                            <th>No Of Months:</th>
                            <td>{{ $installment->no_of_months }}</td>
                        </tr>
                        <tr>
                            <th>Purchase Gold Weight:</th>
                            <td>{{ $installment->total_gold_purchase }}</td>
                        </tr>
                        <tr>
                            <th>Total Yearly Payment:</th>
                            <td>₹ {{ $installment->total_yearly_payment }}</td>
                        </tr>
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Installment Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount Paid</th>
                                    <td>Purchase Gold Weight</td>
                                    <th>Payment Date</th>
                                    <th>Payment Type</th>
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($installment->details as $detail)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>₹ {{ $detail->monthly_payment }}</td>
                                        <td>{{ $detail->purchase_gold_weight }}</td>
                                        <td>{{ $detail->created_at }}</td>
                                        <td>{{ $detail->payment_type }}</td>
                                        <td>{{ ucfirst($detail->payment_status) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No payment history found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
