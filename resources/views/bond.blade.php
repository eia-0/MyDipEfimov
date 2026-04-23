<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bond['name'] }} - MoyCupon</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Montserrat', sans-serif; background: #f5f7fb; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .security-title { font-size: 24px; font-weight: bold; margin-bottom: 8px; }
        .security-subtitle { font-size: 14px; color: #666; margin-bottom: 20px; }
        .data-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .label { font-weight: 500; color: #444; }
        .value { font-weight: 600; color: #1a1a1a; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; }
        .future-row { background-color: #e8f5e9; }
        .past-row { opacity: 0.6; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px 0 0; border-radius: 8px; text-decoration: none; color: white; border: none; cursor: pointer; font-size: 14px; }
        .btn-green { background-color: #2e7d32; }
        .btn-blue { background-color: #1565c0; }
        .btn-gray { background-color: #6c757d; }
        .btn-green:hover, .btn-blue:hover, .btn-gray:hover { opacity: 0.9; }
        .back-link { display: inline-block; margin-top: 20px; color: #1565c0; text-decoration: none; }
        .button-group { margin: 20px 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="security-title">
            {{ $bond['name'] }}
            <div class="security-subtitle">ISIN: {{ $secid }}</div>
        </div>

        <!-- Блок с данными облигации -->
        <div id="data">
            <div class="data-row"><span class="label">Текущая цена:</span> <span class="value">{{ is_numeric($bond['price']) ? number_format((float)$bond['price'], 2) . ' ₽' : '—' }}</span></div>
            <div class="data-row"><span class="label">Доходность:</span> <span class="value">{{ is_numeric($bond['yield']) ? number_format((float)$bond['yield'], 2) . '%' : '—' }}</span></div>
            <div class="data-row"><span class="label">НКД:</span> <span class="value">{{ is_numeric($bond['nkd']) ? number_format((float)$bond['nkd'], 2) . ' ₽' : '—' }}</span></div>
            <div class="data-row"><span class="label">Дата погашения:</span> <span class="value">{{ $bond['maturity'] ? date('d.m.Y', strtotime($bond['maturity'])) : 'Н/Д' }}</span></div>
            <div class="data-row"><span class="label">Тип купона:</span> <span class="value">{{ $bond['coupon_type'] ?? 'Фиксированный' }}</span></div>
            <div class="data-row"><span class="label">Размер купона:</span> <span class="value">{{ is_numeric($bond['coupon_value']) ? number_format((float)$bond['coupon_value'], 2) . ' ₽' : '—' }}</span></div>
            <div class="data-row"><span class="label">Выплат в год:</span> <span class="value">{{ $bond['payments_per_year'] ?? '—' }}</span></div>
            <div class="data-row"><span class="label">Следующий купон:</span> <span class="value">{{ $bond['next_coupon'] ? date('d.m.Y', strtotime($bond['next_coupon'])) : 'Н/Д' }}</span></div>
            <div class="data-row"><span class="label">Объем торгов:</span> <span class="value">{{ is_numeric($bond['volume']) ? number_format((float)$bond['volume']) . ' шт' : '—' }}</span></div>
            <div class="data-row"><span class="label">Сделок:</span> <span class="value">{{ $bond['trades'] ?? '—' }}</span></div>
        </div>

        <!-- Блок с кнопками (перед таблицей) -->
        <div class="button-group">
            @auth
                <button onclick="addToPortfolio('{{ $secid }}', '{{ addslashes($bond['name']) }}')" class="btn btn-green">➕ В портфель</button>
                <button onclick="addToFavorite('{{ $secid }}', '{{ addslashes($bond['name']) }}')" class="btn btn-blue">⭐ В избранное</button>
            @else
                <a href="{{ route('login') }}" class="btn btn-green">➕ В портфель (войдите)</a>
                <a href="{{ route('login') }}" class="btn btn-blue">⭐ В избранное (войдите)</a>
            @endauth
            <a href="{{ url()->previous() }}" class="btn btn-gray">← Назад</a>
        </div>

        <h3>График купонных выплат</h3>
        <table>
            <thead>
                <tr><th>Дата выплаты</th><th>Размер купона (₽)</th><th>Дней до выплаты</th></tr>
            </thead>
            <tbody>
                @php $today = new \DateTime(); @endphp
                @forelse($bond['coupons'] as $coupon)
                    @php 
                        $date = new \DateTime($coupon['date']); 
                        $diff = $today->diff($date)->days;
                        $value = is_numeric($coupon['value']) ? (float)$coupon['value'] : null;
                    @endphp
                    <tr class="{{ $date >= $today ? 'future-row' : 'past-row' }}">
                        <td>{{ $date->format('d.m.Y') }}</td>
                        <td>{{ is_numeric($bond['coupon_value']) ? number_format((float)$bond['coupon_value'], 2) . ' ₽' : '—' }}</td>
                        <td>{{ $date >= $today ? $diff . ' дн.' : 'прошло ' . $diff . ' дн.' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align: center;">Нет данных о купонах</td></tr>
                @endforelse
            </tbody>
        </table>

        <!-- Дублированная кнопка "Назад" внизу -->
        <div style="margin-top: 20px; text-align: center;">
            <a href="{{ url()->previous() }}" class="back-link" style="display: inline-block; padding: 8px 16px; background: #f0f0f0; border-radius: 8px; color: #333; text-decoration: none;">← Назад к списку</a>
        </div>
    </div>

    <script>
        function addToPortfolio(secid, name) {
            const quantity = prompt('Количество:', 1);
            if (!quantity) return;
            const price = prompt('Цена покупки (₽):', '{{ is_numeric($bond['price']) ? (float)$bond['price'] : 1000 }}');
            if (!price) return;
            const date = prompt('Дата покупки (ГГГГ-ММ-ДД):', '{{ date('Y-m-d') }}');
            if (!date) return;

            fetch('{{ route("portfolio.store") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    bond_secid: secid, 
                    bond_name: name, 
                    quantity: parseInt(quantity), 
                    purchase_price: parseFloat(price), 
                    purchase_date: date 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Добавлено в портфель');
                    if (confirm('Перейти в портфель?')) window.location.href = '{{ route("dashboard") }}';
                } else {
                    alert('❌ Ошибка: ' + (data.message || 'неизвестная ошибка'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('⚠️ Ошибка добавления. Возможно, запись уже существует.');
            });
        }

        function addToFavorite(secid, name) {
            fetch('{{ route("favorites.store") }}', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bond_secid: secid, bond_name: name })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) alert('⭐ Добавлено в избранное');
                else alert('❌ ' + (data.message || 'Ошибка'));
            })
            .catch(err => {
                console.error(err);
                alert('⚠️ Ошибка, но возможно запись уже существует.');
            });
        }
    </script>
</body>
</html>