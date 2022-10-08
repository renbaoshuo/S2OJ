<?php
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$REQUIRE_LIB['bootstrap5'] = '';
	$REQUIRE_LIB['calendar_heatmap'] = '';
	?>
<?php echoUOJPageHeader('关于我') ?>

<?php uojIncludeView('user-info', array('user' => UOJContext::user(), 'is_blog_aboutme' => '')) ?>

<?php echoUOJPageFooter() ?>
