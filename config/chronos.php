<?php

// Chronos API docs
return [
    'Project' => [
        'namespace' => 'Cake\Chronos',
        'sourceDirs' => ['src'],
        'excludePatterns' => [
            'Cake\Chronos\Traits',
        ],
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
