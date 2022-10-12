<?php
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$REQUIRE_LIB['bootstrap5'] = '';
	?>

<?php echoUOJPageHeader(UOJLocale::get('image hosting')) ?>

<h1 class="h2">
	<?= UOJLocale::get('image hosting') ?>
</h1>

<?php echoUOJPageFooter() ?>
