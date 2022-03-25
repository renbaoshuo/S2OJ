<?php

use Gregwar\Captcha\PhraseBuilder;
use Gregwar\Captcha\CaptchaBuilder;

$builder = new CaptchaBuilder(null, new PhraseBuilder(4, "2345678abcdefhkmnrstuxz"));
$builder->build();
$_SESSION['phrase'] = $builder->getPhrase();

header('Content-Type: image/jpeg');
$builder->build()->output();

?>
