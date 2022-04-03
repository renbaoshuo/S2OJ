<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
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
?>
<?php echoUOJPageHeader($title) ?>
<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
