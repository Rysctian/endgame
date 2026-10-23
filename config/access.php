<?php

$modules = [
    'members' => [
        'label' => 'Workspace members',
        'actions' => ['view' => 'View members', 'create' => 'Invite members', 'edit' => 'Change member roles', 'delete' => 'Remove members'],
    ],
    'roles' => [
        'label' => 'Roles & permissions',
        'actions' => ['view' => 'View roles', 'create' => 'Create roles', 'edit' => 'Edit role details', 'delete' => 'Delete custom roles', 'setup' => 'Configure role permissions'],
    ],
    'projects' => [
        'label' => 'Projects',
        'actions' => ['view' => 'View assigned projects', 'create' => 'Create projects', 'edit' => 'Edit active projects', 'delete' => 'Archive projects', 'setup' => 'Manage project members and restore projects'],
    ],
    'activity' => ['label' => 'Activity log', 'actions' => ['view' => 'View workspace activity']],
];
$permissions = [];
foreach ($modules as $module => $definition) {
    foreach ($definition['actions'] as $action => $label) {
        $permissions[$module.'.'.$action] = $label;
    }
}

return ['modules' => $modules, 'permissions' => $permissions];
