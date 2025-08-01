@php
    $overallRating = getOverallRating($product->reviews);
    $rating = getRating($product->reviews);
    $productReviews = \App\Utils\ProductManager::get_product_review($product->id);
@endphp

<div class="modal-header rtl">
    <div>
        <h4 class="modal-title product-title">
            <a class="product-title2" href="{{route('product',$product->slug)}}" data-toggle="tooltip"
               data-placement="right"
               title="Go to product page">{{$product['name']}}
                <i class="czi-arrow-{{ Session::get('direction') === "rtl" ? 'left' : 'right' }} ms-2 font-size-lg mr-0"></i>
            </a>
        </h4>
    </div>
    <div>
        <button class="close call-when-done" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>

<div class="modal-body rtl">
    <div class="row ">
        <div class="col-lg-5 col-md-4 col-12">
            <div class="cz-product-gallery position-relative">
                <div class="cz-preview">
                    <div id="sync1" class="owl-carousel owl-theme product-thumbnail-slider">
                        @if($product->images!=null && count($product->images_full_url)>0)
                            @if(json_decode($product->colors) && count($product->color_images_full_url)>0)
                                @foreach ($product->color_images_full_url as $key => $photo)
                                    @if($photo['color'] != null)
                                        <div class="product-preview-item d-flex align-items-center justify-content-center">
                                            <img class="show-imag img-responsive max-height-500px"
                                                 src="{{ getStorageImages(path: $photo['image_name'], type: 'product') }}"
                                                 alt="{{ translate('product') }}" width="">
                                        </div>
                                    @else
                                        <div class="product-preview-item d-flex align-items-center justify-content-center">
                                            <img class="show-imag img-responsive max-height-500px"
                                                 src="{{ getStorageImages(path:$photo['image_name'], type: 'product') }}"
                                                 alt="{{ translate('product') }}" width="">
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                @foreach ($product->images_full_url as $key => $photo)
                                    <div class="product-preview-item d-flex align-items-center justify-content-center">
                                        <img class="show-imag img-responsive max-height-500px"
                                             src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                             alt="{{ translate('product') }}">
                                    </div>
                                @endforeach
                            @endif
                        @endif
                    </div>
                </div>

                <div class="cz-product-gallery-icons">
                    <div class="d-flex flex-column">
                        <button type="button" data-product-id="{{ $product['id'] }}"
                                class="btn __text-18px border wishList-pos-btn d-sm-none product-action-add-wishlist">
                            <i class="fa {{($wishlist_status == 1?'fa-heart':'fa-heart-o')}} wishlist_icon_{{$product['id']}} web-text-primary"
                               id="wishlist_icon_{{$product['id']}}" aria-hidden="true"></i>
                            <div class="wishlist-tooltip" x-placement="top">
                                <div class="arrow"></div><div class="inner">
                                    <span class="add">{{translate('added_to_wishlist')}}</span>
                                    <span class="remove">{{translate('removed_from_wishlist')}}</span>
                                </div>
                            </div>
                        </button>

                        <div class="sharethis-inline-share-buttons share--icons text-align-direction">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div class="d-flex">
                        <div id="sync2" class="owl-carousel owl-theme product-thumb-slider max-height-100px d--none">
                            @if($product->images!=null && count($product->images_full_url)>0)
                                @if(json_decode($product->colors) && count($product->color_images_full_url)>0)
                                    @foreach ($product->color_images_full_url as $key => $photo)
                                        @if($photo['color'] != null)
                                            <div class="">
                                                <a href="javascript:"
                                                   class="product-preview-thumb d-flex align-items-center justify-content-center">
                                                    <img class="click-img" id="preview-img{{$photo['color']}}"
                                                         src="{{ getStorageImages(path:$photo['image_name'], type: 'product') }}"
                                                         alt="{{ translate('product') }}">
                                                </a>
                                            </div>
                                        @else
                                            <div class="">
                                                <a href="javascript:"
                                                   class="product-preview-thumb d-flex align-items-center justify-content-center">
                                                    <img class="click-img" id="preview-img{{$key}}"
                                                         src="{{ getStorageImages(path: $photo['image_name'], type: 'product') }}"
                                                         alt="{{ translate('product') }}">
                                                </a>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    @foreach ($product->images_full_url as $key => $photo)
                                        <div class="">
                                            <a href="javascript:"
                                               class="product-preview-thumb d-flex align-items-center justify-content-center">
                                                <img class="click-img" id="preview-img{{$key}}"
                                                     src="{{ getStorageImages(path: $photo, type: 'product') }}"
                                                     alt="{{ translate('product') }}">
                                            </a>
                                        </div>
                                    @endforeach
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-8 col-12 mt-md-0 mt-sm-3 web-direction">
            <div class="details __h-100 product-cart-option-container">
                <a href="{{route('product',$product->slug)}}" class="h3 mb-2 product-title">{{$product->name}}</a>

                <div class="d-flex flex-wrap align-items-center mb-2 pro">
                    <div class="star-rating me-2">
                        @for($inc=0;$inc<5;$inc++)
                            @if($inc<$overallRating[0])
                                <i class="sr-star czi-star-filled active"></i>
                            @else
                                <i class="sr-star czi-star"></i>
                            @endif
                        @endfor
                    </div>
                    <span
                            class="d-inline-block  align-middle mt-1 {{Session::get('direction') === "rtl" ? 'ml-md-2 ml-sm-0' : 'mr-md-2 mr-sm-0'}} fs-14 text-muted">({{$overallRating[0]}})</span>
                    <span class="font-regular font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-1 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-1 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}}"><span class="web-text-primary">{{$overallRating[1]}}</span> {{translate('reviews')}}</span>
                    <span class="__inline-25"></span>
                    <span class="font-regular font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-1 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-1 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}}">
                        <span class="web-text-primary">
                            {{$countOrder}}
                        </span> {{translate('orders')}}   </span>
                    <span class="__inline-25">    </span>
                    <span class="font-regular font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-0 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-0 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}} text-capitalize">
                        <span class="web-text-primary countWishlist-{{ $product->id }}"> {{$countWishlist}}</span> {{translate('wish_listed')}}
                    </span>

                </div>

                @if($product['product_type'] == 'digital')
                    <div class="digital-product-authors mb-2">
                        @if(count($productPublishingHouseInfo['data']) > 0)
                            <div class="d-flex align-items-center g-2 me-2">
                                <span class="text-capitalize digital-product-author-title">{{ translate('Publishing_House') }} :</span>
                                <div class="item-list">
                                    @foreach($productPublishingHouseInfo['data'] as $publishingHouseName)
                                        <a href="{{ route('products', ['publishing_house_id' => $publishingHouseName['id'], 'product_type' => 'digital', 'page'=>1]) }}"
                                           class="text-base">
                                            {{ $publishingHouseName['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(count($productAuthorsInfo['data']) > 0)
                            <div class="d-flex align-items-center g-2 me-2">
                                <span class="text-capitalize digital-product-author-title">{{ translate('Author') }} :</span>
                                <div class="item-list">
                                    @foreach($productAuthorsInfo['data'] as $productAuthor)
                                        <a href="{{ route('products',['author_id' => $productAuthor['id'], 'product_type' => 'digital', 'page' => 1]) }}"
                                           class="text-base">
                                            {{ $productAuthor['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="mb-3">
                    <span class="font-weight-normal text-accent d-flex align-items-end gap-2">
                        {!! getPriceRangeWithDiscount(product: $product) !!}
                    </span>
                </div>
                <form id="add-to-cart-form" class="mb-2 addToCartDynamicForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    <input type="hidden" name="hallmark_charges" value="{{ $product->hallmark_charges }}">
                    <input type="hidden" name="making_charges" value="{{ $product->making_charges }}">
                    <div class="position-relative {{Session::get('direction') === "rtl" ? 'ml-n4' : 'mr-n4'}} mb-3">
                        @if (count(json_decode($product->colors)) > 0)
                            <div class="flex-start">
                                <div class="product-description-label text-dark font-bold">
                                    {{translate('color')}}:
                                </div>
                                <div class="__pl-15 mt-1">
                                    <ul class="flex-start checkbox-color mb-0 p-0 list-inline">
                                        @foreach (json_decode($product->colors) as $key => $color)
                                            <li>
                                                <input type="radio"
                                                       id="{{ $product->id }}-color-{{ str_replace('#','',$color) }}"
                                                       name="color" value="{{ $color }}"
                                                       @if($key == 0) checked @endif>
                                                <label style="background: {{ $color }};"
                                                    class="quick-view-preview-image-by-color shadow-border"
                                                    for="{{ $product->id }}-color-{{ str_replace('#','',$color) }}"
                                                    data-toggle="tooltip"
                                                    data-key="{{ str_replace('#','',$color) }}" data-title="{{ \App\Utils\get_color_name($color) }}">
                                                    <span class="outline"></span>
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @php
                            $qty = 0;
                            foreach (json_decode($product->variation) as $key => $variation) {
                                $qty += $variation->qty;
                            }
                        @endphp

                    </div>

                    @foreach (json_decode($product->choice_options) as $key => $choice)
                        <div class="row flex-start mx-0 align-items-center">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-2 text-dark fw-bold text-capitalize" style="width: 80px;">
                                    {{ $choice->title }} :
                                </div>
                                <div class="d-flex flex-wrap gap-2 checkbox-alphanumeric checkbox-alphanumeric--style-1">
                                    @foreach ($choice->options as $index => $option)
                                        <div>
                                            <div class="for-mobile-capacity">
                                                <input type="radio" class="btn-check variant-radio"
                                                        id="{{ str_replace(' ', '', ($choice->name. '-'. $option)) }}"
                                                        name="{{ $choice->name }}" value="{{ $option }}"
                                                        @if($index == 0) checked @endif >
                                                <label class="btn btn-outline-primary btn-sm"
                                                        for="{{ str_replace(' ', '', ($choice->name. '-'. $option)) }}">{{ $option }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @php($extensionIndex=0)
                    @if($product['product_type'] == 'digital' && $product['digital_product_file_types'] && count($product['digital_product_file_types']) > 0 && $product['digital_product_extensions'])
                        @foreach($product['digital_product_extensions'] as $extensionKey => $extensionGroup)
                            <div class="row flex-start mx-0 align-items-center mb-1">
                                <div class="product-description-label text-dark font-bold {{Session::get('direction') === "rtl" ? 'pl-2' : 'pr-2'}} text-capitalize mb-2">
                                    {{ translate($extensionKey) }} :
                                </div>
                                <div>
                                    @if(count($extensionGroup) > 0)
                                        <div class="list-inline checkbox-alphanumeric checkbox-alphanumeric--style-1 mb-0 mx-1 flex-start row ps-0">
                                            @foreach($extensionGroup as $index => $extension)
                                                <div>
                                                    <div class="for-mobile-capacity">
                                                        <input type="radio" hidden
                                                               id="extension_{{ str_replace(' ', '-', $extension) }}"
                                                               name="variant_key"
                                                               value="{{ $extensionKey.'-'.preg_replace('/\s+/', '-', $extension) }}"
                                                            {{ $extensionIndex == 0 ? 'checked' : ''}}>
                                                        <label for="extension_{{ str_replace(' ', '-', $extension) }}"
                                                               class="__text-12px">
                                                            {{ $extension }}
                                                        </label>
                                                    </div>
                                                </div>
                                                @php($extensionIndex++)
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif

                    <div class="mb-3">
                        <div class="product-quantity d-flex flex-column __gap-15">
                            <div class="d-flex align-items-center gap-3">
                                <div class="product-description-label text-dark font-bold mt-0">{{translate('quantity')}}
                                    :
                                </div>
                                <div class="d-flex justify-content-center align-items-center quantity-box border rounded border-base web-text-primary">
                                <span class="input-group-btn">
                                    <button class="btn btn-number __p-10 web-text-primary" type="button" data-type="minus"
                                            data-field="quantity"
                                            disabled="disabled">
                                        -
                                    </button>
                                </span>
                                    <input type="text" name="quantity"
                                           class="form-control input-number text-center cart-qty-field __inline-29 border-0 "
                                           placeholder="{{ translate('1') }}" value="{{ $product->minimum_order_qty ?? 1 }}"
                                           data-producttype="{{ $product->product_type }}"
                                           min="{{ $product->minimum_order_qty ?? 1 }}"
                                           max="{{$product['product_type'] == 'physical' ? $product->current_stock : 100}}">
                                    <span class="input-group-btn">
                                    <button class="btn btn-number __p-10 web-text-primary" type="button"
                                            data-producttype="{{ $product->product_type }}"
                                            data-type="plus" data-field="quantity">
                                        +
                                    </button>
                                </span>
                                </div>
                                <input type="hidden" class="product-generated-variation-code" name="product_variation_code" data-product-id="{{ $product['id'] }}">
                                <input type="hidden" value="" class="in_cart_key form-control w-50" name="key">
                            </div>
                            <div id="chosen_price_div">
                                <div
                                        class="d-flex justify-content-start align-items-center me-2">
                                    <div class="product-description-label text-dark font-bold text-capitalize">
                                        <strong>{{translate('total_price')}}</strong> :
                                    </div>
                                    &nbsp; <strong id="chosen_price" class="text-base"></strong>
                                    <small class="ms-2 font-regular">
                                        (<small>{{translate('tax')}} : </small>
                                        <small id="set-tax-amount"></small>)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php($guestCheckout = getWebConfig(name: 'guest_checkout'))
                    <div class="__btn-grp align-items-center mb-2 product-add-and-buy-section" {!! $firstVariationQuantity <= 0 ? 'style="display: none;"' : '' !!}>
                        @if(($product->added_by == 'seller' && ($seller_temporary_close || (isset($product->seller->shop) &&
                        $product->seller->shop->vacation_status && $currentDate >= $seller_vacation_start_date && $currentDate
                        <= $seller_vacation_end_date))) || ($product->added_by == 'admin' && ($inhouse_temporary_close ||
                            ($inHouseVacationStatus && $currentDate >= $inhouse_vacation_start_date && $currentDate <=
                                $inhouse_vacation_end_date))))

                            <button class="btn btn-secondary" type="button" disabled>
                                {{translate('buy_now')}}
                            </button>

                            <button class="btn btn--primary string-limit" type="button" disabled>
                                {{translate('add_to_cart')}}
                            </button>
                        @else
                            <button class="btn btn-secondary action-buy-now-this-product"
                                type="button"
                                data-auth-status="{{($guestCheckout == 1 || Auth::guard('customer')->check() ? 'true':'false')}}"
                                data-route="{{ route('shop-cart') }}"
                            >
                                {{translate('buy_now')}}
                            </button>
                            <button class="btn btn--primary string-limit action-add-to-cart-form" type="button" data-update-text="{{ translate('update_cart') }}" data-add-text="{{ translate('add_to_cart') }}">
                                {{translate('add_to_cart')}}
                            </button>
                        @endif

                        <button type="button" data-product-id="{{$product['id']}}" class="btn __text-18px border product-action-add-wishlist">
                            <i class="fa {{($wishlist_status == 1?'fa-heart':'fa-heart-o')}} wishlist_icon_{{$product['id']}} web-text-primary"
                            id="wishlist_icon_{{$product['id']}}" aria-hidden="true"></i>
                            <span class="fs-14 text-muted align-bottom countWishlist-{{$product['id']}}">
                                {{$countWishlist}}
                            </span>
                            <div class="wishlist-tooltip" x-placement="top">
                                <div class="arrow"></div><div class="inner">
                                    <span class="add">{{translate('added_to_wishlist')}}</span>
                                    <span class="remove">{{translate('removed_from_wishlist')}}</span>
                                </div>
                            </div>
                        </button>

                        @if(($product->added_by == 'seller' && ($seller_temporary_close ||
                        (isset($product->seller->shop) && $product->seller->shop->vacation_status && $currentDate >=
                        $seller_vacation_start_date && $currentDate <= $seller_vacation_end_date))) || ($product->
                            added_by == 'admin' && ($inhouse_temporary_close || ($inHouseVacationStatus &&
                            $currentDate >= $inhouse_vacation_start_date && $currentDate <= $inhouse_vacation_end_date))))
                            <div class="alert alert-danger" role="alert">
                                {{translate('this_shop_is_temporary_closed_or_on_vacation._You_cannot_add_product_to_cart_from_this_shop_for_now')}}
                            </div>
                       @endif
                    </div>

                    @if(($product['product_type'] == 'physical'))
                        <div class="product-restock-request-section collapse" {!! $firstVariationQuantity <= 0 ? 'style="display: block;"' : '' !!}>
                            <button type="button"
                                    class="btn request-restock-btn btn-outline-primary fw-semibold product-restock-request-button"
                                    data-auth="{{ auth('customer')->check() }}"
                                    data-form=".addToCartDynamicForm"
                                    data-default="{{ translate('Request_Restock') }}"
                                    data-requested="{{ translate('Request_Sent') }}"
                            >
                                {{ translate('Request_Restock') }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
<span id="products-details-page-data" data-id="{{ $product['id'] }}"></span>
<script type="text/javascript">
    "use strict";
    productQuickViewFunctionalityInitialize();
    $('.variant-radio').on('change', function() {
    var selectedSize = $(this).val();
    $.ajax({
        url: $("#route-variant-product-size-price").data("url"), // change this URL to your route
        type: 'POST',
        data: {
            size: selectedSize,
            product_id: $("#products-details-page-data").data("id"),
        },
        beforeSend: function () {
            $('#loading').fadeIn();
        },
        success: function(response) {
            $(".discounted_unit_price").text(response.price);
            $(".unit_price").text(response.price);
            $(".unit_price").attr("data-price", response.price);
            $(".unit_price").attr("data-price-variant", response.price);
            $("#chosen_price").text(response.price);
            
        },
        error: function(xhr) {
            console.log('Error:', xhr.responseText);
        },
        complete: function () {
            $('#loading').fadeOut();
        },
    });
});
</script>

<script type="text/javascript" async="async" src="https://platform-api.sharethis.com/js/sharethis.js#property=5f55f75bde227f0012147049&product=sticky-share-buttons"></script>