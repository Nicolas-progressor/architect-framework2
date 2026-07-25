# Техническая спецификация системы пагинации

## Обзор
Система пагинации - это компонент, который предоставляет удобный способ разбивки больших наборов данных на страницы с поддержкой различных стилей отображения и интеграцией с Axiom ORM.

## Архитектура

### Основные классы

#### 1. Paginator
Базовый класс для пагинации.

```php
<?php

namespace Architect\Pagination;

abstract class Paginator
{
    protected mixed $items;
    protected int $perPage;
    protected int $currentPage;
    protected int $total;
    protected int $lastPage;
    protected string $path;
    protected array $query;
    protected string $fragment;
    protected string $pageName;
    
    public function __construct(
        mixed $items,
        int $total,
        int $perPage,
        int $currentPage = 1,
        array $options = []
    ) {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
        $this->path = $options['path'] ?? '/';
        $this->query = $options['query'] ?? [];
        $this->fragment = $options['fragment'] ?? '';
        $this->pageName = $options['pageName'] ?? 'page';
        $this->lastPage = max(1, (int) ceil($total / $perPage));
    }
    
    /**
     * Получает элементы для текущей страницы
     *
     * @return mixed
     */
    public function items();
    
    /**
     * Получает общее количество элементов
     *
     * @return int
     */
    public function total(): int;
    
    /**
     * Получает количество элементов на странице
     *
     * @return int
     */
    public function perPage(): int;
    
    /**
     * Получает номер текущей страницы
     *
     * @return int
     */
    public function currentPage(): int;
    
    /**
     * Получает номер последней страницы
     *
     * @return int
     */
    public function lastPage(): int;
    
    /**
     * Проверяет, есть ли следующая страница
     *
     * @return bool
     */
    public function hasNextPage(): bool;
    
    /**
     * Проверяет, есть ли предыдущая страница
     *
     * @return bool
     */
    public function hasPreviousPage(): bool;
    
    /**
     * Получает URL для определенной страницы
     *
     * @param int $page
     * @return string
     */
    public function url(int $page): string;
    
    /**
     * Получает URL для следующей страницы
     *
     * @return string|null
     */
    public function nextPageUrl(): ?string;
    
    /**
     * Получает URL для предыдущей страницы
     *
     * @return string|null
     */
    public function previousPageUrl(): ?string;
    
    /**
     * Преобразует пагинатор в массив
     *
     * @return array
     */
    public function toArray(): array;
    
    /**
     * Преобразует пагинатор в JSON
     *
     * @return string
     */
    public function toJson(): string;
    
    /**
     * Получает HTML-представление пагинации
     *
     * @param string $view
     * @return string
     */
    public function links(string $view = 'pagination::bootstrap'): string;
    
    /**
     * Устанавливает путь для генерации URL
     *
     * @param string $path
     * @return self
     */
    public function setPath(string $path): self;
    
    /**
     * Добавляет фрагмент к URL
     *
     * @param string $fragment
     * @return self
     */
    public function fragment(string $fragment): self;
    
    /**
     * Добавляет параметры запроса
     *
     * @param array $query
     * @return self
     */
    public function appends(array $query): self;
}
```

#### 2. LengthAwarePaginator
Пагинатор с информацией о длине.

```php
<?php

namespace Architect\Pagination;

class LengthAwarePaginator extends Paginator
{
    /**
     * Создает экземпляр пагинатора
     *
     * @param mixed $items Элементы для текущей страницы
     * @param int $total Общее количество элементов
     * @param int $perPage Количество элементов на странице
     * @param int $currentPage Номер текущей страницы
     * @param array $options Опции пагинации
     */
    public function __construct(
        mixed $items,
        int $total,
        int $perPage,
        int $currentPage = 1,
        array $options = []
    ) {
        parent::__construct($items, $total, $perPage, $currentPage, $options);
    }
    
    /**
     * Получает элементы для текущей страницы
     *
     * @return mixed
     */
    public function items()
    {
        return $this->items;
    }
    
    /**
     * Получает количество элементов до текущей страницы
     *
     * @return int
     */
    public function firstItem(): int
    {
        return ($this->currentPage - 1) * $this->perPage + 1;
    }
    
    /**
     * Получает номер последнего элемента на текущей странице
     *
     * @return int
     */
    public function lastItem(): int
    {
        return min($this->total, $this->currentPage * $this->perPage);
    }
}
```

#### 3. SimplePaginator
Упрощенный пагинатор без информации о длине.

```php
<?php

namespace Architect\Pagination;

class SimplePaginator extends Paginator
{
    protected bool $hasMore;
    
    public function __construct(
        mixed $items,
        int $perPage,
        int $currentPage = 1,
        array $options = []
    ) {
        $this->hasMore = count($items) > $perPage;
        $items = array_slice($items, 0, $perPage);
        
        parent::__construct($items, 0, $perPage, $currentPage, $options);
    }
    
    /**
     * Проверяет, есть ли больше элементов
     *
     * @return bool
     */
    public function hasMorePages(): bool
    {
        return $this->hasMore;
    }
}
```

## Интеграция с Axiom ORM

### QueryBuilderMixin
Миксин для добавления методов пагинации в QueryBuilder.

```php
<?php

namespace Architect\Pagination\Axiom;

class QueryBuilderMixin
{
    /**
     * Пагинирует результаты запроса
     *
     * @param int $perPage
     * @param int $page
     * @param string $pageName
     * @return LengthAwarePaginator
     */
    public function paginate()
    {
        return function (int $perPage = 15, int $page = null, string $pageName = 'page') {
            $page = $page ?: (request($pageName) ?: 1);
            
            $results = $this->forPage($page, $perPage)->get();
            $total = $this->toBase()->getCountForPagination();
            
            return new LengthAwarePaginator(
                $results,
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => $pageName,
                ]
            );
        };
    }
    
    /**
     * Простая пагинация (без подсчета общего количества)
     *
     * @param int $perPage
     * @param int $page
     * @param string $pageName
     * @return SimplePaginator
     */
    public function simplePaginate()
    {
        return function (int $perPage = 15, int $page = null, string $pageName = 'page') {
            $page = $page ?: (request($pageName) ?: 1);
            
            $results = $this->forPage($page, $perPage + 1)->get();
            
            return new SimplePaginator(
                $results,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => $pageName,
                ]
            );
        };
    }
}
```

## Представления пагинации

### Bootstrap 5
Шаблон для Bootstrap 5.

```html
<!-- resources/views/pagination/bootstrap.blade.php -->
<nav>
    <ul class="pagination">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link">@lang('pagination.previous')</span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next')</a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link">@lang('pagination.next')</span>
            </li>
        @endif
    </ul>
</ul>
```

## Конфигурация

Файл конфигурации `app/config/pagination.json`:

```json
{
    "default": "bootstrap",
    "templates": {
        "bootstrap": "pagination::bootstrap",
        "simple-bootstrap": "pagination::simple-bootstrap",
        "default": "pagination::default"
    }
}
```

## Локализация

Файл локализации `app/lang/ru/pagination.php`:

```php
<?php

return [
    'previous' => '« Назад',
    'next' => 'Вперёд »',
];
```

## Использование

### С Axiom ORM

```php
// Пагинация результатов запроса
$users = User::where('active', true)->paginate(15);

// Простая пагинация (без подсчета общего количества)
$users = User::where('active', true)->simplePaginate(15);

// Пагинация с кастомным именем параметра
$users = User::paginate(15, null, 'user_page');
```

### В контроллерах

```php
class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(15);
        
        return view('users.index', compact('users'));
    }
}
```

### В шаблонах

```html
<!-- Отображение элементов -->
@foreach ($users as $user)
    <div>{{ $user->name }}</div>
@endforeach

<!-- Отображение ссылок пагинации -->
{{ $users->links() }}

<!-- Отображение информации о странице -->
<p>Показаны элементы {{ $users->firstItem() }} - {{ $users->lastItem() }} из {{ $users->total() }}</p>
```

## Сервис-провайдер

```php
<?php

namespace Architect\Pagination\Providers;

use Architect\Contracts\ServiceProviderInterface;
use Architect\Core\Contracts\ContainerInterface;
use Architect\Pagination\Axiom\QueryBuilderMixin;

class PaginationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Регистрация сервисов пагинации
    }
    
    public function boot(ContainerInterface $container): void
    {
        // Интеграция с Axiom ORM
        if (class_exists('Axiom\Orm\Query\Builder')) {
            $builderClass = 'Axiom\Orm\Query\Builder';
            $mixin = new QueryBuilderMixin();
            
            // Добавляем методы пагинации в QueryBuilder
            $builderClass::macro('paginate', $mixin->paginate());
            $builderClass::macro('simplePaginate', $mixin->simplePaginate());
        }
    }
}
```

## Производительность

### Оптимизации
- Использование simplePaginate для больших наборов данных
- Кэширование результатов пагинации
- Минимизация количества запросов к БД

## Тестирование

### Unit-тесты
- Тестирование Paginator и LengthAwarePaginator
- Тестирование методов пагинации
- Тестирование генерации URL

### Интеграционные тесты
- Тестирование интеграции с Axiom ORM
- Тестирование представлений пагинации
- Тестирование различных конфигураций

## Совместимость

### Существующая система
- Интеграция с существующими контроллерами
- Совместимость с Blueprint для отображения
- Поддержка существующих стилей CSS

### Обратная совместимость
- Поддержка старых методов пагинации (если есть)
- Совместимость с существующими шаблонами