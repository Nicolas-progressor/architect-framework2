# Техническая спецификация системы отправки почты

## Обзор
Система отправки почты - это компонент, который предоставляет унифицированный интерфейс для отправки электронных писем с поддержкой различных драйверов, шаблонов и очередей.

## Архитектура

### Основные классы

#### 1. MailManager
Менеджер почтовой системы.

```php
<?php

namespace Architect\Mail;

class MailManager
{
    protected ContainerInterface $container;
    protected array $drivers = [];
    protected array $customCreators = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Получает экземпляр почтового драйвера
     *
     * @param string|null $name Название драйвера
     * @return MailerInterface
     */
    public function driver(string $name = null): MailerInterface;
    
    /**
     * Расширяет менеджер кастомным драйвером
     *
     * @param string $name Название драйвера
     * @param callable $callback Фабрика драйвера
     * @return self
     */
    public function extend(string $name, callable $callback): self;
    
    /**
     * Получает конфигурацию драйвера
     *
     * @param string|null $name Название драйвера
     * @return array
     */
    public function getConfig(string $name = null): array;
    
    /**
     * Получает имя драйвера по умолчанию
     *
     * @return string
     */
    public function getDefaultDriver(): string;
}
```

#### 2. Mailer
Основной класс для отправки писем.

```php
<?php

namespace Architect\Mail;

class Mailer implements MailerInterface
{
    protected TransportInterface $transport;
    protected ViewFactoryInterface $views;
    protected QueueInterface $queue;
    protected array $from = [];
    protected array $replyTo = [];
    protected array $returnPath = [];
    
    public function __construct(
        TransportInterface $transport,
        ViewFactoryInterface $views,
        QueueInterface $queue = null
    ) {
        $this->transport = $transport;
        $this->views = $views;
        $this->queue = $queue;
    }
    
    /**
     * Начинает создание нового сообщения
     *
     * @return PendingMail
     */
    public function to($users): PendingMail;
    
    /**
     * Начинает создание нового сообщения с копией
     *
     * @param mixed $users
     * @return PendingMail
     */
    public function cc($users): PendingMail;
    
    /**
     * Начинает создание нового сообщения со скрытой копией
     *
     * @param mixed $users
     * @return PendingMail
     */
    public function bcc($users): PendingMail;
    
    /**
     * Отправляет сообщение
     *
     * @param MailableInterface|string|array $view
     * @param array $data
     * @param \Closure|string|null $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null): void;
    
    /**
     * Отправляет сообщение через очередь
     *
     * @param MailableInterface|string|array $view
     * @param array $data
     * @param \Closure|string|null $callback
     * @return mixed
     */
    public function queue($view, array $data = [], $callback = null);
    
    /**
     * Отправляет сообщение с задержкой
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param MailableInterface|string|array $view
     * @param array $data
     * @param \Closure|string|null $callback
     * @return mixed
     */
    public function later($delay, $view, array $data = [], $callback = null);
    
    /**
     * Получает транспорт
     *
     * @return TransportInterface
     */
    public function getTransport(): TransportInterface;
}
```

#### 3. Mailable
Базовый класс для создаваемых писем.

```php
<?php

namespace Architect\Mail;

abstract class Mailable implements MailableInterface
{
    use Queueable, SerializesModels;
    
    protected array $to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected array $replyTo = [];
    protected string $subject = '';
    protected string $view = '';
    protected string $textView = '';
    protected array $viewData = [];
    protected array $attachments = [];
    protected array $rawAttachments = [];
    protected array $tags = [];
    protected array $metadata = [];
    protected string $locale;
    protected int $tries;
    protected int $timeout;
    protected array $middleware = [];
    
    /**
     * Строит сообщение
     *
     * @param Message $message
     * @return void
     */
    abstract public function build(Message $message): void;
    
    /**
     * Отправляет сообщение
     *
     * @param MailerInterface $mailer
     * @return void
     */
    public function send(MailerInterface $mailer): void;
    
    /**
     * Очередует сообщение
     *
     * @param QueueInterface $queue
     * @return mixed
     */
    public function queue(QueueInterface $queue);
    
    /**
     * Устанавливает получателей
     *
     * @param mixed $address
     * @param string|null $name
     * @return self
     */
    public function to($address, string $name = null): self;
    
    /**
     * Устанавливает тему
     *
     * @param string $subject
     * @return self
     */
    public function subject(string $subject): self;
    
    /**
     * Устанавливает шаблон
     *
     * @param string $view
     * @param array $data
     * @return self
     */
    public function view(string $view, array $data = []): self;
    
    /**
     * Устанавливает текстовый шаблон
     *
     * @param string $view
     * @param array $data
     * @return self
     */
    public function text(string $view, array $data = []): self;
    
    /**
     * Прикрепляет файл
     *
     * @param string $file
     * @param array $options
     * @return self
     */
    public function attach(string $file, array $options = []): self;
    
    /**
     * Прикрепляет данные как файл
     *
     * @param string $data
     * @param string $name
     * @param array $options
     * @return self
     */
    public function attachData(string $data, string $name, array $options = []): self;
    
    /**
     * Устанавливает локаль для сообщения
     *
     * @param string $locale
     * @return self
     */
    public function locale(string $locale): self;
    
    /**
     * Получает данные для шаблона
     *
     * @return array
     */
    public function buildViewData(): array;
}
```

#### 4. Message
Класс для представления сообщения.

```php
<?php

namespace Architect\Mail;

class Message
{
    protected Swift_Message $swift;
    protected array $embeddedFiles = [];
    
    public function __construct(Swift_Message $swift)
    {
        $this->swift = $swift;
    }
    
    /**
     * Устанавливает отправителя
     *
     * @param string|array $address
     * @param string|null $name
     * @return self
     */
    public function from($address, string $name = null): self;
    
    /**
     * Устанавливает получателей
     *
     * @param string|array $address
     * @param string|null $name
     * @return self
     */
    public function to($address, string $name = null): self;
    
    /**
     * Устанавливает тему
     *
     * @param string $subject
     * @return self
     */
    public function subject(string $subject): self;
    
    /**
     * Устанавливает тело сообщения
     *
     * @param string $body
     * @param string|null $contentType
     * @param string|null $charset
     * @return self
     */
    public function body(string $body, string $contentType = null, string $charset = null): self;
    
    /**
     * Устанавливает HTML-тело
     *
     * @param string $body
     * @param string|null $charset
     * @return self
     */
    public function html(string $body, string $charset = null): self;
    
    /**
     * Устанавливает текстовое тело
     *
     * @param string $body
     * @param string|null $charset
     * @return self
     */
    public function text(string $body, string $charset = null): self;
    
    /**
     * Прикрепляет файл
     *
     * @param string $file
     * @param string|null $as
     * @param string|null $mime
     * @return self
     */
    public function attach(string $file, string $as = null, string $mime = null): self;
    
    /**
     * Встраивает файл
     *
     * @param string $file
     * @return string
     */
    public function embed(string $file): string;
    
    /**
     * Получает Swift сообщение
     *
     * @return Swift_Message
     */
    public function getSwiftMessage(): Swift_Message;
}
```

## Драйверы отправки почты

### 1. SMTPTransport
Транспорт через SMTP.

```php
<?php

namespace Architect\Mail\Transports;

class SmtpTransport implements TransportInterface
{
    protected string $host;
    protected int $port;
    protected string $encryption;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected array $options;
    
    public function __construct(
        string $host,
        int $port = 587,
        string $encryption = 'tls',
        string $username = null,
        string $password = null,
        int $timeout = 60,
        array $options = []
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->options = $options;
    }
    
    public function send(Message $message): void;
}
```

### 2. SendmailTransport
Транспорт через Sendmail.

```php
<?php

namespace Architect\Mail\Transports;

class SendmailTransport implements TransportInterface
{
    protected string $command;
    
    public function __construct(string $command = '/usr/sbin/sendmail -bs')
    {
        $this->command = $command;
    }
    
    public function send(Message $message): void;
}
```

### 3. ArrayTransport
Транспорт для тестирования (сохраняет сообщения в массив).

```php
<?php

namespace Architect\Mail\Transports;

class ArrayTransport implements TransportInterface
{
    protected array $messages = [];
    
    public function send(Message $message): void
    {
        $this->messages[] = $message;
    }
    
    public function flush(): void
    {
        $this->messages = [];
    }
    
    public function getMessages(): array
    {
        return $this->messages;
    }
}
```

## Шаблоны писем

### Markdown шаблоны
Поддержка Markdown шаблонов для писем.

```blade
{{-- resources/views/emails/welcome.blade.php --}}
@component('mail::message')
# Добро пожаловать!

Спасибо за регистрацию на нашем сайте.

@component('mail::button', ['url' => $url])
Подтвердить email
@endcomponent

С уважением,<br>
{{ config('app.name') }}
@endcomponent
```

### Компоненты для писем

#### ButtonComponent
Компонент кнопки.

```php
<?php

namespace Architect\Mail\Components;

class ButtonComponent extends Component
{
    public string $url;
    public string $color;
    
    public function __construct(string $url, string $color = 'blue')
    {
        $this->url = $url;
        $this->color = $color;
    }
    
    public function render()
    {
        return 'mail::button';
    }
}
```

#### PanelComponent
Компонент панели.

```php
<?php

namespace Architect\Mail\Components;

class PanelComponent extends Component
{
    public function render()
    {
        return 'mail::panel';
    }
}
```

## Конфигурация

Файл конфигурации `app/config/mail.json`:

```json
{
    "default": "smtp",
    "mailers": {
        "smtp": {
            "transport": "smtp",
            "host": "smtp.mailgun.org",
            "port": 587,
            "encryption": "tls",
            "username": null,
            "password": null,
            "timeout": 60,
            "auth_mode": null
        },
        "sendmail": {
            "transport": "sendmail",
            "path": "/usr/sbin/sendmail -bs"
        },
        "mailgun": {
            "transport": "mailgun"
        },
        "ses": {
            "transport": "ses"
        },
        "array": {
            "transport": "array"
        }
    },
    "from": {
        "address": "hello@example.com",
        "name": "Example"
    },
    "markdown": {
        "theme": "default",
        "paths": [
            "/var/www/resources/views/vendor/mail"
        ]
    }
}
```

## Интеграция с очередями

### QueueableTrait
Трейт для добавления поддержки очередей в Mailable.

```php
<?php

namespace Architect\Mail\Traits;

trait Queueable
{
    protected string $queue;
    protected string $connection;
    protected int $delay;
    protected array $chain;
    protected string $timezone;
    
    /**
     * Устанавливает очередь
     *
     * @param string $queue
     * @return self
     */
    public function queue(string $queue): self;
    
    /**
     * Устанавливает соединение
     *
     * @param string $connection
     * @return self
     */
    public function onConnection(string $connection): self;
    
    /**
     * Устанавливает очередь
     *
     * @param string $queue
     * @return self
     */
    public function onQueue(string $queue): self;
    
    /**
     * Устанавливает задержку
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @return self
     */
    public function delay($delay): self;
    
    /**
     * Цепочка заданий
     *
     * @param array $chain
     * @return self
     */
    public function chain(array $chain): self;
}
```

## Использование

### Базовая отправка писем

```php
// Отправка простого письма
Mail::raw('Текст письма', function ($message) {
    $message->to('user@example.com')
             ->subject('Тема письма');
});

// Отправка письма с шаблоном
Mail::send('emails.welcome', ['user' => $user], function ($message) use ($user) {
    $message->to($user->email)
             ->subject('Добро пожаловать!');
});
```

### Использование Mailable классов

```php
// Создание Mailable класса
class WelcomeEmail extends Mailable
{
    protected User $user;
    
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    
    public function build()
    {
        return $this->subject('Добро пожаловать!')
                    ->view('emails.welcome')
                    ->with([
                        'user' => $this->user
                    ]);
    }
}

// Отправка Mailable
Mail::to($user->email)->send(new WelcomeEmail($user));

// Очередизация отправки
Mail::to($user->email)->queue(new WelcomeEmail($user));

// Отправка с задержкой
Mail::to($user->email)->later(now()->addMinutes(10), new WelcomeEmail($user));
```

### В контроллерах

```php
class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = User::create($request->all());
        
        // Отправка приветственного письма
        Mail::to($user->email)->send(new WelcomeEmail($user));
        
        return redirect('/users');
    }
}
```

## Сервис-провайдер

```php
<?php

namespace Architect\Mail\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;

class MailServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('mail.manager', function ($container) {
            return new \Architect\Mail\MailManager($container);
        });
        
        $container->singleton('mailer', function ($container) {
            return $container->get('mail.manager')->driver();
        });
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Регистрация команд
        if ($container->has('console.registry')) {
            $registry = $container->get('console.registry');
            $registry->register(new \Architect\Mail\Console\Commands\MailMakeCommand());
        }
    }
}
```

## Производительность

### Очередизация
Отправка писем через очередь для улучшения производительности.

### Кэширование
Кэширование скомпилированных шаблонов писем.

### Пакетная отправка
Поддержка отправки писем пакетами.

## Тестирование

### Unit-тесты
- Тестирование каждого драйвера
- Тестирование Mailable классов
- Тестирование компонентов писем

### Интеграционные тесты
- Тестирование интеграции с очередями
- Тестирование отправки писем
- Тестирование шаблонов писем

## Совместимость

### Существующая система
- Интеграция с существующими HTTP-запросами
- Совместимость с текущими методами отправки писем (если есть)
- Поддержка существующих конфигураций

### Обратная совместимость
- Поддержка старых методов отправки писем
- Совместимость с существующими шаблонами