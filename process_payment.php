<?php
/**
 * Обработчик Bitcoin-платежа
 * Принимает TXID, проверяет и сохраняет заказ в MySQL
 */
require_once "includes/config.php";
require_once "includes/database.php";
require_once "includes/products.php";

// Проверка метода
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

// Получаем данные
$productId = isset($_POST["product_id"]) ? (int) $_POST["product_id"] : 0;
$orderId = isset($_POST["order_id"]) ? trim($_POST["order_id"]) : "";
$btcAmount = isset($_POST["btc_amount"]) ? (float) $_POST["btc_amount"] : 0;
$txHash = isset($_POST["tx_hash"]) ? trim($_POST["tx_hash"]) : "";
$buyerEmail = isset($_POST["buyer_email"]) ? trim($_POST["buyer_email"]) : "";
$buyerName = isset($_POST["buyer_name"]) ? trim($_POST["buyer_name"]) : "";

// Валидация
$errors = [];

if ($productId <= 0) {
    $errors[] = "Не указан ID товара.";
}

if (empty($orderId)) {
    $errors[] = "Не указан номер заказа.";
}

if ($btcAmount <= 0) {
    $errors[] = "Некорректная сумма.";
}

if (empty($txHash) || !preg_match('/^[a-fA-F0-9]{10,128}$/', $txHash)) {
    $errors[] =
        "Некорректный TXID. Хеш должен содержать от 10 до 128 hex-символов.";
}

if (empty($buyerEmail) || !filter_var($buyerEmail, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Укажите корректный Email.";
}

// Если есть ошибки — возвращаем на страницу товара
if (!empty($errors)) {
    $_SESSION["payment_errors"] = $errors;
    header("Location: detail.php?id={$productId}#payment");
    exit();
}

// Проверяем существование товара в БД
$product = getProductById($productId);

if (!$product) {
    $_SESSION["payment_errors"] = ["Товар не найден."];
    header("Location: index.php");
    exit();
}

// Проверяем, не использовался ли уже этот TXID
$db = getDB();
$stmtDup = $db->prepare("SELECT COUNT(*) FROM transactions WHERE tx_hash = :h");
$stmtDup->execute([":h" => $txHash]);
if ((int) $stmtDup->fetchColumn() > 0) {
    $_SESSION["payment_errors"] = ["Этот TXID уже зарегистрирован в системе."];
    header("Location: detail.php?id={$productId}#payment");
    exit();
}

// Сохраняем заказ и транзакцию в MySQL (одна бизнес-транзакция)
try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Создаём заказ
    $stmt = $db->prepare('
        INSERT INTO orders (order_number, product_id, product_title, btc_amount, btc_wallet, tx_hash, buyer_email, buyer_name, status, buyer_ip, created_at)
        VALUES (:order_number, :product_id, :product_title, :btc_amount, :btc_wallet, :tx_hash, :buyer_email, :buyer_name, :status, :buyer_ip, NOW())
    ');
    $stmt->execute([
        ":order_number" => $orderId,
        ":product_id" => $productId,
        ":product_title" => $product["title"],
        ":btc_amount" => $btcAmount,
        ":btc_wallet" => BTC_WALLET,
        ":tx_hash" => $txHash,
        ":buyer_email" => $buyerEmail,
        ":buyer_name" => $buyerName ?: null,
        ":status" => "pending",
        ":buyer_ip" => $_SERVER["REMOTE_ADDR"] ?? null,
    ]);

    // 2. Записываем транзакцию
    $stmtTx = $db->prepare('
        INSERT INTO transactions (order_number, product_id, tx_hash, btc_amount, btc_wallet, buyer_email, status, created_at)
        VALUES (:order_number, :product_id, :tx_hash, :btc_amount, :btc_wallet, :buyer_email, :status, NOW())
    ');
    $stmtTx->execute([
        ":order_number" => $orderId,
        ":product_id" => $productId,
        ":tx_hash" => $txHash,
        ":btc_amount" => $btcAmount,
        ":btc_wallet" => BTC_WALLET,
        ":buyer_email" => $buyerEmail,
        ":status" => "pending",
    ]);

    // 3. Увеличиваем счётчик продаж товара
    $stmtSales = $db->prepare(
        "UPDATE products SET sales = sales + 1 WHERE id = :id",
    );
    $stmtSales->execute([":id" => $productId]);

    $db->commit();
} catch (PDOException $e) {
    $db->rollBack();
    $msg =
        $e->getCode() == 23000
            ? "Ошибка: этот TXID или номер заказа уже используется."
            : "Ошибка при сохранении заказа. Попробуйте ещё раз.";
    $_SESSION["payment_errors"] = [$msg];
    header("Location: detail.php?id={$productId}#payment");
    exit();
}

// Очищаем ошибки, устанавливаем успех
unset($_SESSION["payment_errors"]);
$_SESSION["payment_success"] = true;
$_SESSION["payment_order_id"] = $orderId;

// Редирект на страницу успеха
header("Location: detail.php?id={$productId}&payment=success&order={$orderId}");
exit();
