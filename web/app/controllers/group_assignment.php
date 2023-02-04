<?php
if (!Auth::check()) {
	redirectToLogin();
}

requirePHPLib('form');
requireLib('bootstrap5');

$group_id = $_GET['id'];
if (!validateUInt($group_id)) {
	become404Page();
}

UOJGroup::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJGroupAssignment::init(UOJRequest::get('list_id')) || UOJResponse::page404();
UOJGroupAssignment::cur()->valid() || UOJResponse::page404();
UOJGroupAssignment::cur()->userCanView(['ensure' => true]);
?>

<?php echoUOJPageHeader(UOJLocale::get('assignments') . ' ' . UOJGroupAssignment::info('title')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<small class="fs-4">作业：</small><?= UOJList::info('title') ?>
		</h1>
		<ul class="mt-3">
			<li>对应题单：<a href="<?= HTML::url('/list/' . UOJGroupAssignment::info('id')) ?>">#<?= UOJGroupAssignment::info('id') ?></a></li>
			<li>所属小组：<?= UOJGroup::cur()->getLink() ?>
			<li>结束时间：<?= UOJGroupAssignment::info('end_time_str') ?></li>
		</ul>

		<?php
		$problems = UOJGroupAssignment::cur()->getProblemIDs();
		$usernames = UOJGroup::cur()->getUsernames();
		$n_users = count($users);
		$submission_end_time = min(new DateTime(), UOJGroupAssignment::info('end_time'));

		// standings: rank => [total_score, [username, realname], scores[]]
		$standings = [];

		foreach ($usernames as $username) {
			$user = UOJUser::query($username);
			$row = [0, [$user['username'], $user['realname']], []];

			$conds = DB::land([
				"submitter" => $user['username'],
				["unix_timestamp(submit_time)", "<=", $submission_end_time->getTimestamp()],
			]);

			foreach ($problems as $problem_id) {
				$submission = DB::selectFirst([
					"select", DB::fields(["id", "score"]),
					"from submissions",
					"where", [
						"problem_id" => $problem_id,
						$conds,
					],
					"order by score desc, id desc",
				]);

				if ($submission) {
					$row[2][] = [
						(int)$submission['id'],
						UOJSubmission::roundedScore($submission['score']),
					];
					$row[0] = UOJSubmission::roundedScore($row[0] + $submission['score']);
				} else {
					$row[2][] = null;
				}
			}

			$standings[] = $row;
		}

		usort($standings, function ($lhs, $rhs) {
			if ($lhs[0] != $rhs[0]) {
				return $rhs[0] - $lhs[0];
			}

			return strcmp($lhs[1][0], $rhs[1][0]);
		});
		?>

		<div id="standings"></div>

		<script>
			var problems = <?= json_encode($problems) ?>;
			var standings = <?= json_encode($standings) ?>;

			$('#standings').long_table(
				standings,
				1,
				'<tr>' +
				'<th style="width:10em"><?= UOJLocale::get('username') ?></th>' +
				'<th style="width:2em"><?= UOJLocale::get('contests::total score') ?></th>' +
				$.map(problems, function(problem, idx) {
					return '<th style="width:2em"><a href="/problem/' + problem + '">#' + problem + '</a></th>';
				}).join('') +
				'</tr>',
				function(row) {
					var col_tr = '';

					if (row[0] == problems.length * 100) {
						col_tr += '<tr class="table-success">';
					} else {
						col_tr += '<tr>';
					}

					col_tr += '<td>' + getUserLink(row[1][0], row[1][1]) + '</td>';
					col_tr += '<td>' +
						'<span class="uoj-score" data-max="' + (problems.length * 100) + '" style="color:' + getColOfScore(row[0] / problems.length) + '">' + row[0] + '</span>' +
						'</td>';
					for (var i = 0; i < row[2].length; i++) {
						var col = row[2][i];

						if (col) {
							if (col[1] == 100) {
								col_tr += '<td class="table-success">';
							} else {
								col_tr += '<td>';
							}
							col_tr += '<a class="text-decoration-none uoj-score" href="/submission/' + col[0] + '" style="color:' + getColOfScore(col[1]) + '">' + col[1] + '</a>';
							col_tr += '</td>';
						} else {
							col_tr += '<td></td>';
						}
					}

					col_tr += '</tr>';

					return col_tr;
				}, {
					div_classes: ['card', 'my-3', 'table-responsive', 'text-center'],
					table_classes: ['table', 'uoj-table', 'table-bordered', 'mb-0'],
					page_len: 50,
					print_before_table: function() {
						var html = '';

						html += '<div class="card-header bg-transparent text-muted text-start small">' +
							'成绩统计截止时间：<?= $submission_end_time->format('Y-m-d H:i:s') ?>' +
							'</div>';

						return html;
					}
				}
			);
		</script>

	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar', ['assignments_hidden' => true]) ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
