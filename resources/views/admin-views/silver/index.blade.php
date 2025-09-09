@extends('layouts.back-end.app')

@section('content')
<div class="content container-fluid">

    <!-- Adjust Gold Rate Form -->
    <div class="mb-4">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Adjust Gold Rate</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.adjust-gold-rate') }}" method="post">
                    @csrf
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label for="adjust_type" class="form-label">Adjustment Type</label>
                            <select name="adjust_type" id="adjust_type" class="form-select form-control" required>
                                <option value="add" {{ isset($goldAdjustment) && $goldAdjustment->adjust_type === 'add' ? 'selected' : '' }}>Add</option>
                                <option value="subtract" {{ isset($goldAdjustment) && $goldAdjustment->adjust_type === 'subtract' ? 'selected' : '' }}>Subtract</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="amount" class="form-label">Amount in Rupees</label>
                            <input type="number" name="amount" id="amount" class="form-control" placeholder="Enter amount" required value="{{ $goldAdjustment ? $goldAdjustment->amount : '' }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Adjust Rate</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Silver Rates Section -->
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/silver-rate.png') }}" alt="">
            Silver Rates
        </h2>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.silver.store') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="metal">Metal</label>
                            <input type="text" name="metal" class="form-control" id="metal" placeholder="Enter Metal" value="silver" readonly required>
                        </div>

                        <div class="form-group">
                            <label for="currency">Currency</label>
                            <input type="text" name="currency" class="form-control" id="currency" placeholder="Enter Currency" value="Rs" readonly>
                        </div>

                        <div class="form-group">
                            <label for="price">Price per Gram</label>
                            <input type="number" name="price" class="form-control" id="price" placeholder="Enter Price per Gram" required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn--primary">Add Silver Rate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Existing Silver Rates Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="px-3 py-4">
                    <div class="row align-items-center">
                        <div class="col-sm-4 col-md-6 col-lg-8 mb-2 mb-sm-0">
                            <h5 class="mb-0 d-flex align-items-center gap-2">Existing Silver Rates</h5>
                        </div>
                    </div>
                </div>
                <div class="text-start">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Metal</th>
                                    <th>Currency</th>
                                    <th>Price per Gram</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($silverRates as $rate)
                                    <tr>
                                        <td>{{ $rate->id }}</td>
                                        <td>{{ $rate->metal }}</td>
                                        <td>{{ $rate->currency }}</td>
                                        <td>{{ $rate->price }}</td>
                                        <td>
                                            <a href="{{ route('admin.silver.edit', $rate->id) }}" class="btn btn-outline-info btn-sm">Edit</a>
                                            <form action="{{ route('admin.silver.destroy', $rate->id) }}" method="post" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
