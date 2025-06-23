<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SilverRate;

class SilverRateController extends Controller
{
    public function index()
    {
        $silverRates = SilverRate::all();
        return view('admin-views.silver.index', compact('silverRates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // 'metal' => 'required|string',
            // 'currency' => 'required|string',
            'price' => 'required|numeric',
        ]);
    
        SilverRate::create($request->only(['metal', 'currency', 'price']));
    
        return redirect()->route('admin.silver.index')->with('success', 'Silver rate added successfully!');
    }
    
    public function edit($id)
{
    $silverRate = SilverRate::findOrFail($id);
    return view('admin-views.silver.edit', compact('silverRate')); // Update to 'edit' view
}


    public function update(Request $request, $id)
    {
        $request->validate([
            'metal' => 'required|string',
            'currency' => 'required|string',
            'price' => 'required|numeric',
        ]);
    
        $silverRate = SilverRate::findOrFail($id);
        $silverRate->update($request->only(['metal', 'currency', 'price']));
    
        return redirect()->route('admin.silver.index')->with('success', 'Silver rate updated successfully!');
    }
    
    public function destroy($id)
    {
        $silverRate = SilverRate::findOrFail($id);
        $silverRate->delete();
    
        return redirect()->route('admin.silver.index')->with('success', 'Silver rate deleted successfully!');
    }
}
