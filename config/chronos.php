<?php

// Chronos API docs
return [
    'project' => 'Chronos',
    'release' => null,
    'root' => 'Cake\Chronos',

    'templatePath' => 'templates',
    'sourcePaths' => ['src'],
    'exclude' => [
        'namespaces' => ['\Cake\Chronos\Traits'],
        'names' => [],
    ],

    'versions' => [
        '2.x' => '../2.x',
        '1.x' => '../1.x',
    ],
];
