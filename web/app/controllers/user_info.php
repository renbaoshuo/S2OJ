<?php
requireLib('calendar_heatmap');

Auth::check() || redirectToLogin();
($user = UOJUser::query($_GET['username'])) || UOJResponse::page404();
Auth::id() == $user['username'] || UOJUser::checkPermission(Auth::user(), 'users.view') || UOJResponse::page403();
?>

<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>

<?php uojIncludeView('user-info', ['user' => $user]) ?>

<?php echoUOJPageFooter() ?>
