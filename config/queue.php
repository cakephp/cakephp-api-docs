<?php

// Queue API docs
return [
    'Project' => [
        'namespaces' => 'Cake\Queue',
        'sourceDirs' => ['src'],
        'repo' => 'https://github.com/cakephp/queue',
    ],

    'Twig' => [
        'templateDir' => 'templates',
        'globals' => [
            'project' => 'Queue',
            'release' => null,
            'versions' => [
                '2.x' => '../2.x',
                '1.x' => '../2.x',
            ],
        ],
    ],
];
