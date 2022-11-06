<?php
	requireLib('bootstrap5');
	requireLib('calendar_heatmap');

	if (!Auth::check()) {
		redirectToLogin();
	}

	($user = UOJUser::query($_GET['username'])) || UOJResponse::page404();
	?>

<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>

<?php uojIncludeView('user-info', ['user' => $user]) ?>

<?php echoUOJPageFooter() ?>
