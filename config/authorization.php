<?php

// Authorization API docs
return [
    'Project' => [
        'namespace' => 'Authorization',
        'sourceDirs' => ['src'],
        'excludePatterns' => [],
        'repo' => 'https://github.com/cakephp/authorization',
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Authorization',
            'release' => null,
            'versions' => [
                '2.x' => '../2.x',
            ],
        ],
    ],
];
