# Запись к психологам — старт проекта

Минимальный скелет на чистом PHP (без фреймворка), чтобы можно было
сразу начать и не тонуть в конфигурации. Когда Максим пришлёт Docker —
структура файлов не изменится, поменяется только способ запуска.

## Структура

```
public/index.php        — точка входа (весь трафик идёт сюда через веб-сервер)
src/Core/                — роутер, подключение к БД
src/Controllers/         — контроллеры (по одному на раздел сайта)
src/Models/              — здесь будут модели (User, Appointment и т.д.)
templates/                — html-шаблоны (простой php-рендеринг, без Blade/Twig)
config/config.example.php — шаблон конфига (пароли и хосты)
config/config.php         — ваш локальный конфиг (в git не попадает)
database/schema.sql       — схема базы данных
```

## Шаг 1. Создание проекта в PhpStorm

1. `File → New → Project from Existing Sources` → выбрать эту папку.
2. PhpStorm сам предложит настроить PHP interpreter — укажите путь к
   вашему php.exe / php (если PHP ещё не установлен, см. шаг 2).
3. `Settings → PHP` → выставьте версию **8.1** (или любую 8.0–8.3 — что
   реально стоит на вашей машине).

## Шаг 2. Установка PHP и MySQL локально (пока нет Docker)

Проще всего через готовую сборку:

- **Windows**: [XAMPP](https://www.apachefriends.org/) — ставит сразу PHP,
  MySQL (MariaDB) и phpMyAdmin.
- **macOS**: `brew install php mysql` или тоже XAMPP.
- **Linux**: `sudo apt install php php-mysql mysql-server`.

Проверить, что PHP видно из терминала:
```bash
php -v
```

## Шаг 3. Composer (автозагрузка классов)

Composer нужен только чтобы не писать `require` на каждый класс руками.
Поставьте [Composer](https://getcomposer.org/), затем в папке проекта:

```bash
composer install
```

Это создаст папку `vendor/` с автозагрузчиком — без неё `public/index.php`
не запустится (там `require vendor/autoload.php`).

## Шаг 4. База данных

1. Создайте БД и таблицы:
   ```bash
   mysql -u root --default-character-set=utf8mb4 -p < database/schema.sql
   ```
   (через phpMyAdmin — то же самое, просто вкладка "Импорт" и выбрать файл)

2. Скопируйте конфиг и впишите свои данные:
   ```bash
   cp config/config.example.php config/config.php
   ```

## Шаг 5. Запуск сайта

Без Docker и без Apache/Nginx можно запустить встроенным сервером PHP —
этого достаточно для разработки:

```bash
php -S localhost:8000 -t public
```

Откройте http://localhost:8000 — должна открыться заглушка со списком
психологов (пустая, пока в БД нет данных — это нормально).

В PhpStorm то же самое можно сделать через `Run → Edit Configurations →
PHP Built-in Web Server`, указав Document root: `public`.

## Роли и вход

Пока нет SSO портала вуза — используются локальный логин/пароль. Обновите
базу тестовыми данными (или заново, если seed.sql уже был импортирован
раньше — файл изменился, пересоздайте базу):

```bash
mysql -u root -e "DROP DATABASE psycho_booking;"
mysql -u root --default-character-set=utf8mb4 < database/schema.sql
mysql -u root --default-character-set=utf8mb4 psycho_booking < database/seed.sql
```

Пароль у всех тестовых аккаунтов один — **password123**. Вход — на `/login`
(ссылка "Вход для сотрудников" в шапке сайта работает и для студентов).

| Роль        | Email                  | Куда попадаешь после входа |
|-------------|-------------------------|------------------------------|
| Админ       | admin@example.com       | `/admin` — список психологов, создание новых |
| Психолог    | filippova@example.com   | `/dashboard` — свои записи, `/dashboard/schedule` — расписание |
| Психолог    | sergeev@example.com     | то же самое |
| Студент     | student1@example.com    | `/cabinet` — свои записи, запись на приём |
| Студент     | student2@example.com    | то же самое |

### Как устроена авторизация (важно на будущее)

Логика входа разбита на две части специально, чтобы потом подключить SSO
вуза было легко:

- `src/Core/Auth.php` — работа с сессией (кто залогинен, какая роль).
  Не зависит от того, откуда взялась личность пользователя — не трогаем.
- `src/Core/Auth/AuthProviderInterface.php` — контракт "проверить
  учётные данные → вернуть пользователя или null".
- `src/Core/Auth/LocalAuthProvider.php` — сегодняшняя реализация:
  сверяет email/пароль с таблицей `users`.

Когда будет готова интеграция с порталом вуза, появится
`SsoAuthProvider implements AuthProviderInterface`, и в
`AuthController::login()` поменяется **одна строка**:
`new LocalAuthProvider()` → `new SsoAuthProvider()`. Все кабинеты,
проверки ролей и шаблоны продолжат работать как есть.

## Дальше

- `src/Controllers/HomeController.php` — пример контроллера с запросом к БД,
  используйте его как шаблон для остальных страниц.
- Ещё не сделано: заметки психолога к записи (видны только психологам),
  групповые мероприятия/объявления, автоподсчёт 9-часового лимита,
  раздел "Ссылки" с контактами.
- Когда придёт Docker от Максима — просто подключитесь к контейнеру с
  MySQL вместо локального, поменяв `config/config.php`. Остальной код
  трогать не придётся.
