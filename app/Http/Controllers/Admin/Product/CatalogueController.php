<?php
namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Catalogue;
use App\Traits\FileManagerTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\Enums\WebConfigKey;

class CatalogueController extends Controller
{
    use FileManagerTrait;

    public function index()
    {
        $catalogues = Catalogue::with('brand')->orderBy('id', 'desc')->get()->paginate(getWebConfig(name: WebConfigKey::PAGINATION_LIMIT));
        return view('admin-views.catalogue.list', compact('catalogues'));
    }
    public function create()
    {
        $language        = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $brands          = Brand::all();
        return view('admin-views.catalogue.add', compact('language', 'defaultLanguage', 'brands'));
    }

    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'brand_id'         => 'required|exists:brands,id',
            'catalogue_name'   => 'required',
            'catalogue_name.*' => 'required|string',
        ], [
            'brand_id.required'         => 'Please select a brand.',
            'brand_id.exists'           => 'The selected brand is invalid.',

            'catalogue_name.required'   => 'Catalogue name is required.',

            'catalogue_name.*.required' => 'Each catalogue name is required.',
        ]);

        Catalogue::create([
            'brand_id' => $request->input('brand_id'),
            'name'     => $request->input('catalogue_name'),
        ]);
        Toastr::success(translate('Catalogue_added_successfully'));
        return redirect()->route('admin.catalogue.list');
    }

    public function edit($id)
    {
        $catalogue = Catalogue::findOrFail($id);
        $language        = getWebConfig(name: 'pnc_language') ?? null;
        $defaultLanguage = $language[0];
        $brands          = Brand::all();
        return view('admin-views.catalogue.edit', compact('catalogue', 'language', 'defaultLanguage', 'brands'));
    }

    public function update(Request $request, $id)
    {
        $catalogue = Catalogue::findOrFail($id);

        // Validate the request data
        $request->validate([
            'brand_id'         => 'required|exists:brands,id',
            'catalogue_name'   => 'required',
            'catalogue_name.*' => 'required|string',
        ], [
            'brand_id.required'         => 'Please select a brand.',
            'brand_id.exists'           => 'The selected brand is invalid.',

            'catalogue_name.required'   => 'Catalogue name is required.',

            'catalogue_name.*.required' => 'Each catalogue name is required.',
        ]);

        $catalogue->update([
            'brand_id' => $request->input('brand_id'),
            'name'     => $request->input('catalogue_name'),
        ]);

        Toastr::success(translate('Catalogue_updated_successfully'));
        return redirect()->route('admin.catalogue.edit', $id);
    }

    public function statusUpdate(Request $request)
    {
        $catalogue = Catalogue::findOrFail($request->id);
        $catalogue->status = $request->status;
        $catalogue->save();

        return response()->json(['success' => 1, 'message' => translate('status_updated_successfully')], 200);
    }

    public function getCatalogues(Request $request)
    {
        $brand_id = $request->get('brand_id');
        $catalogues = Catalogue::where('brand_id', $brand_id)->get();
        return response()->json($catalogues);
    }
}
