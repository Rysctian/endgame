<?php

return [
    'statuses' => ['active' => 'Active', 'archived' => 'Archived'],
    'fieldLabels' => ['name' => 'Project name', 'key' => 'Project key', 'description' => 'Description'],
    'defaults' => ['name' => '', 'key' => '', 'description' => '', 'status' => 'active'],
    'validationRules' => [
        'name' => ['required' => true, 'maxLength' => 255],
        'key' => ['required' => true, 'minLength' => 2, 'maxLength' => 10, 'pattern' => '[A-Z][A-Z0-9]{1,9}'],
        'description' => ['required' => false, 'maxLength' => 10000],
    ],
];
