<?php
	requireLib('bootstrap5');
	requireLib('calendar_heatmap');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$username = $_GET['username'];

	if (!validateUsername($username) || !($user = queryUser($username))) {
		become404Page();
	}
	?>

<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>

<?php uojIncludeView('user-info', array('user' => $user, 'myUser' => $myUser)) ?>

<?php echoUOJPageFooter() ?>
