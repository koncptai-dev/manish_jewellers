<?php

namespace App\Traits;

use App\Models\Banner;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\MostDemanded;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\Tag;
use App\Utils\BrandManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

trait CacheManagerTrait
{
    public function cacheBusinessSettingsTable()
    {
        return Cache::remember(CACHE_BUSINESS_SETTINGS_TABLE, CACHE_FOR_3_HOURS, function () {
            return BusinessSetting::all();
        });
    }

    public function cacheTagTable()
    {
        return Cache::remember(CACHE_TAGS_TABLE, CACHE_FOR_3_HOURS, function () {
            return Tag::orderBy('visit_count', 'desc')->take(15)->get();
        });
    }

    public function cacheMainCategoriesList()
    {
        return Cache::remember(CACHE_MAIN_CATEGORIES_LIST, CACHE_FOR_3_HOURS, function () {
            return Category::with(['product' => function ($query) {
                return $query->active()->withCount(['orderDetails']);
            }])->withCount(['product' => function ($query) {
                $query->active();
            }])->with(['childes' => function ($query) {
                $query->with(['childes' => function ($query) {
                    $query->withCount(['subSubCategoryProduct' => function ($query) {
                        $query->active();
                    }])->where('position', 2);
                }])->withCount(['subCategoryProduct' => function ($query) {
                    $query->active();
                }])->where('position', 1);
            }, 'childes.childes'])->where('position', 0)->get();
        });
    }

    public function cachePriorityWiseBrandList()
    {
        return Cache::remember(CACHE_PRIORITY_WISE_BRANDS_LIST, CACHE_FOR_3_HOURS, function () {
            return BrandManager::getActiveBrandWithCountingAndPriorityWiseSorting();
        });
    }

    public function cacheInHouseAllProducts()
    {
        return Cache::remember(CACHE_FOR_IN_HOUSE_ALL_PRODUCTS, CACHE_FOR_3_HOURS, function () {
            return Product::active()->with(['reviews', 'rating'])->withCount('reviews')->where(['added_by' => 'admin'])->get();
        });
    }

    public function cacheHomePageTopVendorsList()
    {
        $inHouseProducts = $this->cacheInHouseAllProducts();
        return Cache::remember(CACHE_FOR_HOME_PAGE_TOP_VENDORS_LIST, CACHE_FOR_3_HOURS, function () use ($inHouseProducts) {
            $topVendorsList = Shop::active()
                ->withCount(['products' => function ($query) {
                    $query->active();
                }])
                ->with(['products' => function ($query) {
                    $query->active();
                }])
                ->with('seller', function ($query) {
                    $query->with('product', function ($query) {
                        $query->active()->with('reviews', function ($query) {
                            $query->active();
                        });
                    })->with('coupon')->withCount(['orders']);
                })
                ->get()
                ->each(function ($shop) {
                    $shop->products = Arr::random($shop->products->toArray(), count($shop->products) < 3 ? count($shop->products) : 3);
                    $shop->orders_count = $shop->seller->orders_count;
                    $shop->coupon_list = $shop?->seller?->coupon ?? null;

                    $productReviews = $shop->seller->product->pluck('reviews')->collapse();
                    $shop->average_rating = $productReviews->avg('rating');
                    $shop->review_count = $productReviews->count();
                    $shop->total_rating = $productReviews->sum('rating');

                    $positiveReviewsCount = $productReviews->where('rating', '>=', 4)->count();
                    $shop->positive_review = ($shop->review_count !== 0) ? ($positiveReviewsCount * 100) / $shop->review_count : 0;

                    $currentDate = date('Y-m-d');
                    $startDate = date('Y-m-d', strtotime($shop['vacation_start_date']));
                    $endDate = date('Y-m-d', strtotime($shop['vacation_end_date']));
                    $shop->is_vacation_mode_now = $shop['vacation_status'] && ($currentDate >= $shop['vacation_start_date']) && ($currentDate <= $shop['vacation_end_date']) ? 1 : 0;
                })->take(12);

            $inHouseCoupon = Coupon::where(['added_by' => 'admin', 'coupon_bearer' => 'inhouse', 'status' => 1])
                ->whereDate('start_date', '<=', date('Y-m-d'))
                ->whereDate('expire_date', '>=', date('Y-m-d'))->get();

            $inHouseProductCount = $inHouseProducts->count();

            $inHouseReviewData = Review::active()->whereIn('product_id', $inHouseProducts->pluck('id'));
            $inHouseReviewDataCount = $inHouseReviewData->count();
            $inHouseRattingStatusPositive = 0;
            foreach ($inHouseReviewData->pluck('rating') as $singleRating) {
                ($singleRating >= 4 ? ($inHouseRattingStatusPositive++) : '');
            }

            $inHouseShop = $this->getInHouseShopObject();
            $inHouseShop->id = 0;
            $inHouseShop->products_count = $inHouseProductCount;
            $inHouseShop->coupon_list = $inHouseCoupon;
            $inHouseShop->total_rating = $inHouseReviewDataCount;
            $inHouseShop->review_count = $inHouseReviewDataCount;
            $inHouseShop->average_rating = $inHouseReviewData->avg('rating');
            $inHouseShop->positive_review = $inHouseReviewDataCount != 0 ? ($inHouseRattingStatusPositive * 100) / $inHouseReviewDataCount : 0;
            $inHouseShop->orders_count = Order::where(['seller_is' => 'admin'])->count();
            $inHouseShop->products = Arr::random($inHouseProducts->toArray(), $inHouseProductCount < 3 ? $inHouseProductCount : 3);;
            return $topVendorsList->prepend($inHouseShop);
        });
    }

    public function cacheMostDemandedProductItem()
    {
        return Cache::remember(CACHE_FOR_MOST_DEMANDED_PRODUCT_ITEM, CACHE_FOR_3_HOURS, function () {
            return MostDemanded::where('status', 1)->with(['product' => function ($query) {
                $query->withCount('wishList', 'orderDetails', 'orderDelivered', 'reviews');
            }])->whereHas('product', function ($query) {
                return $query->active();
            })->first();
        });
    }

    public function cacheBannerAllTypeKeys($cacheKey): void
    {
        $cacheKeys = Cache::get(CACHE_BANNER_ALL_CACHE_KEYS, []);
        if (!in_array($cacheKey, $cacheKeys)) {
            $cacheKeys[] = $cacheKey;
            Cache::put(CACHE_BANNER_ALL_CACHE_KEYS, $cacheKeys, CACHE_FOR_3_HOURS);
        }
    }

    public function cacheBannerForTypeMainBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_main_banner_' . ($themeName);
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Main Banner', 'published' => 1, 'theme' => $themeName])->latest()->get();
        });
    }

    public function cacheBannerForTypeSidebarBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_sidebar_banner_' . ($themeName);
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Sidebar Banner', 'published' => 1, 'theme' => $themeName])->latest()->first();
        });
    }

    public function cacheBannerForTypeTopSideBanner()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_top_side_banner_' . ($themeName);
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Top Side Banner', 'published' => 1, 'theme' => $themeName])->orderBy('id', 'desc')->latest()->first();
        });
    }

    public function cacheBannerForTypePromoBannerLeft()
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_top_side_banner_' . ($themeName);
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName) {
            return Banner::where(['banner_type' => 'Promo Banner Left', 'published' => 1, 'theme' => $themeName])->first();
        });
    }

    public function cacheBannerForTypePromoBanner($bannerType)
    {
        $themeName = theme_root_path() ?? 'default';
        $cacheKey = 'cache_banner_type_'. strtolower(str_replace(' ', '_', $bannerType)).'_'. ($themeName);
        $this->cacheBannerAllTypeKeys(cacheKey: $cacheKey);

        return Cache::remember($cacheKey, CACHE_FOR_3_HOURS, function () use ($themeName, $bannerType) {
            return Banner::where(['banner_type' => $bannerType, 'published' => 1, 'theme' => $themeName])->first();
        });
    }

    public function cacheInHouseShopInTemporaryStatus(): void
    {
        $web = $this->cacheBusinessSettingsTable();
        Cache::forget(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);
        $inHouseShopInTemporaryClose = Cache::get(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS);
        if ($inHouseShopInTemporaryClose === null) {
            $inHouseShopInTemporaryClose = getWebConfig(name: 'temporary_close');
            $inHouseShopInTemporaryClose = $inHouseShopInTemporaryClose['status'] ?? 0;
            Cache::put(IN_HOUSE_SHOP_TEMPORARY_CLOSE_STATUS, $inHouseShopInTemporaryClose, (60 * 24));
        }
    }
}
