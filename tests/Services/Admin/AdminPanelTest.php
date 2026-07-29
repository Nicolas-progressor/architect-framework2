<?php

declare(strict_types=1);

namespace Tests\Services\Admin;

use PHPUnit\Framework\TestCase;

class AdminPanelTest extends TestCase
{
    private string $adminAppDir;

    protected function setUp(): void
    {
        $this->adminAppDir = getcwd() . '/app/apps/admin';
    }

    public function testAdminAppDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->adminAppDir);
    }

    public function testAdminRoutesConfigExists(): void
    {
        $this->assertFileExists($this->adminAppDir . '/config/routes.json');
    }

    public function testAdminRoutesConfigIsValidJson(): void
    {
        $content = file_get_contents($this->adminAppDir . '/config/routes.json');
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('routes', $data);
    }

    public function testAdminRoutesContainExpectedEndpoints(): void
    {
        $content = file_get_contents($this->adminAppDir . '/config/routes.json');
        $data = json_decode($content, true);
        $routes = $data['routes'];

        $this->assertArrayHasKey('/', $routes);
        $this->assertArrayHasKey('users', $routes);
        $this->assertArrayHasKey('users/create', $routes);
    }

    public function testDashboardModuleExists(): void
    {
        $this->assertFileExists($this->adminAppDir . '/modules/dashboard/controller.php');
        $this->assertFileExists($this->adminAppDir . '/modules/dashboard/view/index.php');
    }

    public function testUsersModuleExists(): void
    {
        $this->assertFileExists($this->adminAppDir . '/modules/users/controller.php');
        $this->assertFileExists($this->adminAppDir . '/modules/users/model/users.php');
        $this->assertFileExists($this->adminAppDir . '/modules/users/view/index.php');
        $this->assertFileExists($this->adminAppDir . '/modules/users/view/form.php');
    }

    public function testAdminBundleClassExists(): void
    {
        $this->assertFileExists(getcwd() . '/src/Bundle/AdminBundle/AdminBundle.php');
        $this->assertStringContainsString('class AdminBundle', file_get_contents(getcwd() . '/src/Bundle/AdminBundle/AdminBundle.php'));
    }

    public function testAdminBundleRegisteredInComposer(): void
    {
        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        $bundles = $composer['extra']['architect']['bundles'] ?? [];
        $this->assertContains('App\\Bundle\\AdminBundle\\AdminBundle', $bundles);
    }

    public function testDashboardModelReturnsStats(): void
    {
        // Verify the model file contains expected methods
        $modelContent = file_get_contents($this->adminAppDir . '/modules/dashboard/model/dashboard.php');
        $this->assertStringContainsString('getStats', $modelContent);
    }

    public function testUsersModelHasCrudMethods(): void
    {
        $modelContent = file_get_contents($this->adminAppDir . '/modules/users/model/users.php');
        $this->assertStringContainsString('getAll', $modelContent);
        $this->assertStringContainsString('getById', $modelContent);
        $this->assertStringContainsString('create', $modelContent);
        $this->assertStringContainsString('update', $modelContent);
        $this->assertStringContainsString('delete', $modelContent);
    }

    public function testDashboardViewRendersStats(): void
    {
        $viewContent = file_get_contents($this->adminAppDir . '/modules/dashboard/view/index.php');
        $this->assertStringContainsString('Dashboard', $viewContent);
        $this->assertStringContainsString('$stats', $viewContent);
    }

    public function testUsersViewRendersTable(): void
    {
        $viewContent = file_get_contents($this->adminAppDir . '/modules/users/view/index.php');
        $this->assertStringContainsString('<table', $viewContent);
        $this->assertStringContainsString('$users_list', $viewContent);
    }

    public function testUsersFormViewExists(): void
    {
        $viewContent = file_get_contents($this->adminAppDir . '/modules/users/view/form.php');
        $this->assertStringContainsString('<form', $viewContent);
        $this->assertStringContainsString('name="name"', $viewContent);
        $this->assertStringContainsString('name="email"', $viewContent);
        $this->assertStringContainsString('name="role"', $viewContent);
    }

    public function testUsersControllerHasCrudActions(): void
    {
        $controllerContent = file_get_contents($this->adminAppDir . '/modules/users/controller.php');
        $this->assertStringContainsString('index_app_output', $controllerContent);
        $this->assertStringContainsString('create_app_output', $controllerContent);
        $this->assertStringContainsString('edit_app_output', $controllerContent);
        $this->assertStringContainsString('delete_app_data', $controllerContent);
    }

    public function testAppBootstrapExists(): void
    {
        $this->assertFileExists($this->adminAppDir . '/appbootstrap.php');
    }
}
