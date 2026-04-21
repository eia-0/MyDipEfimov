<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = Favorite::where('user_id', auth()->id())->get();
        return view('favorites', compact('favorites'));
    }

    public function store(Request $request)
{
    $request->validate([
        'bond_secid' => 'required|string',
        'bond_name'  => 'nullable|string',
    ]);

    Favorite::firstOrCreate([
        'user_id' => auth()->id(),
        'bond_secid' => $request->bond_secid,
    ], [
        'bond_name' => $request->bond_name,
    ]);

    // Если запрос ожидает JSON (например, fetch)
    if ($request->wantsJson() || $request->ajax()) {
        return response()->json(['success' => true, 'message' => 'Добавлено в избранное']);
    }

    // Обычная форма – редирект
    return redirect()->back()->with('success', 'Добавлено в избранное');
}

    public function destroy(Favorite $favorite)
    {
        if ($favorite->user_id !== auth()->id()) abort(403);
        $favorite->delete();
        return back()->with('success', 'Удалено из избранного');
    }
}