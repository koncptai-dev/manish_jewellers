@extends('layouts.back-end.app')

@section('title', translate('update_sub_Category'))

@section('content')
    <div class="content container-fluid">
        <div class="mb-3">
            <h2 class="h1 mb-0 d-flex gap-2">
                <img src="{{ dynamicAsset(path: 'public/assets/back-end/img/brand-setup.png') }}" alt="">
                {{ translate('update_sub_Category') }}
            </h2>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-start">
                        <form action="{{ route('admin.sub-category.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('POST')

                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach($languages as $lang)
                                    <li class="nav-item">
                                        <span class="nav-link form-system-language-tab cursor-pointer {{ $lang == $defaultLanguage ? 'active' : '' }}"
                                              id="{{ $lang }}-link">
                                            {{ ucfirst(getLanguageName($lang)).' ('.strtoupper($lang).')' }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="row">
                                <div class="{{ theme_root_path() == 'theme_aster' ? 'col-lg-6' : 'col-lg-12' }}">
                                    <div class="{{ theme_root_path() == 'theme_aster' ? 'w-100' : 'row' }}">

                                        {{-- Subcategory Name --}}
                                        <div class="{{ theme_root_path() == 'theme_aster' ? 'w-100' : 'col-md-6 col-lg-4' }}">
                                            @foreach($languages as $lang)
                                                @php
                                                    $translation = $category->translations->firstWhere('locale', $lang);
                                                @endphp
                                                <div class="form-group {{ $lang != $defaultLanguage ? 'd-none' : '' }} form-system-language-form"
                                                     id="{{ $lang }}-form">
                                                    <label class="title-color">{{ translate('sub_category_name') }}
                                                        <span class="text-danger">*</span> ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="name[]" class="form-control"
                                                           value="{{ $translation?->name ?? ($lang == $defaultLanguage ? $category->defaultname : '') }}"
                                                           placeholder="{{ translate('sub_category_name') }}"
                                                           {{ $lang == $defaultLanguage ? 'required' : '' }}>
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                            @endforeach
                                            <input name="position" value="1" class="d-none">
                                        </div>

                                        {{-- Brand --}}
                                        <div class="form-group {{ theme_root_path() == 'theme_aster' ? 'w-100' : 'col-md-6 col-lg-4' }}">
                                            <label class="title-color">{{ translate('Brand') }} <span class="text-danger">*</span></label>
                                            <select id="brandSelect" name="brand_id" class="form-control" required>
                                                <option value="" disabled {{ !$category->brand_id ? 'selected' : '' }}>
                                                    {{ translate('select_brand') }}
                                                </option>
                                                @foreach($brands as $brand)
                                                    <option value="{{ $brand->id }}" {{ $category->brand_id == $brand->id ? 'selected' : '' }}>
                                                        {{ $brand->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Main Category (filtered by brand) --}}
                                        <div class="form-group {{ theme_root_path() == 'theme_aster' ? 'w-100' : 'col-md-6 col-lg-4' }}">
                                            <label class="title-color">{{ translate('main_Category') }} <span class="text-danger">*</span></label>
                                            <select id="categorySelect" name="parent_id" class="form-control" {{ !$category->brand_id ? 'disabled' : '' }} required>
                                                <option value="" disabled {{ !$category->parent_id ? 'selected' : '' }}>
                                                    {{ translate('select_main_category') }}
                                                </option>
                                                @foreach($parentCategories as $parent)
                                                    <option value="{{ $parent->id }}"
                                                            data-brand="{{ $parent->brand_id }}"
                                                            {{ $category->parent_id == $parent->id ? 'selected' : '' }}
                                                            style="{{ $parent->brand_id == $category->brand_id ? '' : 'display:none;' }}">
                                                        {{ $parent->defaultname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Priority --}}
                                        <div class="form-group {{ theme_root_path() == 'theme_aster' ? 'w-100' : 'col-md-6 col-lg-4' }}">
                                            <label class="title-color">{{ translate('priority') }}</label>
                                            <select class="form-control" name="priority" required>
                                                <option disabled>{{ translate('set_Priority') }}</option>
                                                @for ($i = 0; $i <= 10; $i++)
                                                    <option value="{{ $i }}" {{ $category->priority == $i ? 'selected' : '' }}>
                                                        {{ $i }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Logo upload --}}
                                    @if (theme_root_path() == 'theme_aster')
                                        <div class="from_part_2">
                                            <label class="title-color">{{ translate('sub_category_Logo') }}</label>
                                            <span class="text-info">{{ THEME_RATIO[theme_root_path()]['Category Image'] }}</span>
                                            <div class="custom-file text-left">
                                                <input type="file" name="image" id="category-image"
                                                       class="custom-file-input image-preview-before-upload"
                                                       data-preview="#viewer"
                                                       accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <label class="custom-file-label" for="category-image">{{ translate('choose_File') }}</label>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                {{-- Preview --}}
                                @if (theme_root_path() == 'theme_aster')
                                    <div class="col-lg-6 mt-4 mt-lg-0 from_part_2">
                                        <div class="form-group">
                                            <div class="mx-auto text-center">
                                                <img class="upload-img-view" id="viewer"
                                                     src="{{ getStorageImages(path: $category->icon_full_url , type: 'backend-basic') }}"
                                                     alt="">
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <button type="submit" class="btn btn--primary">{{ translate('update') }}</button>
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
    // Brand -> Main category filter
    document.getElementById('brandSelect').addEventListener('change', function () {
        let brandId = this.value;
        let categorySelect = document.getElementById('categorySelect');
        let options = categorySelect.querySelectorAll('option[data-brand]');

        categorySelect.value = "";
        categorySelect.disabled = !brandId;

        options.forEach(opt => {
            opt.style.display = (opt.dataset.brand === brandId) ? 'block' : 'none';
        });
    });
</script>
@endpush
