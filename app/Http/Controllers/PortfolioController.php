<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PortfolioController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'bond_secid' => 'required|string',
        'bond_name'  => 'nullable|string',
        'quantity'   => 'required|integer|min:1',
        'purchase_price' => 'required|numeric|min:0',
        'purchase_date'  => 'required|date',
    ]);

    Portfolio::create([
        'user_id' => auth()->id(),
        'bond_secid' => $request->bond_secid,
        'bond_name'  => $request->bond_name,
        'quantity'   => $request->quantity,
        'purchase_price' => $request->purchase_price,
        'purchase_date'  => $request->purchase_date,
    ]);

    // Всегда возвращаем JSON для API-подобных запросов
    if ($request->ajax() || $request->wantsJson() || $request->is('api/*')) {
        return response()->json(['success' => true, 'message' => 'Добавлено в портфель']);
    }

    return redirect()->route('dashboard')->with('success', 'Облигация добавлена в портфель');
}


    public function destroy(Portfolio $portfolio)
    {
        if ($portfolio->user_id !== auth()->id()) abort(403);
        $portfolio->delete();
        return redirect()->route('dashboard')->with('success', 'Позиция удалена');
    }
}