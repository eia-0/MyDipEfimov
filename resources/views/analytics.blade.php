@extends('layouts.app')

@section('title', 'Аналитика портфеля')

@section('content')
<div class="max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Аналитика портфеля</h1>

    @if($portfolios->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
            У вас пока нет облигаций в портфеле. Добавьте бумаги, чтобы увидеть аналитику.
        </div>
    @else


        <!-- круг -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Структура портфеля<br>(по текущей стоимости)</h2>
                <canvas id="pieChart" height="250" style="max-height: 300px;"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Доли облигаций в портфеле</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b"><tr><th>Облигация</th><th>Кол-во</th><th>Текущая цена</th><th>Стоимость</th><th>Доля</th></tr></thead>
                        <tbody id="shares-table-body"><tr><td colspan="5" class="text-center py-4">Загрузка...</td></table></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Блок с доходностью и выплатами -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <div class="text-xs text-gray-600">Прибыль/убыток</div>
                    <div class="text-lg font-bold" id="total-profit-rub">0 ₽</div>
                    <div class="text-xs" id="total-profit-percent">0%</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 1 месяц</div>
                    <div class="text-lg font-bold" id="payout-1m">0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 6 месяцев</div>
                    <div class="text-lg font-bold" id="payout-6m">0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 1 год</div>
                    <div class="text-lg font-bold" id="payout-1y">0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 2 года</div>
                    <div class="text-lg font-bold" id="payout-2y">0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 3 года</div>
                    <div class="text-lg font-bold" id="payout-3y">0 ₽</div>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-3 text-sm text-gray-600">
                <span>За 4 года: <strong id="payout-4y">0 ₽</strong></span>
                <span>За 5 лет: <strong id="payout-5y">0 ₽</strong></span>
            </div>
        </div>

        <!-- График выплат по месяцам -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Прогноз купонных выплат по месяцам</h2>
            <canvas id="couponChart" height="300" style="max-height: 400px;"></canvas>
            <p class="text-sm text-gray-500 mt-4 text-center">* Сумма = (размер купона) × (количество бумаг). Учитываются только будущие выплаты.</p>
        </div>

        <!-- Таблица ближайших купонов -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-3">Ближайшие купонные выплаты</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b"><tr><th>Облигация</th><th>Кол-во</th><th>Размер купона</th><th>Дата выплаты</th><th>Сумма к выплате</th></tr></thead>
                    <tbody id="details-table-body"><tr><td colspan="5" class="text-center py-4">Загрузка...</td></tr></tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const portfolioItems = @json($portfolios);
    let pieChart = null, monthlyChart = null, historyChart = null;
    let currentHistoryPeriod = 'month';

    function formatNumber(num) {
        if (num === null || isNaN(num)) return '0';
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    async function loadAnalytics() {
        if (portfolioItems.length === 0) return;

        // стоимость портфеля
        let totalPortfolioValue = 0;
        let totalCost = 0;
        const bondShares = [];
        const monthlyMap = new Map();
        const detailsRows = [];

        const today = new Date(); today.setHours(0,0,0,0);
        const end1m = new Date(today); end1m.setMonth(today.getMonth()+1);
        const end6m = new Date(today); end6m.setMonth(today.getMonth()+6);
        const end1y = new Date(today); end1y.setFullYear(today.getFullYear()+1);
        const end2y = new Date(today); end2y.setFullYear(today.getFullYear()+2);
        const end3y = new Date(today); end3y.setFullYear(today.getFullYear()+3);
        const end4y = new Date(today); end4y.setFullYear(today.getFullYear()+4);
        const end5y = new Date(today); end5y.setFullYear(today.getFullYear()+5);

        let payouts1m=0, payouts6m=0, payouts1y=0, payouts2y=0, payouts3y=0, payouts4y=0, payouts5y=0;

        for (const item of portfolioItems) {
            const secid = item.bond_secid;
            const quantity = item.quantity;
            const bondName = item.bond_name ?? secid;
            const purchasePrice = parseFloat(item.purchase_price);
            totalCost += purchasePrice * quantity;

            try {
                const resp = await fetch(`/api/bond/${secid}`);
                if (!resp.ok) throw new Error('API error');
                const data = await resp.json();

                const currentPrice = data.price ? parseFloat(data.price) : 0;
                const currentTotal = currentPrice * quantity;
                totalPortfolioValue += currentTotal;

                bondShares.push({ name: bondName, quantity, currentPrice, totalValue: currentTotal });

                let couponValue = data.coupon_value ? parseFloat(data.coupon_value) : null;
                if (!couponValue && data.coupons?.length) {
                    for (const c of data.coupons) {
                        let v = parseFloat(c.value);
                        if (!isNaN(v) && v < 1000) { couponValue = v; break; }
                    }
                }
                if (!couponValue) couponValue = 0;

                const coupons = data.coupons || [];
                let nextDate = null, nextSum = null;
                for (const coupon of coupons) {
                    const couponDate = new Date(coupon.date);
                    if (couponDate >= today) {
                        if (!nextDate || couponDate < new Date(nextDate)) {
                            nextDate = coupon.date;
                            nextSum = couponValue * quantity;
                        }
                        const amount = couponValue * quantity;
                        const monthKey = `${couponDate.getFullYear()}-${String(couponDate.getMonth()+1).padStart(2,'0')}`;
                        monthlyMap.set(monthKey, (monthlyMap.get(monthKey) || 0) + amount);

                        if (couponDate <= end1m) payouts1m += amount;
                        if (couponDate <= end6m) payouts6m += amount;
                        if (couponDate <= end1y) payouts1y += amount;
                        if (couponDate <= end2y) payouts2y += amount;
                        if (couponDate <= end3y) payouts3y += amount;
                        if (couponDate <= end4y) payouts4y += amount;
                        if (couponDate <= end5y) payouts5y += amount;
                    }
                }
                detailsRows.push({ name: bondName, quantity, couponValue, nextDate, sumByPortfolio: nextSum });
            } catch(e) {
                console.error(secid, e);
                bondShares.push({ name: bondName, quantity, currentPrice: 0, totalValue: 0 });
                detailsRows.push({ name: bondName, quantity, couponValue: 'error', nextDate: null, sumByPortfolio: null });
            }
        }

        
        if (totalPortfolioValue > 0) {
            await fetch('/api/portfolio-history', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ total_value: totalPortfolioValue })
            });
        }

        // --- Круговая диаграмма и доли ---
        bondShares.forEach(s => s.percent = totalPortfolioValue > 0 ? (s.totalValue/totalPortfolioValue)*100 : 0);
        bondShares.sort((a,b) => b.percent - a.percent);
        document.getElementById('shares-table-body').innerHTML = bondShares.map(s => `
            <tr class="border-b"><td class="py-2">${s.name}</td><td class="py-2">${s.quantity}</td><td class="py-2">${s.currentPrice ? formatNumber(s.currentPrice)+' ₽' : '—'}</td>
            <td class="py-2">${formatNumber(s.totalValue)} ₽</td><td class="font-semibold">${s.percent.toFixed(2)}%</td></tr>
        `).join('');
        if (pieChart) pieChart.destroy();
        pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie', data: { labels: bondShares.map(s=>s.name), datasets: [{ data: bondShares.map(s=>s.totalValue), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec489a','#06b6d4','#84cc16','#f97316','#6366f1'] }] },
            options: { responsive: true, plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatNumber(ctx.raw)} ₽ (${((ctx.raw/totalPortfolioValue)*100).toFixed(2)}%)` } }, legend: { position: 'right', labels: { boxWidth: 12 } } } }
        });

        // Прибыль
        const profit = totalPortfolioValue - totalCost;
        const profitPercent = totalCost > 0 ? (profit/totalCost)*100 : 0;
        document.getElementById('total-profit-rub').innerHTML = `${profit>=0?'+':''}${formatNumber(profit)} ₽`;
        document.getElementById('total-profit-percent').innerHTML = `<span class="${profit>=0?'text-green-600':'text-red-600'}">${profit>=0?'+':''}${profitPercent.toFixed(2)}%</span>`;

        // Выплаты
        document.getElementById('payout-1m').innerHTML = formatNumber(payouts1m)+' ₽';
        document.getElementById('payout-6m').innerHTML = formatNumber(payouts6m)+' ₽';
        document.getElementById('payout-1y').innerHTML = formatNumber(payouts1y)+' ₽';
        document.getElementById('payout-2y').innerHTML = formatNumber(payouts2y)+' ₽';
        document.getElementById('payout-3y').innerHTML = formatNumber(payouts3y)+' ₽';
        document.getElementById('payout-4y').innerHTML = formatNumber(payouts4y)+' ₽';
        document.getElementById('payout-5y').innerHTML = formatNumber(payouts5y)+' ₽';

        // График купонов по месяцам
        const months = Array.from(monthlyMap.keys()).sort();
        const ctx = document.getElementById('couponChart').getContext('2d');
        if (monthlyChart) monthlyChart.destroy();
        if (months.length) {
            monthlyChart = new Chart(ctx, {
                type: 'bar', data: { labels: months.map(m=>`${m.split('-')[1]}.${m.split('-')[0]}`), datasets: [{ label: 'Сумма купонов (₽)', data: months.map(m=>monthlyMap.get(m)), backgroundColor: '#3b82f6', borderRadius: 8 }] },
                options: { responsive: true, plugins: { tooltip: { callbacks: { label: (ctx) => formatNumber(ctx.raw)+' ₽' } } }, scales: { y: { beginAtZero: true, ticks: { callback: (v) => formatNumber(v)+' ₽' } } } }
            });
        } else { ctx.fillStyle='#999'; ctx.fillText('Нет будущих выплат',50,200); }

        // Таблица ближайших купонов
        document.getElementById('details-table-body').innerHTML = detailsRows.map(r => {
            if (r.couponValue === 'error') return `<tr><td class="py-2">${r.name}</td><td class="py-2">${r.quantity}</td><td colspan="3" class="text-red-500">Ошибка</td></tr>`;
            if (!r.nextDate) return `<tr><td class="py-2">${r.name}</td><td class="py-2">${r.quantity}</td><td colspan="3" class="text-gray-400">Нет данных</td></tr>`;
            return `<tr><td class="py-2">${r.name}</td><td class="py-2">${r.quantity}</td><td class="py-2">${formatNumber(r.couponValue)} ₽</td><td class="py-2">${new Date(r.nextDate).toLocaleDateString('ru-RU')}</td><td class="font-semibold">${formatNumber(r.sumByPortfolio)} ₽</td></tr>`;
        }).join('');
    }

    // Загрузка истории и отрисовка графика
    async function loadHistory(period = 'month') {
        const resp = await fetch(`/api/portfolio-history?period=${period}`);
        const data = await resp.json();
        const labels = data.map(item => item.date);
        const values = data.map(item => parseFloat(item.total_value));
        const ctx = document.getElementById('historyChart').getContext('2d');
        if (historyChart) historyChart.destroy();
        historyChart = new Chart(ctx, {
            type: 'line',
            data: { labels, datasets: [{ label: 'Стоимость портфеля (₽)', data: values, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.2 }] },
            options: { responsive: true, plugins: { tooltip: { callbacks: { label: (ctx) => formatNumber(ctx.raw) + ' ₽' } } }, scales: { y: { beginAtZero: false, ticks: { callback: (v) => formatNumber(v) + ' ₽' } } } }
        });
    }

    // Переключатель периода (нет)
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('bg-blue-500', 'text-white'));
            btn.classList.add('bg-blue-500', 'text-white');
            currentHistoryPeriod = btn.dataset.period;
            loadHistory(currentHistoryPeriod);
        });
    });

    loadAnalytics().then(() => loadHistory('month'));
</script>
@endpush