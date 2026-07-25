<?php

namespace Tests\Helpers;

use Architect\Helpers\Facades\Helper_Title;
use Architect\Helpers\Core\HelperManager;
use Architect\Helpers\Core\HelpersServiceProvider;
use Architect\Support\ServiceProviders\CoreServiceProvider;
use Architect\Core\Container;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    protected static $container;

    public static function setUpBeforeClass(): void
    {
        // Создаём контейнер
        $container = new Container();

        // Регистрируем базовые сервисы
        $coreProvider = new CoreServiceProvider($container);
        $coreProvider->register($container);

        // Регистрируем HelpersServiceProvider
        $helpersProvider = new HelpersServiceProvider($container);
        $helpersProvider->register($container);

        // Устанавливаем контейнер для фасадов
        Helper_Title::setContainer($container);

        self::$container = $container;
    }

    public function testTitleSetAndGet()
    {
        Helper_Title::set('Test Title');
        $this->assertEquals('Test Title', Helper_Title::get());
    }

    public function testTitleAppend()
    {
        Helper_Title::set('Home');
        Helper_Title::append(' - Site');
        $this->assertEquals('Home - Site', Helper_Title::get());
    }

    public function testTitlePrepend()
    {
        Helper_Title::set('Dashboard');
        Helper_Title::prepend('Admin :: ');
        $this->assertEquals('Admin :: Dashboard', Helper_Title::get());
    }

    public function testTitleClear()
    {
        Helper_Title::set('Some Title');
        Helper_Title::clear();
        $this->assertEquals('', Helper_Title::get());
    }

    public function testTitleRender()
    {
        Helper_Title::set('Page Title');
        $rendered = Helper_Title::render();
        // render returns title without HTML tags
        $this->assertEquals('Page Title', $rendered);
    }
}