<?php

// Queue API docs
return [
    'Project' => [
        'namespace' => 'Cake\Queue',
        'sourceDirs' => ['src'],
        'excludePatterns' => [],
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Queue',
            'release' => null,
            'versions' => [
                '0.x' => '../0.x',
            ],
        ],
    ],
];
