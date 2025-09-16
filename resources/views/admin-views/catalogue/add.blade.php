@extends('layouts.back-end.app')

@section('title', translate('catalogue_Add'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/catalogue.png') }}" alt="">
                {{ translate('catalogue_Setup') }}
            </h2>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.catalogue.store') }}" method="post" enctype="multipart/form-data" class="catalogue-setup-form">
                            @csrf

                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach($language as $lang)
                                    <li class="nav-item">
                                        <span class="nav-link form-system-language-tab cursor-pointer {{$lang == $defaultLanguage ? 'active':''}}"
                                           id="{{$lang}}-link">
                                            {{ ucfirst(getLanguageName($lang)).'('.strtoupper($lang).')' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                            <!-- Brand Dropdown -->
                            <div class="form-group mb-4">
                                <label for="brand_id" class="title-color">{{ translate('Select Brand') }} <span class="text-danger">*</span></label>
                                <select name="brand_id" id="brand_id" class="form-control" required>
                                    <option value="">{{ translate('Select Brand') }}</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Language Tabs for Catalogue Name -->
                            

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="name" class="title-color">
                                        {{ translate('catalogue_Name') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="catalogue_name" class="form-control" id="name" value=""
                                            placeholder="{{ translate('ex') }} : {{translate('Summer Collection') }}" {{$lang == $defaultLanguage? 'required':''}}>
                                </div>
                            </div>

                            <div class="d-flex gap-3 justify-content-end">
                                <button type="reset" id="reset"
                                        class="btn btn-secondary px-4">{{ translate('reset') }}</button>
                                <button type="submit" class="btn btn--primary px-4">{{ translate('submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $('.catalogue-setup-form').on('reset', function () {
            $(this).find('#pre_img_viewer').addClass('d-none');
            $(this).find('.placeholder-image').css('opacity', '1');
        });
    </script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/products-management.js') }}"></script>
@endpush
