<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SilverRate;
use App\Models\AdjustedGoldRate;

class SilverRateController extends Controller
{
    public function index()
    {
        $silverRates = SilverRate::all();
        $goldAdjustment = AdjustedGoldRate::first(); // Fetch the first record for adjustment
        return view('admin-views.silver.index', compact('silverRates', 'goldAdjustment'));
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

    public function adjustGoldRate(Request $request)
    {
        $request->validate([
            'adjust_type' => 'required|in:add,subtract',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        // If you want only one record, update or create it like this:
        $adjustedRate = AdjustedGoldRate::updateOrCreate(
            ['id' => 1], // or other unique identifier if needed
            [
                'adjust_type' => $request->adjust_type,
                'amount'      => $request->amount,
            ]
        );

        return redirect()->back()->with('success', 'Gold rate adjusted successfully.');
    }
}
