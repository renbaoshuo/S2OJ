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

UOJList::init(UOJRequest::get('list_id')) || UOJResponse::page404();

$assignment = queryAssignmentByGroupListID($group_id, UOJList::info('id'));

if (!$assignment) {
	become404Page();
}

$group = queryGroup($assignment['group_id']);
$list = UOJList::info();

if (($group['is_hidden'] || $list['is_hidden']) && !isSuperUser($myUser)) {
	become403Page();
}
?>

<?php echoUOJPageHeader(UOJLocale::get('assignments')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<small class="fs-4">作业：</small><?= UOJList::info('title') ?>
		</h1>
		<ul class="mt-3">
			<li>对应题单：<a class="text-decoration-none" href="<?= HTML::url('/list/' . UOJList::info('id')) ?>">#<?= UOJList::info('id') ?></a></li>
			<li>所属小组：<a class="text-decoration-none" href="<?= HTML::url('/group/' . $group['id']) ?>"><?= $group['title'] ?></a></li>
			<li>结束时间：<?= $assignment['end_time'] ?></li>
		</ul>

		<?php
		$problems = UOJList::cur()->getProblemIDs();
		$users = queryGroupUsers($group['id']);
		$usernames = [];
		$n_users = count($users);
		$submission_end_time = min(new DateTime(), DateTime::createFromFormat('Y-m-d H:i:s', $assignment['end_time']));

		foreach ($users as $user) {
			$usernames[] = $user['username'];
		}

		// standings: rank => [total_score, user => [username, realname], scores[]]
		$standings = [];

		foreach ($usernames as $username) {
			$user = UOJUser::query($username);
			$row = ['total_score' => 0];
			$scores = [];

			$row['user'] = [
				'username' => $user['username'],
				'realname' => $user['realname'],
			];

			$cond = "submitter = '{$user['username']}' AND unix_timestamp(submit_time) <= " . $submission_end_time->getTimestamp();

			foreach ($problems as $problem_id) {
				$submission = DB::selectFirst("SELECT id, score FROM submissions WHERE problem_id = $problem_id AND $cond ORDER BY score DESC, id DESC");

				if ($submission) {
					$row['scores'][] = [
						'submission_id' => (int)$submission['id'],
						'score' => (int)$submission['score'],
					];
					$row['total_score'] += $submission['score'];
				} else {
					$row['scores'][] = null;
				}
			}

			$standings[] = $row;
		}

		usort($standings, function ($lhs, $rhs) {
			if ($lhs['total_score'] != $rhs['total_score']) {
				return $rhs['total_score'] - $lhs['total_score'];
			}

			return strcmp($lhs['user']['username'], $rhs['user']['username']);
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

					if (row['total_score'] == problems.length * 100) {
						col_tr += '<tr class="table-success">';
					} else {
						col_tr += '<tr>';
					}

					col_tr += '<td>' + getUserLink(row['user']['username'], row['user']['realname']) + '</td>';
					col_tr += '<td>' +
						'<span class="uoj-score" data-max="' + (problems.length * 100) + '" style="color:' + getColOfScore(row['total_score'] / problems.length) + '">' + row['total_score'] + '</span>' +
						'</td>';
					for (var i = 0; i < row['scores'].length; i++) {
						var col = row['scores'][i];

						if (col) {
							if (col['score'] == 100) {
								col_tr += '<td class="table-success">';
							} else {
								col_tr += '<td>';
							}
							col_tr += '<a class="text-decoration-none uoj-score" href="/submission/' + col['submission_id'] + '" style="color:' + getColOfScore(col['score']) + '">' + col['score'] + '</a>';
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
