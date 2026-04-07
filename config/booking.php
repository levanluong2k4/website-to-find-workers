<?php

return [
    'worker_reminder' => [
        'minutes_before' => (int) env('BOOKING_WORKER_REMINDER_MINUTES', 30),
    ],
];
