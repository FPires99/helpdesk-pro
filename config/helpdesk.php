<?php

return [
    // Show one-click demo accounts on the login page (turn off for a real deployment).
    'demo' => env('HELPDESK_DEMO', true),

    // Allow anyone to create an employee account from the login page.
    'registration' => env('HELPDESK_REGISTRATION', true),

    'demo_accounts' => [
        ['role' => 'Employee', 'email' => 'employee@helpdesk.test', 'description' => 'Raise tickets and follow their progress'],
        ['role' => 'Technician', 'email' => 'tech@helpdesk.test', 'description' => 'Work the queue, reply and resolve'],
        ['role' => 'Admin', 'email' => 'admin@helpdesk.test', 'description' => 'Everything, plus deleting tickets'],
    ],
];
