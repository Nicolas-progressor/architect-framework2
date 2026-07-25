# План визуализации метрик производительности

## Обзор
Система визуализации метрик производительности с графиками, диаграммами и интерактивными элементами для дебаг-меню Architect Framework 2.

## Типы визуализаций

### 1. Графики временных рядов
- **Response Time Timeline**: График времени ответа по этапам выполнения
- **Memory Usage Timeline**: График использования памяти во времени
- **Database Query Timeline**: Временная шкала выполнения запросов к БД

### 2. Столбчатые диаграммы
- **Stage Duration Comparison**: Сравнение времени выполнения этапов
- **Memory Usage by Component**: Использование памяти по компонентам
- **Query Count by Type**: Количество запросов по типам

### 3. Круговые диаграммы
- **Cache Hit/Miss Ratio**: Соотношение попаданий и промахов кэша
- **Time Distribution**: Распределение времени по компонентам
- **Memory Distribution**: Распределение памяти по компонентам

### 4. Тепловые карты
- **Performance Heatmap**: Тепловая карта производительности по времени/компонентам
- **Memory Heatmap**: Тепловая карта использования памяти

### 5. Индикаторы и дашборды
- **Performance Score**: Индикатор общей производительности (0-100)
- **Health Status**: Индикатор состояния системы
- **Threshold Indicators**: Индикаторы превышения пороговых значений

## Технологии визуализации

### 1. Canvas-based графики
- Использование HTML5 Canvas для lightweight графиков
- Кастомная реализация для минимальных зависимостей
- Высокая производительность

### 2. SVG графики
- Для сложных интерактивных диаграмм
- Масштабируемость без потери качества
- Поддержка анимаций

### 3. CSS-based визуализации
- Простые индикаторы и прогресс-бары
- Цветовая кодировка статусов
- Анимации переходов

## Реализация

### 1. ChartRenderer класс

```javascript
// architect/Services/Debug/View/tabs/performance/ChartRenderer.js
class ChartRenderer {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.options = {
            width: options.width || 800,
            height: options.height || 400,
            margin: options.margin || { top: 20, right: 20, bottom: 40, left: 50 },
            colors: options.colors || ['#4caf50', '#2196f3', '#ff9800', '#f44336', '#9c27b0'],
            ...options
        };
        
        this.canvas = null;
        this.ctx = null;
        this.charts = {};
    }
    
    initialize() {
        if (!this.container) {
            console.error('Chart container not found:', this.containerId);
            return;
        }
        
        // Создание canvas элемента
        this.canvas = document.createElement('canvas');
        this.canvas.width = this.options.width;
        this.canvas.height = this.options.height;
        this.canvas.style.width = '100%';
        this.canvas.style.height = 'auto';
        this.canvas.style.maxWidth = '100%';
        
        this.container.innerHTML = '';
        this.container.appendChild(this.canvas);
        
        this.ctx = this.canvas.getContext('2d');
        
        // Инициализация графиков
        this.initializeCharts();
    }
    
    initializeCharts() {
        this.charts = {
            line: this.createLineChart.bind(this),
            bar: this.createBarChart.bind(this),
            pie: this.createPieChart.bind(this),
            timeline: this.createTimelineChart.bind(this),
            gauge: this.createGaugeChart.bind(this),
            heatmap: this.createHeatmapChart.bind(this)
        };
    }
    
    createLineChart(data, options = {}) {
        const ctx = this.ctx;
        const { width, height, margin } = this.options;
        const chartWidth = width - margin.left - margin.right;
        const chartHeight = height - margin.top - margin.bottom;
        
        // Очистка canvas
        ctx.clearRect(0, 0, width, height);
        
        // Рисование осей
        this.drawAxes(chartWidth, chartHeight, margin);
        
        // Масштабирование данных
        const xScale = this.createXScale(data, chartWidth);
        const yScale = this.createYScale(data, chartHeight);
        
        // Рисование линий
        if (Array.isArray(data) && data.length > 0) {
            ctx.beginPath();
            ctx.strokeStyle = options.color || this.options.colors[0];
            ctx.lineWidth = options.lineWidth || 2;
            
            data.forEach((point, index) => {
                const x = margin.left + xScale(point.x || index);
                const y = margin.top + chartHeight - yScale(point.y);
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
                
                // Рисование точек
                if (options.showPoints !== false) {
                    ctx.beginPath();
                    ctx.arc(x, y, 3, 0, Math.PI * 2);
                    ctx.fillStyle = options.color || this.options.colors[0];
                    ctx.fill();
                }
            });
            
            ctx.stroke();
        }
        
        // Добавление легенды
        if (options.title) {
            this.drawTitle(options.title);
        }
    }
    
    createBarChart(data, options = {}) {
        const ctx = this.ctx;
        const { width, height, margin } = this.options;
        const chartWidth = width - margin.left - margin.right;
        const chartHeight = height - margin.top - margin.bottom;
        
        // Очистка canvas
        ctx.clearRect(0, 0, width, height);
        
        // Рисование осей
        this.drawAxes(chartWidth, chartHeight, margin);
        
        if (!Array.isArray(data) || data.length === 0) {
            return;
        }
        
        // Расчет параметров столбцов
        const barWidth = Math.min(40, chartWidth / data.length - 10);
        const xScale = chartWidth / data.length;
        
        // Нахождение максимального значения для масштабирования
        const maxValue = Math.max(...data.map(item => item.value));
        const yScale = chartHeight / maxValue;
        
        // Рисование столбцов
        data.forEach((item, index) => {
            const x = margin.left + index * xScale + (xScale - barWidth) / 2;
            const barHeight = item.value * yScale;
            const y = margin.top + chartHeight - barHeight;
            
            // Цвет столбца
            const color = this.getBarColor(item.value, maxValue, options);
            
            // Рисование столбца
            ctx.fillStyle = color;
            ctx.fillRect(x, y, barWidth, barHeight);
            
            // Добавление тени/обводки
            if (options.showBorder !== false) {
                ctx.strokeStyle = '#333';
                ctx.lineWidth = 1;
                ctx.strokeRect(x, y, barWidth, barHeight);
            }
            
            // Добавление подписи
            if (options.showLabels !== false) {
                ctx.fillStyle = '#e5e7eb';
                ctx.font = '10px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(item.label || `Item ${index + 1}`, x + barWidth / 2, margin.top + chartHeight + 15);
                
                // Значение над столбцом
                ctx.fillStyle = '#ccc';
                ctx.font = '9px Arial';
                ctx.fillText(item.value.toFixed(1), x + barWidth / 2, y - 5);
            }
        });
        
        // Добавление заголовка
        if (options.title) {
            this.drawTitle(options.title);
        }
    }
    
    createPieChart(data, options = {}) {
        const ctx = this.ctx;
        const { width, height } = this.options;
        
        // Очистка canvas
        ctx.clearRect(0, 0, width, height);
        
        if (!Array.isArray(data) || data.length === 0) {
            return;
        }
        
        // Расчет центра и радиуса
        const centerX = width / 2;
        const centerY = height / 2;
        const radius = Math.min(width, height) / 2 - 40;
        
        // Расчет общего значения
        const total = data.reduce((sum, item) => sum + item.value, 0);
        
        // Рисование секторов
        let startAngle = 0;
        
        data.forEach((item, index) => {
            const sliceAngle = (item.value / total) * 2 * Math.PI;
            const endAngle = startAngle + sliceAngle;
            
            // Цвет сектора
            const color = this.options.colors[index % this.options.colors.length];
            
            // Рисование сектора
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.arc(centerX, centerY, radius, startAngle, endAngle);
            ctx.closePath();
            ctx.fillStyle = color;
            ctx.fill();
            
            // Добавление обводки
            ctx.strokeStyle = '#1a1a1a';
            ctx.lineWidth = 1;
            ctx.stroke();
            
            // Расчет позиции для метки
            const labelAngle = startAngle + sliceAngle / 2;
            const labelRadius = radius * 0.7;
            const labelX = centerX + Math.cos(labelAngle) * labelRadius;
            const labelY = centerY + Math.sin(labelAngle) * labelRadius;
            
            // Добавление метки
            if (options.showLabels !== false) {
                const percentage = ((item.value / total) * 100).toFixed(1);
                ctx.fillStyle = '#fff';
                ctx.font = '10px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${percentage}%`, labelX, labelY);
            }
            
            // Легенда
            if (options.showLegend !== false) {
                this.drawPieLegend(item, index, color, percentage);
            }
            
            startAngle = endAngle;
        });
        
        // Добавление заголовка
        if (options.title) {
            this.drawTitle(options.title, centerX, 20);
        }
    }
    
    createTimelineChart(data, options = {}) {
        const ctx = this.ctx;
        const { width, height, margin } = this.options;
        const chartWidth = width - margin.left - margin.right;
        const chartHeight = height - margin.top - margin.bottom;
        
        // Очистка canvas
        ctx.clearRect(0, 0, width, height);
        
        // Рисование временной шкалы
        ctx.beginPath();
        ctx.moveTo(margin.left, margin.top + chartHeight / 2);
        ctx.lineTo(margin.left + chartWidth, margin.top + chartHeight / 2);
        ctx.strokeStyle = '#444';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        if (!Array.isArray(data) || data.length === 0) {
            return;
        }
        
        // Нахождение общего времени
        const totalTime = data.reduce((sum, item) => sum + item.duration, 0);
        const timeScale = chartWidth / totalTime;
        
        // Рисование событий на временной шкале
        let currentTime = 0;
        
        data.forEach((item, index) => {
            const xStart = margin.left + currentTime * timeScale;
            const eventWidth = item.duration * timeScale;
            const xEnd = xStart + eventWidth;
            
            // Цвет события
            const color = this.getTimelineColor(item, index, options);
            
            // Рисование блока события
            ctx.fillStyle = color;
            const blockHeight = 30;
            const blockY = margin.top + chartHeight / 2 - blockHeight / 2;
            ctx.fillRect(xStart, blockY, eventWidth, blockHeight);
            
            // Обводка блока
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 1;
            ctx.strokeRect(xStart, blockY, eventWidth, blockHeight);
            
            // Текст события
            if (eventWidth > 40) { // Только если достаточно места
                ctx.fillStyle = '#fff';
                ctx.font = '9px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                // Имя события
                const textX = xStart + eventWidth / 2;
                const textY = blockY + blockHeight / 2;
                ctx.fillText(item.name, textX, textY);
                
                // Длительность
                ctx.font = '8px Arial';
                ctx.fillStyle = '#ccc';
                ctx.fillText(`${item.duration.toFixed(1)}ms`, textX, textY + 12);
            }
            
            // Линия времени
            ctx.beginPath();
            ctx.moveTo(xStart, blockY + blockHeight + 5);
            ctx.lineTo(xStart, blockY + blockHeight + 15);
            ctx.strokeStyle = '#666';
            ctx.lineWidth = 1;
            ctx.stroke();
            
            // Метка времени
            ctx.fillStyle = '#888';
            ctx.font = '8px Arial';
            ctx.textAlign = 'center';
            ctx.fillText(`${currentTime.toFixed(1)}ms`, xStart, blockY + blockHeight + 25);
            
            currentTime += item.duration;
        });
        
        // Конечная метка времени
        ctx.fillStyle = '#888';
        ctx.font = '8px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(`${totalTime.toFixed(1)}ms`, margin.left + chartWidth, margin.top + chartHeight / 2 + 45);
        
        // Добавление заголовка
        if (options.title) {
            this.drawTitle(options.title);
        }
    }
    
    createGaugeChart(value, maxValue, options = {}) {
        const ctx = this.ctx;
        const { width, height } = this.options;
        
        // Очистка canvas
        ctx.clearRect(0, 0, width, height);
        
        // Расчет центра и радиуса
        const centerX = width / 2;
        const centerY = height / 2;
        const radius = Math.min(width, height) / 2 - 20;
        
        // Цвета шкалы
        const percentage = (value / maxValue) * 100;
        const gaugeColor = this.getGaugeColor(percentage);
        
        // Рисование фоновой дуги
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI * 0.8, Math.PI * 2.2);
        ctx.strokeStyle = '#333';
        ctx.lineWidth = 20;
        ctx.stroke();
        
        // Рисование заполненной дуги
        const endAngle = Math.PI * 0.8 + (Math.PI * 1.4 * (value / maxValue));
        
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI * 0.8, endAngle);
        ctx.strokeStyle = gaugeColor;
        ctx.lineWidth = 20;
        ctx.lineCap = 'round';
        ctx.stroke();
        
        // Отображение значения
        ctx.fillStyle = '#e5e7eb';
        ctx.font = '24px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(value.toFixed(1), centerX, centerY - 10);
        
        // Отображение метки
        ctx.fillStyle = '#888';
        ctx.font = '12px Arial';
        ctx.fillText(options.label || 'Value', centerX, centerY + 20);
        
        // Отображение максимума
        ctx.fillStyle = '#666';
        ctx.font = '10px Arial';
        ctx.textAlign = 'right';
        ctx.fillText(`Max: ${maxValue}`, width - 10, height - 10);
        
        // Добавление заголовка
        if (options.title) {
            this.drawTitle(options.title, centerX, 20);
        }
    }
    
    // Вспомогательные методы
    drawAxes(chartWidth, chartHeight, margin) {
        const ctx = this.ctx;
        
        // Ось X
        ctx.beginPath();
        ctx.moveTo(margin.left, margin.top + chartHeight);
        ctx.lineTo(margin.left + chartWidth, margin.top + chartHeight);
        ctx.strokeStyle = '#666';
        ctx.lineWidth = 1;
        ctx.stroke();
        
        // Ось Y
        ctx.beginPath();
        ctx.moveTo(margin.left, margin.top);
        ctx.lineTo(margin.left, margin.top + chartHeight);
        ctx.strokeStyle = '#666';
        ctx.lineWidth = 1;
        ctx.stroke();
    }
    
    drawTitle(title, x = null, y = null) {
        const ctx = this.ctx;
        const { width } = this.options;
        
        ctx.fillStyle = '#e5e7eb';
        ctx.font = '14px Arial';
        ctx.textAlign = x ? 'center' : 'left';
        ctx.textBaseline = 'top';
        
        const titleX = x || 10;
        const titleY = y || 10;
        
        ctx.fillText(title, titleX, titleY);
    }
    
    drawPieLegend(item, index, color, percentage) {
        const ctx = this.ctx;
        const { width, height } = this.options;
        
        const legendX = width - 150;
        const legendY = 50 + index * 20;
        
        // Цветной квадрат
        ctx.fillStyle = color;
        ctx.fillRect(legendX, legendY, 10, 10);
        
        // Текст легенды
        ctx.fillStyle = '#ccc';
        ctx.font = '10px Arial';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'top';
        ctx.fillText(`${item.label || 'Item ' + (index + 1)} (${percentage}%)`, legendX + 15, legendY);
    }
    
    createXScale(data, chartWidth) {
        if (!Array.isArray(data) || data.length === 0) {
            return (value) => 0;
        }
        
        const maxX = Math.max(...data.map((item, index) => item.x || index));
        return (value) => (value / maxX) * chartWidth;
    }
    
    createYScale(data, chartHeight) {
        if (!Array.isArray(data) || data.length === 0) {
            return (value) => 0;
        }
        
        const maxY = Math.max(...data.map(item => item.y || item.value || 0));
        return (value) => (value / maxY) * chartHeight;
    }
    
    getBarColor(value, maxValue, options) {
        const percentage = (value / maxValue) * 100;
        
        if (percentage > 90) return '#f44336'; // Красный
        if (percentage > 70) return '#ff9800'; // Оранжевый
        if (percentage > 50) return '#ffc107'; // Желтый
        if (percentage > 30) return '#8bc34a'; // Светло-зеленый
        return '#4caf50'; // Зеленый
    }
    
    getTimelineColor(item, index, options) {
        // Цвет на основе типа события или индекса
        const colors = this.options.colors;
        return colors[index % colors.length];
    }
    
    getGaugeColor(percentage) {
        if (percentage > 90) return '#f44336'; // Красный
        if (percentage > 70) return '#ff9800'; // Оранжевый
        if (percentage > 50) return '#ffc107'; // Желтый
        if (percentage > 30) return '#8bc34a'; // Светло-зеленый
        return '#4caf50'; // Зеленый
    }
}
```

### 2. PerformanceVisualization класс

```javascript
// architect/Services/Debug/View/tabs/performance/PerformanceVisualization.js
class PerformanceVisualization {
    constructor(metrics, thresholds) {
        this.metrics = metrics;
        this.thresholds = thresholds;
        this.charts = {};
    }
    
    initialize() {
        // Инициализация всех графиков
        this.initializeResponseTimeChart();
        this.initializeMemoryUsageChart();
        this.initializeDatabaseChart();
        this.initializeCacheChart();
        this.initializeTimelineChart();
        this.initializePerformanceScoreGauge();
    }
    
    initializeResponseTimeChart() {
        const responseTime = this.metrics.response_time || {};
        const stages = responseTime.stages || [];
        
        const chartData = stages.map(stage => ({
            label: stage.name,
            value: stage.duration * 1000, // Конвертация в ms
            color: this.getStageColor(stage.duration * 1000)
        }));
        
        this.charts.responseTime = new ChartRenderer('response-time-chart', {
            title: 'Response Time by Stage',
            width: 600,
            height: 300
        });
        
        this.charts.responseTime.initialize();
        this.charts.responseTime.createBarChart(chartData, {
            showLabels: true,
            showBorder: true
        });
    }
    
    initializeMemoryUsageChart() {
        const memory = this.metrics.memory_usage || {};
        
        const chartData = [
            { label: 'Current', value: memory.current_mb || 0 },
            { label: 'Peak', value: memory.peak_mb || 0 },
            { label: 'Limit', value: memory.limit_mb || 0 }
        ];
        
        this.charts.memoryUsage = new ChartRenderer('memory-usage-chart', {
            title: 'Memory Usage (MB)',
            width: 600,
            height: 300
        });
        
        this.charts.memoryUsage.initialize();
        this.charts.memoryUsage.createBarChart(chartData, {
            showLabels: true,
            showBorder: true
        });
    }
    
    initializeDatabaseChart() {
        const database = this.metrics.database_queries || {};
        const totalQueries = database.count || 0;
        const slowQueries = database.slow_count || 0;
        const normalQueries = totalQueries - slowQueries;
        
        const chartData = [
            { label: 'Normal Queries', value: normalQueries },
            { label: 'Slow Queries', value: slowQueries }
        ];
        
        this.charts.database = new ChartRenderer('database-chart', {
            title: 'Database Queries',
            width: 400,
            height: 300
        });
        
        this.charts.database.initialize();
        this.charts.database.createPieChart(chartData, {
            showLabels: true,
            showLegend: true
        });
    }
    
    initializeCacheChart() {
        const cache = this.metrics.cache_efficiency || {};
        const hitRatio = cache.hit_ratio || 0;
        const missRatio = 100 - hitRatio;
        
        const chartData = [
            { label: 'Cache Hits', value: hitRatio },
            { label: 'Cache Misses', value: missRatio }
        ];
        
        this.charts.cache = new ChartRenderer('cache-chart', {
            title: 'Cache Efficiency',
            width: 400,
            height: 300,
            colors: ['#4caf50', '#f44336'] // Зеленый для hits, красный для misses
        });
        
        this.charts.cache.initialize();
        this.charts.cache.createPieChart(chartData, {
            showLabels: true,
            showLegend: true
        });
    }
    
    initializeTimelineChart() {
        const responseTime = this.metrics.response_time || {};
        const stages = responseTime.stages || [];
        
        const timelineData = stages.map(stage => ({
            name: stage.name,
            duration: stage.duration * 1000, // Конвертация в ms
            start: stage.start * 1000
        }));
        
        this.charts.timeline = new ChartRenderer('timeline-chart', {
            title: 'Execution Timeline',
            width: 800,
            height: 200
        });
        
        this.charts.timeline.initialize();
        this.charts.timeline.createTimelineChart(timelineData, {
            showLabels: true
        });
    }
    
    initializePerformanceScoreGauge() {
        const analysis = this.metrics.analysis || {};
        const performanceScore = analysis.performance_score || 0;
        
        this.charts.performanceScore = new ChartRenderer('performance-score-chart', {
            title: 'Performance Score',
            width: 300,
            height: 200
        });
        
        this.charts.performanceScore.initialize();
        this.charts.performanceScore.createGaugeChart(performanceScore, 100, {
            label: 'Score',
            title: 'Performance Score'
        });
    }
    
    getStageColor(durationMs) {
        const threshold = this.thresholds.response_time_ms || 500;
        
        if (durationMs > threshold) return '#f44336'; // Красный
        if (durationMs > threshold * 0.7) return '#ff9800'; // Оранжевый
        if (durationMs > threshold * 0.5) return '#ffc107'; // Желтый
        if (durationMs > threshold * 0.3) return '#8bc34a'; // Светло-зеленый
        return '#4caf50'; // Зеленый
    }
    
    updateMetrics(newMetrics) {
        this.metrics = newMetrics;
        this.initialize(); // Перерисовка всех графиков
    }
}
```

### 3. Интеграция с PerformanceTab

```javascript
// В PerformanceTab.php
<script>
debugModules.performance = function() {
    // ... существующий код ...
    
    // Добавить контейнеры для графиков
    html += '<div class="performance-charts-container">';
    html += '<div class="chart-row">';
    html += '<div class="chart-container"><canvas id="response-time-chart"></canvas></div>';
    html += '<div class="chart-container"><canvas id="memory-usage-chart"></canvas></div>';
    html += '</div>';
    html += '<div class="chart-row">';
    html += '<div class="chart-container"><canvas id="database-chart"></canvas></div>';
    html += '<div class="chart-container"><canvas id="cache-chart"></canvas></div>';
    html += '</div>';
    html += '<div class="chart-row">';
    html += '<div class="chart-container full-width"><canvas id="timeline-chart"></canvas></div>';
    html += '</div>';
    html += '<div class="chart-row">';
    html += '<div class="chart-container"><canvas id="performance-score-chart"></canvas></div>';
    html += '</div>';
    html += '</div>';
    
    // Инициализация визуализации
    html += '<script>';
    html += 'setTimeout(function() {';
    html += '  const visualization = new PerformanceVisualization(metrics, thresholds);';
    html += '  visualization.initialize();';
    html += '}, 200);'; // Задержка для гарантированной загрузки DOM
    html += '</script>';
    
    return html;
};
</script>
```

## CSS стили для графиков

```css
/* Performance Charts Styles */
.performance-charts-container {
    margin-top: 20px;
}

.chart-row {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
}

.chart-container {
    flex: 1;
    min-width: 300px;
    background: #1a1a1a;
    border-radius: 6px;
    padding: 15px;
    border: 1px solid #333;
}

.chart-container.full-width {
    flex: 1 0 100%;
}

.chart-container canvas {
    width: 100% !important;
    height: auto !important;
    max-width: 100%;
}

.chart-title {
    color: #e5e7eb;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
    text-align: center;
}

.chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    color: #ccc;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.chart-tooltip {
    position: absolute;
    background: rgba(0, 0, 0, 0.8);
    color: #fff;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    pointer-events: none;
    z-index: 1000;
    max-width: 200px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.chart-tooltip::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    border-width: 5px 5px 0;
    border-style: solid;
    border-color: rgba(0, 0, 0, 0.8) transparent transparent;
}

/* Адаптивность */
@media (max-width: 768px) {
    .chart-row {
        flex-direction: column;
    }
    
    .chart-container {
        min-width: 100%;
    }
}
```

## Интерактивные элементы

### 1. Tooltips при наведении
```javascript
// Добавление tooltips к графикам
function addChartTooltips(chartElement, data) {
    chartElement.addEventListener('mousemove', function(e) {
        const rect = chartElement.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Поиск ближайшей точки данных
        const nearestPoint = findNearestDataPoint(x, y, data);
        
        if (nearestPoint) {
            showTooltip(e.clientX, e.clientY, nearestPoint);
        }
    });
    
    chartElement.addEventListener('mouseleave', function() {
        hideTooltip();
    });
}
```

### 2. Zoom и панорамирование
```javascript
// Функциональность zoom для графиков временных рядов
function enableChartZoom(chartElement, data) {
    let isDragging = false;
    let startX = 0;
    let scale = 1;
    let offsetX = 0;
    
    chartElement.addEventListener('wheel', function(e) {
        e.preventDefault();
        
        const delta = e.deltaY > 0 ? 0.9 : 1.1;
        scale = Math.max(0.5, Math.min(3, scale * delta));
        
        updateChartZoom();
    });
    
    chartElement.addEventListener('mousedown', function(e) {
        isDragging = true;
        startX = e.clientX;
    });
    
    chartElement.addEventListener('mousemove', function(e) {
        if (isDragging) {
            offsetX += (e.clientX - startX) / scale;
            startX = e.clientX;
            updateChartZoom();
        }
    });
    
    chartElement.addEventListener('mouseup', function() {
        isDragging = false;
    });
}
```

### 3. Экспорт графиков
```javascript
// Экспорт графика как изображение
function exportChartAsImage(chartElement, filename = 'chart.png') {
    const dataUrl = chartElement.toDataURL('image/png');
    const link = document.createElement('a');
    link.download = filename;
    link.href = dataUrl;
    link.click();
}
```

## Производительность визуализации

### 1. Оптимизация рендеринга
- Использование requestAnimationFrame для анимаций
- Кэширование отрисованных графиков
- Отложенный рендеринг невидимых графиков

### 2. Минимизация памяти
- Очистка ненужных canvas элементов
- Использование object pooling для временных объектов
- Оптимизация структур данных

### 3. Адаптивность
- Автоматическое масштабирование под размер контейнера
- Оптимизация для мобильных устройств
- Graceful degradation при отключенном JavaScript

## Тестирование

### 1. Функциональное тестирование
- Тестирование отрисовки различных типов графиков
- Тестирование интерактивных элементов
- Тестирование адаптивности

### 2. Производительность
- Измерение времени рендеринга графиков
- Тестирование под нагрузкой (много данных)
- Тестирование использования памяти

### 3. Кросс-браузерная совместимость
- Тестирование в Chrome, Firefox, Safari, Edge
- Тестирование на мобильных браузерах
- Тестирование с отключенными функциями

## Заключение
Система визуализации метрик предоставит разработчикам интуитивно понятные и информативные графики для анализа производительности Architect Framework 2, позволяя быстро выявлять узкие места и отслеживать эффективность оптимизаций.