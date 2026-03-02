<?php
return [
    'Admin' => [
        'create_task' => true,
        'edit_task' => true,
        'update_task' => true,
        'delete_task' => true,
        'modify-task' => true,

        'create_subtask' => true,
        'edit_subtask' => true,
        'update_subtask' => true,
        'delete_subtask' => true,

        'upload_file' => true,
        'delete_file' => true,
        'manage_users' => true,
    ],

    'Supervisor' => [
        'create_task' => true,
        'edit_task' => true,
        'update_task' => true,
        'modify-task' => true,
        'delete_task' => false,

        'create_subtask' => true,
        'edit_subtask' => true,
        'update_subtask' => true,
        'delete_subtask' => false,

        'upload_file' => true,
        'delete_file' => false,
        'manage_users' => false,
    ],

    '' => [
        'create_task' => false,
        'edit_task' => true,
        'update_task' => true, // Only for status/comments
        'delete_task' => false,

        'create_subtask' => false,
        'edit_subtask' => false,
        'update_subtask' => false,
        'delete_subtask' => false,

        'upload_file' => true,
        'delete_file' => false,
        'manage_users' => false,
    ],
];
