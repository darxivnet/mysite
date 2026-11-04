<?php
/**
 * Admin-панель — управление статусами заказов и транзакций
 * Доступ по логину/паролю из .env (ADMIN_USER / ADMIN_PASS)
 */
require_once "includes/config.php";
require_once "includes/database.php";
require_once "includes/products.php";

// --- Аутентификация ---
$isLoginPage =
    $_SERVER["REQUEST_METHOD"] === "GET" && !isset($_SESSION["admin_auth"]);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {
    $user = $_POST["username"] ?? "";
    $pass = $_POST["password"] ?? "";

    if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
        $_SESSION["admin_auth"] = true;
        header("Location: admin.php");
        exit();
    } else {
        $loginError = "Неверный логин или пароlл";
    }
}

// Выход
if (isset($_GET["logout"])) {
    unset($_SESSION["admin_auth"]);
    header("Location: admin.php");
    exit();
}

// Если не авторизован — показываем форму входа
if (empty($_SESSION["admin_auth"])) {

    define("PAGE_TITLE", "Вход в админ-панель");
    include "includes/header.php";
    ?>
    <style>
        .login-box {
            max-width: 400px;
            margin: 80px auto;
            background: var(--white);
            border-radius: var(--radius-md);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
        }
        .login-box__title {
            font-size: 24px;
            font-weight: 700;
            color: var(--blue-800);
            text-align: center;
            margin-bottom: 8px;
        }
        .login-box__subtitle {
            font-size: 14px;
            color: var(--text-dim);
            text-align: center;
            margin-bottom: 28px;
        }
        .login-error {
            background: var(--red-50);
            color: var(--red-700);
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid var(--red-200);
        }
    </style>
    <div class="login-box">
        <h2 class="login-box__title">&#x1F512; Админ-панель</h2>
        <p class="login-box__subtitle">Введите логин и пароль</p>
        <?php if (isset($loginError)): ?>
            <div class="login-error"><?= htmlspecialchars($loginError) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label class="form-label" for="username">Логин</label>
                <input type="text" class="form-input" id="username" name="username"
                       placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Пароль</label>
                <input type="password" class="form-input" id="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" value="1" class="btn btn--primary btn--block">
                Войти
            </button>
        </form>
        <div style="text-align:center; margin-top:16px;">
            <a href="index.php" style="font-size:14px; color:var(--text-dim);">← Вернуться в каталог</a>
        </div>
    </div>
    <?php
    include "includes/footer.php";
    exit();

}

// --- АВТОРИЗОВАННАЯ ЧАСТЬ ---

$message = "";
$messageType = "";

// Обработка смены статуса
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $db = getDB();
    $orderNumber = $_POST["order_number"] ?? "";
    $action = $_POST["action"];

    try {
        $db->beginTransaction();

        if ($action === "confirm") {
            $stmt = $db->prepare(
                "SELECT status FROM orders WHERE order_number = :num",
            );
            $stmt->execute([":num" => $orderNumber]);
            $currentStatus = $stmt->fetchColumn();

            $newStatus = match ($currentStatus) {
                "pending" => "confirming",
                "confirming" => "confirmed",
                default => $currentStatus,
            };

            if ($newStatus !== $currentStatus) {
                $db->prepare(
                    "UPDATE orders SET status = :s WHERE order_number = :n",
                )->execute([":s" => $newStatus, ":n" => $orderNumber]);

                $db->prepare(
                    "UPDATE transactions SET status = :s WHERE order_number = :n",
                )->execute([":s" => $newStatus, ":n" => $orderNumber]);

                if ($newStatus === "confirmed") {
                    $db->prepare(
                        "UPDATE transactions SET confirmations = :c, confirmed_at = NOW(), block_height = :bh WHERE order_number = :n",
                    )->execute([
                        ":c" => rand(3, 12),
                        ":bh" => rand(800000, 850000),
                        ":n" => $orderNumber,
                    ]);
                }

                $message = "Заказ #{$orderNumber}: статус изменён на «{$newStatus}»";
                $messageType = "success";
            } else {
                $message = "Заказ #{$orderNumber}: дальнейшее изменение невозможно (текущий: {$currentStatus})";
                $messageType = "error";
            }
        } elseif ($action === "cancel") {
            $db->prepare(
                "UPDATE orders SET status = 'cancelled' WHERE order_number = :n",
            )->execute([":n" => $orderNumber]);
            $db->prepare(
                "UPDATE transactions SET status = 'failed' WHERE order_number = :n",
            )->execute([":n" => $orderNumber]);

            $message = "Заказ #{$orderNumber} отменён";
            $messageType = "error";
        }

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $message = "Ошибка: " . $e->getMessage();
        $messageType = "error";
    }
}

// Обработка добавления нового сайта
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {
    $db = getDB();
    $title = trim($_POST["title"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $priceBtc = (float) ($_POST["price_btc"] ?? 0);
    $priceRub = (float) ($_POST["price_rub"] ?? 0);
    $description = trim($_POST["description"] ?? "");
    $featuresRaw = trim($_POST["features"] ?? "");
    $techStack = trim($_POST["tech_stack"] ?? "");
    $domainIncluded = isset($_POST["domain_included"]) ? 1 : 0;
    $supportMonths = (int) ($_POST["support_months"] ?? 1);
    $image = trim($_POST["image"] ?? "");
    $demoUrl = trim($_POST["demo_url"] ?? "");
    $rating = (float) ($_POST["rating"] ?? 0);

    // Загрузка изображения
    $uploadedImage = null;
    if (
        isset($_FILES["image_file"]) &&
        $_FILES["image_file"]["error"] === UPLOAD_ERR_OK
    ) {
        $file = $_FILES["image_file"];
        $allowed = [
            "image/jpeg",
            "image/png",
            "image/webp",
            "image/gif",
            "image/svg+xml",
        ];
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowedExt = ["jpg", "jpeg", "png", "webp", "gif", "svg"];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (
            !in_array($file["type"], $allowed) &&
            !in_array($ext, $allowedExt)
        ) {
            $errors[] =
                "Недопустимый формат изображения (JPG, PNG, WEBP, GIF, SVG).";
        } elseif ($file["size"] > $maxSize) {
            $errors[] = "Изображение не должно превышать 5 МБ.";
        } else {
            $uploadDir = __DIR__ . "/assets/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newName =
                "site_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($file["tmp_name"], $dest)) {
                $uploadedImage = "assets/" . $newName;
            } else {
                $errors[] = "Ошибка при сохранении изображения.";
            }
        }
    }
    // Приоритет: загруженный файл > текстовая ссылка
    if ($uploadedImage) {
        $image = $uploadedImage;
    }

    // Превращаем фичи в JSON-массив
    $featuresArr = array_filter(
        array_map("trim", explode("\n", $featuresRaw)),
        fn($s) => $s !== "",
    );
    if (empty($featuresArr)) {
        $featuresArr = ["Без особенностей"];
    }
    $featuresJson = json_encode(
        array_values($featuresArr),
        JSON_UNESCAPED_UNICODE,
    );

    // Валидация
    $errors = [];
    if ($title === "") {
        $errors[] = "Название обязательно.";
    }
    if ($category === "") {
        $errors[] = "Категория обязательна.";
    }
    if ($priceBtc <= 0) {
        $errors[] = "Цена BTC должна быть > 0.";
    }
    if ($priceRub <= 0) {
        $errors[] = "Цена RUB должна быть > 0.";
    }
    if ($description === "") {
        $errors[] = "Описание обязательно.";
    }
    if ($techStack === "") {
        $errors[] = "Стек технологий обязателен.";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare('
                INSERT INTO products (title, category, price_btc, price_rub, description, features, tech_stack, domain_included, support_months, image, demo_url, rating, sales, is_active)
                VALUES (:t, :c, :pb, :pr, :d, :f, :ts, :di, :sm, :im, :du, :r, 0, 1)
            ');
            $stmt->execute([
                ":t" => $title,
                ":c" => $category,
                ":pb" => $priceBtc,
                ":pr" => $priceRub,
                ":d" => $description,
                ":f" => $featuresJson,
                ":ts" => $techStack,
                ":di" => $domainIncluded,
                ":sm" => $supportMonths,
                ":im" => $image ?: null,
                ":du" => $demoUrl ?: null,
                ":r" => $rating,
            ]);
            $newId = $db->lastInsertId();
            $message = "Сайт #{$newId} «{$title}» успешно добавлен!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Ошибка: " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = implode("<br>", $errors);
        $messageType = "error";
    }
}

// Получаем все заказы
$db = getDB();
$allOrders = $db
    ->query(
        '
    SELECT o.*, t.status AS tx_status, t.confirmations, t.block_height
    FROM orders o
    LEFT JOIN transactions t ON t.order_number = o.order_number
    ORDER BY o.created_at DESC
    LIMIT 50
',
    )
    ->fetchAll();

define("PAGE_TITLE", "Админ-панель");
include "includes/header.php";
?>

<section class="section">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Админ-<span>панель</span></h2>
            <p class="section__desc">
                Управление заказами и статусами транзакций &nbsp;|&nbsp;
                <a href="admin.php?logout=1" style="color: var(--red-500); font-size: 14px;">Выйти</a>
            </p>
        </div>

        <?php if ($message): ?>
        <div class="payment__status <?= $messageType === "success"
            ? "payment__status--success"
            : "payment__status--error" ?>" style="margin-bottom: 20px; max-width: 600px; margin-left: auto; margin-right: auto;">
            <?= $message ?>
        </div>
        <?php endif; ?>

        <!-- Форма добавления сайта -->
        <div style="text-align:center; margin-bottom:24px;">
            <button onclick="document.getElementById('addProductForm').classList.toggle('hidden')" class="btn btn--primary" style="font-size:14px;">
                &#xFF0B; Добавить сайт
            </button>
        </div>
        <div id="addProductForm" class="hidden" style="max-width:700px; margin:0 auto 32px; background:var(--white); border-radius:var(--radius-md); padding:28px; box-shadow:var(--shadow-md); border:1px solid var(--border);">
            <h3 style="color:var(--blue-800); margin-bottom:20px;">Добавить новый сайт на продажу</h3>
            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Название *</label>
                        <input type="text" name="title" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Категория *</label>
                        <input type="text" name="category" class="form-input" placeholder="E-Commerce, Корпоративный..." required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Цена BTC *</label>
                        <input type="number" name="price_btc" class="form-input" step="0.000001" min="0.000001" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Цена RUB *</label>
                        <input type="number" name="price_rub" class="form-input" step="0.01" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Описание *</label>
                    <textarea name="description" class="form-input" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Стек технологий *</label>
                    <input type="text" name="tech_stack" class="form-input" placeholder="PHP 8.2, Laravel 10, MySQL..." required>
                </div>
                <div class="form-group">
                    <label class="form-label">Возможности (по одной на строку)</label>
                    <textarea name="features" class="form-input" rows="4" placeholder="Адаптивный дизайн&#10;Админ-панель&#10;SEO-оптимизация"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Поддержка (мес.)</label>
                        <input type="number" name="support_months" class="form-input" value="1" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Рейтинг</label>
                        <input type="number" name="rating" class="form-input" value="0" step="0.1" min="0" max="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Изображение (загрузить)</label>
                        <input type="file" name="image_file" class="form-input" accept="image/*" style="padding:8px;">
                        <small style="color:#94a3b8; font-size:11px;">JPG, PNG, WEBP, GIF, SVG до 5 МБ</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Или укажите путь к изображению</label>
                        <input type="text" name="image" class="form-input" placeholder="assets/site.jpg">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div class="form-group">
                        <label class="form-label">Demo URL</label>
                        <input type="text" name="demo_url" class="form-input" placeholder="demo/?id=7">
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:8px; padding-top:22px;">
                        <input type="checkbox" name="domain_included" id="dominc" value="1">
                        <label for="dominc" class="form-label" style="margin:0;">Домен включён</label>
                    </div>
                </div>
                <button type="submit" name="add_product" value="1" class="btn btn--red btn--block" style="margin-top:8px;">
                    &#xFF0B; Добавить сайт
                </button>
            </form>
        </div>

        <div class="tx-table-wrap">
            <table class="tx-table">
                <thead>
                    <tr>
                        <th>Заказ</th>
                        <th>Товар</th>
                        <th>Email</th>
                        <th>Сумма BTC</th>
                        <th>Статус заказа</th>
                        <th>Статус TX</th>
                        <th>Подтв.</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allOrders as $o):

                        $oStatus = $o["status"];
                        $txStatus = $o["tx_status"] ?? "pending";
                        $confirmations = (int) ($o["confirmations"] ?? 0);

                        $statusBadge = match ($oStatus) {
                            "pending" => ["Ожидает", "tx-badge--pending"],
                            "confirming" => [
                                "Подтверждается",
                                "tx-badge--confirming",
                            ],
                            "confirmed" => [
                                "Подтверждён",
                                "tx-badge--confirmed",
                            ],
                            "cancelled" => ["Отменён", "tx-badge--failed"],
                            "completed" => ["Завершён", "tx-badge--confirmed"],
                            default => [$oStatus, "tx-badge--pending"],
                        };

                        $canConfirm = in_array($oStatus, [
                            "pending",
                            "confirming",
                        ]);
                        $canCancel = in_array($oStatus, [
                            "pending",
                            "confirming",
                        ]);
                        ?>
                    <tr>
                        <td class="tx-table__hash" title="<?= htmlspecialchars(
                            $o["order_number"],
                        ) ?>">
                            <?= htmlspecialchars(
                                substr($o["order_number"], 0, 12),
                            ) ?>...
                        </td>
                        <td><?= htmlspecialchars(
                            mb_substr($o["product_title"], 0, 30),
                        ) ?></td>
                        <td><?= htmlspecialchars($o["buyer_email"]) ?></td>
                        <td class="tx-table__amount">&#x20BF; <?= rtrim(
                            rtrim(
                                number_format((float) $o["btc_amount"], 6),
                                "0",
                            ),
                            ".",
                        ) ?></td>
                        <td><span class="tx-badge <?= $statusBadge[1] ?>"><?= $statusBadge[0] ?></span></td>
                        <td>
                            <span class="tx-badge tx-badge--<?= $txStatus ?>">
                                <?= match ($txStatus) {
                                    "pending" => "Ожидает",
                                    "confirming" => "Подтв...",
                                    "confirmed" => "OK",
                                    "failed" => "Ошибка",
                                    default => $txStatus,
                                } ?>
                            </span>
                        </td>
                        <td><?= $confirmations ?></td>
                        <td style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <?php if ($canConfirm): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_number" value="<?= htmlspecialchars(
                                    $o["order_number"],
                                ) ?>">
                                <button type="submit" name="action" value="confirm" class="payment__copy-btn" style="font-size:11px;">
                                    <?= $oStatus === "pending"
                                        ? "▶ Проверить"
                                        : "✓ Подтвердить" ?>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($canCancel): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Отменить заказ #<?= htmlspecialchars(
                                substr($o["order_number"], 0, 8),
                            ) ?>...?')">
                                <input type="hidden" name="order_number" value="<?= htmlspecialchars(
                                    $o["order_number"],
                                ) ?>">
                                <button type="submit" name="action" value="cancel" class="payment__copy-btn" style="font-size:11px; background:#ef4444;">
                                    ✕ Отменить
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:center; margin-top:24px;">
            <a href="index.php" class="btn btn--outline">← Вернуться в каталог</a>
        </div>
    </div>
</section>

<?php include "includes/footer.php";
