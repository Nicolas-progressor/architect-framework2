# Architect RED 2 - Установка

## Требования

- PHP 8.0 или выше
- Composer
- Веб-сервер (Apache/Nginx) с поддержкой mod_rewrite

## Быстрый старт

### 1. Установка через Composer (рекомендуется)

```bash
# Создать новый проект
composer create-project architect/framework myproject

# Перейти в директорию проекта
cd myproject
```

### 2. Настройка веб-сервера

Настройте Document Root на директорию `htdocs/`:

**Apache (.htaccess уже настроен):**
```apache
<VirtualHost *:80>
    DocumentRoot "path/to/myproject/htdocs"
    ServerName myapp.local
</VirtualHost>
```

**Nginx:**
```nginx
server {
    root /path/to/myproject/htdocs;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Настройка базы данных

Отредактируйте файл `app/config/query.json`:

```json
{
    "cpu": true,
    "db": {
        "driver": "mysql",
        "host": "localhost",
        "database": "myapp",
        "username": "root",
        "password": "your_password",
        "charset": "utf8mb4"
    }
}
```

### 4. Запуск

Откройте в браузере `http://myapp.local/`

---

## Ручная установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/architect/framework.git myproject
cd myproject
composer install
```

### 2. Настройка структуры

```
myproject/
├── htdocs/          # Document Root
│   └── index.php    # Точка входа
├── system/          # Ядро фреймворка
├── app/             # Приложения
└── vendor/          # Composer dependencies
```

### 3. Настройка путей

Отредактируйте `htdocs/index.php` при необходимости:

```php
// Пути определяются автоматически относительно расположения index.php
// ROOT_DIR - директория с index.php
// SYS_DIR  - директория с system/
// APP_DIR  - директория с app/
```

---

## Конфигурация

### Основные конфигурационные файлы

| Файл | Назначение |
|------|------------|
| `app/config/apps.json` | Список приложений |
| `app/config/router.json` | Глобальная маршрутизация |
| `app/config/lang.json` | Настройки языков |
| `app/config/debug.json` | Настройки отладки |
| `app/config/query.json` | Подключение к БД |

### Включение отладки

Отредактируйте `app/config/debug.json`:

```json
{
    "enabled": true,
    "log_categories": ["info", "warning", "error"],
    "show_queries": true,
    "show_memory": true,
    "show_time": true
}
```

---

## Создание нового приложения

### 1. Добавьте приложение в apps.json

```json
{
    "default": "home",
    "apps": {
        "home": "home",
        "admin": "admin"
    }
}
```

### 2. Создайте структуру директорий

```
app/
└── admin/
    ├── config/
    │   └── template.json
    ├── routes/
    │   └── index.json
    ├── modules/
    │   └── dashboard/
    │       ├── controller.php
    │       ├── model/
    │       └── view/
    └── template/
        └── admin/
            ├── template.php
            └── elements.php
```

### 3. Создайте маршрут

Создайте файл `app/admin/routes/index.json`:

```json
{
    "default": "index",
    "routes": {
        "index": {
            "module": "dashboard",
            "controller": "index",
            "action": "index"
        }
    }
}
```

### 4. Создайте контроллер

Создайте файл `app/admin/modules/dashboard/controller.php`:

```php
<?php
namespace admin\controller;

class index extends \pattern\controller {
    
    public function index_app_data() {
        // Обработка данных
    }
    
    public function index_app_output() {
        $this->view->render('index');
    }
}
```

---

## Структура модуля

```
modules/
└── mymodule/
    ├── controller.php      # Основной контроллер
    ├── controller/         # Дополнительные контроллеры
    │   └── about.php
    ├── model/              # Модели
    │   └── sample.php
    ├── view/               # Представления
    │   └── index.php
    ├── widget/             # Виджеты
    │   └── mywidget.php
    └── lang/               # Языковые файлы
        └── ru/
            └── main.php
```

---

## Жизненный цикл контроллера

Контроллеры имеют методы для каждого этапа:

```php
<?php
namespace myapp\controller;

class mycontroller extends \pattern\controller {
    
    // app_load - загрузка контроллера
    public function action_app_load() { ... }
    
    // app_data - обработка данных
    public function action_app_data() { ... }
    
    // app_output - вывод
    public function action_app_output() {
        $this->view->render('view_name', $data);
    }
}
```

---

## Отладка

### Использование debug модуля

```php
// В контроллере
$debug = \debug\Debugger::getInstance();

$debug->info('Информационное сообщение');
$debug->warning('Предупреждение');
$debug->error('Ошибка');

// Профилирование
$debug->beginProfile('operation');
// ... код ...
$debug->endProfile('operation');

// Логирование запросов
$debug->addQuery('SELECT * FROM users', [], 0.05);
```

### Просмотр ошибок

При включённом debug в браузере отображается кнопка **DEBUG** в левом нижнем углу. При клике — панель с информацией о времени выполнения, логах и запросах.

---

## Обновление фреймворка

```bash
composer update architect/framework
```

---

## Структура директорий проекта

```
project/
├── htdocs/                    # Document Root
│   ├── index.php              # Точка входа
│   ├── .htaccess              # Apache config
│   └── assets/                # Статические ресурсы
│       ├── css/
│       ├── scripts/js/
│       └── modules/
├── system/                    # Ядро фреймворка
│   ├── architect/             # Основные классы
│   ├── core/                  # Базовые компоненты
│   ├── modules/               # Системные модули
│   └── unit/                  # Служебные модули
├── app/                       # Приложения
│   ├── config/                # Конфигурация
│   ├── lang/                  # Языковые файлы
│   ├── modules/               # Глобальные модули
│   └── home/                  # Приложение home
│       ├── config/
│       ├── routes/
│       ├── modules/
│       └── template/
└── vendor/                    # Composer dependencies
```

---

## Лицензия

MIT License - подробности в файле LICENSE
