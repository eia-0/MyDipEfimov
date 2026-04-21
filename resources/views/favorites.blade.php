@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Избранные облигации</h1>

    @if($favorites->isEmpty())
        <div class="card p-6 text-center text-gray-500">
            У вас пока нет избранных облигаций. Перейдите на страницу 
            <a href="{{ route('market') }}" class="text-blue-600 hover:underline">«Добавить»</a>, 
            чтобы найти и добавить бумаги в избранное.
        </div>
    @else
        <div class="grid gap-4">
            @foreach($favorites as $fav)
            <div class="card p-4 flex justify-between items-center" data-secid="{{ $fav->bond_secid }}">
                <div>
                    <div class="font-semibold text-lg">{{ $fav->bond_name ?? $fav->bond_secid }}</div>
                    <div class="text-sm text-gray-500">ISIN: {{ $fav->bond_secid }}</div>
                    <div class="text-sm mt-1">
                        <span class="text-gray-600">Цена:</span>
                        <span class="current-price font-medium">—</span> | <br>
                        <span class="text-gray-600">Доходность:</span>
                        <span class="yield">—</span> | <br>
                        <span class="text-gray-600">НКД:</span>
                        <span class="nkd">—</span> |
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                <button onclick="addToPortfolio('{{ $fav->bond_secid }}', '{{ addslashes($fav->bond_name ?? $fav->bond_secid) }}')" 
                        class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                        В портфель
                </button>
                <form action="{{ route('favorites.destroy', $fav) }}" method="POST" onsubmit="return confirm('Удалить из избранного?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 w-full">
                        ✖ Удалить
                    </button>
                </form>
            </div>
            </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Модальное окно добавления в портфель (такое же, как в market.blade.php) -->
<div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-bold mb-4">Добавить в портфель</h3>
        <input type="hidden" id="modal_secid">
        <input type="text" id="modal_name" readonly class="w-full border rounded p-2 mb-2 bg-gray-100">
        <input type="number" id="modal_quantity" placeholder="Количество" class="w-full border rounded p-2 mb-2">
        <input type="number" id="modal_price" placeholder="Цена покупки (₽)" step="0.01" class="w-full border rounded p-2 mb-2">
        <input type="date" id="modal_date" value="{{ date('Y-m-d') }}" class="w-full border rounded p-2 mb-4">
        <div class="flex justify-end gap-2">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Отмена</button>
            <button onclick="submitPortfolio()" class="px-4 py-2 bg-blue-600 text-white rounded">Добавить</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Загрузка актуальных данных для каждой избранной бумаги
    async function loadFavoritesData() {
        const cards = document.querySelectorAll('[data-secid]');
        for (const card of cards) {
            const secid = card.dataset.secid;
            try {
                const resp = await fetch(`/api/bond/${secid}`);
                const data = await resp.json();
                card.querySelector('.current-price').innerHTML = data.price ? data.price.toFixed(2) + ' ₽' : '—';
                card.querySelector('.yield').innerHTML = data.yield ? data.yield.toFixed(2) + '%' : '—';
                card.querySelector('.nkd').innerHTML = data.nkd ? data.nkd.toFixed(2) + ' ₽' : '—';
            } catch(e) {
                console.error('Ошибка загрузки', secid, e);
            }
        }
    }

    // Модальное окно
    function addToPortfolio(secid, name) {
        document.getElementById('modal_secid').value = secid;
        document.getElementById('modal_name').value = name;
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    }

    function closeModal() {
        document.getElementById('addModal').classList.add('hidden');
    }

    function submitPortfolio() {
        const secid = document.getElementById('modal_secid').value;
        const name = document.getElementById('modal_name').value;
        const quantity = document.getElementById('modal_quantity').value;
        const price = document.getElementById('modal_price').value;
        const date = document.getElementById('modal_date').value;

        if (!quantity || !price) {
            alert('Заполните количество и цену покупки');
            return;
        }

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
                quantity: quantity,
                purchase_price: price,
                purchase_date: date
            })
        })
        .then(res => {
            if (res.ok) {
                closeModal();
                alert('Облигация добавлена в портфель');
                // можно сразу перейти в портфель
                window.location.href = '{{ route("dashboard") }}';
            } else {
                return res.json().then(err => { throw new Error(err.message || 'Ошибка') });
            }
        })
        .catch(err => {
            console.error(err);
            alert('Не удалось добавить. Попробуйте позже.');
        });
    }

    // Загружаем данные при загрузке страницы и каждые 30 секунд
    loadFavoritesData();
    setInterval(loadFavoritesData, 30000);
</script>
@endpush