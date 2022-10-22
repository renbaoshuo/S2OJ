<?php
	requireLib('bootstrap5');
	requireLib('calendar_heatmap');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}
	?>
<?php echoUOJPageHeader('关于我') ?>

<?php uojIncludeView('user-info', array('user' => UOJContext::user(), 'is_blog_aboutme' => '')) ?>

<?php echoUOJPageFooter() ?>
