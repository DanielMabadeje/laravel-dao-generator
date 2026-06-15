<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Base Namespace
    |--------------------------------------------------------------------------
    |
    | The root namespace under which all generated classes live. Defaults to
    | the application's namespace (usually "App").
    |
    */
    'base_namespace' => 'App',

    /*
    |--------------------------------------------------------------------------
    | Layer Paths & Namespaces
    |--------------------------------------------------------------------------
    |
    | Each key maps a layer to its relative path (from app/) and namespace
    | suffix (appended to base_namespace). Adjust these to match your
    | team's conventions. Use {Model} as a placeholder for the model name.
    |
    */
    'paths' => [
        'model' => [
            'path'      => 'Models',
            'namespace' => 'Models',
        ],
        'dto' => [
            'path'      => 'DataTransferObjects',
            'namespace' => 'DataTransferObjects',
            'suffix'    => 'Data',
        ],
        'repository_interface' => [
            'path'      => 'Repositories/Contracts',
            'namespace' => 'Repositories\\Contracts',
            'suffix'    => 'RepositoryInterface',
        ],
        'eloquent_repository' => [
            'path'      => 'Repositories/Eloquent',
            'namespace' => 'Repositories\\Eloquent',
            'prefix'    => 'Eloquent',
            'suffix'    => 'Repository',
        ],
        'dao' => [
            'path'      => 'DAOs',
            'namespace' => 'DAOs',
            'suffix'    => 'DAO',
        ],
        'service' => [
            'path'      => 'Services',
            'namespace' => 'Services',
            'suffix'    => 'Service',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Service Provider
    |--------------------------------------------------------------------------
    |
    | The generator will create this provider (if missing) and register
    | interface -> implementation bindings inside it automatically.
    |
    */
    'provider' => [
        'path'      => 'Providers/RepositoryServiceProvider.php',
        'namespace' => 'Providers',
        'class'     => 'RepositoryServiceProvider',
    ],

    /*
    |--------------------------------------------------------------------------
    | DTO Generation
    |--------------------------------------------------------------------------
    |
    | When true, generated DAO/Service methods type-hint and return DTOs
    | instead of raw Models/Collections. The DTO class is generated as a
    | simple readonly data object with a fromModel() factory method.
    |
    */
    'use_dtos' => true,

];
