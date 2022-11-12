<?php
requireLib('bootstrap5');
requireLib('morris');

Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
if (UOJRequest::get('contest_id')) {
	UOJContest::init(UOJRequest::get('contest_id')) || UOJResponse::page404();
	UOJProblem::upgradeToContestProblem() || UOJResponse::page404();
	UOJContest::cur()->userCanSeeProblemStatistics(Auth::user()) || UOJResponse::page403();
}
UOJProblem::cur()->userCanView(Auth::user(), ['ensure' => true]);

function scoreDistributionData() {
	$data = array();
	$res = DB::selectAll([
		"select score, count(*) from submissions",
		"where", [
			"problem_id" => UOJProblem::info('id'),
			["score", "is not", null],
			UOJSubmission::sqlForUserCanView(Auth::user(), UOJProblem::cur())
		], "group by score", "order by score"
	], DB::NUM);
	$has_score_0 = false;
	$has_score_100 = false;
	foreach ($res as $row) {
		if ($row[0] == 0) {
			$has_score_0 = true;
		} else if ($row[0] == 100) {
			$has_score_100 = true;
		}
		$score = $row[0] * 100;
		$data[] = ['score' => $score, 'count' => $row[1]];
	}
	if (!$has_score_0) {
		array_unshift($data, ['score' => 0, 'count' => 0]);
	}
	if (!$has_score_100) {
		$data[] = ['score' => 10000, 'count' => 0];
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

<?php echoUOJPageHeader(HTML::stripTags(UOJProblem::info('title')) . ' - ' . UOJLocale::get('problems::statistics')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<?php if (UOJContest::cur()) : ?>
			<!-- 比赛导航 -->
			<?php
			$tabs_info = [
				'dashboard' => [
					'name' => UOJLocale::get('contests::contest dashboard'),
					'url' => '/contest/' . UOJContest::info('id'),
				],
				'submissions' => [
					'name' => UOJLocale::get('contests::contest submissions'),
					'url' => '/contest/' . UOJContest::info('id') . '/submissions',
				],
				'standings' => [
					'name' => UOJLocale::get('contests::contest standings'),
					'url' => '/contest/' . UOJContest::info('id') . '/standings',
				],
			];

			if (UOJContest::cur()->progress() > CONTEST_TESTING) {
				$tabs_info['after_contest_standings'] = [
					'name' => UOJLocale::get('contests::after contest standings'),
					'url' => '/contest/' . UOJContest::info('id') . '/after_contest_standings',
				];
				$tabs_info['self_reviews'] = [
					'name' => UOJLocale::get('contests::contest self reviews'),
					'url' => '/contest/' . UOJContest::info('id') . '/self_reviews',
				];
			}

			if (UOJContest::cur()->userCanManage(Auth::user())) {
				$tabs_info['backstage'] = [
					'name' => UOJLocale::get('contests::contest backstage'),
					'url' => '/contest/' . UOJContest::info('id') . '/backstage',
				];
			}
			?>
			<div class="mb-2">
				<?= HTML::tablist($tabs_info, '', 'nav-pills') ?>
			</div>
		<?php endif ?>

		<div class="card card-default mb-2">
			<div class="card-body">

				<h1 class="text-center">
					<?php if (UOJContest::cur()) : ?>
						<?= UOJProblem::cur()->getTitle(['with' => 'letter']) ?>
					<?php else : ?>
						<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?>
					<?php endif ?>
				</h1>

				<hr />

				<h2 class="text-center"><?= UOJLocale::get('problems::accepted submissions') ?></h2>
				<div class="text-end mb-2">
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
				if ($submissions_sort_by_choice == 'time') {
					$submid = 'best_ac_submissions.submission_id = submissions.id';
					$orderby = 'order by best_ac_submissions.used_time, best_ac_submissions.used_memory, best_ac_submissions.tot_size, best_ac_submissions.submission_id';
				} else {
					$submid = 'best_ac_submissions.shortest_id = submissions.id';
					$orderby = 'order by best_ac_submissions.shortest_tot_size, best_ac_submissions.shortest_used_time, best_ac_submissions.shortest_used_memory, best_ac_submissions.shortest_id';
				}

				echoSubmissionsList([
					$submid,
					"best_ac_submissions.problem_id" => UOJProblem::info('id')
				], $orderby, [
					'judge_time_hidden' => '',
					'problem_hidden' => true,
					'table_name' => 'best_ac_submissions, submissions',
					'problem' => UOJProblem::cur(),
					'table_config' => [
						'div_classes' => ['table-responsive', 'mb-3'],
						'table_classes' => ['table', 'mb-0', 'text-center'],
					]
				], Auth::user());
				?>

				<h2 class="text-center mt-4">
					<?= UOJLocale::get('problems::score distribution') ?>
				</h2>
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
								'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= UOJProblem::info('id') ?> + '&amp;min_score=' + scr + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
						},
						resize: true
					});
				</script>

				<h2 class="text-center mt-4">
					<?= UOJLocale::get('problems::prefix sum of score distribution') ?>
				</h2>
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
								'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= UOJProblem::info('id') ?> + '&amp;max_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
						},
						resize: true
					});
				</script>

				<h2 class="text-center mt-4">
					<?= UOJLocale::get('problems::suffix sum of score distribution') ?>
				</h2>
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
								'<div class="morris-hover-point">' + '<a href="/submissions?problem_id=' + <?= UOJProblem::info('id') ?> + '&amp;min_score=' + scr + '">' + 'number: ' + row.count + '</a>' + '</div>';
						},
						resize: true
					});
				</script>
			</div>
		</div>

		<!-- end left col -->
	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php if (UOJContest::cur()) : ?>
			<!-- Contest card -->
			<div class="card card-default mb-2">
				<div class="card-body">
					<h3 class="h4 card-title text-center">
						<?= UOJContest::cur()->getLink(['class' => 'text-body']) ?>
					</h3>
					<div class="card-text text-center text-muted">
						<?php if (UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS) : ?>
							<span id="contest-countdown"></span>
						<?php else : ?>
							<?= UOJLocale::get('contests::contest ended') ?>
						<?php endif ?>
					</div>
				</div>
				<div class="card-footer bg-transparent">
					比赛评价：<?= UOJContest::cur()->getZanBlock() ?>
				</div>
			</div>
			<?php if (UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS) : ?>
				<script type="text/javascript">
					$('#contest-countdown').countdown(<?= UOJContest::info('end_time')->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function() {}, '1.75rem', false);
				</script>
			<?php endif ?>
		<?php endif ?>

		<div class="card card-default mb-2">
			<ul class="nav nav-pills nav-fill flex-column" role="tablist">
				<li class="nav-item text-start">
					<a class="nav-link" role="tab" <?php if (UOJContest::cur()) : ?> href="/contest/<?= UOJContest::info('id') ?>/problem/<?= UOJProblem::info('id') ?>" <?php else : ?> href="/problem/<?= UOJProblem::info('id') ?>" <?php endif ?>>
						<i class="bi bi-journal-text"></i>
						<?= UOJLocale::get('problems::statement') ?>
					</a>
				</li>
				<?php if (!UOJContest::cur() || UOJContest::cur()->progress() >= CONTEST_FINISHED) : ?>
					<li class="nav-item text-start">
						<a href="/problem/<?= UOJProblem::info('id') ?>/solutions" class="nav-link" role="tab">
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
				<?php if (UOJProblem::cur()->userCanManage(Auth::user())) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/statement" role="tab">
							<i class="bi bi-sliders"></i>
							<?= UOJLocale::get('problems::manage') ?>
						</a>
					</li>
				<?php endif ?>
			</ul>
			<div class="card-footer bg-transparent">
				评价：<?= UOJProblem::cur()->getZanBlock() ?>
			</div>
		</div>

		<?php uojIncludeView('sidebar') ?>

		<!-- End right col -->
	</aside>

</div>

<?php echoUOJPageFooter() ?>
