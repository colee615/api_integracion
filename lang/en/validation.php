<?php

return [
    'required' => 'The :attribute field is required.',
    'email' => 'The :attribute field must be a valid email address.',
    'string' => 'The :attribute field must be a string.',
    'array' => 'The :attribute field must be an array.',
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'confirmed' => 'The :attribute confirmation does not match.',
    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'current_password' => 'current password',
        'name' => 'name',
        'target_url' => 'target URL',
        'events' => 'events',
        'status' => 'status',
    ],
];
