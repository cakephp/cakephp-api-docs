<?php

// Elastic Search API docs
return [
    'Project' => [
        'namespace' => 'Cake\ElasticSearch',
        'sourceDirs' => ['src'],
        'excludePatterns' => [],
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Elastic Search',
            'release' => null,
            'versions' => [
                '3.x' => '../3.x',
                '2.x' => '../2.x',
            ],
        ],
    ],
];
