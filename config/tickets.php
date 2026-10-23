<?php

return [
    'types' => ['task' => 'Task', 'bug' => 'Bug', 'story' => 'Story'],
    'priorities' => ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'],
    'categories' => ['open' => 'Open', 'active' => 'Active', 'done' => 'Done'],
    'fieldLabels' => ['title' => 'Title', 'description' => 'Description', 'type' => 'Type', 'priority' => 'Priority', 'assignee_id' => 'Assignee', 'due_date' => 'Due date'],
    'validationRules' => ['title' => ['required' => true, 'maxLength' => 255], 'description' => ['required' => false, 'maxLength' => 10000]],
    'defaults' => ['title' => '', 'description' => '', 'type' => 'task', 'priority' => 'medium', 'assignee_id' => null, 'due_date' => null],
    'workflow' => [
        ['code' => 'todo', 'name' => 'To Do', 'category' => 'open', 'is_initial' => true, 'next' => ['in_progress']],
        ['code' => 'in_progress', 'name' => 'In Progress', 'category' => 'active', 'is_initial' => false, 'next' => ['todo', 'in_review']],
        ['code' => 'in_review', 'name' => 'In Review', 'category' => 'active', 'is_initial' => false, 'next' => ['in_progress', 'done']],
        ['code' => 'done', 'name' => 'Done', 'category' => 'done', 'is_initial' => false, 'next' => ['todo']],
    ],
];
