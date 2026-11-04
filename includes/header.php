<?php require_once __DIR__ . "/config.php"; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined("PAGE_TITLE")
        ? PAGE_TITLE . " | " . SITE_NAME
        : SITE_NAME . " — Маркетплейс готовых сайтов" ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛒</text></svg>">
</head>
<body>
    <!-- Верхняя панель -->
    <header class="header">
        <div class="container header__inner">
            <a href="index.php" class="logo">
                <span class="logo__icon">&#9670;</span>
                <span class="logo__text">Site<span class="logo__accent">Market</span></span>
            </a>
            <nav class="nav">
                <a href="index.php" class="nav__link <?= basename(
                    $_SERVER["PHP_SELF"],
                ) == "index.php"
                    ? "nav__link--active"
                    : "" ?>">Каталог</a>
                <a href="index.php#howitworks" class="nav__link">Как купить</a>
                <a href="index.php#faq" class="nav__link">FAQ</a>
            </nav>
        </div>
    </header>
    <main>
