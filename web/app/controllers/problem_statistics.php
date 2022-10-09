<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	
	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}
	
	$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;
	if ($contest != null) {
		genMoreContestInfo($contest);
		if (!isContestProblemVisibleToUser($problem, $contest, $myUser)) {
			become404Page();
		}

		$problem_rank = queryContestProblemRank($contest, $problem);
		if ($problem_rank == null) {
			become404Page();
		} else {
			$problem_letter = chr(ord('A') + $problem_rank - 1);
		}
	} else {
		if (!isProblemVisibleToUser($problem, $myUser)) {
			become404Page();
		}

		if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
			become403Page();
		}
	}

	function scoreDistributionData() {
		$data = array();
		$result = DB::select("select score, count(*) from submissions where problem_id = {$_GET['id']} and score is not null group by score");
		$is_res_empty = true;
		$has_score_0 = false;
		$has_score_100 = false;
		while ($row = DB::fetch($result, MYSQLI_NUM)) {
			if ($row[0] == 0) {
				$has_score_0 = true;
			} elseif ($row[0] == 100) {
				$has_score_100 = true;
			}
			$score = $row[0] * 100;
			$data[] = array('score' => $score, 'count' => $row[1]);
		}
		if (!$has_score_0) {
			array_unshift($data, array('score' => 0, 'count' => 0));
		}
		if (!$has_score_100) {
			$data[] = array('score' => 10000, 'count' => 0);
		}
		return $data;
	}
	
	$data = scoreDistributionData();
	$pre_data = $data;
	$suf_data = $data;
	for ($i = 0; $i < count($data); $i++) {
		$data[$i]['score'] /= 100;
	}
	for ($i = 1; $i < count($data); $i++) {
		$pre_data[$i]['count'] += $pre_data[$i - 1]['count'];
	}
	for ($i = count($data) - 1; $i > 0; $i--) {
		$suf_data[$i - 1]['count'] += $suf_data[$i]['count'];
	}
	
	$submissions_sort_by_choice = !isset($_COOKIE['submissions-sort-by-code-length']) ? 'time' : 'tot_size';
	?>
<?php
	$REQUIRE_LIB['morris'] = "";
	?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - ' . UOJLocale::get('problems::statistics')) ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5']) && $contest): ?>
<!-- 比赛导航 -->
<?php
	$tabs_info = array(
		'dashboard' => array(
			'name' => UOJLocale::get('contests::contest dashboard'),
			'url' => "/contest/{$contest['id']}"
		),
		'submissions' => array(
			'name' => UOJLocale::get('contests::contest submissions'),
			'url' => "/contest/{$contest['id']}/submissions"
		),
		'standings' => array(
			'name' => UOJLocale::get('contests::contest standings'),
			'url' => "/contest/{$contest['id']}/standings"
		),
	);

	if ($contest['cur_progress'] > CONTEST_TESTING) {
		$tabs_info['after_contest_standings'] = array(
			'name' => UOJLocale::get('contests::after contest standings'),
			'url' => "/contest/{$contest['id']}/after_contest_standings"
		);
		$tabs_info['self_reviews'] = array(
			'name' => UOJLocale::get('contests::contest self reviews'),
			'url' => "/contest/{$contest['id']}/self_reviews"
		);
	}

	if (hasContestPermission(Auth::user(), $contest)) {
		$tabs_info['backstage'] = array(
			'name' => UOJLocale::get('contests::contest backstage'),
			'url' => "/contest/{$contest['id']}/backstage"
		);
	}
	?>
	<div class="mb-2">
		<?= HTML::tablist($tabs_info, '', 'nav-pills') ?>
	</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-2">
<div class="card-body">
<?php endif ?>

<h1 class="page-header text-center
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	h2
<?php endif ?>
">
	<?php if ($contest): ?>
		<?= $problem_letter ?>.
	<?php else: ?>
		#<?= $problem['id'] ?>.
	<?php endif ?>
	<?= $problem['title'] ?>
<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
	<?= UOJLocale::get('problems::statistics') ?>
<?php endif ?>
</h1>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<hr />
<?php endif ?>

<?php if ($contest && !hasContestPermission($myUser, $contest) && $contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<h2 class="text-center text-muted">比赛尚未结束</h2>
<?php else: ?>
<h2 class="text-center
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	h3
<?php endif ?>"><?= UOJLocale::get('problems::accepted submissions') ?></h2>
<div class="
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	text-end mb-2
<?php else: ?>
	text-right bot-buffer-sm
<?php endif ?>">
	<div class="btn-group btn-group-sm">
		<a href="<?= UOJContext::requestURI() ?>" class="btn btn-secondary btn-xs <?= $submissions_sort_by_choice == 'time' ? 'active' : '' ?>" id="submissions-sort-by-run-time">
			<?= UOJLocale::get('problems::fastest') ?>
		</a>
		<a href="<?= UOJContext::requestURI() ?>" class="btn btn-secondary btn-xs <?= $submissions_sort_by_choice == 'tot_size' ? 'active' : '' ?>" id="submissions-sort-by-code-length">
			<?= UOJLocale::get('problems::shortest') ?>
		</a>
	</div>
</div>

<script type="text/javascript">
	$('#submissions-sort-by-run-time').click(function() {
		$.cookie('submissions-sort-by-run-time', '');
		$.removeCookie('submissions-sort-by-code-length');
	});
	$('#submissions-sort-by-code-length').click(function() {
		$.cookie('submissions-sort-by-code-length', '');
		$.removeCookie('submissions-sort-by-run-time');
	});
</script>

<?php
$table_config = [];
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$table_config['div_classes'] = ['mb-3'];
		$table_config['table_classes'] = ['table', 'mb-0', 'text-center'];
	}
	?>

<?php if ($submissions_sort_by_choice == 'time'): ?>
	<?php echoSubmissionsList("best_ac_submissions.submission_id = submissions.id and best_ac_submissions.problem_id = {$problem['id']}", 'order by best_ac_submissions.used_time, best_ac_submissions.used_memory, best_ac_submissions.tot_size', array('problem_hidden' => '', 'judge_time_hidden' => '', 'table_name' => 'best_ac_submissions, submissions', 'table_config' => $table_config), $myUser); ?>
<?php else: ?>
	<?php echoSubmissionsList("best_ac_submissions.shortest_id = submissions.id and best_ac_submissions.problem_id = {$problem['id']}", 'order by best_ac_submissions.shortest_tot_size, best_ac_submissions.shortest_used_time, best_ac_submissions.shortest_used_memory', array('problem_hidden' => '', 'judge_time_hidden' => '', 'table_name' => 'best_ac_submissions, submissions', 'table_config' => $table_config), $myUser); ?>
<?php endif ?>

<h2 class="text-center
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	h3 mt-4
<?php endif ?>"><?= UOJLocale::get('problems::score distribution') ?></h2>
<div id="score-distribution-chart" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Bar({
	element: 'score-distribution-chart',
	data: <?= json_encode($data) ?>,
	barColors: function(r, s, type) {
		return getColOfScore(r.label);
	},
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	hoverCallback: function(index, options, content, row) {
		var scr = row.score;
		return '<div class="morris-hover-row-label">' + 'score: ' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;min_score=' + scr + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<h2 class="text-center
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	h3 mt-4
<?php endif ?>"><?= UOJLocale::get('problems::prefix sum of score distribution') ?></h2>
<div id="score-distribution-chart-pre" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Line({
	element: 'score-distribution-chart-pre',
	data: <?= json_encode($pre_data) ?>,
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	lineColors: function(row, sidx, type) {
		if (type == 'line') {
			return '#0b62a4';
		}
		return getColOfScore(row.src.score / 100);
	},
	xLabelFormat: function(x) {
		return (x.getTime() / 100).toString();
	},
	hoverCallback: function(index, options, content, row) {
		var scr = row.score / 100;
		return '<div class="morris-hover-row-label">' + 'score: &le;' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<h2 class="text-center
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	h3 mt-4
<?php endif ?>"><?= UOJLocale::get('problems::suffix sum of score distribution') ?></h2>
<div id="score-distribution-chart-suf" style="height: 250px;"></div>
<script type="text/javascript">
new Morris.Line({
	element: 'score-distribution-chart-suf',
	data: <?= json_encode($suf_data) ?>,
	xkey: 'score',
	ykeys: ['count'],
	labels: ['number'],
	lineColors: function(row, sidx, type) {
		if (type == 'line') {
			return '#0b62a4';
		}
		return getColOfScore(row.src.score / 100);
	},
	xLabelFormat: function(x) {
		return (x.getTime() / 100).toString();
	},
	hoverCallback: function(index, options, content, row) {
		var scr = row.score / 100;
		return '<div class="morris-hover-row-label">' + 'score: &ge;' + scr + '</div>' +
			'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= $problem['id'] ?> + '&amp;min_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
	},
	resize: true
});
</script>

<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<!-- Right col -->
<aside class="col mt-3 mt-lg-0">

<?php if ($contest): ?>
<!-- Contest card -->
<div class="card card-default mb-2">
	<div class="card-body">
		<h3 class="h5 card-title text-center">
			<a class="text-decoration-none text-body" href="/contest/<?= $contest['id'] ?>">
				<?= $contest['name'] ?>
			</a>
		</h3>
		<div class="card-text text-center text-muted">
			<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
				<span id="contest-countdown"></span>
			<?php else: ?>
				<?= UOJLocale::get('contests::contest ended') ?>
			<?php endif ?>
		</div>
	</div>
	<div class="card-footer bg-transparent">
		比赛评价：<?= getClickZanBlock('C', $contest['id'], $contest['zan']) ?>
	</div>
</div>
<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
$('#contest-countdown').countdown(<?= $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function(){}, '1.75rem', false);
</script>
<?php endif ?>
<?php endif ?>

<div class="card card-default mb-2">
	<ul class="nav nav-pills nav-fill flex-column" role="tablist">
		<li class="nav-item text-start">
			<a class="nav-link" role="tab"
			<?php if ($contest): ?>
				href="/contest/<?= $contest['id'] ?>/problem/<?= $problem['id'] ?>"
			<?php else: ?>
				href="/problem/<?= $problem['id'] ?>"
			<?php endif ?>>
				<i class="bi bi-journal-text"></i>
				<?= UOJLocale::get('problems::statement') ?>
			</a>
		</li>
		<?php if (!$contest || $contest['cur_progress'] >= CONTEST_FINISHED): ?>
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
				<i class="bi bi-journal-bookmark"></i>
				<?= UOJLocale::get('problems::solutions') ?>
			</a>
		</li>
		<?php endif ?>
		<li class="nav-item text-start">
			<a class="nav-link active" href="#">
				<i class="bi bi-graph-up"></i>
				<?= UOJLocale::get('problems::statistics') ?>
			</a>
		</li>
		<?php if (hasProblemPermission($myUser, $problem)): ?>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
				<i class="bi bi-sliders"></i>
				<?= UOJLocale::get('problems::manage') ?>
			</a>
		</li>
		<?php endif ?>
	</ul>
	<div class="card-footer bg-transparent">
		评价：<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
	</div>
</div>

<?php uojIncludeView('sidebar', array()); ?>

<!-- End right col -->
</aside>

</div>
<?php endif ?>

<?php if ($contest && $contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
checkContestNotice(<?= $contest['id'] ?>, '<?= UOJTime::$time_now_str ?>');
</script>
<?php endif ?>

<?php echoUOJPageFooter() ?>
