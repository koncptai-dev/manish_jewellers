@extends('admin-views.layouts.master')

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/silver-rate.png') }}" alt="">
            Add Silver Rate
        </h2>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.silver.store') }}" method="post" class="text-start">
                        @csrf

                        <div class="form-group">
                            <label for="metal">Metal</label>
                            <input type="text" name="metal" class="form-control" id="metal" placeholder="Enter Metal" required>
                        </div>

                        <div class="form-group">
                            <label for="currency">Currency</label>
                            <input type="text" name="currency" class="form-control" id="currency" placeholder="Enter Currency" required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price per Gram</label>
                            <input type="number" name="price" class="form-control" id="price" placeholder="Enter Price per Gram" required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn--primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
