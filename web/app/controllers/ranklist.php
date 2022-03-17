<?php
	if (!Auth::check()) {
		becomeMsgPage(UOJLocale::get('need login'));
	}

	become404Page();
?>
<?php echoUOJPageHeader('比赛排行榜') ?>
<?php echoRanklist($config) ?>
<?php echoUOJPageFooter() ?>
