<?php

namespace DanielMabadeje\LaravelDaoGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeDaoCommand extends Command
{
    protected $signature = 'make:dao
        {name : The model name (e.g. Product, Blog/Post)}
        {--model : Also generate the Eloquent model}
        {--dto : Force-generate a DTO regardless of config (use_dtos)}
        {--no-dto : Force-skip DTO generation regardless of config}
        {--force : Overwrite existing files}';

    protected $description = 'Scaffold Service -> DAO -> RepositoryInterface -> EloquentRepository (-> DTO) for a model';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $rawName = $this->argument('name');
        $model = class_basename($rawName);
        $subPath = trim(Str::replaceLast($model, '', $rawName), '/\\');
        $subPath = $subPath !== '' ? str_replace('/', '\\', $subPath) : '';

        $config = config('dao-generator');
        $baseNamespace = rtrim($config['base_namespace'], '\\');

        $useDtos = $this->option('dto')
            ? true
            : ($this->option('no-dto') ? false : (bool) ($config['use_dtos'] ?? true));

        $names = $this->buildNames($model, $subPath, $config, $baseNamespace, $useDtos);

        if ($this->option('model')) {
            $this->generateModel($names);
        }

        if ($useDtos) {
            $this->generateFromStub(
                stub: 'dto',
                path: $names['dto']['file'],
                replacements: [
                    'namespace' => $names['dto']['namespace'],
                    'class' => $names['dto']['class'],
                    'model' => $model,
                    'modelNamespace' => $names['model']['namespace'],
                ],
            );
        }

        $this->generateFromStub(
            stub: 'repository-interface',
            path: $names['repository_interface']['file'],
            replacements: [
                'namespace' => $names['repository_interface']['namespace'],
                'class' => $names['repository_interface']['class'],
                'model' => $model,
                'modelNamespace' => $names['model']['namespace'],
            ],
        );

        $this->generateFromStub(
            stub: 'eloquent-repository',
            path: $names['eloquent_repository']['file'],
            replacements: [
                'namespace' => $names['eloquent_repository']['namespace'],
                'class' => $names['eloquent_repository']['class'],
                'model' => $model,
                'modelNamespace' => $names['model']['namespace'],
                'interface' => $names['repository_interface']['class'],
                'interfaceNamespace' => $names['repository_interface']['namespace'],
            ],
        );

        $this->generateFromStub(
            stub: $useDtos ? 'dao' : 'dao-no-dto',
            path: $names['dao']['file'],
            replacements: [
                'namespace' => $names['dao']['namespace'],
                'class' => $names['dao']['class'],
                'model' => $model,
                'modelNamespace' => $names['model']['namespace'],
                'interface' => $names['repository_interface']['class'],
                'interfaceNamespace' => $names['repository_interface']['namespace'],
                'dto' => $useDtos ? $names['dto']['class'] : '',
                'dtoNamespace' => $useDtos ? $names['dto']['namespace'] : '',
                'service' => $names['service']['class'],
            ],
        );

        $this->generateFromStub(
            stub: $useDtos ? 'service' : 'service-no-dto',
            path: $names['service']['file'],
            replacements: [
                'namespace' => $names['service']['namespace'],
                'class' => $names['service']['class'],
                'model' => $model,
                'modelNamespace' => $names['model']['namespace'],
                'dao' => $names['dao']['class'],
                'daoNamespace' => $names['dao']['namespace'],
                'dto' => $useDtos ? $names['dto']['class'] : '',
                'dtoNamespace' => $useDtos ? $names['dto']['namespace'] : '',
            ],
        );

        $this->registerBinding($names, $config, $baseNamespace);

        $this->info("DAO stack for [{$model}] generated successfully.");

        return self::SUCCESS;
    }

    /**
     * Build all namespace/class/path combinations for the given model.
     */
    protected function buildNames(string $model, string $subPath, array $config, string $baseNamespace, bool $useDtos): array
    {
        $names = [];

        $names['model'] = [
            'class' => $model,
            'namespace' => $this->joinNamespace($baseNamespace, $config['paths']['model']['namespace'], $subPath),
        ];

        foreach (['dto', 'repository_interface', 'eloquent_repository', 'dao', 'service'] as $key) {
            $layer = $config['paths'][$key];
            $prefix = $layer['prefix'] ?? '';
            $suffix = $layer['suffix'] ?? '';
            $class = $prefix . $model . $suffix;
            $namespace = $this->joinNamespace($baseNamespace, $layer['namespace'], $subPath);
            $relativePath = trim($layer['path'] . ($subPath ? '/' . str_replace('\\', '/', $subPath) : ''), '/');

            $names[$key] = [
                'class' => $class,
                'namespace' => $namespace,
                'file' => app_path($relativePath . '/' . $class . '.php'),
            ];
        }

        // Model file path (only used with --model)
        $modelLayer = $config['paths']['model'];
        $modelRelativePath = trim($modelLayer['path'] . ($subPath ? '/' . str_replace('\\', '/', $subPath) : ''), '/');
        $names['model']['file'] = app_path($modelRelativePath . '/' . $model . '.php');

        return $names;
    }

    protected function joinNamespace(string $base, string $layerNamespace, string $subPath): string
    {
        $parts = array_filter([$base, $layerNamespace, $subPath]);

        return implode('\\', $parts);
    }

    protected function generateModel(array $names): void
    {
        $path = $names['model']['file'];

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->line("  <fg=yellow>skip</> Model already exists: {$path}");

            return;
        }

        $this->call('make:model', [
            'name' => $names['model']['class'],
        ]);
    }

    /**
     * Generate a file from a stub, with publishable-stub override support.
     */
    protected function generateFromStub(string $stub, string $path, array $replacements): void
    {
        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->line("  <fg=yellow>skip</> Already exists: {$path}");

            return;
        }

        $contents = $this->loadStub($stub);

        foreach ($replacements as $key => $value) {
            $contents = str_replace('{{ ' . $key . ' }}', $value, $contents);
        }

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);

        $this->line("  <fg=green>created</> " . str_replace(base_path() . '/', '', $path));
    }

    /**
     * Load a stub, preferring an app-published override if present.
     */
    protected function loadStub(string $name): string
    {
        $published = base_path("stubs/dao/{$name}.stub");

        if ($this->files->exists($published)) {
            return $this->files->get($published);
        }

        return $this->files->get(__DIR__ . "/../../../resources/stubs/dao/{$name}.stub");
    }

    /**
     * Register the interface => implementation binding in RepositoryServiceProvider,
     * creating the provider (and registering it) if it doesn't exist yet.
     */
    protected function registerBinding(array $names, array $config, string $baseNamespace): void
    {
        $providerConfig = $config['provider'];
        $providerPath = app_path($providerConfig['path']);
        $providerNamespace = $this->joinNamespace($baseNamespace, $providerConfig['namespace'], '');
        $providerClass = $providerConfig['class'];

        if (! $this->files->exists($providerPath)) {
            $contents = $this->loadStub('repository-service-provider');
            $contents = str_replace('{{ namespace }}', $providerNamespace, $contents);
            $contents = str_replace('{{ class }}', $providerClass, $contents);

            $this->files->ensureDirectoryExists(dirname($providerPath));
            $this->files->put($providerPath, $contents);

            $this->line("  <fg=green>created</> " . str_replace(base_path() . '/', '', $providerPath));

            $this->registerProviderInBootstrap($providerNamespace, $providerClass);
        }

        $contents = $this->files->get($providerPath);

        $interfaceFqcn = $names['repository_interface']['namespace'] . '\\' . $names['repository_interface']['class'];
        $implementationFqcn = $names['eloquent_repository']['namespace'] . '\\' . $names['eloquent_repository']['class'];

        $bindingLine = "        \\{$interfaceFqcn}::class => \\{$implementationFqcn}::class,";

        if (str_contains($contents, $bindingLine)) {
            $this->line('  <fg=yellow>skip</> Binding already registered.');

            return;
        }

        $marker = '// dao-generator:bindings-end';

        if (! str_contains($contents, $marker)) {
            $this->warn('  Could not find binding marker in RepositoryServiceProvider; add this binding manually:');
            $this->line('    ' . trim($bindingLine));

            return;
        }

        $contents = str_replace($marker, $bindingLine . "\n        " . $marker, $contents);

        $this->files->put($providerPath, $contents);

        $this->line('  <fg=green>bound</> ' . class_basename($interfaceFqcn) . ' -> ' . class_basename($implementationFqcn));
    }

    /**
     * Register the RepositoryServiceProvider in bootstrap/providers.php (Laravel 11+)
     * or config/app.php (Laravel <=10), if not already registered.
     */
    protected function registerProviderInBootstrap(string $namespace, string $class): void
    {
        $fqcn = $namespace . '\\' . $class;
        $bootstrapProviders = base_path('bootstrap/providers.php');

        if ($this->files->exists($bootstrapProviders)) {
            $contents = $this->files->get($bootstrapProviders);

            if (str_contains($contents, $fqcn)) {
                return;
            }

            $contents = preg_replace(
                '/return \[\n/',
                "return [\n    \\{$fqcn}::class,\n",
                $contents,
                1
            );

            $this->files->put($bootstrapProviders, $contents);
            $this->line('  <fg=green>registered</> ' . $class . ' in bootstrap/providers.php');

            return;
        }

        $this->warn("  Register {$fqcn} in your application's provider list manually (e.g. bootstrap/providers.php or config/app.php).");
    }
}
