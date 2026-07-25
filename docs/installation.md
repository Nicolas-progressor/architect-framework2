# Установка

В этом руководстве описана установка Architect RED 2 на локальную машину или сервер.

## Требования

- **PHP** 8.1 или выше
- **Composer** (менеджер зависимостей)
- **Веб-сервер** (Apache, Nginx или встроенный PHP-сервер)
- **База данных** (MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+ или другая, поддерживаемая PDO)
- **Расширения PHP**: PDO, OpenSSL, JSON, MBstring (рекомендуется)

## Шаг 1: Установка Composer

Если Composer ещё не установлен, скачайте его с [официального сайта](https://getcomposer.org/) и установите глобально.

Проверьте установку:

```bash
composer --version
```

## Шаг 2: Создание проекта

### Вариант A: Клонирование репозитория (рекомендуется)

```bash
git clone https://github.com/your-repo/architect-framework-2.git myproject
cd myproject
```

### Вариант B: Установка через Composer create-project

```bash
composer create-project architect/red2 myproject
cd myproject
```

## Шаг 3: Установка зависимостей

В корне проекта выполните:

```bash
composer install
```

Composer загрузит все необходимые пакеты, включая ядро фреймворка, сервисы и расширения.

## Шаг 4: Настройка окружения

Скопируйте файл `.env.example` в `.env`:

```bash
cp .env.example .env
```

Отредактируйте `.env` в текстовом редакторе. Укажите настройки вашего окружения:

```bash
# Окружение (development, testing, staging, production)
APP_ENV=development

# База данных
DB_HOST=localhost
DB_PORT=3306
DB_NAME=myapp
DB_USER=root
DB_PASSWORD=

# Кэш
CACHE_DRIVER=file

# Секретный ключ (сгенерируйте случайную строку)
APP_KEY=your-secret-key-here

# URL приложения
APP_URL=http://localhost
```

## Шаг 5: Настройка веб-сервера

### Apache

Настройте виртуальный хост, указывающий на папку `htdocs/` внутри проекта.

Пример конфигурации:

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /path/to/myproject/htdocs
    <Directory /path/to/myproject/htdocs>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Не забудьте включить mod_rewrite.

### Nginx

Пример конфигурации:

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /path/to/myproject/htdocs;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Встроенный PHP-сервер (для разработки)

Вы можете использовать встроенный сервер PHP:

```bash
php -S localhost:8000 -t htdocs/
```

Затем откройте в браузере `http://localhost:8000`.

## Шаг 6: Проверка установки

Откройте в браузере URL вашего приложения (например, `http://myapp.local`). Вы должны увидеть стартовую страницу Architect RED 2 с сообщением об успешной установке.

Если отображается ошибка, проверьте:
- Правильность настроек `.env`.
- Наличие прав на запись в папки `app/logs/`, `cache/` (если используются).
- Корректность конфигурации веб-сервера.

## Шаг 7: Дополнительные настройки

### Настройка базы данных

Создайте базу данных, указанную в `.env`. Затем выполните миграции (если они есть):

```bash
php arc migrate
```

(Команда `arc` — это консольный инструмент фреймворка, расположенный в `bin/arc`.)

### Настройка отладочной панели

В файле `app/config/debug.json` можно включить/отключить отладочную панель, настроить IP-белый список и другие параметры.

## Устранение неполадок

### Ошибка "Composer autoloader not found"

Убедитесь, что вы выполнили `composer install` и файл `vendor/autoload.php` существует.

### Ошибка 404

Проверьте, что веб-сервер правильно перенаправляет запросы на `htdocs/index.php`. Включите mod_rewrite для Apache или проверьте конфигурацию try_files для Nginx.

### Ошибка "PDOException"

Убедитесь, что расширение PDO установлено и активировано, а параметры подключения к БД верны.

### Ошибка "Permission denied"

Установите правильные права на запись для папок `app/logs/`, `cache/`, `storage/` (если используются):

```bash
chmod -R 775 app/logs cache storage
```

## Дальнейшие шаги

После успешной установки перейдите к руководству [Создание первого приложения](first-app.md), чтобы начать разработку.

## Обновление

Для обновления фреймворка до новой версии выполните:

```bash
composer update
```

Перед обновлением рекомендуется сделать резервную копию проекта и базы данных.