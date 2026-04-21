@extends('layouts.app')

@section('title', 'Мой портфель')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold">Портфель</h1>
        <a href="{{ route('market') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-center md:text-left">
            + Добавить облигацию
        </a>
    </div>

    @if($portfolios->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            У вас пока нет облигаций в портфеле.<br>
            <a href="{{ route('market') }}" class="text-blue-600 hover:underline">Перейти на рынок</a> и добавьте первую бумагу.
        </div>
    @else
        <!-- Общая стоимость и общая прибыль -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
            <div class="text-lg opacity-90">Общая стоимость портфеля</div>
            <div class="text-4xl font-bold" id="total-value">0 ₽</div>
            <div class="text-sm opacity-80 mt-1" id="total-profit-info"></div>
        </div>
        <div class="flex flex-wrap gap-3 justify-center md:justify-end">
            <a href="{{ route('market') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100">➕ Добавить</a>
            <a href="{{ route('analytics') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100">📈 Аналитика</a>
            <a href="{{ route('favorites.index') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100">⭐ Избранное</a>
        </div>
    </div>
</div>

        <!-- Таблица портфеля -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm md:text-base">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left py-3 px-2 md:px-4">Название</th>
                        <th class="text-left py-3 px-2 md:px-4">Кол-во</th>
                        <th class="text-left py-3 px-2 md:px-4">Цена покупки</th>
                        <th class="text-left py-3 px-2 md:px-4">Текущая цена</th>
                        <th class="text-left py-3 px-2 md:px-4 hidden sm:table-cell">Доходность</th>
                        <th class="text-left py-3 px-2 md:px-4">Прибыль/убыток</th>
                        <th class="text-left py-3 px-2 md:px-4 hidden md:table-cell">НКД (на 1 шт.)</th>
                        <th class="text-left py-3 px-2 md:px-4">Суммарный НКД</th>
                        <th class="text-left py-3 px-2 md:px-4"></th>
                    </tr>
                </thead>
                <tbody id="portfolio-table-body">
                    @foreach($portfolios as $item)
                    <tr data-secid="{{ $item->bond_secid }}" 
                        data-quantity="{{ $item->quantity }}" 
                        data-purchase="{{ $item->purchase_price }}"
                        data-id="{{ $item->id }}">
                        <td class="py-2 px-2 md:px-4 font-medium">{{ $item->bond_name ?? $item->bond_secid }}</td>
                        <td class="py-2 px-2 md:px-4">{{ $item->quantity }}</td>
                        <td class="py-2 px-2 md:px-4 purchase-price">{{ number_format($item->purchase_price, 2) }} ₽</td>
                        <td class="py-2 px-2 md:px-4 current-price">—</td>
                        <td class="py-2 px-2 md:px-4 yield hidden sm:table-cell">—</td>
                        <td class="py-2 px-2 md:px-4 profit">—</td>
                        <td class="py-2 px-2 md:px-4 nkd hidden md:table-cell">—</td>
                        <td class="py-2 px-2 md:px-4 total-nkd">—</td>
                        <td class="py-2 px-2 md:px-4">
                            <form action="{{ route('portfolio.destroy', $item) }}" method="POST" onsubmit="return confirm('Удалить эту позицию?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700">✖</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function formatNumber(num) {
        if (num === null || isNaN(num)) return '0';
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    async function loadPortfolioData() {
        const rows = document.querySelectorAll('#portfolio-table-body tr');
        if (rows.length === 0) return;
        
        let totalValue = 0;
        let totalCost = 0;
        
        for (const row of rows) {
            const secid = row.dataset.secid;
            const quantity = parseInt(row.dataset.quantity);
            const purchasePrice = parseFloat(row.dataset.purchase);
            const purchaseTotal = purchasePrice * quantity;
            totalCost += purchaseTotal;
            
            try {
                const response = await fetch(`/api/bond/${secid}`);
                if (!response.ok) throw new Error('API error');
                const data = await response.json();
                
                const currentPrice = data.price ? parseFloat(data.price) : 0;
                const currentTotal = currentPrice * quantity;
                const profit = currentTotal - purchaseTotal;
                const profitPercent = purchaseTotal > 0 ? (profit / purchaseTotal) * 100 : 0;
                
                // Текущая цена
                row.querySelector('.current-price').innerHTML = currentPrice ? formatNumber(currentPrice) + ' ₽' : '—';
                
                // Доходность
                const yieldCell = row.querySelector('.yield');
                if (yieldCell) yieldCell.innerHTML = data.yield ? parseFloat(data.yield).toFixed(2) + '%' : '—';
                
                // Прибыль/убыток с цветом
                const profitCell = row.querySelector('.profit');
                const profitClass = profit >= 0 ? 'text-green-600' : 'text-red-600';
                profitCell.innerHTML = `<span class="${profitClass} font-semibold">${profit >= 0 ? '+' : ''}${formatNumber(profit)} ₽ (${profitPercent.toFixed(2)}%)</span>`;
                
                // НКД на одну облигацию
                const nkd = data.nkd ? parseFloat(data.nkd) : 0;
                const nkdCell = row.querySelector('.nkd');
                if (nkdCell) nkdCell.innerHTML = nkd ? formatNumber(nkd) + ' ₽' : '—';
                
                // Суммарный НКД = НКД на одну * количество
                const totalNkd = nkd * quantity;
                const totalNkdCell = row.querySelector('.total-nkd');
                totalNkdCell.innerHTML = totalNkd ? formatNumber(totalNkd) + ' ₽' : '—';
                
                totalValue += currentTotal;
            } catch (error) {
                console.error(`Ошибка загрузки ${secid}:`, error);
                row.querySelector('.current-price').innerHTML = 'ошибка';
            }
        }
        
        // Общая стоимость портфеля
        document.getElementById('total-value').innerHTML = formatNumber(totalValue) + ' ₽';
        const totalProfit = totalValue - totalCost;
        const totalProfitPercent = totalCost > 0 ? (totalProfit / totalCost) * 100 : 0;
        const profitColor = totalProfit >= 0 ? 'text-green-300' : 'text-red-300';
        const sign = totalProfit >= 0 ? '+' : '';
        document.getElementById('total-profit-info').innerHTML = `<span class="${profitColor}">${sign}${formatNumber(totalProfit)} ₽ (${totalProfitPercent.toFixed(2)}%)</span>`;
    }
    
    loadPortfolioData();
    setInterval(loadPortfolioData, 10000);
</script>
@endpush