<?php
require_once "includes/config.php";
require_once "includes/products.php";
define("PAGE_TITLE", "Каталог готовых сайтов");
include "includes/header.php";
?>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <h1 class="hero__title">Покупайте готовые <span>сайты</span><br>за Биткоины</h1>
        <p class="hero__subtitle">
            Маркетплейс готовых веб-проектов. Запустите бизнес за 24 часа — выберите сайт, оплатите Bitcoin и получите исходный код.
        </p>
        <div class="hero__stats">
            <div class="hero__stat">
                <div class="hero__stat-value"><?= getProductsCount() ?>+</div>
                <div class="hero__stat-label">Готовых проектов</div>
            </div>
            <div class="hero__stat">
                <div class="hero__stat-value">&#x20BF;</div>
                <div class="hero__stat-label">Оплата в Bitcoin</div>
            </div>
            <div class="hero__stat">
                <div class="hero__stat-value">24/7</div>
                <div class="hero__stat-label">Поддержка</div>
            </div>
        </div>
    </div>
</section>

<!-- Каталог -->
<section class="section section--light" id="catalog">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Готовые <span>сайты</span> на продажу</h2>
            <p class="section__desc">Каждый проект проверен, имеет документацию и техническую поддержку</p>
        </div>
        <div class="catalog-grid">
            <?php
            $page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
            $perPage = ITEMS_PER_PAGE;
            $totalProducts = getProductsCount();
            $totalPages = max(1, (int) ceil($totalProducts / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $products = getProductsPage($page, $perPage);
            foreach ($products as $product): ?>
            <div class="card">
                <div class="card__image-wrap">
                    <?php if (file_exists($product["image"])): ?>
                        <img src="<?= $product[
                            "image"
                        ] ?>" alt="<?= htmlspecialchars(
    $product["title"],
) ?>" class="card__image" loading="lazy">
                    <?php else: ?>
                        <div class="card__image-placeholder">&#x1F4BB;</div>
                    <?php endif; ?>
                    <span class="card__category"><?= htmlspecialchars(
                        $product["category"],
                    ) ?></span>
                    <span class="card__rating"><span>&#9733;</span> <?= $product[
                        "rating"
                    ] ?></span>
                </div>
                <div class="card__body">
                    <h3 class="card__title"><?= htmlspecialchars(
                        $product["title"],
                    ) ?></h3>
                    <p class="card__desc"><?= htmlspecialchars(
                        $product["description"],
                    ) ?></p>
                    <div class="card__tags">
                        <?php foreach (
                            array_slice($product["features"], 0, 3)
                            as $feat
                        ): ?>
                            <span class="card__tag"><?= htmlspecialchars(
                                $feat,
                            ) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card__footer">
                    <div>
                        <div class="card__price-btc">&#x20BF; <?= number_format(
                            $product["price_btc"],
                            4,
                        ) ?></div>
                        <div class="card__price-rub">≈ <?= number_format(
                            $product["price_rub"],
                            0,
                            ",",
                            " ",
                        ) ?> ₽</div>
                    </div>
                    <a href="detail.php?id=<?= $product[
                        "id"
                    ] ?>" class="card__btn">Купить</a>
                    <?php if (!empty($product["demo_url"])): ?>
                    <a href="viewer.php?id=<?= $product[
                        "id"
                    ] ?>" class="card__btn" style="background:var(--blue-700);margin-left:6px;">&#x1F441; Смотреть</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach;
            ?>
        </div>

        <!-- Пагинация -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page -
                    1 ?>" class="pagination__btn">&laquo; Назад</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?= $p ?>" class="pagination__btn <?= $p ===
$page
    ? "pagination__btn--active"
    : "" ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page +
                    1 ?>" class="pagination__btn">Вперёд &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Как это работает -->
<section class="section" id="howitworks">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Как <span>купить</span> сайт</h2>
            <p class="section__desc">Простой процесс покупки за 4 шага</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step__num">1</div>
                <h3 class="step__title">Выберите сайт</h3>
                <p class="step__desc">Просмотрите каталог, выберите подходящий проект и ознакомьтесь с деталями</p>
            </div>
            <div class="step">
                <div class="step__num">2</div>
                <h3 class="step__title">Оплатите Bitcoin</h3>
                <p class="step__desc">Отсканируйте QR-код или отправьте BTC на указанный кошелёк</p>
            </div>
            <div class="step">
                <div class="step__num">3</div>
                <h3 class="step__title">Подтвердите платёж</h3>
                <p class="step__desc">Укажите хеш транзакции — мы проверим поступление средств</p>
            </div>
            <div class="step">
                <div class="step__num">4</div>
                <h3 class="step__title">Получите сайт</h3>
                <p class="step__desc">Сразу после подтверждения вы получите архив с исходным кодом и документацией</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section">
    <div class="container">
        <div class="cta">
            <h2 class="cta__title">Не нашли нужный сайт?</h2>
            <p class="cta__desc">Мы можем разработать сайт под ваши задачи. Напишите нам — обсудим проект и стоимость.</p>
            <a href="mailto:support@sitemarket.ru" class="btn btn--primary btn--lg">Заказать разработку</a>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section section--light" id="faq">
    <div class="container">
        <div class="section__header">
            <h2 class="section__title">Частые <span>вопросы</span></h2>
        </div>
        <div class="faq">
            <div class="faq__item">
                <button class="faq__q" onclick="this.parentElement.classList.toggle('faq__item--open')">Почему оплата только в Bitcoin?</button>
                <div class="faq__a"><p>Bitcoin обеспечивает быстрые, безопасные и необратимые транзакции без посредников. Это позволяет нам мгновенно передавать вам сайт после подтверждения платежа.</p></div>
            </div>
            <div class="faq__item">
                <button class="faq__q" onclick="this.parentElement.classList.toggle('faq__item--open')">Что я получу после покупки?</button>
                <div class="faq__a"><p>Вы получите полный исходный код сайта, базу данных, документацию по установке, домен (если указано) и техническую поддержку на указанный период.</p></div>
            </div>
            <div class="faq__item">
                <button class="faq__q" onclick="this.parentElement.classList.toggle('faq__item--open')">Как быстро я получу сайт?</button>
                <div class="faq__a"><p>Сразу после подтверждения Bitcoin-транзакции (обычно 1-3 подтверждения сети) вы получите доступ к скачиванию архива с проектом.</p></div>
            </div>
            <div class="faq__item">
                <button class="faq__q" onclick="this.parentElement.classList.toggle('faq__item--open')">Можно ли вернуть деньги?</button>
                <div class="faq__a"><p>Так как вы получаете полный исходный код, возврат средств не предусмотрен. Однако мы предоставляем техническую поддержку, чтобы помочь с установкой и запуском.</p></div>
            </div>
        </div>
    </div>
</section>

<?php include "includes/footer.php"; ?>
