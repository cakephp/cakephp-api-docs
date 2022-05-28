<?php

// Chronos API docs
return [
    'Project' => [
        'namespaces' => 'Cake\Chronos',
        'sourceDirs' => ['src'],
        'excludePatterns' => [
            'Cake\Chronos\Traits',
        ],
        'repo' => 'https://github.com/cakephp/chronos',
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Chronos',
            'release' => null,
            'versions' => [
                '2.x' => '../2.x',
                '1.x' => '../1.x',
            ],
        ],
    ],
];
