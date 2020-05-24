<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3033ce084e95b0374b965bd2744c3fa1
{
    public static $prefixLengthsPsr4 = array (
        'J' => 
        array (
            'Jaybizzle\\CrawlerDetect\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Jaybizzle\\CrawlerDetect\\' => 
        array (
            0 => __DIR__ . '/..' . '/jaybizzle/crawler-detect/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3033ce084e95b0374b965bd2744c3fa1::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3033ce084e95b0374b965bd2744c3fa1::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
