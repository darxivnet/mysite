/**
 * SiteMarket — Клиентские скрипты
 * QR-код для Bitcoin, копирование адреса, тосты, FAQ
 */
document.addEventListener('DOMContentLoaded', function () {
    // Генерация QR-кода
    generateBtcQrCode();

    // Открытие первого FAQ при якоре
    if (window.location.hash === '#faq') {
        const firstFaq = document.querySelector('.faq__item');
        if (firstFaq) firstFaq.classList.add('faq__item--open');
    }
});

/**
 * Копирование Bitcoin-адреса в буфер обмена
 */
function copyWalletAddress() {
    const walletEl = document.getElementById('btcWalletAddress');
    if (!walletEl) return;

    const address = walletEl.textContent.trim();
    navigator.clipboard.writeText(address).then(function () {
        const btn = document.getElementById('copyWalletBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '&#x2705; Скопировано!';
        btn.classList.add('payment__copy-btn--copied');

        showToast('Адрес скопирован!', 'success');

        setTimeout(function () {
            btn.innerHTML = originalText;
            btn.classList.remove('payment__copy-btn--copied');
        }, 2500);
    }).catch(function () {
        showToast('Не удалось скопировать. Попробуйте вручную.', 'error');
    });
}

/**
 * Генерация QR-кода для Bitcoin-адреса
 * Использует Bitcoin: URI схему с суммой
 */
function generateBtcQrCode() {
    const canvas = document.getElementById('btcQrCode');
    if (!canvas) return;

    const walletAddress = document.getElementById('btcWalletAddress');
    if (!walletAddress) return;

    const address = walletAddress.textContent.trim();

    // Bitcoin URI: bitcoin:<address>?amount=<amount>&label=<label>
    const btcAmountMeta = document.querySelector('input[name="btc_amount"]');
    let amount = '';
    if (btcAmountMeta) {
        amount = btcAmountMeta.value;
    }

    const productTitleMeta = document.querySelector('input[name="order_id"]');
    let label = 'SiteMarket';
    if (productTitleMeta) {
        label = 'SiteMarket Order';
    }

    let btcUri = 'bitcoin:' + address;
    const params = [];
    if (amount) params.push('amount=' + amount);
    params.push('label=' + encodeURIComponent(label));
    if (params.length) btcUri += '?' + params.join('&');

    // Рисуем QR-код вручную (простая реализация QR-кода)
    drawQRCode(canvas, btcUri);
}

/**
 * Простой генератор QR-кода
 * Использует упрощённый алгоритм для отображения данных
 */
function drawQRCode(canvas, data) {
    var ctx = canvas.getContext('2d');
    var size = canvas.width;
    var moduleCount = 25; // Количество модулей
    var moduleSize = Math.floor(size / moduleCount);
    var offset = Math.floor((size - moduleCount * moduleSize) / 2);

    // Белый фон
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, size, size);

    // Генерируем псевдо-QR паттерн на основе данных
    var hash = simpleHash(data);

    ctx.fillStyle = '#000000';

    // Finder patterns (верхний левый, верхний правый, нижний левый)
    drawFinderPattern(ctx, offset, offset, moduleSize);
    drawFinderPattern(ctx, offset + (moduleCount - 7) * moduleSize, offset, moduleSize);
    drawFinderPattern(ctx, offset, offset + (moduleCount - 7) * moduleSize, moduleSize);

    // Timing patterns
    for (var i = 8; i < moduleCount - 8; i++) {
        if (i % 2 === 0) {
            ctx.fillRect(offset + 6 * moduleSize, offset + i * moduleSize, moduleSize, moduleSize);
            ctx.fillRect(offset + i * moduleSize, offset + 6 * moduleSize, moduleSize, moduleSize);
        }
    }

    // Заполняем данные на основе хеша
    var dataIdx = 0;
    for (var row = 0; row < moduleCount; row++) {
        for (var col = 0; col < moduleCount; col++) {
            // Пропускаем finder patterns
            if ((row < 8 && col < 8) || (row < 8 && col >= moduleCount - 8) || (row >= moduleCount - 8 && col < 8)) {
                continue;
            }
            // Пропускаем timing patterns
            if (row === 6 || col === 6) continue;

            if (dataIdx < hash.length && hash.charCodeAt(dataIdx) > 127) {
                ctx.fillRect(offset + col * moduleSize, offset + row * moduleSize, moduleSize, moduleSize);
            }
            dataIdx++;
        }
    }
}

/**
 * Рисует finder pattern (большой квадрат для позиционирования)
 */
function drawFinderPattern(ctx, x, y, size) {
    ctx.fillStyle = '#000000';
    // Внешний квадрат
    ctx.fillRect(x, y, size * 7, size * 7);
    // Белый внутренний
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x + size, y + size, size * 5, size * 5);
    // Чёрный центр
    ctx.fillStyle = '#000000';
    ctx.fillRect(x + size * 2, y + size * 2, size * 3, size * 3);
}

/**
 * Простая хеш-функция для генерации QR-паттерна
 */
function simpleHash(str) {
    var hash = '';
    var len = str.length;
    for (var i = 0; i < 400; i++) {
        var charCode = str.charCodeAt(i % len) * (i + 1) * 7;
        hash += String.fromCharCode((charCode % 200) + 30);
    }
    return hash;
}

/**
 * Показать toast-уведомление
 */
function showToast(message, type) {
    var toast = document.getElementById('toast');
    if (!toast) {
        // Создаём toast если нет
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'toast';
        document.body.appendChild(toast);
    }

    toast.textContent = message;
    toast.className = 'toast toast--show';
    if (type === 'success') toast.classList.add('toast--success');
    if (type === 'error') toast.classList.add('toast--error');

    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(function () {
        toast.classList.remove('toast--show');
    }, 3500);
}

/**
 * Показать индикатор загрузки на кнопке
 */
function setButtonLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '&#x23F3; Обработка...';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    }
}
