<?php

// Authorization API docs
return [
    'Project' => [
        'namespaces' => 'Authorization',
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
                '3.x' => '../3.x',
                '2.x' => '../2.x',
            ],
        ],
    ],
];
