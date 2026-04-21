<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    /**
     * Поиск облигаций по названию или ISIN (для автодополнения)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $url = "https://iss.moex.com/iss/securities.json?q=" . urlencode($query) . 
               "&group_by=group&group_list=bonds&is_trading=1&limit=20";

        try {
            $response = Http::get($url);
            if (!$response->successful()) {
                return response()->json([]);
            }

            $json = $response->json();
            $suggestions = [];

            if (isset($json['securities']['data'])) {
                $cols = $json['securities']['columns'];
                $secidIdx = array_search('secid', $cols);
                $nameIdx = array_search('name', $cols);
                $shortnameIdx = array_search('shortname', $cols);

                foreach ($json['securities']['data'] as $row) {
                    $secid = $row[$secidIdx];
                    $name = $row[$nameIdx] ?? $row[$shortnameIdx] ?? $secid;
                    $suggestions[] = [
                        'secid' => $secid,
                        'name'  => $name,
                    ];
                }
            }

            return response()->json($suggestions);
        } catch (\Exception $e) {
            // Логирование ошибки при необходимости
            return response()->json([]);
        }
    }
}