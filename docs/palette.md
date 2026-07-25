# Палитра цветов Architect RED 2

## Основная палитра (из _404.php)

### Фон
- `linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)` — тёмный градиент (основной фон)
- `#1a1a2e` — тёмно-синий (начало градиента)
- `#16213e` — средний синий (середина градиента)
- `#0f3460` — насыщенный синий (конец градиента)

### Акценты
- `#e94560` — основной акцент (красный)
- `#ff6b6b` — вторичный акцент (светло-красный)

### Текст
- `#ffffff` — белый
- `rgba(255, 255, 255, 0.7)` — полупрозрачный белый
- `rgba(255, 255, 255, 0.5)` — приглушённый белый

### UI элементы
- `rgba(255, 255, 255, 0.05)` — полупрозрачный белый для фона карточек
- `rgba(255, 255, 255, 0.1)` — границы
- `rgba(255, 255, 255, 0.1)` — полупрозрачный фон для backdrop

---

## Дополнительные цвета (из дизайн-системы)

### Стандартные
- `#ffffff` — белый
- `#000000` — чёрный
- `#f5f5f5` — светло-серый фон
- `#e5e7eb` — границы
- `#9ca3af` — приглушённый текст

### Semantic
- `#22c55e` — success (зелёный)
- `#e94560` — error (красный)
- `#f59e0b` — warning (жёлтый)
- `#3b82f6` — info (синий)

### Bootstrap адаптация
- `--bs-primary: #e94560`
- `--bs-danger: #e94560`
- `--bs-body-bg: #1a1a2e`
- `--bs-body-color: #ffffff`

---

## Использование в Error Views

```css
/* Основной контейнер */
.error-page {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    min-height: 100vh;
    padding: 40px 20px;
}

/* Карточка ошибки */
.error-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
}

/* Заголовок */
.error-title {
    color: #e94560;
}

/* Текст */
.error-message {
    color: rgba(255, 255, 255, 0.7);
}

/* Кнопка */
.btn-copy {
    background: linear-gradient(135deg, #e94560, #ff6b6b);
    border: none;
    border-radius: 12px;
    color: #fff;
}
```

---

## Градиенты

```css
/* Основной градиент */
background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);

/* Акцентный градиент для кнопок */
background: linear-gradient(135deg, #e94560, #ff6b6b);

/* Градиент для hover */
box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
```

---

## Анимации

```css
/* Fade in up */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Pulse */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
```

---

## Тень

```css
/* Карточка */
box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);

/* Кнопка hover */
box-shadow: 0 10px 30px rgba(233, 69, 96, 0.4);
```
