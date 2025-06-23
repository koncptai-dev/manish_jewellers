<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use App\Models\User;
use App\Models\SilverRate;
// use App\Contracts\Repositories\AttributeRepositoryInterface;
// use App\Contracts\Repositories\AuthorRepositoryInterface;
// use App\Contracts\Repositories\BannerRepositoryInterface;
// use App\Contracts\Repositories\BrandRepositoryInterface;
// use App\Contracts\Repositories\CartRepositoryInterface;
// use App\Contracts\Repositories\CategoryRepositoryInterface;
// use App\Contracts\Repositories\ColorRepositoryInterface;
// use App\Contracts\Repositories\DealOfTheDayRepositoryInterface;
// use App\Contracts\Repositories\DigitalProductAuthorRepositoryInterface;
// use App\Contracts\Repositories\DigitalProductVariationRepositoryInterface;
// use App\Contracts\Repositories\FlashDealProductRepositoryInterface;
use App\Contracts\Repositories\InstallmentRepositoryInterface;
// use App\Contracts\Repositories\CustomerRepositoryInterface;
// use App\Contracts\Repositories\ProductSeoRepositoryInterface;
// use App\Contracts\Repositories\PublishingHouseRepositoryInterface;
// use App\Contracts\Repositories\RestockProductCustomerRepositoryInterface;
// use App\Contracts\Repositories\RestockProductRepositoryInterface;
// use App\Contracts\Repositories\ReviewRepositoryInterface;
// use App\Contracts\Repositories\TranslationRepositoryInterface;
// use App\Contracts\Repositories\VendorRepositoryInterface;
// use App\Contracts\Repositories\WishlistRepositoryInterface;
use App\Enums\WebConfigKey;

class PaymentHistoryController extends Controller
{
    public function __construct(
        // private readonly AuthorRepositoryInterface                  $authorRepo,
        // private readonly DigitalProductAuthorRepositoryInterface    $digitalProductAuthorRepo,
        // private readonly PublishingHouseRepositoryInterface         $publishingHouseRepo,
        // private readonly CategoryRepositoryInterface                $categoryRepo,
        // private readonly BrandRepositoryInterface                   $brandRepo,
        // private readonly InstallmentRepositoryInterface             $installmentRepo,
        // private readonly CustomerRepositoryInterface                $customerRepo,
        // private readonly RestockProductRepositoryInterface          $restockProductRepo,
        // private readonly RestockProductCustomerRepositoryInterface  $restockProductCustomerRepo,
        // private readonly DigitalProductVariationRepositoryInterface $digitalProductVariationRepo,
        // private readonly ProductSeoRepositoryInterface              $productSeoRepo,
        // private readonly VendorRepositoryInterface                  $sellerRepo,
        // private readonly ColorRepositoryInterface                   $colorRepo,
        // private readonly AttributeRepositoryInterface               $attributeRepo,
        // private readonly TranslationRepositoryInterface             $translationRepo,
        // private readonly CartRepositoryInterface                    $cartRepo,
        // private readonly WishlistRepositoryInterface                $wishlistRepo,
        // private readonly FlashDealProductRepositoryInterface        $flashDealProductRepo,
        // private readonly DealOfTheDayRepositoryInterface            $dealOfTheDayRepo,
        // private readonly ReviewRepositoryInterface                  $reviewRepo,
        // private readonly BannerRepositoryInterface                  $bannerRepo,
    ) {}

    // public function index(Request $request)
    // {
    //     // $silverRates = SilverRate::all();
    //     // Fetching all the installment payment data with related details and user information
    //     $installments = InstallmentPayment::with('details')->get();
    //     // ->get()
    //     // ->map(function ($installment) {
    //     //     // Fetching the user's first name based on the user_id
    //     //     $user = User::find($installment->user_id);
    //     //     $installment->user_name = $user ? $user->f_name : 'Unknown'; // Add user name to the installment
    //     //     return $installment;
    //     // });
    //     // $installments = InstallmentPaymentResource::collection($installments);

    //     // Returning the 'payment_history' view within the 'admin-views' folder
    //     // return view('admin-views.payment-history', compact('installments', 'silverRates'));


    //     // $products = $this->installmentRepo->getListWhere(orderBy: ['id' => 'desc'], searchValue: $request['searchValue'], filters: $filters, dataLimit: getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));

    //     $installmentPayments = InstallmentPayment::with('details')

    //         ->when(!empty($request['searchValue']), function ($query) use ($request) {
    //             // Apply search value dynamically
    //             $query->where('column_name', 'like', '%' . $request['searchValue'] . '%'); // Replace 'column_name' with the appropriate column
    //         })
    //         ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)); // Apply pagination


    //     return view("admin-views.product.list", compact(
    //         'installmentPayments'
    //     ));
    // }
    public function index(Request $request)
    {
        $installments = InstallmentPayment::with(['details', 'user'])
            ->when(!empty($request['searchValue']), function ($query) use ($request) {
                $searchValue = $request['searchValue'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('plan_code', 'like', '%' . $searchValue . '%')
                        ->orWhere('plan_category', 'like', '%' . $searchValue . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                            $userQuery->where('name', 'like', '%' . $searchValue . '%');
                        });
                });
            })
            ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT)); // Pagination limit

        return view('admin-views.installment.list', compact('installments'));
    }
}
