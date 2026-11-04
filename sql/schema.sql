-- ============================================
-- SiteMarket — схема базы данных MySQL
-- ============================================

CREATE DATABASE IF NOT EXISTS sitemarket
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sitemarket;

-- Таблица товаров
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255)    NOT NULL,
    category        VARCHAR(100)    NOT NULL,
    price_btc       DECIMAL(12,6)   NOT NULL,
    price_rub       DECIMAL(12,2)   NOT NULL,
    description     TEXT            NOT NULL,
    features        JSON            NOT NULL COMMENT 'JSON-массив возможностей',
    tech_stack      VARCHAR(500)    NOT NULL,
    domain_included TINYINT(1)      NOT NULL DEFAULT 0,
    support_months  INT UNSIGNED    NOT NULL DEFAULT 1,
    image           VARCHAR(255)    DEFAULT NULL,
    demo_url        VARCHAR(500)    DEFAULT NULL,
    rating          DECIMAL(3,1)    NOT NULL DEFAULT 0.0,
    sales           INT UNSIGNED    NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица транзакций (Bitcoin-платежи)
CREATE TABLE transactions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(50)     NOT NULL,
    product_id      INT UNSIGNED    NOT NULL,
    tx_hash         VARCHAR(128)    NOT NULL,
    btc_amount      DECIMAL(12,6)   NOT NULL,
    btc_wallet      VARCHAR(255)    NOT NULL,
    buyer_email     VARCHAR(255)    NOT NULL,
    confirmations   INT UNSIGNED    NOT NULL DEFAULT 0 COMMENT 'Подтверждений в сети',
    block_height    INT UNSIGNED    DEFAULT NULL,
    status          ENUM('pending','confirming','confirmed','failed','cancelled') NOT NULL DEFAULT 'pending',
    raw_response    JSON            DEFAULT NULL COMMENT 'Ответ от Blockchain API',
    confirmed_at    TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_tx_hash (tx_hash),
    UNIQUE INDEX uq_tx_hash (tx_hash),
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица заказов
CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(50)     NOT NULL UNIQUE,
    product_id      INT UNSIGNED    NOT NULL,
    product_title   VARCHAR(255)    NOT NULL,
    btc_amount      DECIMAL(12,6)   NOT NULL,
    btc_wallet      VARCHAR(255)    NOT NULL,
    tx_hash         VARCHAR(128)    NOT NULL,
    buyer_email     VARCHAR(255)    NOT NULL,
    buyer_name      VARCHAR(255)    DEFAULT NULL,
    status          ENUM('pending','confirming','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    buyer_ip        VARCHAR(45)     DEFAULT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_order_number (order_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Начальные данные (сиды)
-- ============================================

INSERT INTO products (title, category, price_btc, price_rub, description, features, tech_stack, domain_included, support_months, image, demo_url, rating, sales) VALUES
(
    'Интернет-магазин «TechShop»',
    'E-Commerce',
    0.025000,
    150000.00,
    'Полнофункциональный интернет-магазин на Laravel + Vue.js. Включает админ-панель, корзину, онлайн-оплату, интеграцию с 1С и Telegram-бота для уведомлений.',
    '["Адаптивный дизайн","Админ-панель","Онлайн-оплата (ЮKassa, CloudPayments)","Интеграция с 1С","Telegram-бот","SEO-оптимизация"]',
    'PHP 8.2, Laravel 10, Vue.js 3, MySQL, Redis',
    1, 3, 'assets/site1.jpg', 'demo/?id=1', 4.8, 24
),
(
    'Корпоративный портал «BizHub»',
    'Корпоративный',
    0.045000,
    280000.00,
    'Внутренний портал для компаний с CRM, документооборотом, задачами и чатами. Разработан на Symfony с микро-сервисной архитектурой.',
    '["CRM-система","Электронный документооборот","Трекер задач (Kanban)","Корпоративный чат","API для интеграций","Ролевая модель доступа"]',
    'PHP 8.2, Symfony 6, PostgreSQL, RabbitMQ, Docker',
    0, 6, 'assets/site2.jpg', 'demo/?id=2', 4.9, 17
),
(
    'Блог-платформа «WriteFlow»',
    'Контент',
    0.012000,
    75000.00,
    'Современная платформа для блогеров с Markdown-редактором, системой подписок, email-рассылками и монетизацией контента.',
    '["Markdown/WYSIWYG редактор","Система подписок","Email-рассылки","Монетизация через подписки","RSS-лента","Тёмная тема"]',
    'PHP 8.1, Laravel 9, Alpine.js, Tailwind CSS, MySQL',
    1, 2, 'assets/site3.jpg', 'demo/?id=3', 4.6, 42
),
(
    'Сервис бронирования «BookEasy»',
    'Сервис',
    0.035000,
    220000.00,
    'Платформа для бронирования услуг: отели, рестораны, салоны красоты. Календарь, уведомления, оплата депозитов.',
    '["Интерактивный календарь","SMS/Email уведомления","Оплата депозитов онлайн","Личный кабинет","Панель администратора","Мультиязычность"]',
    'PHP 8.2, Laravel 10, Livewire, MySQL, Stripe API',
    1, 4, 'assets/site4.jpg', 'demo/?id=4', 4.7, 31
),
(
    'Маркетплейс «DealZone»',
    'E-Commerce',
    0.080000,
    500000.00,
    'Многофункциональный маркетплейс с системой рейтингов, отзывов, арбитража сделок и безопасной оплаты через эскроу.',
    '["Мульти-вендорная платформа","Эскроу-платежи","Рейтинги и отзывы","Арбитраж сделок","Продвинутый поиск (Elasticsearch)","Мобильное API"]',
    'PHP 8.2, Symfony 6, Elasticsearch, PostgreSQL, Docker',
    0, 12, 'assets/site5.jpg', 'demo/?id=5', 5.0, 9
),
(
    'Лендинг-конструктор «PageForge»',
    'Инструмент',
    0.018000,
    110000.00,
    'Drag-and-drop конструктор лендингов с 50+ готовыми блоками, A/B тестированием, аналитикой и экспортом HTML.',
    '["Drag-and-drop редактор","50+ готовых блоков","A/B тестирование","Аналитика посещений","Экспорт HTML/CSS","Интеграция с CRM"]',
    'PHP 8.2, Laravel 10, React, MySQL, Redis',
    1, 3, 'assets/site6.jpg', 'demo/?id=6', 4.5, 56
);
