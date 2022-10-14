<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (isset($_GET['type']) && $_GET['type'] == 'accepted') {
		$config = array('page_len' => 100, 'by_accepted' => true);
		$title = UOJLocale::get('top solver');
	} else {
		become404Page();
	}
	
	requireLib('bootstrap5');

	$config = [
		'div_classes' => ['card', 'mb-3'],
		'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center'],
	];
	?>
<?php echoUOJPageHeader($title) ?>
<h1 class="h2"><?= $title ?></h1>
<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
