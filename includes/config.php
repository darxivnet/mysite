<?php
/**
 * Конфигурация сайта — загрузка из .env
 */

// Загружаем .env файл
$envFile = __DIR__ . "/../.env";
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Пропускаем комментарии
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }
        $parts = explode("=", $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Убираем кавычки если есть
            $value = trim($value, "\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Константы сайта
define("SITE_NAME", $_ENV["SITE_NAME"] ?? "SiteMarket");
define("SITE_URL", $_ENV["SITE_URL"] ?? "http://localhost:8000");
define("CURRENCY", $_ENV["CURRENCY"] ?? "BTC");
define(
    "BTC_WALLET",
    $_ENV["BTC_WALLET"] ?? "bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq",
);
define("ITEMS_PER_PAGE", (int) ($_ENV["ITEMS_PER_PAGE"] ?? 6));

// Настройки БД
define("DB_HOST", $_ENV["DB_HOST"] ?? "127.0.0.1");
define("DB_PORT", $_ENV["DB_PORT"] ?? "3306");
define("DB_NAME", $_ENV["DB_NAME"] ?? "sitemarket");
define("DB_USER", $_ENV["DB_USER"] ?? "root");
define("DB_PASS", $_ENV["DB_PASS"] ?? "");

// Админ-доступ
define("ADMIN_USER", $_ENV["ADMIN_USER"] ?? "admin");
define("ADMIN_PASS", $_ENV["ADMIN_PASS"] ?? "admin123");

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
