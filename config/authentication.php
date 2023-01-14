<?php

// Authentication API docs
return [
    'Project' => [
        'namespace' => 'Authentication',
        'sourceDirs' => ['src'],
        'excludePatterns' => [],
        'repo' => 'https://github.com/cakephp/authentication',
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Authentication',
            'release' => null,
            'versions' => [
                '2.x' => '../2.x',
            ],
        ],
    ],
];
