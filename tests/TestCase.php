<?php

namespace DanielMabadeje\LaravelDaoGenerator\Tests;

use DanielMabadeje\LaravelDaoGenerator\DaoGeneratorServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem();
    }

    protected function tearDown(): void
    {
        // Clean up any generated files so tests stay isolated/repeatable.
        $this->files->deleteDirectory(app_path('DataTransferObjects'));
        $this->files->deleteDirectory(app_path('Repositories'));
        $this->files->deleteDirectory(app_path('DAOs'));
        $this->files->deleteDirectory(app_path('Services'));
        $this->files->deleteDirectory(app_path('Models/Blog'));

        $providerPath = app_path('Providers/RepositoryServiceProvider.php');
        if ($this->files->exists($providerPath)) {
            $this->files->delete($providerPath);
        }

        $bootstrapProviders = base_path('bootstrap/providers.php');
        if ($this->files->exists($bootstrapProviders)) {
            $this->files->put($bootstrapProviders, "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DaoGeneratorServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dao-generator.base_namespace', 'App');
    }

    /**
     * Ensure the application skeleton (app/, bootstrap/providers.php) exists
     * in the Testbench workbench, since the generator writes real files.
     */
    protected function defineRoutes($router): void
    {
        $bootstrapDir = base_path('bootstrap');
        $this->files->ensureDirectoryExists($bootstrapDir);

        $providersFile = $bootstrapDir . '/providers.php';
        if (! $this->files->exists($providersFile)) {
            $this->files->put($providersFile, "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
        }

        $appServiceProvider = app_path('Providers/AppServiceProvider.php');
        if (! $this->files->exists($appServiceProvider)) {
            $this->files->ensureDirectoryExists(app_path('Providers'));
            $this->files->put($appServiceProvider, "<?php\n\nnamespace App\\Providers;\n\nuse Illuminate\\Support\\ServiceProvider;\n\nclass AppServiceProvider extends ServiceProvider\n{\n    public function register(): void {}\n    public function boot(): void {}\n}\n");
        }
    }
}
