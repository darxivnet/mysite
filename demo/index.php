<?php
/**
 * Демо-страница продаваемого сайта
 * Генерирует preview на основе ID товара
 */
require_once "../includes/config.php";
require_once "../includes/database.php";
require_once "../includes/products.php";

$productId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$product = $productId > 0 ? getProductById($productId) : null;

if (!$product) {
    http_response_code(404);
    echo '<h1 style="text-align:center;padding:100px;font-family:sans-serif;">404 — Демо не найдено</h1>';
    exit();
}

// Цвета для разных товаров
$colors = [
    1 => ["#2563eb", "#1e40af"],
    2 => ["#7c3aed", "#5b21b6"],
    3 => ["#059669", "#047857"],
    4 => ["#ea580c", "#c2410c"],
    5 => ["#dc2626", "#b91c1c"],
    6 => ["#0891b2", "#0e7490"],
];
$c = $colors[$product["id"]] ?? ["#2563eb", "#1d4ed8"];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product["title"]) ?> — Демо</title>
<p>oooooooooooooooooooooooooooooooooooooooooooooooooooooooooo</p>
</body>
</html>
