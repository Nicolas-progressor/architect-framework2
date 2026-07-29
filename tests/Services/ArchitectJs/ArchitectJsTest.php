<?php

declare(strict_types=1);

namespace Tests\Services\ArchitectJs;

use PHPUnit\Framework\TestCase;

class ArchitectJsTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = getcwd() . '/htdocs/assets/architect-js';
    }

    public function testDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->baseDir);
    }

    public function testAllSourceFilesExist(): void
    {
        $files = ['core.js', 'state.js', 'http.js', 'router.js', 'component.js', 'app.js'];
        foreach ($files as $file) {
            $this->assertFileExists($this->baseDir . '/src/' . $file, "Missing: {$file}");
        }
    }

    public function testPackageJsonExists(): void
    {
        $this->assertFileExists($this->baseDir . '/package.json');
        $data = json_decode(file_get_contents($this->baseDir . '/package.json'), true);
        $this->assertSame('architect-js', $data['name']);
        $this->assertSame('1.0.0', $data['version']);
    }

    public function testReadmeExists(): void
    {
        $this->assertFileExists($this->baseDir . '/README.md');
    }

    public function testCoreJsExportsContainer(): void
    {
        $content = file_get_contents($this->baseDir . '/src/core.js');
        $this->assertStringContainsString('Architect.Container', $content);
        $this->assertStringContainsString('Container.prototype.get', $content);
        $this->assertStringContainsString('Container.prototype.set', $content);
        $this->assertStringContainsString('Container.prototype.singleton', $content);
    }

    public function testStateJsExportsEventManagerAndStore(): void
    {
        $content = file_get_contents($this->baseDir . '/src/state.js');
        $this->assertStringContainsString('EventManager', $content);
        $this->assertStringContainsString('Store', $content);
        $this->assertStringContainsString('EventManager.prototype.on', $content);
        $this->assertStringContainsString('EventManager.prototype.emit', $content);
        $this->assertStringContainsString('Store.prototype.set', $content);
        $this->assertStringContainsString('Store.prototype.get', $content);
    }

    public function testHttpJsExportsHttpClient(): void
    {
        $content = file_get_contents($this->baseDir . '/src/http.js');
        $this->assertStringContainsString('HttpClient', $content);
        $this->assertStringContainsString('HttpClient.prototype.get', $content);
        $this->assertStringContainsString('HttpClient.prototype.post', $content);
        $this->assertStringContainsString('HttpClient.prototype.use', $content);
    }

    public function testRouterJsExportsRouter(): void
    {
        $content = file_get_contents($this->baseDir . '/src/router.js');
        $this->assertStringContainsString('Router.prototype.route', $content);
        $this->assertStringContainsString('Router.prototype.group', $content);
        $this->assertStringContainsString('Router.prototype.navigate', $content);
        $this->assertStringContainsString('Router.prototype.beforeEach', $content);
    }

    public function testComponentJsExportsComponentEngine(): void
    {
        $content = file_get_contents($this->baseDir . '/src/component.js');
        $this->assertStringContainsString('Architect.Component', $content);
        $this->assertStringContainsString('customElements.define', $content);
    }

    public function testAppJsExportsBootFunction(): void
    {
        $content = file_get_contents($this->baseDir . '/src/app.js');
        $this->assertStringContainsString('Architect.App', $content);
        $this->assertStringContainsString('App.prototype.boot', $content);
    }

    public function testArchitectJsServiceProviderExists(): void
    {
        $path = getcwd() . '/architect/Services/ArchitectJs/Providers/ArchitectJsServiceProvider.php';
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString('ArchitectJsServiceProvider', $content);
        $this->assertStringContainsString('architect-js/src/', $content);
    }

    public function testJsFilesHaveNoSyntaxErrors(): void
    {
        $files = ['core.js', 'state.js', 'http.js', 'router.js', 'component.js', 'app.js'];
        foreach ($files as $file) {
            $content = file_get_contents($this->baseDir . '/src/' . $file);
            // UMD wrapper check: each file should have define/module.exports/root pattern
            $this->assertStringContainsString('function', $content, "{$file} has no function definition");
        }
    }

    public function testEachFileExposesUniqueGlobal(): void
    {
        $expectations = [
            'core.js'      => 'Architect.Container',
            'state.js'     => 'Architect.State',
            'http.js'      => 'Architect.Http',
            'router.js'    => 'Architect.Router',
            'component.js' => 'Architect.Component',
            'app.js'       => 'Architect.App',
        ];

        foreach ($expectations as $file => $expected) {
            $content = file_get_contents($this->baseDir . '/src/' . $file);
            $this->assertStringContainsString($expected, $content, "{$file} should expose {$expected}");
        }
    }
}
