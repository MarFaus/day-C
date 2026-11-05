# WorldSkills TP17 — Event Platform («День С2»)

Платформа для проведения конференций: админка организатора, REST API и
одностраничное приложение для участников.

Стек: Laravel 13.40, PHP 8.3, MySQL/MariaDB (SQLite — как резервный вариант),
Blade + Bootstrap в админке, vanilla JS в клиентской части.

## Что реализовано

### Админка организатора
- Вход по email и паролю, сессионная авторизация.
- CRUD событий, билетов, сессий, каналов и комнат.
- Проверка занятости комнаты при создании/редактировании сессии
  (`Room already booked during this time`).
- Валидация слагов: `a-z`, `0-9`, `-`, уникальность в рамках организатора.
- Отчёт «Room capacity» с диаграммой Chart.js.
- Мультитенантность: организатор видит и редактирует только свои события.

### REST API (`/api/v1`)
- `GET /events` — ближайшие события.
- `GET /organizers/{organizer}/events/{event}` — детали события.
- `POST /login`, `POST /logout` — авторизация участника.
- `POST /organizers/{organizer}/events/{event}/registration` — регистрация.
- `GET /registrations` — регистрации текущего участника.
- `GET /ratings`, `POST /events/{id}/rating`, `POST /sessions/{id}/rating` —
  оценки событий и сессий.

### Клиентская часть (`/app`)
- Список событий, программа по каналам/комнатам, карточка сессии.
- Вход по фамилии и коду регистрации.
- Выбор билета и воркшопов, подсчёт стоимости, покупка.
- Отметки о регистрации и выставление оценок.

## Быстрый старт (MySQL/MariaDB)

1. Создайте базу (по умолчанию `wsc_t17`).
2. Проверьте параметры подключения в `.env`.
3. Установите схему и демо-данные:

```bash
php artisan app:install --fresh
php artisan serve --host=127.0.0.1 --port=8000
```

Адреса:

- Админка: <http://127.0.0.1:8000/login>
  - `demo1@worldskills.org` / `demopass1`
  - `demo2@worldskills.org` / `demopass2`
- Приложение участника: <http://127.0.0.1:8000/app>
  - `Yakovich` / `35DGZX`

### Вариант без MySQL (SQLite)

В репозитории рядом лежит `.env.sqlite` с готовой SQLite-конфигурацией:

```powershell
Copy-Item .env.sqlite .env -Force
php artisan app:install --fresh
php artisan serve --host=127.0.0.1 --port=8000
```

## Приёмочные тесты (PHPUnit)

Тесты находятся в `tests/php-api`. Им нужен запущенный сервер приложения и
MySQL-база, в которую они загружают `tests/php-api/data/db-dump.sql`.

1. Настройте `tests/php-api/.env` (URL — **без** завершающего слэша):

```dotenv
DB_HOST=127.0.0.1
DB_USER=root
DB_PW=
DB_NAME=wsc_t17
URL=http://127.0.0.1:8000
```

2. Убедитесь, что приложение в `.env` использует ту же MySQL-базу.
3. Запустите:

```bash
cd tests/php-api
composer install
composer test
```

Ожидаемый результат: `OK (21 tests, 77 assertions)` и сводка по блокам
B1–B4. Для запуска одного блока:

```bash
composer test -- --filter B2EventDetailTest
```

Примечания:

- В комплекте тестов PHPUnit обновлён до 9.6, а кастомный принтер
  (`tests/printer.php`) адаптирован под новый API.
- Приёмочная база должна слушать порт `3306`: конфигурация тестов не
  поддерживает `DB_PORT`.

## Данные и настройки

- `database/db-dump.sql` — исходный дамп WorldSkills.
- `database/seeders/DumpSeeder.php` — импорт дампа (работает и на MySQL, и на SQLite).
- `EVENTS_UPCOMING_FROM=2019-09-23` — дата, от которой считаются «ближайшие»
  события (датасет 2019 года).
- `RULES_AS_OF=2019-09-23` — опорная дата для правил доступности билетов.

## Структура

```
app/Http/Controllers/Admin   — CRUD админки и отчёт
app/Http/Controllers/Api     — REST API
app/Models                   — модели Eloquent
database/migrations          — схема БД
database/seeders             — импорт дампа
public/assets/attendee       — SPA участника
resources/views              — Blade-шаблоны
routes/web.php, routes/api.php
tests/php-api                — приёмочные тесты
```

## Про папку `День С2__chatgpt_backup`

`D:\Проекты\День С2__chatgpt_backup` — резервная копия предыдущей, незавершённой
попытки. Для запуска проекта она не нужна и её можно удалить. Если в проводнике
файлы выглядят «задвоенными», это результаты поиска по обеим папкам: в самом
проекте дубликатов нет.
