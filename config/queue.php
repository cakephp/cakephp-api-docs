<?php

// Queue API docs
return [
    'Project' => [
        'namespaces' => 'Cake\Queue',
        'sourceDirs' => ['src'],
        'excludePatterns' => [],
        'repo' => 'https://github.com/cakephp/queue',
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
