
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoyCupon - облигационный сайт</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/welcome.css'] )

</head>
<body>
    <div class="">
        <div class="max-wrapper">
            <div class="header-wrapper bg-[#29292B]">
            <p class="logotext">MoyCupon.ru</p>
                <div class="auth-buttons">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="auth-btn">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="auth-btn">Войти</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="auth-btn">Регистрация</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        <div class="app-wrapper">
            <div class="zalovok">
                <img src="{{ Vite::asset('resources/images/iconka.svg') }}" alt="">
                <p>«MoyCupon.ru» — это аналитический веб-сервис для инвесторов,<br>
                выбирать облигации, считать доходность и управлять портфелем.</p>
            </div>
            <div class="search-container">
            <p class="mb-2">Для совершения действий с портфелем и облигациями, пожалуйста, зайдите в свой профиль или создайте его.</p>
            <input type="text" id="search-input" placeholder="Введите ISIN или название облигации..." autocomplete="off">
            <div class="filter-options">
                <label><input type="radio" name="bondFilter" id="filter-all" value="all" checked> Все облигации</label>
                <label><input type="radio" name="bondFilter" id="filter-ofz" value="ofz"> Только ОФЗ</label>
            </div>
            <div class="suggestions" id="suggestions"></div>
        </div>

        <div class="security-title">
            <span id="securityName">Загрузка...</span>
            <div class="security-subtitle">
                ISIN/Код: <span id="securityIsin">SU26238RMFS5</span>
            </div>
        </div>
        
        <div id="data">
            <div class="data-row"><span class="label">Текущая цена:</span> <span id="price" class="value">--</span></div>
            <div class="data-row"><span class="label">Доходность:</span> <span id="yield" class="value">--</span></div>
            <div class="data-row"><span class="label">НКД:</span> <span id="nkd" class="value">--</span></div>
            <div class="data-row"><span class="label">Дата погашения:</span> <span id="maturity" class="value">--</span></div>
            <div class="data-row"><span class="label">Тип купона:</span> <span id="couponType" class="value">--</span></div>
            <div class="data-row"><span class="label">Размер купона:</span> <span id="coupon" class="value">--</span></div>
            <div class="data-row"><span class="label">Выплат в год:</span> <span id="paymentsPerYear" class="value">--</span></div>
            <div class="data-row"><span class="label">Следующий купон:</span> <span id="nextCoupon" class="value">--</span></div>
            <div class="data-row"><span class="label">Объем торгов:</span> <span id="volume" class="value">--</span></div>
            <div class="data-row"><span class="label">Сделок:</span> <span id="trades" class="value">--</span></div>
            <div class="data-row"><span class="label">Обновлено:</span> <span id="time" class="value">--</span></div>
        </div>
        
        <h3>График купонных выплат</h3>
        <table id="couponTable">
            <thead>
                <tr><th>Дата выплаты</th><th>Размер купона (₽)</th><th>Дней до выплаты</th>               </thead>
            <tbody id="couponTableBody">
                <tr><td colspan="3" style="text-align: center;">Загрузка данных о купонах...</td>               </tbody>
         </table>

        <div class="mp-5 min-h-[3rem]" id="error"></div>
        </div>
    </div>

    <script>
        let currentSecId = 'SU26238RMFS5';
        const BOARDS = ['TQCB', 'TQBD', 'EQOB', 'TQOB'];
        let allCoupons = [];
        let defaultCouponValue = 1.1;
        
        const searchInput = document.getElementById('search-input');
        const suggestionsDiv = document.getElementById('suggestions');
        const securityNameSpan = document.getElementById('securityName');
        const securityIsinSpan = document.getElementById('securityIsin');
        const filterAll = document.getElementById('filter-all');
        const filterOfz = document.getElementById('filter-ofz');
        
        let searchTimeout = null;
        let abortController = null;

        function formatDate(dateStr) {
            if (!dateStr) return 'Н/Д';
            return new Date(dateStr).toLocaleDateString('ru-RU');
        }

        function determineCouponType(coupons) {
            if (!coupons || coupons.length < 2) return 'Фиксированный (по умолчанию)';
            try {
                const checkCount = Math.min(4, coupons.length);
                const firstValue = coupons[0].value;
                const allSame = coupons.slice(0, checkCount).every(c => 
                    c.value && Math.abs(c.value - firstValue) < 0.01
                );
                if (allSame) return 'Фиксированный';
                
                const variations = coupons.slice(0, checkCount).map(c => c.value).filter(v => v);
                if (variations.length === 0) return 'Фиксированный';
                const uniqueValues = [...new Set(variations.map(v => Math.round(v * 100) / 100))];
                return uniqueValues.length > 1 ? 'Плавающий (переменный)' : 'Фиксированный';
            } catch (e) {
                console.error('Ошибка определения типа купона:', e);
                return 'Фиксированный';
            }
        }

        function calculatePaymentsPerYear(couponDates) {
            if (!couponDates || couponDates.length < 2) return 2;
            try {
                const date1 = new Date(couponDates[0]);
                const date2 = new Date(couponDates[1]);
                const diffDays = Math.abs((date2 - date1) / (1000 * 60 * 60 * 24));
                if (diffDays > 0) return Math.round(365 / diffDays);
            } catch (e) {
                console.error('Ошибка расчета выплат:', e);
            }
            return 2;
        }

        function updateCouponTable(coupons) {
            const tbody = document.getElementById('couponTableBody');
            
            if (!coupons || coupons.length === 0) {
                tbody.innerHTML = '\                    <tr><td colspan="3" style="text-align: center;">Нет данных о купонных выплатах</td></tr>\              ';
                return;
            }
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            let html = '';
            let futureFound = false;
            
            const sortedCoupons = [...coupons].sort((a, b) => new Date(a.date) - new Date(b.date));
            
            sortedCoupons.forEach(coupon => {
                const couponDate = new Date(coupon.date);
                couponDate.setHours(0, 0, 0, 0);
                
                const daysLeft = Math.ceil((couponDate - today) / (1000 * 60 * 60 * 24));
                const rowClass = couponDate >= today ? 'future-row' : 'past-row';
                
                if (couponDate >= today) futureFound = true;
                
                let couponValue = coupon.value;
                if (!couponValue && couponValue !== 0) {
                    couponValue = defaultCouponValue;
                }
                
                html += `<tr class="${rowClass}">
                    <td>${formatDate(coupon.date)}</td>
                    <td class="coupon-value">${couponValue ? couponValue.toFixed(2) + ' ₽' : '—'}</td>
                    <td>${couponDate >= today ? daysLeft + ' дн.' : 'прошло ' + Math.abs(daysLeft) + ' дн.'}</td>
                </tr>`;
            });
            
            if (!futureFound && coupons.length > 0) {
                html += '<tr><td colspan="3" style="color: orange; text-align: center;">⚠️ Все выплаты в прошлом (возможно, облигация погашена)</td></tr>';
            }
            
            tbody.innerHTML = html;
        }

        async function loadData() {
            const errorDiv = document.getElementById('error');
            errorDiv.innerHTML = '';
            
            try {
                // Получаем описание инструмента
                const descUrl = `https://iss.moex.com/iss/securities/${currentSecId}.json?iss.meta=off`;
                const descResponse = await fetch(descUrl);
                const descJson = await descResponse.json();
                
                // Данные по купонам
                const couponUrl = `https://iss.moex.com/iss/statistics/engines/stock/markets/bonds/bondization/${currentSecId}.json?iss.meta=off&iss.only=coupons&coupons.columns=coupondate,couponvalue,couponperiod&limit=100`;
                const couponResponse = await fetch(couponUrl);
                const couponJson = await couponResponse.json();
                
                if (couponJson.coupons?.data?.length > 0) {
                    const couponCols = couponJson.coupons.columns;
                    const dateIndex = couponCols.indexOf('coupondate');
                    const valueIndex = couponCols.indexOf('couponvalue');
                    
                    allCoupons = couponJson.coupons.data.map(row => ({
                        date: row[dateIndex],
                        value: row[valueIndex] ? Number(row[valueIndex]) : null
                    })).filter(c => c.date);
                    
                    const firstValidValue = allCoupons.find(c => c.value);
                    if (firstValidValue && firstValidValue.value) {
                        defaultCouponValue = firstValidValue.value;
                    }
                } else {
                    allCoupons = [];
                }
                
                updateCouponTable(allCoupons);
                
                for (const board of BOARDS) {
                    const url = `https://iss.moex.com/iss/engines/stock/markets/bonds/boards/${board}/securities/${currentSecId}.json?iss.meta=off&iss.only=marketdata,securities&marketdata.columns=SECID,LAST,LASTCHANGE,LASTCHANGE_PRC,PREVPRICE,OPEN,CLOSE,HIGH,LOW,VALTODAY,VALTODAY_USD,VOLTODAY,NUMTRADES,ACCRUEDINT,YIELD,BOARDID,LCURRENTPRICE,SYSTIME&securities.columns=SECID,PREVPRICE,SHORTNAME,SECNAME,COUPONVALUE,COUPONPERCENT,MATURITYDATE,COUPONDATE,NEXTCOUPON,ACCRUEDINT,CURRENTVALUE`;
                    
                    const response = await fetch(url);
                    const json = await response.json();
                    
                    if (json.marketdata?.data?.length > 0) {
                        const marketColumns = json.marketdata.columns;
                        const marketRow = json.marketdata.data[0];
                        
                        const getMarketValue = (name) => {
                            const index = marketColumns.indexOf(name);
                            return index !== -1 ? marketRow[index] : null;
                        };
                        
                        let secData = null;
                        let secColumns = [];
                        if (json.securities?.data?.length > 0) {
                            secData = json.securities.data[0];
                            secColumns = json.securities.columns;
                        }
                        
                        const getSecValue = (name) => {
                            if (!secData) return null;
                            const index = secColumns.indexOf(name);
                            return index !== -1 ? secData[index] : null;
                        };
                        
                        
                        let shortName = getSecValue('SHORTNAME');
                        let secName = getSecValue('SECNAME');
                        if (shortName) {
                            securityNameSpan.textContent = shortName;
                        } else if (secName) {
                            securityNameSpan.textContent = secName;
                        } else {
                            securityNameSpan.textContent = 'Облигация ' + currentSecId;
                        }
                        
                        securityIsinSpan.textContent = currentSecId;
                        
                        // Цена
                        let last = getMarketValue('LAST');
                        const lcurrent = getMarketValue('LCURRENTPRICE');
                        if ((last === null || last === '') && lcurrent !== null) last = lcurrent;
                        
                        if (last !== null && last !== '') {
                            const priceRub = last * 10; // LAST в %, номинал 1000₽
                            document.getElementById('price').textContent = priceRub.toFixed(2) + ' ₽';
                        } else {
                            document.getElementById('price').textContent = '--';
                        }
                        
                        // Доходность
                        const yield_ = getMarketValue('YIELD');
                        document.getElementById('yield').textContent = yield_ ? yield_.toFixed(2) + '%' : '--';
                        
                        // НКД
                        let nkd = getMarketValue('ACCRUEDINT');
                        if ((nkd === null || nkd === '') && secData) nkd = getSecValue('ACCRUEDINT');
                        document.getElementById('nkd').textContent = nkd ? Number(nkd).toFixed(2) + ' ₽' : '--';
                        
                        // Дата погашения
                        let maturity = null;
                        maturity = getSecValue('MATURITYDATE');
                        
                        if (!maturity && descJson.description?.data) {
                            const descData = descJson.description.data;
                            const descCols = descJson.description.columns;
                            const matIndex = descCols.indexOf('MATURITYDATE');
                            if (matIndex !== -1 && descData[0]) {
                                maturity = descData[0][matIndex];
                            }
                        }
                        
                        if (!maturity && allCoupons.length > 0) {
                            const lastCouponDate = new Date(allCoupons[allCoupons.length - 1].date);
                            const today = new Date();
                            const yearsDiff = (lastCouponDate - today) / (1000 * 60 * 60 * 24 * 365);
                            if (yearsDiff <= 30) {
                                maturity = allCoupons[allCoupons.length - 1].date;
                            }
                        }
                        
                        document.getElementById('maturity').textContent = maturity ? formatDate(maturity) : 'Н/Д';
                        
                        // Тип купона
                        let couponType = 'Фиксированный';
                        if (allCoupons.length > 0) {
                            couponType = determineCouponType(allCoupons);
                        }
                        document.getElementById('couponType').textContent = couponType;
                        
                        // Размер купона
                        const coupon = getSecValue('COUPONVALUE');
                        if (coupon) {
                            document.getElementById('coupon').textContent = Number(coupon).toFixed(2) + ' ₽';
                            defaultCouponValue = Number(coupon);
                        } else if (allCoupons.length > 0) {
                            const today = new Date();
                            const futureCoupon = allCoupons.find(c => new Date(c.date) >= today && c.value);
                            if (futureCoupon && futureCoupon.value) {
                                document.getElementById('coupon').textContent = futureCoupon.value.toFixed(2) + ' ₽';
                            } else if (allCoupons[0].value) {
                                document.getElementById('coupon').textContent = allCoupons[0].value.toFixed(2) + ' ₽';
                            } else {
                                document.getElementById('coupon').textContent = defaultCouponValue.toFixed(2) + ' ₽ (по умолчанию)';
                            }
                        } else {
                            document.getElementById('coupon').textContent = defaultCouponValue.toFixed(2) + ' ₽ (по умолчанию)';
                        }
                        
                        // Выплат в год
                        let paymentsPerYear = 2;
                        if (allCoupons.length >= 2) {
                            const couponDates = allCoupons.map(c => c.date);
                            paymentsPerYear = calculatePaymentsPerYear(couponDates);
                        }
                        document.getElementById('paymentsPerYear').textContent = paymentsPerYear;
                        
                        // Следующий купон
                        const today = new Date();
                        const futureCoupons = allCoupons.filter(c => new Date(c.date) >= today);
                        if (futureCoupons.length > 0) {
                            const next = futureCoupons[0];
                            const daysLeft = Math.ceil((new Date(next.date) - today) / (1000 * 60 * 60 * 24));
                            document.getElementById('nextCoupon').textContent = formatDate(next.date) + 
                                ' (осталось ' + daysLeft + ' дн.)';
                        } else {
                            document.getElementById('nextCoupon').textContent = 'Н/Д';
                        }
                        
                        // Объём и сделки
                        const volume = getMarketValue('VOLTODAY');
                        document.getElementById('volume').textContent = volume ? volume.toLocaleString() + ' шт' : '--';
                        
                        const trades = getMarketValue('NUMTRADES');
                        document.getElementById('trades').textContent = trades ? trades : '--';
                        
                        // Время обновления
                        document.getElementById('time').textContent = new Date().toLocaleTimeString('ru-RU');
                        
                        updateCouponTable(allCoupons);
                        
                        return;
                    }
                }
                
                if (allCoupons.length > 0) {
                    errorDiv.innerHTML = '⚠️ Нет текущих рыночных данных, отображается историческая информация по купонам';
                } else {
                    errorDiv.innerHTML = '⚠️ Нет данных с биржи, отображаются справочные данные';
                }
                
            } catch (error) {
                errorDiv.innerHTML = '⚠️ Ошибка загрузки: ' + error.message;
                console.error(error);
                updateCouponTable([]);
            }
        }

    
        async function fetchSuggestions(query) {
            if (!query || query.length < 2) {
                suggestionsDiv.classList.remove('active');
                return;
            }

            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();
            const { signal } = abortController;

            try {
                const url = `https://iss.moex.com/iss/securities.json?q=${encodeURIComponent(query)}&group_by=group&group_list=bonds&is_trading=1&limit=15`;
                const response = await fetch(url, { signal });
                const json = await response.json();

                let suggestions = [];
                if (json.securities && json.securities.data) {
                    const cols = json.securities.columns;
                    const data = json.securities.data;

                    for (const row of data) {
                        const secid = row[cols.indexOf('secid')];
                        const shortname = row[cols.indexOf('shortname')];
                        const name = row[cols.indexOf('name')] || shortname;
                        suggestions.push({ secid, name });
                    }
                }

                if (filterOfz.checked) {
                    suggestions = suggestions.filter(item => 
                        item.name.includes('ОФЗ') || item.secid.startsWith('SU')
                    );
                }

                renderSuggestions(suggestions);
            } catch (e) {
                if (e.name === 'AbortError') return;
                console.warn('Ошибка поиска:', e);
                suggestionsDiv.classList.remove('active');
            } finally {
                abortController = null;
            }
        }

        function renderSuggestions(suggestions) {
            suggestionsDiv.innerHTML = '';
            if (suggestions.length === 0) {
                suggestionsDiv.classList.remove('active');
                return;
            }

            suggestions.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `<div class="name">${item.name}</div><div class="secid">${item.secid}</div>`;
                div.addEventListener('click', async () => {
                    currentSecId = item.secid;
                    searchInput.value = item.name;
                    suggestionsDiv.classList.remove('active');
                    await loadData();
                });
                suggestionsDiv.appendChild(div);
            });

            suggestionsDiv.classList.add('active');
        }


        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            if (searchTimeout) clearTimeout(searchTimeout);
            if (query.length < 2) {
                suggestionsDiv.classList.remove('active');
                return;
            }
            searchTimeout = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        });

        filterAll.addEventListener('change', () => {
            if (searchInput.value.trim().length >= 2) fetchSuggestions(searchInput.value.trim());
        });
        filterOfz.addEventListener('change', () => {
            if (searchInput.value.trim().length >= 2) fetchSuggestions(searchInput.value.trim());
        });

        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.remove('active');
            }
        });

        
        (async () => {
            await loadData();
        })();

        setInterval(loadData, 1000);
    </script>
</body>
</html>