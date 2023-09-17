<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd6783eb944943bfd2754703b95d342fc
{
    public static $prefixLengthsPsr4 = array (
        'T' => 
        array (
            'Twilio\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Twilio\\' => 
        array (
            0 => __DIR__ . '/..' . '/twilio/sdk/src/Twilio',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitd6783eb944943bfd2754703b95d342fc::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd6783eb944943bfd2754703b95d342fc::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd6783eb944943bfd2754703b95d342fc::$classMap;

        }, null, ClassLoader::class);
    }
}