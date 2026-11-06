# Приёмочные тесты API

Набор из 21 теста WorldSkills TP17. Тесты работают как чёрный ящик: сбрасывают
базу `wsc_t17`, наполняют её `data/db-dump.sql` и ходят по HTTP к запущенному
приложению.

## Настройка

Нужны MySQL/MariaDB на `127.0.0.1:3306` и запущенный сервер приложения
(например, `php artisan serve`).

```bash
cd tests/php-api
composer install
cp .env.example .env       # Windows PowerShell: Copy-Item .env.example .env
```

Проверьте `.env`:

```dotenv
DB_HOST=127.0.0.1
DB_USER=root
DB_PW=
DB_NAME=wsc_t17
URL=http://127.0.0.1:8000
```

`URL` — без завершающего слэша.

## Запуск

```bash
composer test
```

Ожидаемый результат: `OK (21 tests, 77 assertions)` и сводка по блокам B1–B4.

Один блок:

```bash
composer test -- --filter B2EventDetailTest
```

## Структура

```text
data/db-dump.sql        исходные данные для тестов
tests/Feature           B1–B4: события, вход, регистрация
tests/Helper            HTTP-клиент, конфиг и assert-хелперы
tests/printer.php       кастомный вывод результата
phpunit.xml             конфигурация PHPUnit 9.6
```
