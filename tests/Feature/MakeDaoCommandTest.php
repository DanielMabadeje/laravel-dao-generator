<?php

namespace DanielMabadeje\LaravelDaoGenerator\Tests\Feature;

use DanielMabadeje\LaravelDaoGenerator\Tests\TestCase;

class MakeDaoCommandTest extends TestCase
{
    public function test_it_generates_full_stack_with_dtos_by_default(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])
            ->assertExitCode(0);

        $this->assertFileExists(app_path('DataTransferObjects/ProductData.php'));
        $this->assertFileExists(app_path('Repositories/Contracts/ProductRepositoryInterface.php'));
        $this->assertFileExists(app_path('Repositories/Eloquent/EloquentProductRepository.php'));
        $this->assertFileExists(app_path('DAOs/ProductDAO.php'));
        $this->assertFileExists(app_path('Services/ProductService.php'));
        $this->assertFileExists(app_path('Providers/RepositoryServiceProvider.php'));
    }

    public function test_generated_dto_has_expected_namespace_and_class(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('DataTransferObjects/ProductData.php'));

        $this->assertStringContainsString('namespace App\DataTransferObjects;', $contents);
        $this->assertStringContainsString('final class ProductData', $contents);
        $this->assertStringContainsString('use App\Models\Product;', $contents);
        $this->assertStringContainsString('public static function fromModel(Product $model): self', $contents);
    }

    public function test_generated_repository_interface_has_expected_methods(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Repositories/Contracts/ProductRepositoryInterface.php'));

        $this->assertStringContainsString('namespace App\Repositories\Contracts;', $contents);
        $this->assertStringContainsString('interface ProductRepositoryInterface', $contents);

        foreach (['query', 'all', 'paginate', 'find', 'findOrFail', 'create', 'update', 'delete'] as $method) {
            $this->assertStringContainsString("function {$method}(", $contents);
        }
    }

    public function test_generated_eloquent_repository_implements_interface(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Repositories/Eloquent/EloquentProductRepository.php'));

        $this->assertStringContainsString('namespace App\Repositories\Eloquent;', $contents);
        $this->assertStringContainsString('class EloquentProductRepository implements ProductRepositoryInterface', $contents);
        $this->assertStringContainsString('use App\Repositories\Contracts\ProductRepositoryInterface;', $contents);
    }

    public function test_generated_dao_wraps_results_in_dto_by_default(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('DAOs/ProductDAO.php'));

        $this->assertStringContainsString('namespace App\DAOs;', $contents);
        $this->assertStringContainsString('class ProductDAO', $contents);
        $this->assertStringContainsString('use App\DataTransferObjects\ProductData;', $contents);
        $this->assertStringContainsString('ProductData::fromModel($model)', $contents);
        $this->assertStringContainsString('public function find(int|string $id): ?ProductData', $contents);
    }

    public function test_generated_service_depends_on_dao(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Services/ProductService.php'));

        $this->assertStringContainsString('namespace App\Services;', $contents);
        $this->assertStringContainsString('class ProductService', $contents);
        $this->assertStringContainsString('use App\DAOs\ProductDAO;', $contents);
        $this->assertStringContainsString('protected ProductDAO $dao', $contents);
    }

    public function test_no_dto_option_skips_dto_and_returns_models(): void
    {
        $this->artisan('make:dao', ['name' => 'Product', '--no-dto' => true])
            ->assertExitCode(0);

        $this->assertFileDoesNotExist(app_path('DataTransferObjects/ProductData.php'));

        $daoContents = file_get_contents(app_path('DAOs/ProductDAO.php'));
        $this->assertStringNotContainsString('ProductData', $daoContents);
        $this->assertStringContainsString('public function find(int|string $id): ?Product', $daoContents);

        $serviceContents = file_get_contents(app_path('Services/ProductService.php'));
        $this->assertStringNotContainsString('ProductData', $serviceContents);
        $this->assertStringContainsString('use App\Models\Product;', $serviceContents);
    }

    public function test_repository_service_provider_is_created_with_binding(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Providers/RepositoryServiceProvider.php'));

        $this->assertStringContainsString('namespace App\Providers;', $contents);
        $this->assertStringContainsString('class RepositoryServiceProvider extends ServiceProvider', $contents);
        $this->assertStringContainsString(
            '\App\Repositories\Contracts\ProductRepositoryInterface::class => \App\Repositories\Eloquent\EloquentProductRepository::class,',
            $contents
        );
    }

    public function test_repository_service_provider_is_registered_in_bootstrap_providers(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(base_path('bootstrap/providers.php'));

        $this->assertStringContainsString('\App\Providers\RepositoryServiceProvider::class', $contents);
    }

    public function test_running_command_twice_does_not_duplicate_binding(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $contents = file_get_contents(app_path('Providers/RepositoryServiceProvider.php'));

        $occurrences = substr_count(
            $contents,
            '\App\Repositories\Contracts\ProductRepositoryInterface::class => \App\Repositories\Eloquent\EloquentProductRepository::class,'
        );

        $this->assertSame(1, $occurrences);
    }

    public function test_running_command_twice_does_not_overwrite_files_without_force(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $path = app_path('DAOs/ProductDAO.php');
        file_put_contents($path, "<?php\n// custom edits made by developer\n");

        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $this->assertStringContainsString('custom edits made by developer', file_get_contents($path));
    }

    public function test_force_option_overwrites_existing_files(): void
    {
        $this->artisan('make:dao', ['name' => 'Product'])->assertExitCode(0);

        $path = app_path('DAOs/ProductDAO.php');
        file_put_contents($path, "<?php\n// custom edits made by developer\n");

        $this->artisan('make:dao', ['name' => 'Product', '--force' => true])->assertExitCode(0);

        $this->assertStringNotContainsString('custom edits made by developer', file_get_contents($path));
        $this->assertStringContainsString('class ProductDAO', file_get_contents($path));
    }

    public function test_nested_model_path_generates_correct_namespaces(): void
    {
        $this->artisan('make:dao', ['name' => 'Blog/Post', '--no-dto' => true])
            ->assertExitCode(0);

        $this->assertFileExists(app_path('DAOs/Blog/PostDAO.php'));
        $this->assertFileExists(app_path('Repositories/Contracts/Blog/PostRepositoryInterface.php'));
        $this->assertFileExists(app_path('Repositories/Eloquent/Blog/EloquentPostRepository.php'));
        $this->assertFileExists(app_path('Services/Blog/PostService.php'));

        $dao = file_get_contents(app_path('DAOs/Blog/PostDAO.php'));
        $this->assertStringContainsString('namespace App\DAOs\Blog;', $dao);
        $this->assertStringContainsString('use App\Models\Blog\Post;', $dao);
        $this->assertStringContainsString('use App\Repositories\Contracts\Blog\PostRepositoryInterface;', $dao);

        // Cleanup nested DAOs/Repositories/Services dirs created under Blog
        $this->files->deleteDirectory(app_path('DAOs/Blog'));
        $this->files->deleteDirectory(app_path('Repositories/Contracts/Blog'));
        $this->files->deleteDirectory(app_path('Repositories/Eloquent/Blog'));
        $this->files->deleteDirectory(app_path('Services/Blog'));
    }

    public function test_dto_option_forces_dto_generation_even_if_config_disables_it(): void
    {
        config()->set('dao-generator.use_dtos', false);

        $this->artisan('make:dao', ['name' => 'Product', '--dto' => true])
            ->assertExitCode(0);

        $this->assertFileExists(app_path('DataTransferObjects/ProductData.php'));

        $dao = file_get_contents(app_path('DAOs/ProductDAO.php'));
        $this->assertStringContainsString('ProductData', $dao);
    }
}
