# Формы (Form)

Компонент Form предоставляет инструменты для создания, валидации и обработки HTML-форм. Он включает генерацию полей, CSRF-защиту, работу с устаревшими данными (old input) и валидацию по правилам.

## Структура компонента

Компонент Form состоит из нескольких классов:

- `Form` – фасад, предоставляющий высокоуровневый API.
- `FormBuilder` – генерация HTML-полей формы.
- `FormValidator` – валидация данных по правилам.
- `CSRFTokenManager` – управление CSRF-токенами.

## Использование

### Получение экземпляра

Форма доступна через контейнер зависимостей:

```php
$form = $container->get('form');
```

Или через контроллер (унаследованный от `Controller`):

```php
$form = $this->form;
```

## Генерация HTML

### Открытие и закрытие формы

```php
echo $form->open('/submit', ['method' => 'post']);
echo $form->close();
```

Метод `open` автоматически добавляет скрытое поле CSRF-токена, если метод `POST`, `PUT`, `PATCH` или `DELETE`.

### Поля формы

```php
echo $form->input('name', 'John', ['class' => 'form-control']);
echo $form->textarea('bio', '', ['rows' => 5]);
echo $form->select('role', ['admin' => 'Administrator', 'user' => 'User'], 'user');
echo $form->checkbox('agree', 1, true);
echo $form->radio('gender', 'male', true);
echo $form->submit('Send');
```

Все методы принимают атрибуты HTML в виде массива.

### Старые данные (Old Input)

После валидации с ошибками часто нужно повторно заполнить поля введёнными ранее значениями. Form автоматически сохраняет данные из запроса в сессию и предоставляет метод `old`:

```php
echo $form->input('email', $form->old('email', ''));
```

Данные очищаются после следующего запроса или могут быть очищены вручную через `$form->flushOld()`.

## Валидация

### Правила валидации

Валидатор поддерживает набор правил, похожих на Laravel:

```php
$validator = $form->validator($_POST, [
    'name' => 'required|min:3|max:255',
    'email' => 'required|email|unique:users,email',
    'password' => 'required|confirmed|min:8',
    'age' => 'integer|between:18,99'
]);
```

Доступные правила: `required`, `email`, `numeric`, `integer`, `string`, `min`, `max`, `between`, `in`, `regex`, `confirmed`, `unique`, `exists` и другие.

### Проверка валидации

```php
if ($validator->fails()) {
    $errors = $validator->errors();
    // вернуть пользователя к форме с ошибками
} else {
    $validated = $validator->validated();
    // обработать данные
}
```

Ошибки валидации автоматически сохраняются в сессии и могут быть отображены в форме.

### Кастомные правила

Вы можете добавить собственное правило через метод `extend`:

```php
$validator->extend('phone', function($attribute, $value, $parameters) {
    return preg_match('/^\+?[0-9\s\-\(\)]+$/', $value);
}, 'Некорректный номер телефона.');
```

## CSRF-защита

### Токен

CSRF-токен генерируется автоматически при создании формы методом `POST`. Токен хранится в сессии и проверяется при обработке запроса.

Для ручной генерации токена:

```php
$token = $form->csrfToken();
```

### Проверка токена

Middleware `csrf` автоматически проверяет токен для маршрутов, требующих защиты. Вы также можете проверить вручную:

```php
if (!$form->validateCsrfToken($_POST['_token'] ?? '')) {
    throw new \Exception('CSRF token mismatch');
}
```

## Загрузка файлов

Компонент Form не обрабатывает загрузку файлов напрямую, но предоставляет метод `file` для генерации поля:

```php
echo $form->file('avatar', ['accept' => 'image/*']);
```

Загруженные файлы доступны через `$_FILES`. Валидацию файлов нужно выполнять отдельно.

## Интеграция с шаблонизатором

### В PHP-шаблонах

Используйте глобальную функцию `form()` (если зарегистрирована) или обращайтесь к сервису через контейнер.

```php
<?= $this->form->input('username') ?>
```

### В Blueprint

Blueprint предоставляет функции `form_open`, `form_close`, `input` и другие, которые делегируют вызовы компоненту Form.

```blade
{{ form_open('/submit') }}
    {{ input('name', old('name')) }}
    {{ errors('name') }}
{{ form_close() }}
```

## Кастомизация

### Собственный FormBuilder

Вы можете заменить стандартный FormBuilder, создав класс, реализующий `FormBuilderInterface`, и зарегистрировав его в контейнере.

```php
use Architect\Services\Form\Contracts\FormBuilderInterface;

class MyFormBuilder implements FormBuilderInterface
{
    // реализация методов
}
```

Регистрация:

```php
$container->set('form.builder', new MyFormBuilder());
```

### Изменение HTML-шаблонов

FormBuilder использует простую генерацию HTML. Чтобы изменить разметку, можно расширить класс и переопределить методы, либо использовать декоратор.

## Примеры

### Полная форма регистрации

```php
// В контроллере
public function register()
{
    return $this->view('auth.register');
}
```

В шаблоне:

```php
<?= $this->form->open('/register', ['method' => 'post']) ?>
    <?= $this->form->input('name', $this->form->old('name'), ['placeholder' => 'Ваше имя']) ?>
    <?= $this->form->error('name') ?>

    <?= $this->form->input('email', $this->form->old('email'), ['type' => 'email']) ?>
    <?= $this->form->error('email') ?>

    <?= $this->form->input('password', '', ['type' => 'password']) ?>
    <?= $this->form->error('password') ?>

    <?= $this->form->input('password_confirmation', '', ['type' => 'password']) ?>

    <?= $this->form->submit('Зарегистрироваться') ?>
<?= $this->form->close() ?>
```

### Валидация в контроллере

```php
public function postRegister()
{
    $validator = $this->form->validator($_POST, [
        'name' => 'required|min:2',
        'email' => 'required|email|unique:users',
        'password' => 'required|confirmed|min:6'
    ]);

    if ($validator->fails()) {
        // сохраняем старые данные и ошибки
        $this->form->withErrors($validator);
        return $this->redirect('/register');
    }

    // создание пользователя
    $this->userModel->create($validator->validated());
    return $this->redirect('/dashboard');
}
```

## Заключение

Компонент Form предоставляет удобный и безопасный способ работы с HTML-формами, включая генерацию полей, CSRF-защиту и валидацию. Его интеграция с сессиями и шаблонизаторами упрощает создание пользовательских интерфейсов.

Дополнительные сведения см. в [документации по формам](../docs2/forms.md).