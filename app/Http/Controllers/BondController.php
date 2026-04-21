<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BondController extends Controller
{
    /**
     * Публичная страница карточки облигации
     */
    public function show($secid)
    {
        $bond = $this->fetchBondData($secid);
        return view('bond', compact('bond', 'secid'));
    }

    /**
     * API для получения данных облигации в формате JSON
     */
    public function apiBond($secid)
    {
        $data = $this->fetchBondData($secid);
        return response()->json($data);
    }

    /**
     * Получение данных с кешированием
     */
    private function fetchBondData($secid)
    {
        $cacheKey = "bond_data_{$secid}";
        // Кешируем на 30 секунд – данные MOEX не меняются каждую секунду
        return Cache::remember($cacheKey, 30, function () use ($secid) {
            return $this->loadBondDataFromMoex($secid);
        });
    }

    /**
     * Реальная загрузка данных из API MOEX (скопировано из вашего welcome.blade.php)
     */
    private function loadBondDataFromMoex($secid)
    {
        $boards = ['TQCB', 'TQBD', 'EQOB', 'TQOB'];
        $result = [
            'secid' => $secid,
            'name' => $secid,
            'price' => null,
            'yield' => null,
            'nkd' => null,
            'maturity' => null,
            'coupon_type' => 'Фиксированный',
            'coupon_value' => null,
            'payments_per_year' => 2,
            'next_coupon' => null,
            'volume' => null,
            'trades' => null,
            'coupons' => [],
        ];

        try {
            // 1. Получаем описание инструмента (нужно для даты погашения)
            $descUrl = "https://iss.moex.com/iss/securities/{$secid}.json?iss.meta=off";
            $descResponse = Http::get($descUrl);
            $descJson = $descResponse->json();

            // 2. Получаем купоны (bondization)
            $couponUrl = "https://iss.moex.com/iss/statistics/engines/stock/markets/bonds/bondization/{$secid}.json?iss.meta=off&iss.only=coupons&coupons.columns=coupondate,couponvalue,couponperiod&limit=100";
            $couponResp = Http::get($couponUrl);
            $allCoupons = []; // массив для хранения купонов (дата, значение)
            if ($couponResp->successful()) {
                $couponJson = $couponResp->json();
                if (isset($couponJson['coupons']['data']) && count($couponJson['coupons']['data']) > 0) {
                    $cols = $couponJson['coupons']['columns'];
                    $dateIdx = array_search('coupondate', $cols);
                    $valueIdx = array_search('couponvalue', $cols);
                    foreach ($couponJson['coupons']['data'] as $row) {
                        $allCoupons[] = [
                            'date' => $row[$dateIdx],
                            'value' => $row[$valueIdx] ? (float)$row[$valueIdx] : null,
                        ];
                    }
                }
            }
            $result['coupons'] = $allCoupons;

            // 3. Перебираем торговые доски
            foreach ($boards as $board) {
                $url = "https://iss.moex.com/iss/engines/stock/markets/bonds/boards/{$board}/securities/{$secid}.json?iss.meta=off&iss.only=marketdata,securities&marketdata.columns=SECID,LAST,LASTCHANGE,LASTCHANGE_PRC,PREVPRICE,OPEN,CLOSE,HIGH,LOW,VALTODAY,VALTODAY_USD,VOLTODAY,NUMTRADES,ACCRUEDINT,YIELD,BOARDID,LCURRENTPRICE,SYSTIME&securities.columns=SECID,PREVPRICE,SHORTNAME,SECNAME,COUPONVALUE,COUPONPERCENT,MATURITYDATE,COUPONDATE,NEXTCOUPON,ACCRUEDINT,CURRENTVALUE";
                $resp = Http::get($url);
                if (!$resp->successful()) continue;
                $json = $resp->json();

                if (!empty($json['marketdata']['data'])) {
                    $marketColumns = $json['marketdata']['columns'];
                    $marketRow = $json['marketdata']['data'][0];
                    $getMarketValue = function ($name) use ($marketColumns, $marketRow) {
                        $idx = array_search($name, $marketColumns);
                        return $idx !== false ? $marketRow[$idx] : null;
                    };

                    // Цена
                    $last = $getMarketValue('LAST');
                    $lcurrent = $getMarketValue('LCURRENTPRICE');
                    if (($last === null || $last === '') && $lcurrent !== null) $last = $lcurrent;
                    if ($last !== null && $last !== '') {
                        $result['price'] = (float)$last * 10;
                    }

                    // Доходность
                    $yield = $getMarketValue('YIELD');
                    $result['yield'] = $yield !== null ? (float)$yield : null;

                    // НКД (ключевое поле)
                    $nkd = $getMarketValue('ACCRUEDINT');
                    $result['nkd'] = $nkd !== null ? (float)$nkd : null;

                    // Объём и сделки
                    $volume = $getMarketValue('VOLTODAY');
                    $result['volume'] = $volume !== null ? (float)$volume : null;
                    $trades = $getMarketValue('NUMTRADES');
                    $result['trades'] = $trades !== null ? (int)$trades : null;
                }

                if (!empty($json['securities']['data'])) {
                    $secColumns = $json['securities']['columns'];
                    $secRow = $json['securities']['data'][0];
                    $getSecValue = function ($name) use ($secColumns, $secRow) {
                        $idx = array_search($name, $secColumns);
                        return $idx !== false ? $secRow[$idx] : null;
                    };

                    $result['name'] = $getSecValue('SHORTNAME') ?? $getSecValue('SECNAME') ?? $secid;

                    // Дата погашения: сначала из securities, потом из описания, потом последний купон
                    $maturity = $getSecValue('MATURITYDATE');
                    if (!$maturity && isset($descJson['description']['data'][0])) {
                        $descData = $descJson['description']['data'][0];
                        $descCols = $descJson['description']['columns'];
                        $matIdx = array_search('MATURITYDATE', $descCols);
                        if ($matIdx !== false) $maturity = $descData[$matIdx];
                    }
                    if (!$maturity && count($allCoupons) > 0) {
                        $lastCouponDate = $allCoupons[count($allCoupons) - 1]['date'];
                        $today = new \DateTime();
                        $yearsDiff = (strtotime($lastCouponDate) - $today->getTimestamp()) / (86400 * 365);
                        if ($yearsDiff <= 30) $maturity = $lastCouponDate;
                    }
                    $result['maturity'] = $maturity;

                    // Размер купона (в рублях) – берём из COUPONVALUE
                    $coupon = $getSecValue('COUPONVALUE');
                    if ($coupon !== null && is_numeric($coupon)) {
                        $result['coupon_value'] = (float)$coupon;
                    } elseif (count($allCoupons) > 0) {
                        // fallback: первый будущий купон
                        $today = new \DateTime();
                        foreach ($allCoupons as $c) {
                            if (strtotime($c['date']) >= $today->getTimestamp() && $c['value'] !== null && $c['value'] < 1000) {
                                $result['coupon_value'] = (float)$c['value'];
                                break;
                            }
                        }
                        if ($result['coupon_value'] === null && $allCoupons[0]['value'] !== null && $allCoupons[0]['value'] < 1000) {
                            $result['coupon_value'] = (float)$allCoupons[0]['value'];
                        }
                    }
                }

                // Если цену нашли – выходим
                if ($result['price'] !== null) break;
            }

            // Тип купона – как в welcome
            if (count($allCoupons) >= 2) {
                $checkCount = min(4, count($allCoupons));
                $firstValue = $allCoupons[0]['value'];
                $allSame = true;
                for ($i = 0; $i < $checkCount; $i++) {
                    if (abs(($allCoupons[$i]['value'] ?? 0) - $firstValue) > 0.01) {
                        $allSame = false;
                        break;
                    }
                }
                $result['coupon_type'] = $allSame ? 'Фиксированный' : 'Плавающий (переменный)';
            }

            // Выплат в год (по первым двум датам купонов)
            if (count($allCoupons) >= 2) {
                $date1 = new \DateTime($allCoupons[0]['date']);
                $date2 = new \DateTime($allCoupons[1]['date']);
                $diffDays = abs($date2->diff($date1)->days);
                if ($diffDays > 0) $result['payments_per_year'] = round(365 / $diffDays);
            }

            // Следующий купон (дата)
            $today = new \DateTime();
            foreach ($allCoupons as $c) {
                if (strtotime($c['date']) >= $today->getTimestamp()) {
                    $result['next_coupon'] = $c['date'];
                    break;
                }
            }

            // Если НКД так и не получен, пробуем взять из securities (как в welcome)
            if ($result['nkd'] === null && isset($json['securities']['data'][0])) {
                $secRow = $json['securities']['data'][0];
                $secCols = $json['securities']['columns'];
                $nkdIdx = array_search('ACCRUEDINT', $secCols);
                if ($nkdIdx !== false && isset($secRow[$nkdIdx])) {
                    $result['nkd'] = (float)$secRow[$nkdIdx];
                }
            }

        } catch (\Exception $e) {
            \Log::error('BondController error: ' . $e->getMessage());
        }

        return $result;
    }
}