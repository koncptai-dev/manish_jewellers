<?php
namespace App\Http\Controllers\Admin;

use App\Enums\WebConfigKey;
use App\Http\Controllers\Controller;
use App\Models\InstallmentPayment;
use Illuminate\Http\Request;

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

        $installments = InstallmentPayment::with([
            'user',
            'details' => function ($q) {
                $q->where('payment_status', 'paid');
            }
        ])
        ->when(! empty($request['searchValue']), function ($query) use ($request) {
            $searchValue = $request['searchValue'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('plan_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('plan_category', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', '%' . $searchValue . '%');
                    });
            });
        })
        ->whereHas('details', function ($query) {
            $query->where('payment_status', 'paid');
        })
        ->select('installment_payments.*')
        ->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));

        return view('admin-views.installment.list', compact('installments'));

    }

    public function show($id)
    {

        // Fetch installment details along with associated payment details

        $installment = InstallmentPayment::select('installment_payments.*', 'subscription_mandates.status as mandate_status', 'subscription_mandates.frequency as mandate_frequency')
        ->leftJoin('subscription_mandates', 'subscription_mandates.installment_id', '=', 'installment_payments.id')
        ->with(['details', 'user'])
        ->findOrFail($id);

        // Return view with data

        return view('admin-views.installment.details', compact('installment'));

    }

}
