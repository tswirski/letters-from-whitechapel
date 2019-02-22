<?php

return [
    'email' => [
        'not_empty' => 'Required field, fill up!',
        'email' => 'This doesn\'t look like email at all...',
        'email_domain' => 'Invalid domain',
        'max_length' => 'Must not exceed :param2 characters',
        'Model_User::email_is_available' => 'Email already taken',
    ], 'password' => [
        'not_empty' => 'Required field, fill up!',
        'not_blank' => 'Password can not be blank',
        'min_length' => 'Required field, fill up!',
        'max_length' => 'Password must not exceed :value characters',
    ],
    'nickname' => [
        'not_empty' => 'Required field, fill up!',
        'not_blank' => 'Required field, fill up!',
        'alpha_name' => 'Letters, dashes and spaces only',
        'nickname' => 'Letters, numbers and dashes only',
        'max_length' => 'Must not exceed :param2 characters',
        'Model_User::nickname_is_available' => 'Nickname already taken',
    ],
];
