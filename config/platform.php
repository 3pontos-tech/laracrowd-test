<?php

return [
    /*
     * Address that receives operational alerts raised by background flows.
     */
    'ops_email' => env('PLATFORM_OPS_EMAIL', 'ops@example.test'),

    'money' => [
        'defaultCurrency' => 'BRL',
        'defaultCurrencySymbol' => 'R$',
        'defaultCurrencyLocale' => 'pt_BR',
        'scale' => 8,
    ],
];
