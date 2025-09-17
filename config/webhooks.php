<?php

// config/webhooks.php

return [
    'handlers' => [
        //'create.entity'   => \App\Webhooks\Handlers\EntityCreated::class,
        'contact.created' => \App\Webhooks\Handlers\ContactCreated::class,
    ],
];
