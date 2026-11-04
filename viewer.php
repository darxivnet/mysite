<?php
/**
 * Просмотр демо продаваемого сайта в iframe
 */
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/products.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $productId > 0 ? getProductById($productId) : null;

if (!$product) {
    define('PAGE_TITLE', 'Демо не найдено');
    include 'includes/header.php';
    ?>
    <div class="not-found">
        <div class="not-found__code">404</div>
        <p class="not-found__text">Демо-сайт не найден</p>
        <a href="index.php" class="btn btn--primary">Вернуться в каталог</a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

define('PAGE_TITLE', 'Демо: ' . $product['title']);
$demoUrl = $product['demo_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Демо: <?= htmlspecialchars($product['title']) ?> | SiteMarket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body { height:100%; overflow:hidden; }
        body { display:flex; flex-direction:column; font-family:'Segoe UI',system-ui,sans-serif; }

        .viewer-bar {
            display:flex; align-items:center; justify-content:space-between;
            padding:0 20px; height:56px; background:var(--white);
            border-bottom:1px solid var(--border); flex-shrink:0; z-index:10;
            box-shadow: var(--shadow-sm);
        }
        .viewer-bar__left {
            display:flex; align-items:center; gap:12px;
        }
        .viewer-bar__title {
            font-weight:700; font-size:16px; color:var(--blue-800);
            max-width:400px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .viewer-bar__badge {
            background:var(--blue-50); color:var(--blue-600);
            padding:3px 10px; border-radius:var(--radius-xl);
            font-size:12px; font-weight:600;
        }
        .viewer-bar__right {
            display:flex; align-items:center; gap:10px;
        }
        .iframe-wrap {
            flex:1; position:relative; background:#f1f5f9;
        }
        .iframe-wrap iframe {
            width:100%; height:100%; border:none;
        }
        .iframe-placeholder {
            position:absolute; inset:0; display:flex;
            align-items:center; justify-content:center;
            flex-direction:column; color:var(--text-dim); font-size:18px;
        }
        .iframe-placeholder .icon { font-size:64px; margin-bottom:12px; }
    </style>
</head>
<body>
    <div class="viewer-bar">
        <div class="viewer-bar__left">
            <a href="detail.php?id=<?= $product['id'] ?>" style="color:var(--text-dim);text-decoration:none;font-size:20px;" title="Назад">&larr;</a>
            <span class="viewer-bar__title"><?= htmlspecialchars($product['title']) ?></span>
            <span class="viewer-bar__badge">Демо</span>
        </div>
        <div class="viewer-bar__right">
            <span style="color:var(--red-500);font-weight:800;font-size:18px;margin-right:8px;">
                &#x20BF; <?= rtrim(rtrim(number_format($product['price_btc'], 4), '0'), '.') ?>
            </span>
            <a href="detail.php?id=<?= $product['id'] ?>#payment" class="btn btn--red">
                &#x20BF; Купить
            </a>
        </div>
    </div>

    <div class="iframe-wrap">
        <?php if ($demoUrl): ?>
            <iframe src="<?= htmlspecialchars($demoUrl) ?>" title="Демо сайта"></iframe>
        <?php else: ?>
            <div class="iframe-placeholder">
                <div class="icon">&#x1F4BB;</div>
                <div>Демо-версия недоступна для этого сайта</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
