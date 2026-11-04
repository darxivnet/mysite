<?php
/**
 * Работа с товарами через MySQL
 */
require_once __DIR__ . "/database.php";

/**
 * Получить все активные товары
 * @return array
 */
function getAllProducts(): array
{
    $db = getDB();
    $stmt = $db->query(
        "SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC",
    );
    $products = $stmt->fetchAll();

    // Преобразуем JSON features в массив
    foreach ($products as &$product) {
        $product["features"] = json_decode($product["features"], true) ?? [];
        $product["domain_included"] = (bool) $product["domain_included"];
        $product["price_btc"] = (float) $product["price_btc"];
        $product["price_rub"] = (float) $product["price_rub"];
        $product["rating"] = (float) $product["rating"];
        $product["sales"] = (int) $product["sales"];
        $product["support_months"] = (int) $product["support_months"];
        $product["id"] = (int) $product["id"];
    }
    unset($product);

    return $products;
}

/**
 * Получить товар по ID
 * @param int $id
 * @return array|null
 */
function getProductById(int $id): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT * FROM products WHERE id = :id AND is_active = 1",
    );
    $stmt->execute([":id" => $id]);
    $product = $stmt->fetch();

    if (!$product) {
        return null;
    }

    $product["features"] = json_decode($product["features"], true) ?? [];
    $product["domain_included"] = (bool) $product["domain_included"];
    $product["price_btc"] = (float) $product["price_btc"];
    $product["price_rub"] = (float) $product["price_rub"];
    $product["rating"] = (float) $product["rating"];
    $product["sales"] = (int) $product["sales"];
    $product["support_months"] = (int) $product["support_months"];
    $product["id"] = (int) $product["id"];

    return $product;
}

/**
 * Получить похожие товары (исключая текущий)
 * @param int $currentId
 * @param int $limit
 * @return array
 */
function getRelatedProducts(int $currentId, int $limit = 3): array
{
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT * FROM products WHERE id != :id AND is_active = 1 ORDER BY RAND() LIMIT :limit",
    );
    $stmt->bindValue(":id", $currentId, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product["features"] = json_decode($product["features"], true) ?? [];
        $product["domain_included"] = (bool) $product["domain_included"];
        $product["price_btc"] = (float) $product["price_btc"];
        $product["price_rub"] = (float) $product["price_rub"];
        $product["rating"] = (float) $product["rating"];
        $product["sales"] = (int) $product["sales"];
        $product["support_months"] = (int) $product["support_months"];
        $product["id"] = (int) $product["id"];
    }
    unset($product);

    return $products;
}

/**
 * Получить количество активных товаров
 * @return int
 */
function getProductsCount(): int
{
    $db = getDB();
    return (int) $db
        ->query("SELECT COUNT(*) FROM products WHERE is_active = 1")
        ->fetchColumn();
}

/**
 * Получить товары с пагинацией
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getProductsPage(int $page = 1, int $perPage = 6): array
{
    $db = getDB();
    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare(
        "SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC LIMIT :limit OFFSET :offset",
    );
    $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        $product["features"] = json_decode($product["features"], true) ?? [];
        $product["domain_included"] = (bool) $product["domain_included"];
        $product["price_btc"] = (float) $product["price_btc"];
        $product["price_rub"] = (float) $product["price_rub"];
        $product["rating"] = (float) $product["rating"];
        $product["sales"] = (int) $product["sales"];
        $product["support_months"] = (int) $product["support_months"];
        $product["id"] = (int) $product["id"];
    }
    unset($product);

    return $products;
}

/**
 * Получить количество покупок конкретного товара (из таблицы orders)
 * @param int $productId
 * @return int
 */
function getProductPurchaseCount(int $productId): int
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM orders WHERE product_id = :pid AND status IN (\'pending\', \'confirming\', \'confirmed\', \'completed\')',
    );
    $stmt->execute([":pid" => $productId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Получить общее количество всех покупок (из таблицы orders)
 * @return int
 */
function getTotalPurchases(): int
{
    $db = getDB();
    return (int) $db
        ->query(
            "SELECT COUNT(*) FROM orders WHERE status IN ('pending','confirming','confirmed','completed')",
        )
        ->fetchColumn();
}

/**
 * Получить последние транзакции (для товара или все)
 * @param int|null $productId  если null — все транзакции
 * @param int      $limit
 * @return array
 */
function getRecentTransactions(?int $productId = null, int $limit = 5): array
{
    $db = getDB();
    if ($productId !== null) {
        $stmt = $db->prepare(
            'SELECT t.*, p.title AS product_title
             FROM transactions t
             JOIN products p ON t.product_id = p.id
             WHERE t.product_id = :pid
             ORDER BY t.created_at DESC
             LIMIT :limit',
        );
        $stmt->bindValue(":pid", $productId, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare(
            'SELECT t.*, p.title AS product_title
             FROM transactions t
             JOIN products p ON t.product_id = p.id
             ORDER BY t.created_at DESC
             LIMIT :limit',
        );
    }
    $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$row) {
        $row["btc_amount"] = (float) $row["btc_amount"];
        $row["confirmations"] = (int) $row["confirmations"];
        $row["product_id"] = (int) $row["product_id"];
        $row["id"] = (int) $row["id"];
    }
    unset($row);

    return $rows;
}

/**
 * Получить статистику транзакций
 * @return array
 */
function getTransactionStats(): array
{
    $db = getDB();
    return [
        "total_transactions" => (int) $db
            ->query("SELECT COUNT(*) FROM transactions")
            ->fetchColumn(),
        "total_btc" => (float) $db
            ->query(
                'SELECT COALESCE(SUM(btc_amount), 0) FROM transactions WHERE status != \'failed\'',
            )
            ->fetchColumn(),
        "pending" => (int) $db
            ->query(
                "SELECT COUNT(*) FROM transactions WHERE status = 'pending'",
            )
            ->fetchColumn(),
        "confirmed" => (int) $db
            ->query(
                "SELECT COUNT(*) FROM transactions WHERE status = 'confirmed'",
            )
            ->fetchColumn(),
    ];
}

/**
 * Получить заказ по номеру (order_number)
 * @param string $orderNumber
 * @return array|null
 */
function getOrderByNumber(string $orderNumber): ?array
{
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT o.*, t.status AS tx_status, t.confirmations, t.block_height
         FROM orders o
         LEFT JOIN transactions t ON t.order_number = o.order_number
         WHERE o.order_number = :num
         LIMIT 1',
    );
    $stmt->execute([":num" => $orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        return null;
    }

    $order["btc_amount"] = (float) $order["btc_amount"];
    $order["product_id"] = (int) $order["product_id"];
    $order["id"] = (int) $order["id"];
    $order["confirmations"] = (int) ($order["confirmations"] ?? 0);

    return $order;
}
