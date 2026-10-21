<?php

spl_autoload_register(function ($class) {
    $prefixes = [
        'PhalconInertia\\Tests\\' => __DIR__.'/',
        'PhalconInertia\\' => __DIR__.'/../src/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (strpos($class, $prefix) === 0) {
            $file = $dir.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($file)) {
                require $file;
            }

            return;
        }
    }
});

require __DIR__.'/../src/aliases.php';
require __DIR__.'/../src/helpers.php';
