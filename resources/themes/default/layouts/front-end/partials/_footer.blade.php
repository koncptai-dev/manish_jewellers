<div class="__inline-9 rtl">
    <div class="text-center pb-4">
        <div class="max-w-860px mx-auto footer-slider-container">
            <div class="container">
                <div class="footer-slider owl-theme owl-carousel">
                    <div class="footer-slide-item">
                        <div>
                            <a href="{{route('about-us')}}">
                                <div class="text-center text-primary">
                                    <img class="object-contain svg" width="36" height="36" src="{{theme_asset(path: "public/assets/front-end/img/icons/about-us.svg")}}"
                                        alt="">
                                </div>
                                <div class="text-center">
                                    <p class="m-0 mt-2">
                                        {{ translate('about_us')}}
                                    </p>
                                    <small class="d-none d-sm-block">{{translate('Know_about_our_company_more.')}}</small>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="footer-slide-item">
                        <div>
                            <a href="{{route('contacts')}}">
                                <div class="text-center text-primary">
                                    <img class="object-contain svg" width="36" height="36" src="{{ theme_asset(path: "public/assets/front-end/img/icons/contact-us.svg") }}"
                                        alt="">
                                </div>
                                <div class="text-center">
                                    <p class="m-0 mt-2">
                                        {{ translate('contact_Us')}}
                                    </p>
                                    <small class="d-none d-sm-block">{{translate('We_are_Here_to_Help')}}</small>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="footer-slide-item">
                        <div>
                            <a href="{{route('helpTopic')}}">
                                <div class="text-center text-primary">
                                    <img class="object-contain svg" width="36" height="36" src="{{theme_asset(path: "public/assets/front-end/img/icons/faq-icon.svg")}}"
                                        alt="">
                                </div>
                                <div class="text-center">
                                    <p class="m-0 mt-2">
                                        {{ translate('FAQ')}}
                                    </p>
                                    <small class="d-none d-sm-block">{{translate('Get_all_Answers')}}</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="page-footer font-small mdb-color rtl">
        <div class="pt-4 custom-light-primary-color-20">
            <div class="container text-center __pb-13px">

                <div
                    class="row mt-3 pb-3 ">
                    <div class="col-md-3 footer-web-logo text-center text-md-start ">
                        <a class="d-block" href="{{route('home')}}">
                            <img class="{{Session::get('direction') === "rtl" ? 'right-align' : ''}}"
                                 src="{{ getStorageImages(path: $web_config['footer_logo'], type: 'logo') }}"
                                 alt="{{ $web_config['company_name'] }}"/>
                        </a>

                        @if($web_config['ios']['status'] || $web_config['android']['status'])
                            <div class="mt-4 pt-lg-4">
                                <h6 class="text-uppercase font-weight-bold footer-header align-items-center">
                                    {{ translate('download_our_app')}}
                                </h6>
                            </div>
                        @endif

                        <div class="store-contents d-flex justify-content-center pr-lg-4">
                            @if($web_config['ios']['status'])
                                <div class="me-2 mb-2">
                                    <a class="" href="{{ $web_config['ios']['link'] }}" role="button">
                                        <img width="100" src="{{theme_asset(path: "public/assets/front-end/png/apple_app.png")}}"
                                             alt="">
                                    </a>
                                </div>
                            @endif

                            @if($web_config['android']['status'])
                                <div class="me-2 mb-2">
                                    <a href="{{ $web_config['android']['link'] }}" role="button">
                                        <img width="100" src="{{theme_asset(path: "public/assets/front-end/png/google_app.png")}}"
                                             alt="">
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-sm-3 col-6 footer-padding-bottom text-start">
                                <h6 class="text-uppercase mobile-fs-12 font-semi-bold footer-header">{{ translate('special')}}</h6>
                                <ul class="widget-list __pb-10px">
                                    @php($flash_deals=\App\Models\FlashDeal::where(['status'=>1,'deal_type'=>'flash_deal'])->whereDate('start_date','<=',date('Y-m-d'))->whereDate('end_date','>=',date('Y-m-d'))->first())
                                    @if(isset($flash_deals))
                                        <li class="widget-list-item">
                                            <a class="widget-list-link"
                                               href="{{route('flash-deals',[$flash_deals['id']])}}">
                                                {{ translate('flash_deal')}}
                                            </a>
                                        </li>
                                    @endif
                                    <li class="widget-list-item">
                                        <a class="widget-list-link"
                                           href="{{route('products',['data_from'=>'featured','page'=>1])}}">
                                            {{ translate('featured_products')}}
                                        </a>
                                    </li>
                                    <li class="widget-list-item">
                                        <a class="widget-list-link"
                                           href="{{route('products',['data_from'=>'latest','page'=>1])}}">
                                            {{ translate('latest_products')}}
                                        </a>
                                    </li>
                                    <li class="widget-list-item">
                                        <a class="widget-list-link"
                                           href="{{route('products',['data_from'=>'best-selling','page'=>1])}}">
                                            {{ translate('best_selling_product')}}
                                        </a>
                                    </li>
                                    <li class="widget-list-item">
                                        <a class="widget-list-link"
                                           href="{{route('products',['data_from'=>'top-rated','page'=>1])}}">
                                            {{ translate('top_rated_product')}}
                                        </a>
                                    </li>

                                </ul>
                            </div>
                            <div class="col-sm-4 col-6 footer-padding-bottom text-start">
                                <h6 class="text-uppercase mobile-fs-12 font-semi-bold footer-header">{{ translate('account_&_shipping_info')}}</h6>
                                @php($refund_policy = getWebConfig(name: 'refund-policy'))
                                @php($return_policy = getWebConfig(name: 'return-policy'))
                                @php($cancellation_policy = getWebConfig(name: 'cancellation-policy'))
                                @php($shippingPolicy = getWebConfig(name: 'shipping-policy'))
                                @if(auth('customer')->check())
                                    <ul class="widget-list __pb-10px">
                                        <li class="widget-list-item">
                                            <a class="widget-list-link" href="{{route('user-account')}}">
                                                {{ translate('profile_info')}}
                                            </a>
                                        </li>

                                        <li class="widget-list-item">
                                            <a class="widget-list-link" href="{{route('track-order.index')}}">
                                                {{ translate('track_order')}}
                                            </a>
                                        </li>

                                        @if(isset($refund_policy['status']) && $refund_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link" href="{{route('refund-policy')}}">
                                                    {{ translate('refund_policy')}}
                                                </a>
                                            </li>
                                        @endif

                                        @if(isset($return_policy['status']) && $return_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link" href="{{route('return-policy')}}">
                                                    {{ translate('return_policy')}}
                                                </a>
                                            </li>
                                        @endif

                                        @if(isset($cancellation_policy['status']) && $cancellation_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link" href="{{route('cancellation-policy')}}">
                                                    {{ translate('cancellation_policy')}}
                                                </a>
                                            </li>
                                        @endif

                                        @if(isset($shippingPolicy['status']) && $shippingPolicy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link" href="{{route('shipping-policy')}}">
                                                    {{ translate('Shipping_Policy')}}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <ul class="widget-list __pb-10px">
                                        <li class="widget-list-item">
                                            <a class="widget-list-link"
                                               href="{{route('customer.auth.login')}}">{{ translate('profile_info')}}</a>
                                        </li>
                                        <li class="widget-list-item">
                                            <a class="widget-list-link"
                                               href="{{route('customer.auth.login')}}">{{ translate('wish_list')}}</a>
                                        </li>

                                        <li class="widget-list-item">
                                            <a class="widget-list-link"
                                               href="{{route('track-order.index')}}">{{ translate('track_order')}}</a>
                                        </li>

                                        @if(isset($refund_policy['status']) && $refund_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link"
                                                   href="{{route('refund-policy')}}">{{ translate('refund_policy')}}</a>
                                            </li>
                                        @endif

                                        @if(isset($return_policy['status']) && $return_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link"
                                                   href="{{route('return-policy')}}">{{ translate('return_policy')}}</a>
                                            </li>
                                        @endif

                                        @if(isset($cancellation_policy['status']) && $cancellation_policy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link"
                                                   href="{{route('cancellation-policy')}}">{{ translate('cancellation_policy')}}</a>
                                            </li>
                                        @endif

                                        @if(isset($shippingPolicy['status']) && $shippingPolicy['status'] == 1)
                                            <li class="widget-list-item">
                                                <a class="widget-list-link" href="{{route('shipping-policy')}}">
                                                    {{ translate('shipping_Policy')}}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                            <div class="col-sm-5 footer-padding-bottom offset-max-sm--1 pb-3 pb-sm-0">
                                <div class="mb-2">
                                    <h6 class="text-uppercase mobile-fs-12 font-semi-bold footer-header text-center text-sm-start">{{ translate('newsletter')}}</h6>
                                    <div class="text-center text-sm-start mobile-fs-12">{{ translate('subscribe_to_our_new_channel_to_get_latest_updates')}}</div>
                                </div>
                                <div class="text-nowrap mb-4 position-relative">
                                    <form action="{{ route('subscription') }}" method="post">
                                        @csrf
                                        <input type="email" name="subscription_email"
                                               class="form-control subscribe-border text-align-direction p-12px"
                                               placeholder="{{ translate('your_Email_Address')}}" required>
                                        <button class="subscribe-button" type="submit">
                                            {{ translate('subscribe')}}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4 {{Session::get('direction') === "rtl" ? ' flex-row-reverse' : ''}}">
                            <div class="col-md-7">
                                <div
                                    class="d-flex align-items-center mobile-view-center-align text-start justify-content-between">
                                    <div class="me-3">
                                        <span class="mb-4 font-weight-bold footer-header text-capitalize">{{ translate('start_a_conversation')}}</span>
                                    </div>
                                    <div
                                        class="flex-grow-1 d-none d-md-block {{Session::get('direction') === "rtl" ? 'mr-4 mx-sm-4' : 'mx-sm-4'}}">
                                        <hr>
                                    </div>
                                </div>
                                <div class="row text-start">
                                    <div class="col-12 start_address ">
                                        <div class="">
                                            <a class="widget-list-link" href="{{ 'tel:'.$web_config['phone'] }}">
                                                <span class="">
                                                    <i class="fa fa-phone  me-2 mt-2 mb-2"></i>
                                                    <span class="direction-ltr">
                                                        {{getWebConfig(name: 'company_phone')}}
                                                    </span>
                                                </span>
                                            </a>

                                        </div>
                                        <div>
                                            <a class="widget-list-link"
                                               href="{{ 'mailto:'.getWebConfig(name: 'company_email') }}">
                                                <span><i class="fa fa-envelope  me-2 mt-2 mb-2"></i> {{getWebConfig(name: 'company_email')}} </span>
                                            </a>
                                        </div>
                                        <div class="pe-3">
                                            @if(auth('customer')->check())
                                                <a class="widget-list-link" href="{{route('account-tickets')}}">
                                                    <span><i class="fa fa-user-o  me-2 mt-2 mb-2"></i> {{ translate('support_ticket')}} </span>
                                                </a>
                                                <br class="d-none d-md-block" />
                                            @else
                                                <a class="widget-list-link" href="{{route('customer.auth.login')}}">
                                                    <span><i class="fa fa-user-o  me-2 mt-2 mb-2"></i> {{ translate('support_ticket')}} </span>
                                                </a>
                                                <br class="d-none d-md-block" />
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-start">
                                <div
                                    class="row d-flex align-items-center mobile-view-center-align justify-content-center justify-content-md-start pb-0">
                                    <div class="d-none d-md-block">
                                        <span class="mb-4 font-weight-bold footer-header">{{ translate('address')}}</span>
                                    </div>
                                    <div
                                        class="flex-grow-1 d-none d-md-block {{Session::get('direction') === "rtl" ? 'mr-3 ' : 'ml-3'}}">
                                        <hr class="address_under_line"/>
                                    </div>
                                </div>
                                <div>
                                    <span
                                        class="__text-14px d-flex align-items-center">
                                        <i class="fa fa-map-marker me-2 mt-2 mb-2"></i>
                                        <span>{{ getWebConfig(name: 'shop_address')}}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white-overlay-50">
            <div class="container">
                <div class="d-flex flex-wrap end-footer footer-end last-footer-content-align text-center pt-3 pb-4 py-md-0">
                    <div class="mt-3">
                        <p class="__text-16px">© 2025 Manish Jewellers . All rights reserved. Website designed and developed by <a href="https://koncpt.ai" target="_blank" rel="noopener" style="color:#ffde59">KONCPT AI</a></p>
                    </div>
                    <div
                        class="max-sm-100 justify-content-center d-flex flex-wrap mt-md-3 mt-0 mb-md-3">
                        @if($web_config['social_media'])
                            @foreach ($web_config['social_media'] as $item)
                                <span class="social-media ">
                                    @if ($item->name == "twitter")
                                        <a class="social-btn text-white sb-light sb-{{$item->name}} me-2 mb-2 d-flex justify-content-center align-items-center"
                                           target="_blank" href="{{$item->link}}">
                                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="16"
                                                 height="16" viewBox="0 0 24 24">
                                                <g opacity=".3">
                                                    <polygon fill="#fff" fill-rule="evenodd"
                                                     points="16.002,19 6.208,5 8.255,5 18.035,19"
                                                             clip-rule="evenodd">
                                                    </polygon>
                                                    <polygon points="8.776,4 4.288,4 15.481,20 19.953,20 8.776,4">
                                                    </polygon>
                                                </g>
                                                <polygon fill-rule="evenodd"
                                                         points="10.13,12.36 11.32,14.04 5.38,21 2.74,21"
                                                    clip-rule="evenodd">
                                                </polygon>
                                                <polygon fill-rule="evenodd"
                                                         points="20.74,3 13.78,11.16 12.6,9.47 18.14,3"
                                                         clip-rule="evenodd">
                                                </polygon>
                                                <path
                                                    d="M8.255,5l9.779,14h-2.032L6.208,5H8.255 M9.298,3h-6.93l12.593,18h6.91L9.298,3L9.298,3z"
                                                    fill="currentColor">
                                                </path>
                                            </svg>
                                        </a>
                                    @else
                                        <a class="social-btn text-white sb-light sb-{{$item->name}} me-2 mb-2"
                                           target="_blank" href="{{$item->link}}">
                                            <i class="{{$item->icon}}" aria-hidden="true"></i>
                                        </a>
                                    @endif
                                </span>
                            @endforeach
                        @endif
                    </div>
                    <div class="d-flex __text-14px justify-content-center">
                        <div class="me-3">
                            <a class="widget-list-link"
                               href="{{route('terms')}}">{{ translate('terms_&_conditions')}}</a>
                        </div>
                        <div>
                            <a class="widget-list-link" href="{{route('privacy-policy')}}">
                                {{ translate('privacy_policy')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php($cookie = $web_config['cookie_setting'] ? json_decode($web_config['cookie_setting']['value'], true):null)
        @if($cookie && $cookie['status']==1)
            <section id="cookie-section"></section>
        @endif
    </footer>
</div>
