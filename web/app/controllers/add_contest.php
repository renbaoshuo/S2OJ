<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	requirePHPLib('form');
	
	if (!isSuperUser($myUser)) {
		become403Page();
	}
	$time_form = new UOJForm('time');
	$time_form->addVInput(
		'name', 'text', '比赛标题', 'New Contest',
		function($str) {
			return '';
		},
		null
	);
	$time_form->addVInput(
		'start_time', 'text', '开始时间', date("Y-m-d H:i:s"),
		function($str, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($str);
			} catch (Exception $e) {
				return '无效时间格式';
			}
			return '';
		},
		null
	);
	$time_form->addVInput(
		'last_min', 'text', '时长（单位：分钟）', 180,
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
				
		$purifier = HTML::purifier_inline();
		
		$esc_name = $_POST['name'];
		$esc_name = $purifier->purify($esc_name);
		$esc_name = DB::escape($esc_name);
		
		DB::query("insert into contests (name, start_time, last_min, status) values ('$esc_name', '$start_time_str', ${_POST['last_min']}, 'unfinished')");
	};
	$time_form->succ_href="/contests";
	$time_form->runAtServer();
	?>
<?php echoUOJPageHeader('添加比赛') ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-2">
<div class="card-body">
<?php endif ?>

<h1 class="page-header
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
h2 card-title
<?php endif ?>
">添加比赛</h1>

<div class="w-full" style="max-width: 400px">
<?php $time_form->printHTML(); ?>
</div>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">
<?php uojIncludeView('sidebar', array()) ?>
</aside>

</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
