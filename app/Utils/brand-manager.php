<?php

namespace App\Utils;

use App\Utils\Helpers;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class BrandManager
{
    public static function get_brands()
    {
        return Brand::withCount('brandProducts')->latest()->get();
    }

    public static function get_products($brand_id, $request = null)
    {
        $user = Helpers::getCustomerInformation($request);

        $products = Product::active()
            ->withCount(['reviews', 'wishList' => function ($query) use ($user) {
                $query->where('customer_id', $user != 'offline' ? $user->id : '0');
            }])
            ->where(['brand_id' => $brand_id])
            ->get();

        return Helpers::product_data_formatting($products, true);
    }

    public static function getActiveBrandWithCountingAndPriorityWiseSorting()
    {
        return Cache::remember(CACHE_ACTIVE_BRANDS_WITH_COUNTING_AND_PRIORITY, now()->addMinutes(60), function () {
            $brandList = Brand::active()->withCount(['brandProducts' => function ($query) {
                $query->active();
            }]);
            return self::getPriorityWiseBrandProductsQuery(query: $brandList);
        });
    }

    public static function getPriorityWiseBrandProductsQuery($query)
    {
        $brandProductSortBy = getWebConfig(name: 'brand_list_priority');
        if ($brandProductSortBy && ($brandProductSortBy['custom_sorting_status'] == 1)) {
            if ($brandProductSortBy['sort_by'] == 'most_order') {
                return $query->with(['brandProducts' => function ($query) {
                    return $query->active()->withCount('orderDetails');
                }])->get()->map(function ($brand) {
                    $brand['order_count'] = $brand?->brandProducts?->sum('order_details_count') ?? 0;
                    return $brand;
                })->sortByDesc('order_count');
            } elseif ($brandProductSortBy['sort_by'] == 'latest_created') {
                return $query->latest()->get();
            } elseif ($brandProductSortBy['sort_by'] == 'first_created') {
                return $query->orderBy('id', 'asc')->get();
            } elseif ($brandProductSortBy['sort_by'] == 'a_to_z') {
                return $query->orderBy('name', 'asc')->get();
            } elseif ($brandProductSortBy['sort_by'] == 'z_to_a') {
                return $query->orderBy('name', 'desc')->get();
            }
        }

        return $query->latest()->get();
    }
}
