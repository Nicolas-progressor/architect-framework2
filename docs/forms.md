
# Формы и валидация

Architect Framework предоставляет мощную систему для работы с HTML-формами, включая генерацию полей, CSRF-защиту, валидацию данных и обработку отправки. Система форм построена на принципах безопасности, удобства и расширяемости.

## Содержание

- [Введение](#введение)
- [Быстрый старт](#быстрый-старт)
- [Сервис Form](#сервис-form)
  - [Получение сервиса](#получение-сервиса)
  - [Основные методы](#основные-методы)
- [Обработка формы](#обработка-формы)
  - [Метод handle()](#метод-handle)
  - [Результат обработки](#результат-обработки)
  - [Пример полного цикла](#пример-полного-цикла)
- [Генерация HTML](#генерация-html)
  - [Открытие и закрытие формы](#открытие-и-закрытие-формы)
  - [Текстовые поля](#текстовые-поля)
  - [Специальные поля](#специальные-поля)
  - [Выпадающие списки и чекбоксы](#выпадающие-списки-и-чекбоксы)
  - [Кнопки](#кнопки)
- [Валидация данных](#валидация-данных)
  - [Поддерживаемые правила](#поддерживаемые-правила)
  - [Кастомные правила](#кастомные-правила)
  - [Метки полей и сообщения об ошибках](#метки-полей-и-сообщения-об-ошибках)
- [CSRF-защита](#csrf-защита)
  - [Автоматическая генерация токена](#автоматическая-генерация-токена)
  - [Проверка токена](#проверка-токена)
  - [Мета-тег для AJAX](#мета-тег-для-ajax)
- [Расширенные возможности](#расширенные-возможности)
  - [Работа с FormBuilder напрямую](#работа-с-formbuilder-напрямую)
  - [Кастомные валидаторы](#кастомные-валидаторы)
  - [Интеграция с Blueprint](#интеграция-с-blueprint)
- [Лучшие практики](#лучшие-практики)
- [Частые вопросы](#частые-вопросы)

## Введение

Система форм Architect состоит из нескольких компонентов:

1. **Form** – основной сервис, предоставляющий высокоуровневый API для работы с формами.
2. **FormBuilder** – генератор HTML-элементов форм с поддержкой Bootstrap-классов.
3. **FormValidator** – валидатор данных с гибкой системой правил.
4. **CSRFTokenManager** – менеджер CSRF-токенов для защиты от межсайтовой подделки запросов.
5. **FormHandler** – внутренний класс, координирующий обработку формы.

Все компоненты следуют принципу инверсии зависимостей и могут быть заменены собственными реализациями через интерфейсы.

## Быстрый старт

Создадим простую форму регистрации в контроллере:

```php
<?php
// app/modules/auth/controller.php
namespace Modules\Auth;

use Architect\Services\Mvc\Controller;

class AuthController extends Controller
{
    public function register()
    {
        $form = $this->container->get('form');
        
        $result = $form->handle('register', [
            'username' => 'required|min_length:3|max_length:20|alpha_num',
            'email'    => 'required|email',
            'password' => 'required|min_length:6',
            'confirm_password' => 'required|match:password',
        ], function($data) {
            // Действие при успешной валидации
            $user = User::create([
                'username' => $data['username'],
                'email'    => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            
            $this->session->set('user_id', $user->id);
            return $this->redirect('/dashboard');
        });
        
        if ($result->hasErrors()) {
            // Передаём ошибки в представление
            return $this->view('register', [
                'errors' => $result->getErrors(),
                'form'   => $form,
            ]);
        }
        
        // Если форма не отправлена, показываем пустую форму
        return $this->view('register', ['form' => $form]);
    }
}
```

В представлении `register.php`:

```php
<?= $form->open('/auth/register') ?>

<div class="mb-3">
    <label for="username" class="form-label">Имя пользователя</label>
    <?= $form->text('username', '', ['class' => 'form-control', 'id' => 'username']) ?>
    <?= $form->error('username') ?>
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <?= $form->email('email', '', ['class' => 'form-control', 'id' => 'email']) ?>
    <?= $form->error('email') ?>
</div>

<div class="mb-3">
    <label for="password" class="form-label">Пароль</label>
    <?= $form->password('password', ['class' => 'form-control', 'id' => 'password']) ?>
    <?= $form->error('password') ?>
</div>

<div class="mb-3">
    <label for="confirm_password" class="form-label">Подтверждение пароля</label>
    <?= $form->password('confirm_password', ['class' => 'form-control', 'id' => 'confirm_password']) ?>
    <?= $form->error('confirm_password') ?>
</div>

<button type="submit" class="btn btn-primary">Зарегистрироваться</button>

<?= $form->close() ?>
```

## Сервис Form

### Получение сервиса

Сервис форм доступен через контейнер зависимостей:

```php
$form = $container->get('form');
```

В контроллерах можно использовать свойство `$this->form` (если подключен трейт `FormTrait`) или получить через контейнер:

```php
$form = $this->container->get('form');
```

### Основные методы

| Метод | Описание |
|-------|----------|
| `handle(string $formName, array $rules, ?callable $callback)` | Обработать форму с валидацией и CSRF |
| `validate(array $data, array $rules)` | Валидировать данные без обработки формы |
| `open(string $action, string $method = 'POST', array $attrs = [])` | Открыть тег формы |
| `close()` | Закрыть тег формы |
| `text(string $name, mixed $value = '', array $attrs = [])` | Текстовое поле |
| `email(string $name, mixed $value = '', array $attrs = [])` | Поле email |
| `password(string $name, array $attrs = [])` | Поле пароля |
| `textarea(string $name, mixed $value = '', array $attrs = [])` | Текстовая область |
| `select(string $name, array $options, mixed $selected = null, array $attrs = [])` | Выпадающий список |
| `checkbox(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attrs = [])` | Чекбокс |
| `radio(string $name, mixed $value = '1', bool $checked = false, string $label = '', array $attrs = [])` | Радиокнопка |
| `submit(string $label = 'Отправить', array $attrs = [])` | Кнопка отправки |
| `token(string $formName = 'default')` | Получить CSRF-токен |
| `tokenField(string $formName = 'default')` | Получить скрытое поле с токеном |
| `error(string $field)` | Получить HTML ошибки для поля |
| `hasError(string $field)` | Проверить наличие ошибки у поля |

## Обработка формы

### Метод handle()

Метод `handle()` выполняет полный цикл обработки формы:

1. Проверяет, была ли форма отправлена (наличие POST-данных).
2. Проверяет CSRF-токен.
3. Валидирует данные по переданным правилам.
4. Если валидация успешна, выполняет callback.
5. Возвращает объект `FormResult` с результатами.

Сигнатура:

```php
public function handle(
    string $formName,
    array $validationRules,
    ?callable $callback = null
): FormResult
```

- `$formName` – уникальное имя формы (используется для CSRF).
- `$validationRules` – массив правил валидации в формате `'field' => 'rule1|rule2:param'`.
- `$callback` – функция, вызываемая при успешной валидации. Принимает массив данных и экземпляр `FormHandler`. Может возвращать любой результат (например, редирект).

### Результат обработки

Метод возвращает объект `FormResult` со следующими методами:

- `isSuccess(): bool` – успешна ли обработка.
- `hasErrors(): bool` – есть ли ошибки.
- `getErrors(): array` – массив ошибок.
- `getData(): array` – данные формы.
- `getResult(): mixed` – результат callback (если был).
- `isCsrfError(): bool` – является ли ошибка CSRF.
- `getCsrfError(): ?string` – сообщение CSRF-ошибки.

### Пример полного цикла

```php
$result = $form->handle('contact', [
    'name'    => 'required|min_length:2',
    'email'   => 'required|email',
    'message' => 'required|min_length:10',
], function($data) {
    // Отправляем email
    Mail::send('contact@example.com', 'Новое сообщение', $data['message']);
    return 'Сообщение отправлено';
});

if ($result->isSuccess()) {
    echo $result->getResult(); // "Сообщение отправлено"
} elseif ($result->hasErrors()) {
    foreach ($result->getErrors() as $field => $errors) {
        foreach ($errors as $error) {
            echo "$field: $error<br>";
        }
    }
}
```

## Генерация HTML

### Открытие и закрытие формы

```php
<?= $form->open('/submit', 'POST', ['class' => 'form-horizontal']) ?>
<!-- поля формы -->
<?= $form->close() ?>
```

Для POST-форм автоматически добавляется скрытое поле CSRF.

### Текстовые поля

```php
<?= $form->text('username', '', [
    'class'       => 'form-control',
    'placeholder' => 'Введите имя',
    'id'          => 'username',
]) ?>
```

Доступные типы полей: `text`, `email`, `password`, `number`, `tel`, `url`, `search`, `date`, `time`, `datetime-local`, `color`, `range`, `file`.

### Специальные поля

**Текстовая область:**

```php
<?= $form->textarea('bio', '', [
    'rows' => 4,
    'class' => 'form-control',
]) ?>
```

**Скрытое поле:**

```php
<?= $form->hidden('id', $id) ?>
```

### Выпадающие списки и чекбоксы

**Select:**

```php
<?= $form->select('country', [
    'ru' => 'Россия',
    'us' => 'США',
    'de' => 'Германия',
], 'ru', ['class' => 'form-select']) ?>
```

**Чекбокс:**

```php
<?= $form->checkbox('agree', '1', false, 'Я согласен с условиями', [
    'class' => 'form-check-input',
]) ?>
```

**Радиокнопка:**

```php
<?= $form->radio('gender', 'male', false, 'Мужской', ['class' => 'form-check-input']) ?>
<?= $form->radio('gender', 'female', false, 'Женский', ['class' => 'form-check-input']) ?>
```

### Кнопки

```php
<?= $form->submit('Отправить', ['class' => 'btn btn-primary']) ?>
<?= $form->button('Отмена', '/cancel', ['class' => 'btn btn-secondary']) ?>
```

## Валидация данных

### Поддерживаемые правила

| Правило | Описание | Пример |
|---------|----------|--------|
| `required` | Обязательное поле | `'username' => 'required'` |
| `email` | Валидный email | `'email' => 'email'` |
| `min_length:n` | Минимальная длина | `'password' => 'min_length:6'` |
| `max_length:n` | Максимальная длина | `'title' => 'max_length:100'` |
| `numeric` | Числовое значение | `'age' => 'numeric'` |
| `min:n` | Минимальное значение | `'age' => 'min:18'` |
| `max:n` | Максимальное значение | `'price' => 'max:1000'` |
| `match:field` | Совпадение с другим полем | `'confirm_password' => 'match:password'` |
| `url` | Валидный URL | `'website' => 'url'` |
| `alpha` | Только буквы | `'first_name' => 'alpha'` |
| `alpha_num` | Буквы и цифры | `'username' => 'alpha_num'` |
| `date` | Валидная дата | `'birthday' => 'date'` |
| `in:value1,value2` | Значение из списка | `'status' => 'in:active,pending,blocked'` |

Правила можно комбинировать через `|`:

```php
'username' => 'required|min_length:3|max_length:20|alpha_num',
'email'    => 'required|email',
'age'      => 'required|numeric|min:18|max:99',
```

### Кастомные правила

Вы можете добавить собственные правила валидации:

```php
$validator = $form->getValidator();
$validator->addRule('unique', function($value, $param, $data) {
    // Проверяем уникальность в базе данных
    return !User::where($param, $value)->exists();
});

// Использование
$rules = ['email' => 'required|email|unique:users,email'];
```

Также можно зарегистрировать глобальное правило:

```php
FormValidator::addGlobalRule('phone', function($value) {
    return preg_match('/^\+7\d{10}$/', $value);
});
```

### Метки полей и сообщения об ошибках

По умолчанию сообщения об ошибках содержат имя поля. Вы можете задать человекочитаемые метки:

```php
$validator = $form->getValidator();
$validator->setFieldLabels([
    'username' => 'Имя пользователя',
    'email'    => 'Адрес электронной почты',
    'password' => 'Пароль',
]);
```

Тогда ошибка будет выглядеть: «Поле «Имя пользователя» обязательно для заполнения».

## CSRF-защита

### Автоматическая генерация токена

При использовании `$form->open()` для POST-форм автоматически добавляется скрытое поле `csrf_token`. Токен генерируется для каждой формы отдельно и хранится в сессии с ограниченным временем жизни (по умолчанию 1 час).

### Проверка токена

Метод `handle()` автоматически проверяет CSRF-токен. Если токен невалиден, возвращается ошибка CSRF.

Вы можете проверить токен вручную:

```php
if ($form->validateToken('form_name', $_POST['csrf_token'])) {
    // Токен верный
} else {
    // Токен неверный или истёк
}
```

### Мета-тег для AJAX

Для AJAX-запросов можно добавить CSRF-токен в мета-тег:

```php
echo $form->getCSRF()->getMetaTag('default');
```

В JavaScript:

```javascript
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
fetch('/api/submit', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify(data),
});
```

## Расширенные возможности

### Работа с FormBuilder напрямую

Если вам нужен больший контроль, вы можете использовать `FormBuilder` отдельно:

```php
$builder = new FormBuilder();
$builder->setData($oldData);
$builder->setErrors($errors);

echo $builder->open('/submit');
echo $builder->textField('name');
echo $builder->submitButton();
echo $builder->close();
```

### Кастомные валидаторы

Вы можете создать собственный валидатор, реализующий `FormValidatorInterface`, и передать его в