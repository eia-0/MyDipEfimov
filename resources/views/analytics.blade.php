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

        <!-- Блок с дополнительной статистикой (НКД, YTM, количество) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <div class="text-xs text-gray-500">Суммарный НКД на данный момент</div>
                <div class="text-xl font-bold text-blue-600" id="total-nkd-all">0 ₽</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <div class="text-xs text-gray-500">Средневзвешенная YTM</div>
                <div class="text-xl font-bold text-green-600" id="avg-ytm">0%</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <div class="text-xs text-gray-500">Количество бумаг (шт.)</div>
                <div class="text-xl font-bold text-purple-600" id="total-quantity">0</div>
            </div>
            <div class="bg-white rounded-lg shadow p-3 text-center">
                <div class="text-xs text-gray-500">Выпусков</div>
                <div class="text-xl font-bold text-orange-600" id="unique-issues">0</div>
            </div>
        </div>

        <!-- Круговая диаграмма и доли -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Структура портфеля (по текущей стоимости)</h2>
                <canvas id="pieChart" height="250" style="max-height: 300px;"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">Доли облигаций в портфеле</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b"><tr><th class="whitespace-nowrap">Облигация</th><th class="whitespace-nowrap">Кол-во</th><th class="whitespace-nowrap">Текущая цена</th><th class="whitespace-nowrap">Стоимость</th><th class="whitespace-nowrap">Доля</th></tr></thead>
                        <tbody id="shares-table-body"><tr><td colspan="5" class="text-center py-4">Загрузка...</table></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Блок с доходностью, выплатами и выбором НДФЛ -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
                <div class="text-lg font-semibold">Прогноз купонных выплат и налоги</div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Ставка НДФЛ:</span>
                    <select id="ndfl-rate" class="border rounded px-3 py-1 text-sm">
                        <option value="13">13%</option>
                        <option value="15">15%</option>
                        <option value="18">18%</option>
                        <option value="20">20%</option>
                        <option value="22">22%</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <div class="text-xs text-gray-600">Прибыль/убыток</div>
                    <div class="text-lg font-bold" id="total-profit-rub">0 ₽</div>
                    <div class="text-xs" id="total-profit-percent">0%</div>
                    <div class="text-xs text-gray-500 mt-1" id="total-profit-tax"></div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 1 месяц</div>
                    <div class="text-lg font-bold" id="payout-1m">0 ₽</div>
                    <div class="text-xs text-gray-500" id="tax-1m">налог: 0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 6 месяцев</div>
                    <div class="text-lg font-bold" id="payout-6m">0 ₽</div>
                    <div class="text-xs text-gray-500" id="tax-6m">налог: 0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 1 год</div>
                    <div class="text-lg font-bold" id="payout-1y">0 ₽</div>
                    <div class="text-xs text-gray-500" id="tax-1y">налог: 0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 2 года</div>
                    <div class="text-lg font-bold" id="payout-2y">0 ₽</div>
                    <div class="text-xs text-gray-500" id="tax-2y">налог: 0 ₽</div>
                </div>
                <div class="p-2 bg-green-50 rounded-lg">
                    <div class="text-xs text-gray-600">За 3 года</div>
                    <div class="text-lg font-bold" id="payout-3y">0 ₽</div>
                    <div class="text-xs text-gray-500" id="tax-3y">налог: 0 ₽</div>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-3 text-sm text-gray-600">
                <span>За 4 года: <strong id="payout-4y">0 ₽</strong> <span class="text-xs text-gray-500" id="tax-4y">(налог: 0 ₽)</span></span>
                <span>За 5 лет: <strong id="payout-5y">0 ₽</strong> <span class="text-xs text-gray-500" id="tax-5y">(налог: 0 ₽)</span></span>
            </div>
        </div>

        <!-- График выплат по месяцам -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Прогноз купонных выплат по месяцам (₽)</h2>
            <canvas id="couponChart" height="300" style="max-height: 400px;"></canvas>
            <p class="text-sm text-gray-500 mt-4 text-center">* Сумма = (размер купона) × (количество бумаг). Учитываются только будущие выплаты.</p>
        </div>

        <!-- Тепловая карта купонных выплат -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Тепловая карта купонных выплат (₽)</h2>
            <div class="overflow-x-auto">
                <table id="heatmap-table" class="w-full text-xs text-center border-collapse">
                    <thead><tr id="heatmap-header"></td></thead>
                    <tbody id="heatmap-body"></tbody>
                </table>
            </div>
            <p class="text-sm text-gray-500 mt-2 text-center">* Чем темнее цвет, тем выше сумма выплат. Данные за месяц показаны в одной строке.</p>
        </div>

        <!-- Таблица ближайших купонов (адаптивная, с уплотнёнными отступами) -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-3">Ближайшие купонные выплаты</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="whitespace-nowrap px-1 py-2 text-left">Облигация</th>
                            <th class="whitespace-nowrap px-1 py-2 text-left">Кол-во</th>
                            <th class="whitespace-nowrap px-1 py-2 text-left">Размер купона</th>
                            <th class="whitespace-nowrap px-1 py-2 text-left">Дата выплаты</th>
                            <th class="whitespace-nowrap px-1 py-2 text-left">Сумма к выплате</th>
                        </tr>
                    </thead>
                    <tbody id="details-table-body">
                        <tr><td colspan="5" class="text-center py-4">Загрузка...</tr>
                    </tbody>
                <tr>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const portfolioItems = @json($portfolios);
    let pieChart = null, monthlyChart = null;
    
    let payoutsRaw = { 1:0, 6:0, 12:0, 24:0, 36:0, 48:0, 60:0 };
    let totalProfitRaw = 0;
    let totalCostRaw = 0;

    function formatNumber(num) {
        if (num === null || isNaN(num)) return '0';
        return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function updateTaxes() {
        const rate = parseFloat(document.getElementById('ndfl-rate').value);
        if (totalProfitRaw > 0) {
            const taxProfit = totalProfitRaw * rate / 100;
            document.getElementById('total-profit-tax').innerHTML = `налог: ${formatNumber(taxProfit)} ₽`;
        } else {
            document.getElementById('total-profit-tax').innerHTML = `налог: 0 ₽ (убыток)`;
        }
        const taxIds = ['tax-1m', 'tax-6m', 'tax-1y', 'tax-2y', 'tax-3y', 'tax-4y', 'tax-5y'];
        const rawValues = [payoutsRaw[1], payoutsRaw[6], payoutsRaw[12], payoutsRaw[24], payoutsRaw[36], payoutsRaw[48], payoutsRaw[60]];
        rawValues.forEach((val, idx) => {
            const tax = val * rate / 100;
            const el = document.getElementById(taxIds[idx]);
            if (el) el.innerHTML = `налог: ${formatNumber(tax)} ₽`;
        });
    }

    function renderHeatmap(monthlyMap) {
        const rows = new Map();
        for (const [key, sum] of monthlyMap.entries()) {
            const [year, month] = key.split('-');
            if (!rows.has(year)) rows.set(year, new Array(12).fill(0));
            rows.get(year)[parseInt(month) - 1] = sum;
        }
        if (rows.size === 0) {
            document.getElementById('heatmap-table').innerHTML = '<tr><td class="p-4 text-center">Нет данных о будущих выплатах<\/td><\/tr>';
            return;
        }
        const years = Array.from(rows.keys()).sort();
        const monthNames = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'];
        let headerHtml = '<tr><th class="p-1 border whitespace-nowrap">Год</th>';
        for (let i = 0; i < 12; i++) headerHtml += `<th class="p-1 border whitespace-nowrap">${monthNames[i]}</th>`;
        headerHtml += '<\/tr>';
        document.getElementById('heatmap-header').innerHTML = headerHtml;

        let bodyHtml = '';
        const allValues = Array.from(rows.values()).flat();
        const maxVal = Math.max(...allValues, 1);
        for (const year of years) {
            const monthsData = rows.get(year);
            bodyHtml += `<tr><td class="p-1 border font-bold whitespace-nowrap">${year}<\/td>`;
            for (let m = 0; m < 12; m++) {
                const val = monthsData[m];
                if (val === 0) {
                    bodyHtml += `<td class="p-1 border bg-gray-100 text-gray-400 whitespace-nowrap">—<\/td>`;
                } else {
                    const intensity = Math.min(0.9, val / maxVal);
                    const r = 255;
                    const g = Math.floor(235 - intensity * 180);
                    const b = Math.floor(180 - intensity * 140);
                    bodyHtml += `<td class="p-1 border whitespace-nowrap" style="background-color: rgb(${r}, ${g}, ${b});">${formatNumber(val)}<\/td>`;
                }
            }
            bodyHtml += '<\/tr>';
        }
        document.getElementById('heatmap-body').innerHTML = bodyHtml;
    }

    async function loadAnalytics() {
        if (portfolioItems.length === 0) return;

        let totalPortfolioValue = 0;
        let totalCost = 0;
        let totalNkdSum = 0;       // Суммарный НКД по портфелю
        let weightedYtmSum = 0;     // Для средневзвешенной YTM
        let totalQuantitySum = 0;   // Общее количество бумаг (штук)
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

        let payouts = { 1:0, 6:0, 12:0, 24:0, 36:0, 48:0, 60:0 };

        for (const item of portfolioItems) {
            const secid = item.bond_secid;
            const quantity = item.quantity;
            const bondName = item.bond_name ?? secid;
            const purchasePrice = parseFloat(item.purchase_price);
            totalCost += purchasePrice * quantity;
            totalQuantitySum += quantity;

            try {
                const resp = await fetch(`/api/bond/${secid}`);
                if (!resp.ok) throw new Error('API error');
                const data = await resp.json();

                const currentPrice = data.price ? parseFloat(data.price) : 0;
                const currentTotal = currentPrice * quantity;
                totalPortfolioValue += currentTotal;

                // Накопленный НКД
                const nkd = data.nkd ? parseFloat(data.nkd) : 0;
                totalNkdSum += nkd * quantity;

                // Взвешенная YTM
                if (data.yield) {
                    weightedYtmSum += parseFloat(data.yield) * currentTotal;
                }

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

                        if (couponDate <= end1m) payouts[1] += amount;
                        if (couponDate <= end6m) payouts[6] += amount;
                        if (couponDate <= end1y) payouts[12] += amount;
                        if (couponDate <= end2y) payouts[24] += amount;
                        if (couponDate <= end3y) payouts[36] += amount;
                        if (couponDate <= end4y) payouts[48] += amount;
                        if (couponDate <= end5y) payouts[60] += amount;
                    }
                }
                detailsRows.push({ name: bondName, quantity, couponValue, nextDate, sumByPortfolio: nextSum });
            } catch(e) {
                console.error(secid, e);
                bondShares.push({ name: bondName, quantity, currentPrice: 0, totalValue: 0 });
                detailsRows.push({ name: bondName, quantity, couponValue: 'error', nextDate: null, sumByPortfolio: null });
            }
        }

        totalProfitRaw = totalPortfolioValue - totalCost;
        payoutsRaw = payouts;

        // Обновление дополнительной статистики
        const avgYtm = totalPortfolioValue > 0 ? (weightedYtmSum / totalPortfolioValue).toFixed(2) : '0.00';
        document.getElementById('total-nkd-all').innerHTML = formatNumber(totalNkdSum) + ' ₽';
        document.getElementById('avg-ytm').innerHTML = avgYtm + '%';
        document.getElementById('total-quantity').innerHTML = totalQuantitySum;
        document.getElementById('unique-issues').innerHTML = portfolioItems.length;

        // Круговая диаграмма
        bondShares.forEach(s => s.percent = totalPortfolioValue > 0 ? (s.totalValue/totalPortfolioValue)*100 : 0);
        bondShares.sort((a,b) => b.percent - a.percent);
        document.getElementById('shares-table-body').innerHTML = bondShares.map(s => `
            <tr class="border-b">
                <td class="py-2 px-1 whitespace-nowrap">${s.name}<\/td>
                <td class="py-2 px-1 whitespace-nowrap">${s.quantity}<\/td>
                <td class="py-2 px-1 whitespace-nowrap">${s.currentPrice ? formatNumber(s.currentPrice)+' ₽' : '—'}<\/td>
                <td class="py-2 px-1 whitespace-nowrap">${formatNumber(s.totalValue)} ₽<\/td>
                <td class="py-2 px-1 whitespace-nowrap font-semibold">${s.percent.toFixed(2)}%<\/td>
            <\/tr>
        `).join('');
        if (pieChart) pieChart.destroy();
        pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie', data: { labels: bondShares.map(s=>s.name), datasets: [{ data: bondShares.map(s=>s.totalValue), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec489a','#06b6d4','#84cc16','#f97316','#6366f1'] }] },
            options: { responsive: true, plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${formatNumber(ctx.raw)} ₽ (${((ctx.raw/totalPortfolioValue)*100).toFixed(2)}%)` } }, legend: { position: 'right', labels: { boxWidth: 12 } } } }
        });

        // Прибыль
        const profit = totalProfitRaw;
        const profitPercent = totalCost > 0 ? (profit/totalCost)*100 : 0;
        document.getElementById('total-profit-rub').innerHTML = `${profit>=0?'+':''}${formatNumber(profit)} ₽`;
        document.getElementById('total-profit-percent').innerHTML = `<span class="${profit>=0?'text-green-600':'text-red-600'}">${profit>=0?'+':''}${profitPercent.toFixed(2)}%</span>`;

        // Выплаты за периоды
        document.getElementById('payout-1m').innerHTML = formatNumber(payouts[1]) + ' ₽';
        document.getElementById('payout-6m').innerHTML = formatNumber(payouts[6]) + ' ₽';
        document.getElementById('payout-1y').innerHTML = formatNumber(payouts[12]) + ' ₽';
        document.getElementById('payout-2y').innerHTML = formatNumber(payouts[24]) + ' ₽';
        document.getElementById('payout-3y').innerHTML = formatNumber(payouts[36]) + ' ₽';
        document.getElementById('payout-4y').innerHTML = formatNumber(payouts[48]) + ' ₽';
        document.getElementById('payout-5y').innerHTML = formatNumber(payouts[60]) + ' ₽';

        updateTaxes();

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

        renderHeatmap(monthlyMap);

        // Таблица ближайших купонов (адаптивная)
        document.getElementById('details-table-body').innerHTML = detailsRows.map(r => {
            if (r.couponValue === 'error') return `<tr class="border-b"><td class="py-1 px-1 whitespace-nowrap">${r.name}<\/td><td class="py-1 px-1 whitespace-nowrap">${r.quantity}<\/td><td colspan="3" class="text-red-500">Ошибка<\/td><\/tr>`;
            if (!r.nextDate) return `<tr class="border-b"><td class="py-1 px-1 whitespace-nowrap">${r.name}<\/td><td class="py-1 px-1 whitespace-nowrap">${r.quantity}<\/td><td colspan="3" class="text-gray-400">Нет данных<\/td><\/tr>`;
            return `<tr class="border-b">
                <td class="py-1 px-1 whitespace-nowrap">${r.name}<\/td>
                <td class="py-1 px-1 whitespace-nowrap">${r.quantity}<\/td>
                <td class="py-1 px-1 whitespace-nowrap">${formatNumber(r.couponValue)} ₽<\/td>
                <td class="py-1 px-1 whitespace-nowrap">${new Date(r.nextDate).toLocaleDateString('ru-RU')}<\/td>
                <td class="py-1 px-1 whitespace-nowrap font-semibold">${formatNumber(r.sumByPortfolio)} ₽<\/td>
            <\/tr>`;
        }).join('');
    }

    document.getElementById('ndfl-rate').addEventListener('change', updateTaxes);
    loadAnalytics();
</script>
@endpush