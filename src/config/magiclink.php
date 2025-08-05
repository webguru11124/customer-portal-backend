<?php

return [
    'secret' => env('MAGICLINK_SECRET', ''),
    'ttl' => (int) env('MAGICLINK_TTL', 24),
];
