<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    
    if ('prod' === $context['APP_ENV']) {
        $kernel = new HttpCache($kernel, [
            'debug' => $context['APP_DEBUG'],
            'default_ttl' => 3600,
            'private_headers' => ['Authorization', 'Cookie'],
            'allow_reload' => false,
            'allow_revalidate' => false,
        ]);
    }
    
    return $kernel;
};
