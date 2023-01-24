<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d7c2cd5c2dbf2120e4372996869e900
{
    public static $files = array (
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        'a4a119a56e50fbb293281d9a48007e0e' => __DIR__ . '/..' . '/symfony/polyfill-php80/bootstrap.php',
        '2cffec82183ee1cea088009cef9a6fc3' => __DIR__ . '/..' . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php',
        '16eed290c5592c18dc3f16802ad3d0e4' => __DIR__ . '/..' . '/ivopetkov/html5-dom-document-php/autoload.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php80\\' => 23,
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
        'Symfony\\Polyfill\\Php80\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php80',
        ),
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
        'Attribute' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'CaseInsensitiveArray' => __DIR__ . '/..' . '/php-curl-class/php-curl-class/src/Curl.class.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Curl' => __DIR__ . '/..' . '/php-curl-class/php-curl-class/src/Curl.class.php',
        'ParsedownMath' => __DIR__ . '/..' . '/parsedown-math/ParsedownMath.php',
        'PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'Stringable' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
        'UnhandledMatchError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
        'ValueError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
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
