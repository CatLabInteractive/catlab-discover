<?php
return [

    'rootDomain' => env('CLOUDFLARE_ROOT_DOMAIN'),

    'validRootDomains' => explode(',', env('CLOUDFLARE_VALID_ROOT_DOMAINS')),

    'email' => env('CLOUDFLARE_EMAIL'),
    'apiKey' => env('CLOUDFLARE_API_KEY')

];
