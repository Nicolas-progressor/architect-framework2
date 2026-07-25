# Architect-Framework
Statement based, PHP framework for projects

## Development

### Running PHP via Docker

To ensure consistency across environments, all PHP execution should be done through Docker. The project includes a Docker Compose configuration with a `php_arc2` service.

#### Using the wrapper script

A convenience script `docker-php` is provided in the project root. It automatically starts the PHP container if needed and runs the given PHP command inside it.

Make the script executable:

```bash
chmod +x docker-php
```

Usage:

```bash
./docker-php <php_script> [arguments...]
```

Examples:

```bash
./docker-php bin/arc migrate
./docker-php vendor/bin/phpunit
./docker-php -v
```

#### Direct docker-compose command

Alternatively, you can use `docker-compose exec` directly:

```bash
docker-compose exec php_arc2 php bin/arc
```

#### Rule

**All PHP execution must be performed via Docker.** This includes:

- Running console commands (`arc`)
- Running tests (`phpunit`)
- Running custom PHP scripts
- Any other PHP CLI usage

This ensures that the same PHP version, extensions, and environment are used as in production-like containers.

### Starting the environment

To start all services (PHP, MySQL, Nginx, Redis):

```bash
docker-compose up -d
```

Check running containers:

```bash
docker-compose ps
```

### Stopping the environment

```bash
docker-compose down
```

## License

MIT
