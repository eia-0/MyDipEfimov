@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Поиск и добавление облигаций</h1>
    
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-4">
            <input type="text" id="search-input" placeholder="Введите ISIN или название облигации..." 
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        
        <div class="flex gap-4 mb-4">
            <label class="inline-flex items-center">
                <input type="radio" name="bondFilter" value="all" checked class="mr-1"> Все облигации
            </label>
            <label class="inline-flex items-center">
                <input type="radio" name="bondFilter" value="ofz" class="mr-1"> Только ОФЗ
            </label>
        </div>

        <div id="suggestions" class="space-y-2"></div>
        <div id="loading" class="text-gray-500 hidden">Загрузка...</div>
    </div>
</div>

<!-- Скрипт для поиска и кликабельных результатов -->
@push('scripts')
<script>
    const searchInput = document.getElementById('search-input');
    const suggestionsDiv = document.getElementById('suggestions');
    const loadingDiv = document.getElementById('loading');
    let searchTimeout = null;
    let abortController = null;

    function fetchSuggestions(query) {
        if (!query || query.length < 2) {
            suggestionsDiv.innerHTML = '';
            return;
        }

        if (abortController) abortController.abort();
        abortController = new AbortController();
        loadingDiv.classList.remove('hidden');

        fetch(`/api/search?q=${encodeURIComponent(query)}`, { signal: abortController.signal })
            .then(res => res.json())
            .then(data => {
                loadingDiv.classList.add('hidden');
                renderSuggestions(data);
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error(err);
                    loadingDiv.classList.add('hidden');
                    suggestionsDiv.innerHTML = '<div class="text-red-500">Ошибка загрузки</div>';
                }
            });
    }

    function renderSuggestions(items) {
        const ofzOnly = document.querySelector('input[name="bondFilter"]:checked').value === 'ofz';
        let filtered = items;
        if (ofzOnly) {
            filtered = items.filter(item => 
                item.name.toLowerCase().includes('офз') || item.secid.startsWith('SU')
            );
        }

        if (filtered.length === 0) {
            suggestionsDiv.innerHTML = '<div class="text-gray-500 text-center py-4">Ничего не найдено</div>';
            return;
        }

        suggestionsDiv.innerHTML = filtered.map(item => `
            <div class="bond-item border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition"
                 data-secid="${item.secid}">
                <div class="font-semibold text-lg">${item.name}</div>
                <div class="text-sm text-gray-500">ISIN: ${item.secid}</div>
            </div>
        `).join('');

        // Добавляем обработчики кликов
        document.querySelectorAll('.bond-item').forEach(el => {
            el.addEventListener('click', () => {
                const secid = el.dataset.secid;
                window.location.href = `/bond/${secid}`;
            });
        });
    }

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        if (query.length < 2) {
            suggestionsDiv.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(() => fetchSuggestions(query), 300);
    });

    document.querySelectorAll('input[name="bondFilter"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (searchInput.value.trim().length >= 2) {
                fetchSuggestions(searchInput.value.trim());
            }
        });
    });
</script>
@endpush
@endsection