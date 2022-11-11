<?php
requireLib('bootstrap5');

Auth::check() || redirectToLogin();
UOJUser::checkPermission(Auth::user(), 'users.view') || UOJResponse::page403();

$config = [
	'page_len' => 50,
	'div_classes' => ['card', 'mb-3'],
	'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center'],
	'card' => true
];

if (isset($_GET['type']) && $_GET['type'] == 'accepted') {
	$config['by_accepted'] = true;
	$title = UOJLocale::get('top solver');
} else {
	become404Page();
}
?>

<?php echoUOJPageHeader($title) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1><?= $title ?></h1>

		<?php UOJRanklist::printHTML($config) ?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
