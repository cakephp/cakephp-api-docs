<?php

// CakePHP API docs
return [
    'project' => 'CakePHP',
    'release' => 'Strawberry',
    'namespace' => '\Cake',

    'templatePath' => 'templates',
    'sourcePaths' => ['src', 'config'],
    'exclude' => [
        'namespaces' => [],
        'names' => [
            '\Cake\Collection\CollectionTrait',
            '\Cake\Collection\ExtractTrait',
            '\Cake\Datasource\EntityTrait',
            '\Cake\Datasource\QueryTrait',
            '\Cake\I18n\DateFormatTrait',
        ],
    ],

    'versions' => [
        '4.2' => '../4.2/',
        '4.1' => '../4.1/',
        '4.0' => '../4.0/',
        '3.9' => '../3.9/',
        '3.8' => '../3.8/',
        '3.7' => '../3.7/',
        '3.6' => '../3.6/',
        '3.5' => '../3.5/',
        '3.4' => '../3.4/',
        '3.3' => '../3.3/',
        '3.2' => '../3.2/',
        '3.1' => '../3.1/',
        '3.0' => '../3.0/',
        '2.10' => '../2.10/',
        '2.9' => '../2.9/',
        '2.8' => '../2.8/',
        '2.7' => '../2.7/',
        '2.6' => '../2.6/',
        '2.5' => '../2.5/',
        '2.4' => '../2.4/',
        '2.3' => '../2.3/',
        '2.2' => '../2.2/',
        '2.1' => '../2.1/',
        '2.0' => '../2.0/',
        '1.3' => '../1.3/',
        '1.2' => '../1.2/',
    ],
];
