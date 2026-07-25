# Performance Monitoring System

## Overview

The Architect Framework 2 now includes a comprehensive performance monitoring system that helps identify and optimize performance bottlenecks. The system provides real-time metrics collection, visualization, alerting, and export capabilities.

## Key Features

### 1. Real-time Metrics Collection
- **Response Time**: Track request/response cycle times
- **Memory Usage**: Monitor PHP memory consumption
- **Database Queries**: Count and time SQL queries
- **Cache Efficiency**: Track cache hit/miss ratios
- **Template Rendering**: Monitor Blueprint template compilation and rendering
- **Service Loading**: Track service initialization times

### 2. Performance Visualization
- Interactive charts and graphs in the debug panel
- Historical trend analysis
- Comparison between different time periods
- Color-coded performance indicators

### 3. Alert System
- Configurable threshold values
- Three severity levels: Info, Warning, Critical
- Multiple notification channels (log, debug panel)
- Real-time alert generation

### 4. Data Export
- JSON format for programmatic analysis
- CSV format for spreadsheet applications
- Historical data retention
- Customizable export intervals

## Configuration

### Performance Configuration File
Create or edit `app/config/performance.json`:

```json
{
  "thresholds": {
    "response_time": 2000,
    "memory_usage": 134217728,
    "database_queries": 100,
    "cache_hit_ratio": 0.8
  },
  "alerts": {
    "enabled": true,
    "channels": ["log", "debug"],
    "severity_levels": {
      "info": true,
      "warning": true,
      "critical": true
    }
  },
  "monitoring": {
    "interval": 60,
    "retention": 3600
  }
}
```

### Threshold Configuration
- `response_time`: Maximum acceptable response time in milliseconds (default: 2000ms)
- `memory_usage`: Maximum memory usage in bytes (default: 128MB)
- `database_queries`: Maximum number of database queries per request (default: 100)
- `cache_hit_ratio`: Minimum acceptable cache hit ratio (default: 0.8 or 80%)

## Usage

### Accessing Performance Data

#### Through Debug Panel
1. Enable debug mode in your environment
2. Navigate to any page with the debug panel enabled
3. Click on the "Performance" tab
4. View real-time metrics and historical data

#### Programmatically
```php
// Get performance monitor instance
$performance = $container->get('performance.monitor');

// Collect current metrics
$metrics = $performance->collectMetrics();

// Get aggregated historical data
$aggregated = $performance->getAggregatedMetrics();

// Export metrics
$export = $performance->exportMetrics('json');
```

### Using the Alert System
```php
// Get alert manager
$alerts = $container->get('performance.alerts');

// Set custom thresholds
$alerts->setThreshold('response_time', 1500);
$alerts->setThreshold('memory_usage', 100 * 1024 * 1024);

// Check for alerts
$currentAlerts = $alerts->checkAlerts();

// Register alert callback
$alerts->onAlert(function($alert) {
    // Handle alert notification
    error_log("Performance alert: {$alert['metric']} = {$alert['value']}");
});
```

### Metric Collection
```php
// Get metric collector
$collector = $container->get('performance.collector');

// Record custom metrics
$collector->recordDatabaseQuery($sql, $duration, $params);
$collector->recordCacheOperation($key, 'hit');
$collector->recordServiceLoad($serviceName, $loadTime);
```

## Integration with Existing Systems

### Debug System Integration
The performance monitoring system integrates seamlessly with the existing debug system:

1. **Automatic Data Collection**: Performance metrics are automatically collected when debug mode is enabled
2. **Debug Panel Tab**: A dedicated "Performance" tab shows real-time metrics
3. **Alert Notifications**: Performance alerts appear in the debug panel
4. **Historical Data**: Performance history is available through the debug interface

### Service Provider Integration
The `PerformanceServiceProvider` automatically registers all performance monitoring components:

```php
// In your bootstrap or service provider configuration
$container->register(PerformanceServiceProvider::class);
```

## Performance Optimization Tips

### 1. Template Caching
- Enable Blueprint template caching in production
- Use compiled template storage
- Monitor template compilation times

### 2. Service Loading
- Use lazy loading for non-essential services
- Implement service compilation for frequently used services
- Monitor service initialization times

### 3. Database Optimization
- Use query caching where appropriate
- Implement database connection pooling
- Monitor slow queries through the performance system

### 4. Memory Management
- Implement object pooling for frequently created objects
- Use weak references where appropriate
- Monitor memory usage trends

## Troubleshooting

### Common Issues

#### High Response Times
1. Check database query counts and times
2. Review template compilation caching
3. Examine service initialization sequences
4. Monitor external API calls

#### Memory Leaks
1. Use the memory usage tracking
2. Check for circular references
3. Review large object allocations
4. Monitor PHP memory limit settings

#### Cache Inefficiency
1. Review cache hit/miss ratios
2. Check cache key distribution
3. Monitor cache storage performance
4. Review cache expiration policies

### Debugging Performance Issues

1. **Enable Detailed Logging**: Set performance logging to verbose
2. **Use Export Features**: Export metrics for offline analysis
3. **Set Aggressive Alerts**: Configure lower thresholds during debugging
4. **Compare Environments**: Compare performance between development and production

## Best Practices

### Development Environment
- Enable all performance monitoring features
- Set conservative alert thresholds
- Use detailed logging for performance analysis
- Regularly review performance metrics

### Production Environment
- Enable essential monitoring only
- Set appropriate alert thresholds
- Use aggregated metrics to reduce overhead
- Schedule regular performance reviews

### Testing Environment
- Use performance monitoring during load testing
- Compare performance between code changes
- Establish performance baselines
- Test alert system functionality

## API Reference

### PerformanceMonitorInterface
```php
interface PerformanceMonitorInterface {
    public function collectMetrics(): array;
    public function getAggregatedMetrics(): array;
    public function exportMetrics(string $format): string;
    public function clearMetrics(): void;
}
```

### MetricCollectorInterface
```php
interface MetricCollectorInterface {
    public function recordDatabaseQuery(string $sql, float $duration, array $params = []): void;
    public function recordCacheOperation(string $key, string $operation): void;
    public function recordServiceLoad(string $service, float $duration): void;
    public function getCollectedMetrics(): array;
}
```

### AlertManagerInterface
```php
interface AlertManagerInterface {
    public function setThreshold(string $metric, float|int $value): void;
    public function checkAlerts(): array;
    public function getAlerts(): array;
    public function clearAlerts(): void;
    public function onAlert(callable $callback): void;
}
```

## Migration from Previous Versions

If you're upgrading from a previous version of Architect Framework:

1. **Backup Configuration**: Backup any existing performance-related configuration
2. **Review Thresholds**: Update threshold values based on your application needs
3. **Test Integration**: Verify integration with your existing debug setup
4. **Monitor Performance**: Use the new system to identify optimization opportunities

## Support and Resources

- **Documentation**: See the main documentation for framework features
- **Debug Panel**: Use the integrated debug panel for real-time monitoring
- **Export Tools**: Use export features for detailed analysis
- **Community**: Check the framework community for performance tips and best practices

## Conclusion

The performance monitoring system provides comprehensive tools for identifying, analyzing, and optimizing performance bottlenecks in your Architect Framework 2 applications. By leveraging real-time metrics, historical data analysis, and configurable alerts, you can ensure your applications maintain optimal performance under various load conditions.