<?php
require_once "includes/config.php";
require_once "includes/products.php";

// Получаем ID товара
$productId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$product = getProductById($productId);

// Если товар не найден — 404
if (!$product) {

    define("PAGE_TITLE", "Товар не найден");
    include "includes/header.php";
    ?>
    <div class="not-found">
        <div class="not-found__code">404</div>
        <p class="not-found__text">Такой сайт не найден в каталоге</p>
        <a href="index.php" class="btn btn--primary">Вернуться в каталог</a>
    </div>
    <?php
    include "includes/footer.php";
    exit();

}

define("PAGE_TITLE", $product["title"]);
$relatedProducts = getRelatedProducts($product["id"], 3);
$purchaseCount = getProductPurchaseCount($product["id"]);
$recentTransactions = getRecentTransactions($product["id"], 5);
$totalPurchases = getTotalPurchases();
$txStats = getTransactionStats();

// Генерация уникального ID заказа для этого просмотра
$orderId =
    "SM-" . strtoupper(substr(md5($product["id"] . time() . rand()), 0, 10));
$_SESSION["last_order_id"] = $orderId;
$_SESSION["last_product_id"] = $product["id"];
$_SESSION["last_btc_amount"] = $product["price_btc"];

include "includes/header.php";
?>

<!-- Хлебные крошки -->
<div class="container">
    <div class="breadcrumbs">
        <a href="index.php">Главная</a> &rsaquo;
        <a href="index.php#catalog">Каталог</a> &rsaquo;
        <span><?= htmlspecialchars($product["title"]) ?></span>
    </div>
</div>

<!-- Товар -->
<section class="product">
    <div class="container">
        <div class="product__grid">
            <!-- Изображение -->
            <div class="product__image-wrap">
                <?php if (file_exists($product["image"])): ?>
                    <img src="<?= $product[
                        "image"
                    ] ?>" alt="<?= htmlspecialchars(
    $product["title"],
) ?>" class="product__image">
                <?php else: ?>
                    &#x1F4BB;
                <?php endif; ?>
                <?php if (!empty($product["demo_url"])): ?>
                <a href="viewer.php?id=<?= $product[
                    "id"
                ] ?>" class="btn btn--outline" style="position:absolute;bottom:16px;left:16px;background:rgba(255,255,255,0.95);font-size:13px;padding:8px 18px;">&#x1F441; Посмотреть демо</a>
                <?php endif; ?>
            </div>

            <!-- Информация -->
            <div>
                <span class="product__category"><?= htmlspecialchars(
                    $product["category"],
                ) ?></span>
                <h1 class="product__title"><?= htmlspecialchars(
                    $product["title"],
                ) ?></h1>

                <div class="product__rating-row">
                    <span class="product__stars">
                        <?php
                        $fullStars = floor($product["rating"]);
                        $halfStar = $product["rating"] - $fullStars >= 0.5;
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo "&#9733;";
                        }
                        if ($halfStar) {
                            echo "&#9733;";
                        } // упрощённо
                        for (
                            $i = $fullStars + ($halfStar ? 1 : 0);
                            $i < 5;
                            $i++
                        ) {
                            echo "&#9734;";
                        }
                        ?>
                    </span>
                    <span class="product__rating-val"><?= $product[
                        "rating"
                    ] ?></span>
                    <span class="product__sales">(продано: <?= $product[
                        "sales"
                    ] ?>)</span>
                </div>

                <!-- Цена -->
                <div class="product__price-block">
                    <div class="product__price-btc">
                        &#x20BF; <?= rtrim(
                            rtrim(number_format($product["price_btc"], 4), "0"),
                            ".",
                        ) ?> <small>BTC</small>
                    </div>
                    <div class="product__price-rub">
                        ≈ <?= number_format(
                            $product["price_rub"],
                            0,
                            ",",
                            " ",
                        ) ?> $
                    </div>
                </div>

                <!-- Описание -->
                <p class="product__desc"><?= htmlspecialchars(
                    $product["description"],
                ) ?></p>

                <!-- Мета -->
                <div class="product__meta">
                    <div class="product__meta-item">
                        <span class="product__meta-icon">&#x1F4E6;</span>
                        <span>Стек: <strong><?= htmlspecialchars(
                            $product["tech_stack"],
                        ) ?></strong></span>
                    </div>
                    <div class="product__meta-item">
                        <span class="product__meta-icon">&#x1F310;</span>
                        <span>Домен: <strong> Не предоставляется.</strong></span>
                    </div>
                    <div class="product__meta-item">
                        <span class="product__meta-icon">&#x1F6E0;</span>
                        <span>Поддержка: <strong><?= $product[
                            "support_months"
                        ] ?> мес.</strong></span>
                    </div>
                    <div class="product__meta-item">
                        <span class="product__meta-icon">&#x2705;</span>
                        <span>Гарантия: <strong>30 дней</strong></span>
                    </div>
                </div>

                <!-- Особенности -->
                <div class="product__features">
                    <h3 class="product__features-title">Возможности</h3>
                    <div class="product__features-list">
                        <?php foreach ($product["features"] as $feature): ?>
                            <div class="product__feature"><?= htmlspecialchars(
                                $feature,
                            ) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Статистика покупок -->
                <div class="product__stats">
                    <div class="product__stats-item">
                        <span class="product__stats-value">&#x1F4E6; <?= $purchaseCount ?></span>
                        <span class="product__stats-label">Всего покупок</span>
                    </div>
                    <div class="product__stats-item">
                        <span class="product__stats-value">&#x20BF; <?= number_format(
                            $txStats["total_btc"],
                            4,
                        ) ?></span>
                        <span class="product__stats-label">Общий оборот BTC</span>
                    </div>
                    <div class="product__stats-item">
                        <span class="product__stats-value">&#x1F3AF; <?= $totalPurchases ?></span>
                        <span class="product__stats-label">Сделок на платформе</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================
             BITCOIN ОПЛАТА
             ============================================ -->
        <div class="payment-section" id="payment">
            <div class="payment-section__inner">
                <!-- Левая колонка: инструкция + QR -->
                <div>
                    <h2 class="payment__title">Оплата <span>Bitcoin</span> &#x20BF;</h2>
                    <p class="payment__subtitle">
                        Для покупки сайта «<?= htmlspecialchars(
                            $product["title"],
                        ) ?>» отправьте точно
                        <strong>&#x20BF; <?= rtrim(
                            rtrim(number_format($product["price_btc"], 4), "0"),
                            ".",
                        ) ?></strong>
                        на указанный кошелёк и подтвердите транзакцию.
                    </p>

                    <!-- QR-код -->
                    <div class="payment__qr-block" style="margin-bottom: 20px;">
                        <div class="payment__qr-title">Отсканируйте QR-код для оплаты</div>
                        <div class="payment__qr-code" id="qrCodeContainer">
                            <canvas id="btcQrCode" width="180" height="180"></canvas>
                        </div>
                        <div class="payment__wallet-label">Адрес кошелька:</div>
                        <div class="payment__wallet" id="btcWalletAddress"><?= BTC_WALLET ?></div>
                        <button type="button" class="payment__copy-btn" onclick="copyWalletAddress()" id="copyWalletBtn">
                            &#x1F4CB; Скопировать адрес
                        </button>
                    </div>

                    <ol class="payment__steps">
                        <li class="payment__step">
                            <span class="payment__step-num">1</span>
                            <span class="payment__step-text">
                                <strong>Отсканируйте QR-код</strong> вашим Bitcoin-кошельком или скопируйте адрес вручную.
                            </span>
                        </li>
                        <li class="payment__step">
                            <span class="payment__step-num">2</span>
                            <span class="payment__step-text">
                                <strong>Отправьте ровно &#x20BF; <?= rtrim(
                                    rtrim(
                                        number_format($product["price_btc"], 4),
                                        "0",
                                    ),
                                    ".",
                                ) ?></strong> на адрес кошелька. Убедитесь, что сумма точная.
                            </span>
                        </li>
                        <li class="payment__step">
                            <span class="payment__step-num">3</span>
                            <span class="payment__step-text">
                                <strong>Скопируйте TXID</strong> (хеш транзакции) из вашего кошелька и вставьте в форму подтверждения.
                            </span>
                        </li>
                        <li class="payment__step">
                            <span class="payment__step-num">4</span>
                            <span class="payment__step-text">
                                <strong>Нажмите «Подтвердить оплату».</strong> Мы проверим транзакцию и сразу предоставим доступ к сайту.
                            </span>
                        </li>
                    </ol>

                    <!-- Сумма к оплате -->
                    <div style="margin-top: 24px; background: rgba(255,255,255,0.1); border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;">
                        <span style="font-size: 14px; opacity: 0.8;">Сумма к оплате:</span>
                        <span style="font-size: 24px; font-weight: 800; color: #f87171;">&#x20BF; <?= rtrim(
                            rtrim(number_format($product["price_btc"], 4), "0"),
                            ".",
                        ) ?></span>
                    </div>
                </div>

                <!-- Правая колонка: форма подтверждения -->
                <div>
                    <!-- Сообщение об успешной оплате -->
                    <?php if (
                        isset($_GET["payment"]) &&
                        $_GET["payment"] === "success"
                    ):

                        $successOrderNum = $_GET["order"] ?? "";
                        $orderInfo = $successOrderNum
                            ? getOrderByNumber($successOrderNum)
                            : null;
                        $txStatus = $orderInfo["tx_status"] ?? "pending";
                        $statusMap = [
                            "pending" => [
                                "Ожидает проверки",
                                "payment__status--pending",
                            ],
                            "confirming" => [
                                "Подтверждается",
                                "payment__status--pending",
                            ],
                            "confirmed" => [
                                "Подтверждён",
                                "payment__status--success",
                            ],
                            "failed" => ["Ошибка", "payment__status--error"],
                        ];
                        [$statusText, $statusClass] =
                            $statusMap[$txStatus] ?? $statusMap["pending"];
                        $confirmations = $orderInfo["confirmations"] ?? 0;
                        ?>
                    <div class="payment__form" style="text-align: center;">
                        <div style="font-size: 56px; margin-bottom: 12px;">
                            <?= $txStatus === "confirmed"
                                ? "&#x2705;"
                                : "&#x23F3;" ?>
                        </div>
                        <h3 class="payment__form-title" style="color: <?= $txStatus ===
                        "confirmed"
                            ? "#16a34a"
                            : "#2563eb" ?>;">
                            Платёж зарегистрирован!
                        </h3>
                        <div class="payment__status <?= $statusClass ?>">
                            <strong>Статус:</strong> <?= $statusText ?>
                            <?php if ($confirmations > 0): ?>
                                <br><small>Подтверждений сети: <?= $confirmations ?></small>
                            <?php endif; ?>
                            <br><small>Номер заказа: <?= htmlspecialchars(
                                $successOrderNum,
                            ) ?></small>
                        </div>
                        <?php if ($txStatus === "confirmed"): ?>
                        <p style="color: #16a34a; font-size: 14px; margin-top: 16px; font-weight: 600;">
                            &#x2705; Транзакция подтверждена! Доступ к сайту отправлен на Email.
                        </p>
                        <?php else: ?>
                        <p style="color: #64748b; font-size: 14px; margin-top: 16px;">
                            Мы проверяем вашу транзакцию в сети Bitcoin. После 1-3 подтверждений вы получите письмо с доступом к сайту на указанный Email.
                        </p>
                        <?php endif; ?>
                        <a href="index.php" class="btn btn--primary" style="margin-top: 12px;">Вернуться в каталог</a>
                    </div>
                    <?php
                    else:
                         ?>
                    <!-- Форма подтверждения оплаты -->
                    <div class="payment__form" id="paymentFormBlock">
                        <h3 class="payment__form-title">Подтверждение оплаты</h3>

                        <!-- Ошибки валидации -->
                        <?php if (isset($_SESSION["payment_errors"])): ?>
                        <div class="payment__status payment__status--error" style="margin-bottom: 16px;">
                            <?php foreach (
                                $_SESSION["payment_errors"]
                                as $err
                            ): ?>
                                <div><?= htmlspecialchars($err) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php unset($_SESSION["payment_errors"]); ?>
                        <?php endif; ?>

                        <form id="paymentConfirmForm" action="process_payment.php" method="POST">
                            <input type="hidden" name="product_id" value="<?= $product[
                                "id"
                            ] ?>">
                            <input type="hidden" name="order_id" value="<?= $orderId ?>">
                            <input type="hidden" name="btc_amount" value="<?= $product[
                                "price_btc"
                            ] ?>">

                            <div class="form-group">
                                <label class="form-label" for="buyer_email">Ваш Email *</label>
                                <input type="email" class="form-input" id="buyer_email" name="buyer_email"
                                       placeholder="you@example.com" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tx_hash">TXID (хеш транзакции) *</label>
                                <input type="text" class="form-input form-input--mono" id="tx_hash" name="tx_hash"
                                       placeholder="Например: a1b2c3d4e5f6..."
                                       pattern="[a-fA-F0-9]{10,128}"
                                       title="Введите хеш Bitcoin-транзакции (от 10 до 128 символов hex)"
                                       required>
                                <small style="color: #94a3b8; font-size: 11px; margin-top: 4px; display: block;">
                                    Скопируйте TXID из вашего Bitcoin-кошелька после отправки платежа
                                </small>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="buyer_name">Ваше имя</label>
                                <input type="text" class="form-input" id="buyer_name" name="buyer_name"
                                       placeholder="Иван Петров">
                            </div>

                            <button type="submit" class="btn btn--red btn--block btn--lg" id="submitPaymentBtn">
                                &#x20BF; Подтвердить оплату
                            </button>
                        </form>

                        <!-- Статус (скрыт по умолчанию, показывается после отправки) -->
                        <div id="paymentStatus" style="display:none;"></div>
                    </div>
                    <?php
                    endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- История транзакций -->
<?php if (!empty($recentTransactions)): ?>
<section class="section section--light">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">История <span>транзакций</span></h2>
            <p class="section__desc">Последние Bitcoin-платежи за этот сайт</p>
        </div>
        <div class="tx-table-wrap">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>TXID</th>
                        <th>Сумма</th>
                        <th>Статус</th>
                        <th>Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                    <tr>
                        <td class="tx-table__hash">
                            <?php
                            $hash = $tx["tx_hash"];
                            $len = strlen($hash);
                            if ($len > 16) {
                                echo htmlspecialchars(substr($hash, 0, 6)) .
                                    "&bull;&bull;&bull;" .
                                    htmlspecialchars(substr($hash, -6));
                            } else {
                                echo htmlspecialchars(substr($hash, 0, 6)) .
                                    "...";
                            }
                            ?>
                        </td>
                        <td class="tx-table__amount">&#x20BF; <?= rtrim(
                            rtrim(number_format($tx["btc_amount"], 6), "0"),
                            ".",
                        ) ?></td>
                        <td>
                            <span class="tx-badge tx-badge--<?= $tx[
                                "status"
                            ] ?>">
                                <?= match ($tx["status"]) {
                                    "pending" => "Ожидает",
                                    "confirming" => "Подтверждается",
                                    "confirmed" => "Подтверждён",
                                    "failed" => "Ошибка",
                                    default => $tx["status"],
                                } ?>
                            </span>
                        </td>
                        <td class="tx-table__date"><?= date(
                            "d.m.Y H:i",
                            strtotime($tx["created_at"]),
                        ) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Похожие товары -->
<?php if (!empty($relatedProducts)): ?>
<section class="section section--light">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Похожие <span>сайты</span></h2>
        </div>
        <div class="related__grid">
            <?php foreach ($relatedProducts as $rel): ?>
            <div class="card">
                <div class="card__image-wrap">
                    <?php if (file_exists($rel["image"])): ?>
                        <img src="<?= $rel[
                            "image"
                        ] ?>" alt="<?= htmlspecialchars(
    $rel["title"],
) ?>" class="card__image" loading="lazy">
                    <?php else: ?>
                        <div class="card__image-placeholder">&#x1F4BB;</div>
                    <?php endif; ?>
                    <span class="card__category"><?= htmlspecialchars(
                        $rel["category"],
                    ) ?></span>
                    <span class="card__rating"><span>&#9733;</span> <?= $rel[
                        "rating"
                    ] ?></span>
                </div>
                <div class="card__body">
                    <h3 class="card__title"><?= htmlspecialchars(
                        $rel["title"],
                    ) ?></h3>
                    <p class="card__desc"><?= htmlspecialchars(
                        $rel["description"],
                    ) ?></p>
                </div>
                <div class="card__footer">
                    <div>
                        <div class="card__price-btc">&#x20BF; <?= number_format(
                            $rel["price_btc"],
                            4,
                        ) ?></div>
                        <div class="card__price-rub">≈ <?= number_format(
                            $rel["price_rub"],
                            0,
                            ",",
                            " ",
                        ) ?> ₽</div>
                    </div>
                    <a href="detail.php?id=<?= $rel[
                        "id"
                    ] ?>" class="card__btn">Купить</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Toast -->
<div class="toast" id="toast"></div>

<?php include "includes/footer.php"; ?>
