<?php

namespace DanielMabadeje\LaravelDaoGenerator\Tests\Unit;

use DanielMabadeje\LaravelDaoGenerator\Tests\TestCase;

class StubOverrideTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->files->deleteDirectory(base_path('stubs'));

        parent::tearDown();
    }

    public function test_published_stub_overrides_default_stub(): void
    {
        $stubPath = base_path('stubs/dao/service.stub');
        $this->files->ensureDirectoryExists(dirname($stubPath));
        $this->files->put($stubPath, "<?php\n\nnamespace {{ namespace }};\n\n// CUSTOM TEAM TEMPLATE\nclass {{ class }} {}\n");

        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Services/ProductService.php'));

        $this->assertStringContainsString('CUSTOM TEAM TEMPLATE', $contents);
        $this->assertStringContainsString('class ProductService {}', $contents);
    }

    public function test_default_stub_is_used_when_no_override_published(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Services/ProductService.php'));

        $this->assertStringNotContainsString('CUSTOM TEAM TEMPLATE', $contents);
        $this->assertStringContainsString('Business logic layer for Product.', $contents);
    }
}
