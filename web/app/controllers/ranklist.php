<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	if (isset($_GET['type']) && $_GET['type'] == 'accepted') {
		$config = array('page_len' => 100, 'by_accepted' => true);
		$title = UOJLocale::get('top solver');
	} else {
		become404Page();
	}
	
	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';

		$config['div_classes'] = array('card', 'mb-3');
		$config['table_classes'] = array('table', 'uoj-table', 'mb-0', 'text-center');
	}
	?>
<?php echoUOJPageHeader($title) ?>
<h1 class="h2"><?= $title ?></h1>
<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
