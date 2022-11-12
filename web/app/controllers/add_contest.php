<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::userCanCreateContest(Auth::user()) || UOJResponse::page403();

$time_form = new UOJBs4Form('time');
$time_form->addVInput(
	'name',
	'text',
	'比赛标题',
	'New Contest',
	function ($name, &$vdata) {
		if ($name == '') {
			return '标题不能为空';
		}

		if (strlen($name) > 100) {
			return '标题过长';
		}

		$name = HTML::escape($name);

		if ($name === '') {
			return '无效编码';
		}

		$vdata['name'] = $name;

		return '';
	},
	null
);
$time_form->addVInput(
	'start_time',
	'text',
	'开始时间',
	date("Y-m-d H:i:s"),
	function ($str, &$vdata) {
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
	'last_min',
	'text',
	'时长（单位：分钟）',
	180,
	function ($str, &$vdata) {
		if (!validateUInt($str)) {
			return '必须为一个整数';
		}

		$vdata['last_min'] = $str;

		return '';
	},
	null
);
$time_form->handle = function (&$vdata) {
	$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');

	DB::insert([
		"insert into contests",
		"(name, start_time, last_min, status)", "values",
		DB::tuple([$vdata['name'], $start_time_str, $vdata['last_min'], 'unfinished'])
	]);
};
$time_form->succ_href = "/contests";
$time_form->runAtServer();
?>
<?php echoUOJPageHeader('添加比赛') ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<div class="card card-default mb-2">
			<div class="card-body">

				<h1 class="card-title">添加比赛</h1>

				<div class="w-full" style="max-width: 400px">
					<?php $time_form->printHTML(); ?>
				</div>

			</div>
		</div>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
</div>

<?php echoUOJPageFooter() ?>
