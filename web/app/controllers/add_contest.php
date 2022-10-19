<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	requireLib('bootstrap5');
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

<div class="row">
<div class="col-lg-9">

<div class="card card-default mb-2">
<div class="card-body">

<h1 class="h2 card-title">添加比赛</h1>

<div class="w-full" style="max-width: 400px">
<?php $time_form->printHTML(); ?>
</div>

</div>
</div>

</div>

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar', array()) ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
