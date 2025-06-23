
@extends('layouts.back-end.app')
@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="h1 mb-0 d-flex gap-2">
            <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/silver-rate.png') }}" alt="">
            Edit Silver Rate
        </h2>
    </div>

    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.silver.update', $silverRate->id) }}" method="post" class="text-start">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="metal">Metal</label>
                            <input type="text" name="metal" class="form-control" id="metal" value="{{ $silverRate->metal }}" readonly required>
                        </div>

                        <div class="form-group">
                            <label for="currency">Currency</label>
                            <input type="text" name="currency" class="form-control" id="currency" value="{{ $silverRate->currency }}" readonly required>
                        </div>

                        <div class="form-group">
                            <label for="price">Price per Gram</label>
                            <input type="number" name="price" class="form-control" id="price" value="{{ $silverRate->price }}" required>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn--primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
