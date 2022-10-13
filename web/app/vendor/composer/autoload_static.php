<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900
{
    public static $files = array (
        '2cffec82183ee1cea088009cef9a6fc3' => __DIR__ . '/..' . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Finder\\' => 25,
        ),
        'P' => 
        array (
            'PHPMailer\\PHPMailer\\' => 20,
        ),
        'G' => 
        array (
            'Gregwar\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Finder\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/finder',
        ),
        'PHPMailer\\PHPMailer\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpmailer/phpmailer/src',
        ),
        'Gregwar\\' => 
        array (
            0 => __DIR__ . '/..' . '/gregwar/captcha/src/Gregwar',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Parsedown' => 
            array (
                0 => __DIR__ . '/..' . '/erusev/parsedown',
            ),
        ),
        'H' => 
        array (
            'HTMLPurifier' => 
            array (
                0 => __DIR__ . '/..' . '/ezyang/htmlpurifier/library',
            ),
        ),
    );

    public static $classMap = array (
        'ParsedownMath' => __DIR__ . '/..' . '/parsedown-math/ParsedownMath.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900::$classMap;

        }, null, ClassLoader::class);
    }
}