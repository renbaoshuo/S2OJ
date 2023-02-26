<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();

$upcoming_contest_name = null;
$upcoming_contest_href = null;
$rest_second = 1000000;
function echoContest($info) {
	global $upcoming_contest_name, $upcoming_contest_href, $rest_second;

	$contest = new UOJContest($info);

	$contest_name_link = '<a class="text-decoration-none" href="/contest/' . $contest->info['id'] . '">' . $contest->info['name'] . '</a>';

	if ($contest->progress() == CONTEST_NOT_STARTED) {
		$cur_rest_second = $contest->info['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
		if ($cur_rest_second < $rest_second) {
			$upcoming_contest_name = $contest->info['name'];
			$upcoming_contest_href = '/contest/' . $contest->info['id'];
			$rest_second = $cur_rest_second;
		}
		if ($contest->userHasRegistered(Auth::user())) {
			$contest_name_link .= '<sup><a class="text-decoration-none" style="color:green">' . UOJLocale::get('contests::registered') . '</a></sup>';
		} else {
			$contest_name_link .= '<sup><a class="text-decoration-none" style="color:red" href="/contest/' . $contest->info['id'] . '/register">' . UOJLocale::get('contests::register') . '</a></sup>';
		}
	} elseif ($contest->progress() == CONTEST_IN_PROGRESS) {
		if ($contest->allowExtraRegistration() && !$contest->userHasRegistered(Auth::user())) {
			$contest_name_link .= '<sup><a class="text-decoration-none" style="color:red" href="/contest/' . $contest->info['id'] . '/register">' . UOJLocale::get('contests::register') . ' (' . UOJLocale::get('contests::in progress') . ')' . '</a></sup>';
		} else {
			$contest_name_link .= '<sup><a class="text-decoration-none" style="color:blue" href="/contest/' . $contest->info['id'] . '">' . UOJLocale::get('contests::in progress') . '</a></sup>';
		}
	} elseif ($contest->progress() == CONTEST_FINISHED) {
		$contest_name_link .= '<sup><a class="text-decoration-none" style="color:grey" href="/contest/' . $contest->info['id'] . '/standings">' . UOJLocale::get('contests::ended') . '</a></sup>';
	} else {
		if ($contest->basicRule() == 'OI') {
			if ($contest->progress() == CONTEST_PENDING_FINAL_TEST) {
				$contest_name_link .= '<sup><a style="color:blue" href="/contest/' . $contest->info['id'] . '">' . UOJLocale::get('contests::pending final test') . '</a></sup>';
			} elseif ($contest->progress() == CONTEST_TESTING) {
				$contest_name_link .= '<sup><a style="color:blue" href="/contest/' . $contest->info['id'] . '">' . UOJLocale::get('contests::final testing') . '</a></sup>';
			}
		} elseif ($contest->basicRule()  == 'ACM' || $contest->basicRule() == 'IOI') {
			$contest_name_link .= '<sup><a style="color:blue" href="/contest/' . $contest->info['id'] . '">' . UOJLocale::get('contests::official results to be announced') . '</a></sup>';
		}
	}

	$last_hour = round($contest->info['last_min'] / 60, 2);
	echo '<tr>';
	echo '<td>', $contest_name_link, '</td>';
	echo '<td>', '<a class="text-decoration-none"  href="' . HTML::timeanddate_url($contest->info['start_time'], ['duration' => $contest->info['last_min']]) . '">' . $contest->info['start_time_str'] . '</a>', '</td>';
	echo '<td>', UOJLocale::get('hours', $last_hour), '</td>';
	echo '<td>', '<a class="text-decoration-none"  href="/contest/' . $contest->info['id'] . '/registrants">', '<i class="bi bi-person-fill"></i>', ' &times;' . $contest->info['player_num'] . '</a>', '</td>';
	echo HTML::tag('td', [], $contest->getZanBlock());
	echo '</tr>';
}
?>
<?php echoUOJPageHeader(UOJLocale::get('contests')) ?>

<!-- title container -->
<div class="d-flex justify-content-between">
	<h1>
		<?= UOJLocale::get('contests') ?>
	</h1>

	<?php if (UOJContest::userCanCreateContest(Auth::user())) : ?>
		<div class="text-end">
			<a href="/contest/new" class="btn btn-primary"><?= UOJLocale::get('contests::add new contest') ?></a>
		</div>
	<?php endif ?>

</div>
<!-- end title container -->

<h2>
	<?= UOJLocale::get('contests::current or upcoming contests') ?>
</h2>
<?php
$table_header = '';
$table_header .= '<tr>';
$table_header .= '<th>' . UOJLocale::get('contests::contest name') . '</th>';
$table_header .= '<th style="width:15em;">' . UOJLocale::get('contests::start time') . '</th>';
$table_header .= '<th style="width:100px;">' . UOJLocale::get('contests::duration') . '</th>';
$table_header .= '<th style="width:100px;">' . UOJLocale::get('contests::the number of registrants') . '</th>';
$table_header .= '<th style="width:180px;">' . UOJLocale::get('appraisal') . '</th>';
$table_header .= '</tr>';

$table_config = [
	'page_len' => 50,
	'div_classes' => ['card', 'mb-3'],
	'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center'],
];

if (!UOJUser::checkPermission(Auth::user(), 'contests.view')) {
	$table_config['post_filter'] = function ($info) {
		return (new UOJContest($info))->userCanView(Auth::user());
	};
}

echoLongTable(
	['*'],
	'contests',
	[["status", "!=", 'finished']],
	'order by start_time asc, id asc',
	$table_header,
	'echoContest',
	$table_config + ['echo_full' => true],
);

if ($rest_second <= 86400) {
	$notification = json_encode($upcoming_contest_name . " 已经开始了。是否要跳转到比赛页面？");
	echo <<<EOD
<div class="text-center bot-buffer-lg">
<div class="text-secondary">$upcoming_contest_name 倒计时</div>
<div class="text-secondary" id="contest-countdown"></div>
<script>
$('#contest-countdown').countdown($rest_second, {
	font_size: '30px',
	callback: function() {
		if (confirm($notification)) {
			window.location.href = "$upcoming_contest_href";
		}
	}
});
</script>
</div>
EOD;
}
?>

<h2>
	<?= UOJLocale::get('contests::ended contests') ?>
</h2>

<?php
echoLongTable(
	['*'],
	'contests',
	['status' => 'finished'],
	'order by start_time desc, id desc',
	$table_header,
	'echoContest',
	$table_config
);
?>

<?php echoUOJPageFooter() ?>
