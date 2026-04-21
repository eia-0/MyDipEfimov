<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Отображение главной страницы портфеля
     */
    public function index()
    {
        $portfolios = Portfolio::where('user_id', auth()->id())->get();
        return view('dashboard', compact('portfolios'));
    }

    /**
     * Сохранить текущую стоимость портфеля в историю (если ещё не сохраняли сегодня)
     * Возвращает JSON
     */
    public function savePortfolioValue()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Проверяем, есть ли уже запись за сегодня
        $exists = PortfolioHistory::where('user_id', $user->id)
                    ->whereDate('created_at', $today)
                    ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Сегодня уже сохранено']);
        }

        // Получаем текущую стоимость портфеля
        $portfolios = Portfolio::where('user_id', $user->id)->get();
        $totalValue = 0;

        foreach ($portfolios as $item) {
            // Запрашиваем актуальную цену через API (можно использовать кеш из BondController)
            $secid = $item->bond_secid;
            $quantity = $item->quantity;
            try {
                // Используем наш API маршрут
                $response = Http::get(route('api.bond', $secid));
                if ($response->successful()) {
                    $data = $response->json();
                    $price = $data['price'] ?? 0;
                    $totalValue += $price * $quantity;
                } else {
                    // Если API недоступен, используем цену покупки (худший вариант)
                    $totalValue += $item->purchase_price * $quantity;
                }
            } catch (\Exception $e) {
                $totalValue += $item->purchase_price * $quantity;
            }
        }

        // Сохраняем
        PortfolioHistory::create([
            'user_id' => $user->id,
            'value' => $totalValue,
        ]);

        return response()->json(['success' => true, 'value' => $totalValue, 'date' => $today]);
    }

    /**
     * Получить историю стоимости портфеля за указанное количество дней
     * По умолчанию 90 дней
     */
    public function getPortfolioHistory(Request $request)
    {
        $days = $request->get('days', 90);
        $user = auth()->user();

        $history = PortfolioHistory::where('user_id', $user->id)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'asc')
                    ->get(['value', 'created_at']);

        $formatted = $history->map(function ($item) {
            return [
                'date' => $item->created_at->toDateString(),
                'value' => $item->value,
            ];
        });

        return response()->json($formatted);
    }
}