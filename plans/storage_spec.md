# Техническая спецификация файлового хранилища

## Обзор
Файловое хранилище - это компонент, который предоставляет унифицированный интерфейс для работы с файлами с поддержкой различных драйверов хранения (локальная файловая система, S3, FTP и т.д.).

## Архитектура

### Основные классы

#### 1. Storage
Фасад для работы с файловым хранилищем.

```php
<?php

namespace Architect\Storage;

class Storage
{
    /**
     * Получает экземпляр диска
     *
     * @param string|null $disk Название диска
     * @return FilesystemInterface
     */
    public static function disk(string $disk = null): FilesystemInterface;
    
    /**
     * Получает файл по пути
     *
     * @param string $path Путь к файлу
     * @param string|null $disk Название диска
     * @return string|null
     */
    public static function get(string $path, string $disk = null): ?string;
    
    /**
     * Проверяет существование файла
     *
     * @param string $path Путь к файлу
     * @param string|null $disk Название диска
     * @return bool
     */
    public static function exists(string $path, string $disk = null): bool;
    
    /**
     * Сохраняет файл
     *
     * @param string $path Путь к файлу
     * @param string|resource $contents Содержимое файла
     * @param array $options Опции
     * @param string|null $disk Название диска
     * @return bool
     */
    public static function put(string $path, $contents, array $options = [], string $disk = null): bool;
    
    /**
     * Удаляет файл
     *
     * @param string $path Путь к файлу
     * @param string|null $disk Название диска
     * @return bool
     */
    public static function delete(string $path, string $disk = null): bool;
}
```

#### 2. FilesystemManager
Менеджер файловых систем.

```php
<?php

namespace Architect\Storage;

class FilesystemManager
{
    protected ContainerInterface $container;
    protected array $disks = [];
    protected array $customCreators = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Получает экземпляр диска
     *
     * @param string|null $name Название диска
     * @return FilesystemInterface
     */
    public function disk(string $name = null): FilesystemInterface;
    
    /**
     * Расширяет менеджер кастомным драйвером
     *
     * @param string $name Название драйвера
     * @param callable $callback Фабрика драйвера
     * @return self
     */
    public function extend(string $name, callable $callback): self;
    
    /**
     * Получает конфигурацию диска
     *
     * @param string|null $name Название диска
     * @return array
     */
    public function getConfig(string $name = null): array;
    
    /**
     * Получает имя диска по умолчанию
     *
     * @return string
     */
    public function getDefaultDriver(): string;
}
```

#### 3. FilesystemInterface
Интерфейс файловой системы.

```php
<?php

namespace Architect\Storage\Contracts;

interface FilesystemInterface
{
    /**
     * Получает содержимое файла
     *
     * @param string $path Путь к файлу
     * @return string|null
     */
    public function get(string $path): ?string;
    
    /**
     * Записывает содержимое в файл
     *
     * @param string $path Путь к файлу
     * @param string|resource $contents Содержимое файла
     * @param array $options Опции
     * @return bool
     */
    public function put(string $path, $contents, array $options = []): bool;
    
    /**
     * Проверяет существование файла
     *
     * @param string $path Путь к файлу
     * @return bool
     */
    public function exists(string $path): bool;
    
    /**
     * Удаляет файл
     *
     * @param string $path Путь к файлу
     * @return bool
     */
    public function delete(string $path): bool;
    
    /**
     * Копирует файл
     *
     * @param string $from Исходный путь
     * @param string $to Целевой путь
     * @return bool
     */
    public function copy(string $from, string $to): bool;
    
    /**
     * Перемещает файл
     *
     * @param string $from Исходный путь
     * @param string $to Целевой путь
     * @return bool
     */
    public function move(string $from, string $to): bool;
    
    /**
     * Получает размер файла
     *
     * @param string $path Путь к файлу
     * @return int|null
     */
    public function size(string $path): ?int;
    
    /**
     * Получает время последней модификации файла
     *
     * @param string $path Путь к файлу
     * @return int|null
     */
    public function lastModified(string $path): ?int;
    
    /**
     * Получает список файлов в директории
     *
     * @param string $directory Директория
     * @param bool $recursive Рекурсивно
     * @return array
     */
    public function files(string $directory = '/', bool $recursive = false): array;
    
    /**
     * Получает список директорий
     *
     * @param string $directory Директория
     * @param bool $recursive Рекурсивно
     * @return array
     */
    public function directories(string $directory = '/', bool $recursive = false): array;
    
    /**
     * Создает директорию
     *
     * @param string $path Путь к директории
     * @return bool
     */
    public function makeDirectory(string $path): bool;
    
    /**
     * Удаляет директорию
     *
     * @param string $directory Директория
     * @return bool
     */
    public function deleteDirectory(string $directory): bool;
    
    /**
     * Получает URL файла (если поддерживается)
     *
     * @param string $path Путь к файлу
     * @return string|null
     */
    public function url(string $path): ?string;
    
    /**
     * Получает временный URL файла (если поддерживается)
     *
     * @param string $path Путь к файлу
     * @param \DateTimeInterface $expiration Время истечения
     * @param array $options Опции
     * @return string|null
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): ?string;
}
```

## Драйверы файловых систем

### 1. LocalFilesystem
Локальная файловая система.

```php
<?php

namespace Architect\Storage\Filesystems;

class LocalFilesystem implements Contracts\FilesystemInterface
{
    protected string $root;
    protected array $permissions;
    
    public function __construct(string $root, array $permissions = [])
    {
        $this->root = rtrim($root, '/');
        $this->permissions = array_merge([
            'file' => [
                'public' => 0644,
                'private' => 0600,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ], $permissions);
    }
    
    // Реализация всех методов интерфейса...
}
```

### 2. S3Filesystem
Amazon S3 файловая система.

```php
<?php

namespace Architect\Storage\Filesystems;

class S3Filesystem implements Contracts\FilesystemInterface
{
    protected S3Client $client;
    protected string $bucket;
    protected string $prefix;
    protected array $options;
    
    public function __construct(S3Client $client, string $bucket, string $prefix = '', array $options = [])
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->prefix = $prefix;
        $this->options = $options;
    }
    
    // Реализация всех методов интерфейса...
}
```

### 3. FtpFilesystem
FTP файловая система.

```php
<?php

namespace Architect\Storage\Filesystems;

class FtpFilesystem implements Contracts\FilesystemInterface
{
    protected array $config;
    protected ?resource $connection = null;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    // Реализация всех методов интерфейса...
}
```

### 4. SftpFilesystem
SFTP файловая система.

```php
<?php

namespace Architect\Storage\Filesystems;

class SftpFilesystem implements Contracts\FilesystemInterface
{
    protected SFTP $sftp;
    protected string $root;
    
    public function __construct(SFTP $sftp, string $root = '')
    {
        $this->sftp = $sftp;
        $this->root = $root;
    }
    
    // Реализация всех методов интерфейса...
}
```

## Загрузка файлов

### UploadedFile
Класс для работы с загруженными файлами.

```php
<?php

namespace Architect\Storage;

class UploadedFile extends File
{
    protected string $originalName;
    protected string $mimeType;
    protected int $error;
    
    public function __construct(
        string $path,
        string $originalName,
        string $mimeType = null,
        int $error = null,
        bool $test = false
    ) {
        $this->originalName = $originalName;
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->error = $error ?: UPLOAD_ERR_OK;
        
        parent::__construct($path, $test);
    }
    
    /**
     * Перемещает загруженный файл
     *
     * @param string $directory Директория назначения
     * @param string|null $name Имя файла
     * @return File
     */
    public function move(string $directory, string $name = null): File;
    
    /**
     * Получает оригинальное имя файла
     *
     * @return string
     */
    public function getClientOriginalName(): string;
    
    /**
     * Получает MIME-тип файла
     *
     * @return string
     */
    public function getClientMimeType(): string;
    
    /**
     * Получает размер файла
     *
     * @return int|null
     */
    public function getClientSize(): ?int;
    
    /**
     * Получает код ошибки загрузки
     *
     * @return int
     */
    public function getError(): int;
    
    /**
     * Проверяет, был ли файл успешно загружен
     *
     * @return bool
     */
    public function isValid(): bool;
}
```

## Конфигурация

Файл конфигурации `app/config/filesystems.json`:

```json
{
    "default": "local",
    "cloud": "s3",
    "disks": {
        "local": {
            "driver": "local",
            "root": "/var/www/storage/app"
        },
        "public": {
            "driver": "local",
            "root": "/var/www/storage/app/public",
            "url": "/storage",
            "visibility": "public"
        },
        "s3": {
            "driver": "s3",
            "key": "your-key",
            "secret": "your-secret",
            "region": "us-east-1",
            "bucket": "your-bucket",
            "url": "https://your-bucket.s3.amazonaws.com"
        },
        "ftp": {
            "driver": "ftp",
            "host": "ftp.example.com",
            "username": "your-username",
            "password": "your-password",
            "port": 21,
            "root": "/path/to/root",
            "passive": true,
            "ssl": true,
            "timeout": 30
        },
        "sftp": {
            "driver": "sftp",
            "host": "sftp.example.com",
            "username": "your-username",
            "password": "your-password",
            "port": 22,
            "root": "/path/to/root",
            "timeout": 30
        }
    },
    "links": {
        "/var/www/public/storage": "/var/www/storage/app/public"
    }
}
```

## Интеграция с HTTP

### FileUploadMiddleware
Middleware для обработки загрузки файлов.

```php
<?php

namespace Architect\Storage\Middleware;

class FileUploadMiddleware
{
    public function handle(Request $request, callable $next)
    {
        // Обработка загруженных файлов
        $files = $this->convertUploadedFiles($request->getFiles());
        $request = $request->withUploadedFiles($files);
        
        return $next($request);
    }
    
    protected function convertUploadedFiles(array $files): array
    {
        // Конвертация в UploadedFile объекты
    }
}
```

### StorageServiceProvider
Сервис-провайдер для регистрации файлового хранилища.

```php
<?php

namespace Architect\Storage\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;

class StorageServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton('filesystem', function ($container) {
            return new \Architect\Storage\FilesystemManager($container);
        });
        
        $container->singleton('filesystem.disk', function ($container) {
            return $container->get('filesystem')->disk();
        });
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Регистрация команд
        if ($container->has('console.registry')) {
            $registry = $container->get('console.registry');
            $registry->register(new \Architect\Storage\Console\Commands\StorageLinkCommand());
        }
    }
}
```

## Использование

### Базовое использование

```php
// Получение диска
$disk = Storage::disk('local');

// Запись файла
Storage::put('file.txt', 'Contents');

// Чтение файла
$contents = Storage::get('file.txt');

// Проверка существования файла
if (Storage::exists('file.txt')) {
    // ...
}

// Удаление файла
Storage::delete('file.txt');

// Копирование файла
Storage::copy('old/file.txt', 'new/file.txt');

// Перемещение файла
Storage::move('old/file.txt', 'new/file.txt');
```

### Работа с загруженными файлами

```php
class FileController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);
        
        $path = $request->file('avatar')->store('avatars', 'public');
        
        // Сохранение пути в базе данных
        auth()->user()->update(['avatar_path' => $path]);
        
        return back()->with('success', 'Avatar uploaded successfully!');
    }
}
```

### Работа с облачными хранилищами

```php
// Загрузка файла в S3
$path = $request->file('document')->store('documents', 's3');

// Получение URL файла
$url = Storage::disk('s3')->url('documents/file.pdf');

// Получение временного URL
$tempUrl = Storage::disk('s3')->temporaryUrl(
    'documents/file.pdf',
    now()->addMinutes(5)
);
```

## Безопасность

### Валидация файлов
- Проверка MIME-типов
- Проверка размера файлов
- Проверка расширений файлов

### Защита от вредоносных файлов
- Санитизация имен файлов
- Проверка содержимого файлов
- Ограничение типов загружаемых файлов

## Производительность

### Кэширование метаданных
Кэширование информации о файлах для ускорения доступа.

### Потоковая передача
Поддержка потоковой передачи больших файлов.

### Параллельные операции
Поддержка параллельных операций с файлами.

## Тестирование

### Unit-тесты
- Тестирование каждого драйвера
- Тестирование методов файловой системы
- Тестирование загрузки файлов

### Интеграционные тесты
- Тестирование интеграции с HTTP-запросами
- Тестирование работы с различными драйверами
- Тестирование команд консоли

## Совместимость

### Существующая система
- Интеграция с существующими HTTP-запросами
- Совместимость с текущими методами работы с файлами
- Поддержка существующих конфигураций

### Обратная совместимость
- Поддержка старых методов работы с файлами (если есть)
- Совместимость с существующими шаблонами