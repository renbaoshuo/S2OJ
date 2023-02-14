<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::userCanCreateContest(Auth::user()) || UOJResponse::page403();

$time_form = new UOJForm('time');
$time_form->addInput('name', [
	'label' => UOJLocale::get('contests::contest name'),
	'default_value' => 'New Contest',
	'validator_php' => function ($name, &$vdata) {
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
]);
$time_form->addInput('start_time', [
	'div_class' => 'mt-2',
	'label' => UOJLocale::get('contests::start time'),
	'default_value' => UOJTime::$time_now_str,
	'validator_php' => function ($str, &$vdata) {
		try {
			$vdata['start_time'] = new DateTime($str);
		} catch (Exception $e) {
			return '无效时间格式';
		}

		return '';
	},
]);
$time_form->addInput('last_min', [
	'div_class' => 'mt-2',
	'label' => UOJLocale::get('contests::duration'),
	'default_value' => '180',
	'help' => '单位为分钟。',
	'validator_php' => function ($str, &$vdata) {
		if (!validateUInt($str)) {
			return '必须为一个整数';
		}

		$vdata['last_min'] = $str;

		return '';
	},
]);
$time_form->handle = function (&$vdata) {
	$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');

	DB::insert([
		"insert into contests",
		DB::bracketed_fields(["name", "start_time", "last_min", "status", "extra_config"]),
		"values",
		DB::tuple([$vdata['name'], $start_time_str, $vdata['last_min'], 'unfinished', "{}"])
	]);
};
$time_form->succ_href = "/contests";
$time_form->runAtServer();
?>

<?php echoUOJPageHeader(UOJLocale::get('contests::add new contest')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<div class="card card-default mb-2">
			<div class="card-body">
				<h1 class="card-title">
					<?= UOJLocale::get('contests::add new contest') ?>
				</h1>

				<div class="w-full" style="max-width: 400px">
					<?php $time_form->printHTML() ?>
				</div>
			</div>
		</div>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
