# Laravel DAO Generator

Scaffold the `Service -> DAO -> RepositoryInterface -> EloquentRepository -> Model` architecture
with a single artisan command — and optionally generate a DTO layer too.

## Installation

```bash
composer require danielmabadeje/laravel-dao-generator --dev
```

The package auto-registers via Laravel's package discovery.

## Usage

```bash
php artisan make:dao Product
```

This generates:

```
app/
├── DataTransferObjects/ProductData.php
├── Repositories/
│   ├── Contracts/ProductRepositoryInterface.php
│   └── Eloquent/EloquentProductRepository.php
├── DAOs/ProductDAO.php
├── Services/ProductService.php
└── Providers/RepositoryServiceProvider.php   (created on first run)
```

It also:

- Binds `ProductRepositoryInterface` -> `EloquentProductRepository` inside `RepositoryServiceProvider`.
- Registers `RepositoryServiceProvider` in `bootstrap/providers.php` (Laravel 11+) if not already present.
- Is **idempotent** — running it again won't duplicate bindings or overwrite existing files (unless `--force`).

### Options

| Option | Description |
|---|---|
| `--model` | Also generate the Eloquent model via `make:model`. |
| `--dto` | Force DTO generation, overriding `use_dtos` config. |
| `--no-dto` | Skip DTO generation, overriding `use_dtos` config. |
| `--force` | Overwrite existing files. |

### Nested models

```bash
php artisan make:dao Blog/Post
```

Generates everything under the `Blog` sub-namespace, e.g. `App\DAOs\Blog\PostDAO`.

## Layer responsibilities

- **EloquentRepository** — pure Eloquent queries (`find`, `paginate`, `create`, `update`, `delete`). No business logic.
- **DAO** — orchestrates repository calls, wraps DB transactions, maps Models <-> DTOs.
- **Service** — business logic, validation orchestration, events. Controllers depend on this.
- **DTO** *(optional)* — immutable data object returned to controllers instead of raw Models.

## Configuration

```bash
php artisan vendor:publish --tag=dao-generator-config
```

Adjust namespaces, paths, naming conventions (prefixes/suffixes per layer), and toggle
`use_dtos` globally in `config/dao-generator.php`.

## Customizing stubs

```bash
php artisan vendor:publish --tag=dao-generator-stubs
```

Published to `stubs/dao/`. Edit these to match your team's code style — the generator
prefers published stubs over its built-in defaults.

## License

MIT
