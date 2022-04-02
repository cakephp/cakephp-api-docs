<?php

// Chronos API docs
return [
    'project' => 'Chronos',
    'release' => null,
    'root' => 'Cake\Chronos',
    'githubRepoName' => 'chronos',

    'templatePath' => 'templates',
    'sourceDirs' => ['src'],
    'exclude' => [
        'namespaces' => ['\Cake\Chronos\Traits'],
        'names' => [],
    ],

    'versions' => [
        '2.x' => '../2.x',
        '1.x' => '../1.x',
    ],
];
