# Система Helpers

Система Helpers — это замена устаревшей системы Statics, предоставляющая удобные фасады для часто используемых сервисов, аналогично Laravel Facades.

## Обзор

Helpers организованы как сервисы, зарегистрированные в контейнере зависимостей, с статическими фасадами для простого доступа. Каждый хелпер представляет собой отдельный класс с определённым набором методов.

Система поддерживает **автоматическое обнаружение** хелперов через Composer autoload, что позволяет добавлять новые хелперы без редактирования конфигурационных файлов. Для этого достаточно создать класс, реализующий интерфейс `HelperInterface` (или наследующий от `AbstractHelper`), и система автоматически зарегистрирует его при запуске.

## Доступные хелперы

| Фасад | Сервис | Описание |
|-------|--------|----------|
| `Helper_Title` | `Architect\Helpers\Title\TitleHelper` | Управление заголовками страниц |
| `Helper_Breadcrumbs` | `Architect\Helpers\Breadcrumbs\BreadcrumbsHelper` | Построение навигационной цепочки |
| `Helper_Html` | `Architect\Helpers\Html\HtmlHelper` | Генерация HTML-элементов |
| `Helper_Assets` | `Architect\Helpers\Assets\AssetsHelper` | Управление ресурсами (CSS, JS) |
| `Helper_Request` | `Architect\Helpers\Request\RequestHelper` | Работа с параметрами HTTP-запроса (GET, POST, CPU) |
| `Helper_Db` | `Architect\Helpers\Db\DbHelper` | Прямой доступ к базе данных |
| `Helper_Arr` | `Architect\Helpers\ArrayHelper\ArrayHelper` | Работа с массивами |
| `Helper_Number` | `Architect\Helpers\NumberHelper\NumberHelper` | Форматирование чисел |

## Использование

### Через фасад

```php
use Architect\Helpers\Title\Facades\Helper_Title;

Helper_Title::set('Главная страница');
echo Helper_Title::render();
```

### Через контейнер

```php
$title = $container->get('helpers')->get('title');
$title->set('Главная страница');
```

### В шаблонах Blueprint

Система интегрирована с Blueprint, предоставляя несколько способов доступа к хелперам:

1. **Функция `helpers()`** (и её алиасы `statics()`, `Helpers()`, `Statics()`):
   ```twig
   {{ helpers('title').set('Заголовок') }}
   {{ helpers('html').href('/about') }}
   ```

2. **Шорткаты** — автоматически создаются для каждого обнаруженного хелпера (имя в нижнем регистре):
   ```twig
   {{ title().set('Заголовок') }}
   {{ breadcrumbs().add('Главная', '/') }}
   {{ html().link('Ссылка', '/url') }}
   {{ assets().style('css/app.css') }}
   ```

3. **Прямые статические вызовы фасадов** — благодаря автоматической регистрации псевдонимов классов, вы можете использовать фасады напрямую, как в PHP-коде:
   ```twig
   {{ Helper_Title::set('Заголовок') }}
   {{ Helper_Html::href('/about') }}
   {{ Helper_Assets::style('css/app.css') }}
   ```
   Это обеспечивает полную универсальность и единообразие синтаксиса между PHP и шаблонами.

Все три способа работают одинаково и возвращают один и тот же экземпляр хелпера, зарегистрированный в контейнере.

> **Примечание:** Для работы статических вызовов фасадов в Blueprint необходимо, чтобы сервис-провайдер хелперов был зарегистрирован (это происходит автоматически при использовании стандартного `bootstrap.php`). Псевдонимы классов (`Helper_*`) создаются автоматически при регистрации провайдера.

## Конфигурация

Хелперы могут быть настроены через файл `app/config/helpers.json`, но это не обязательно. Система автоматически обнаруживает все классы, реализующие `HelperInterface` (или наследующие от `AbstractHelper`), и регистрирует их.

Конфигурационный файл полезен для переопределения стандартных хелперов или для ручной регистрации хелперов, которые не могут быть обнаружены автоматически (например, классы вне стандартных пространств имён). Пример:

```json
{
    "title": "Architect\\Helpers\\Title\\TitleHelper",
    "breadcrumbs": "Architect\\Helpers\\Breadcrumbs\\BreadcrumbsHelper",
    "html": "Architect\\Helpers\\Html\\HtmlHelper",
    "assets": "Architect\\Helpers\\Assets\\AssetsHelper",
    "request": "Architect\\Helpers\\Request\\RequestHelper",
    "db": "Architect\\Helpers\\Db\\DbHelper",
    "arr": "Architect\\Helpers\\ArrayHelper\\ArrayHelper",
    "number": "Architect\\Helpers\\NumberHelper\\NumberHelper"
}
```

Если конфигурация отсутствует или пуста, система выполнит автоматическое обнаружение хелперов в пространствах имён `Architect\Helpers` и `App\Helpers`. Обнаруженные хелперы регистрируются с алиасами, определёнными методом `getAlias()` каждого класса.

## Добавление нового хелпера

Создание нового хелпера стало проще благодаря автоматическому обнаружению. Достаточно выполнить следующие шаги:

1. **Создайте класс сервиса**, реализующий интерфейс `HelperInterface` (или наследующий от `AbstractHelper`). Рекомендуется размещать класс в пространстве имён `Architect\Helpers\*` (например, `Architect\Helpers\MyHelper`) или `App\Helpers`, но это не обязательно — система обнаружит класс в любом месте, где работает автозагрузка Composer.

   Пример класса:
   ```php
   namespace App\Helpers;

   use Architect\Helpers\Core\AbstractHelper;

   class MyHelper extends AbstractHelper
   {
       public function greet(string $name): string
       {
           return "Hello, $name!";
       }
   }
   ```

   Алиас хелпера будет определён автоматически (по умолчанию — имя класса без суффикса `Helper` в нижнем регистре). Вы можете переопределить метод `getAlias()`, чтобы задать свой алиас.

2. **Создайте фасад** (опционально, но рекомендуется для удобства). Фасад должен находиться в пространстве имён `Architect\Helpers\MyHelper\Facades` и наследовать от `Architect\Helpers\Core\Facade`. Метод `getFacadeAccessor()` должен возвращать алиас хелпера.

   Пример фасада:
   ```php
   namespace Architect\Helpers\MyHelper\Facades;

   use Architect\Helpers\Core\Facade;

   class Helper_MyHelper extends Facade
   {
       protected static function getFacadeAccessor(): string
       {
           return 'myhelper'; // алиас, который возвращает getAlias()
       }
   }
   ```

   Если вы используете `AbstractHelper`, фасад может быть создан автоматически — система попытается найти класс фасада по соглашению (замена последнего сегмента namespace на `Facades` и добавление префикса `Helper_`). Если фасад не найден, хелпер всё равно будет доступен через контейнер.

3. **Готово**. Хелпер автоматически будет обнаружен при следующем запуске приложения и зарегистрирован в контейнере. Вы можете использовать его через фасад `Helper_MyHelper::greet('World')` или через контейнер `$container->get('helpers')->get('myhelper')`.

   В шаблонах Blueprint хелпер также станет доступен через функцию `myhelper()` (если алиас `myhelper`).

### Ручная регистрация

Если по какой-то причине автоматическое обнаружение не работает (например, класс находится вне стандартных пространств имён), вы можете зарегистрировать хелпер вручную через конфигурационный файл `app/config/helpers.json`:

```json
{
    "custom": "App\\Helpers\\MyHelper"
}
```

Конфигурация имеет приоритет над автоматическим обнаружением, поэтому вы можете переопределить стандартные хелперы или добавить новые.

## Обратная совместимость

Класс `Statics` был полностью удалён. Все вызовы `Statics::*` должны быть заменены на соответствующие фасады с префиксом `Helper_`. В случае конфликтов имён (например, класс `Query` в пространстве имён приложения) использование префикса позволяет избежать коллизий.

## Тестирование

Для тестирования хелперов используется PHPUnit. Пример теста:

```php
use Architect\Helpers\Title\Facades\Helper_Title;

class TitleTest extends TestCase
{
    public function testTitle()
    {
        Helper_Title::set('Test');
        $this->assertEquals('Test', Helper_Title::get());
    }
}
```

Запуск тестов:

```bash
./vendor/bin/phpunit tests/Helpers/
```

## Миграция с Statics

1. Замените `Statics::Title()` на `Helper_Title::`.
2. Замените `Statics::Breadcrumbs()` на `Helper_Breadcrumbs::`.
3. Замените `Statics::Html()` на `Helper_Html::`.
4. Замените `Statics::Assets()` на `Helper_Assets::`.
5. Замените `Statics::Query()` на `Helper_Request::` (хелпер переименован).
6. Замените `Statics::DB()` на `Helper_Db::`.

После полной замены можно удалить пакет `architect/blueprint-statics` и директорию `architect/Statics/` (уже выполнено).

## Примечания

- Фасады требуют инициализации контейнера. В обычном приложении это происходит автоматически через `bootstrap.php`.
- Для использования в изолированном окружении (тесты, консольные скрипты) необходимо вручную установить контейнер через `Facade::setContainer()`.
- Префикс `Helper_` добавлен ко всем хелперам для предотвращения конфликтов имён с классами приложений. В коде приложений следует использовать полные имена фасадов (например, `Helper_Title`).
- Хелпер `Helper_Request` предоставляет методы `get()`, `post()`, `cpu()`, `all()` для работы с параметрами HTTP-запроса.
- Каждый хелпер размещается в собственной папке внутри `architect/Helpers/` (например, `Title/`, `Html/`), а его фасад — в подпапке `Facades/`. Это позволяет упаковывать хелперы как отдельные Composer-пакеты.