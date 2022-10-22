<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	requirePHPLib('form');
	requireLib('bootstrap5');

	$group_id = $_GET['id'];
	if (!validateUInt($group_id)) {
		become404Page();
	}

	$list_id = $_GET['list_id'];
	if (!validateUInt($list_id)) {
		become404Page();
	}

	$assignment = queryAssignmentByGroupListID($group_id, $list_id);

	if (!$assignment) {
		become404Page();
	}

	$group = queryGroup($assignment['group_id']);
	$list = queryProblemList($assignment['list_id']);

	if (($group['is_hidden'] || $list['is_hidden']) && !isSuperUser($myUser)) {
		become403Page();
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('assignments')) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<h1 class="h2">
	<small class="fs-4">作业：</small><?= $list['title'] ?>
</h1>
<ul class="mt-3">
	<li>对应题单：<a class="text-decoration-none" href="<?= HTML::url('/list/'.$list['id']) ?>">#<?= $list['id'] ?></a></li>
	<li>所属小组：<a class="text-decoration-none" href="<?= HTML::url('/group/'.$group['id']) ?>"><?= $group['title'] ?></a></li>
	<li>结束时间：<?= $assignment['end_time'] ?></li>
</ul>

<?php
	$problems = DB::selectAll("select problem_id from lists_problems where list_id = {$list['id']}");
	$users = queryGroupUsers($group['id']);
	$problem_ids = [];
	$usernames = [];
	$n_users = count($users);
	$n_problems = count($problems);
	$submission_end_time = min(new DateTime(), DateTime::createFromFormat('Y-m-d H:i:s', $assignment['end_time']));

	foreach ($problems as $problem) {
		$problem_ids[] = $problem['problem_id'];
	}

	sort($problem_ids);

	foreach ($users as $user) {
		$usernames[] = $user['username'];
	}
	
	// standings: rank => [total_score, user => [username, realname], scores[]]
	$standings = [];

	foreach ($usernames as $username) {
		$user = queryUser($username);
		$row = ['total_score' => 0];
		$scores = [];

		$row['user'] = [
			'username' => $user['username'],
			'realname' => $user['realname'],
		];

		$cond = "submitter = '{$user['username']}' AND unix_timestamp(submit_time) <= " . $submission_end_time->getTimestamp();

		foreach ($problem_ids as $problem_id) {
			$submission = DB::selectFirst("SELECT id, score FROM submissions WHERE problem_id = $problem_id AND $cond ORDER BY score DESC, id DESC");

			if ($submission) {
				$row['scores'][] = [
					'submission_id' => $submission['id'],
					'score' => intval($submission['score']),
				];
				$row['total_score'] += $submission['score'];
			} else {
				$row['scores'][] = null;
			}
		}

		$standings[] = $row;
	}
	
	usort($standings, function($lhs, $rhs) {
		if ($lhs['total_score'] != $rhs['total_score']) {
			return $rhs['total_score'] - $lhs['total_score'];
		}

		return strcmp($lhs['user']['username'], $rhs['user']['username']);
	});
	?>

<div id="standings"></div>

<script>
var n_problems = <?= $n_problems ?>;
var max_total_score = <?= $n_problems * 100 ?>;
var standings = <?= json_encode($standings) ?>;

$('#standings').long_table(
	standings,
	1,
	'<tr>' +
		'<th style="width:10em"><?= UOJLocale::get('username') ?></th>' +
		'<th style="width:2em"><?= UOJLocale::get('contests::total score') ?></th>' +
	<?php foreach ($problem_ids as $problem_id): ?>
		'<th style="width:2em">' +
			'<a class="text-decoration-none" href="<?= HTML::url('/problem/' . $problem_id) ?>">#<?= $problem_id ?></a>' +
		'</th>' +
	<?php endforeach ?>
	'</tr>',
	function(row) {
		var col_tr = '';

		if (row['total_score'] == max_total_score) {
			col_tr += '<tr class="table-success">';
		} else {
			col_tr += '<tr>';
		}

		col_tr += '<td>' + getUserLink(row['user']['username'], row['user']['realname']) + '</td>';
		col_tr += '<td>' +
					'<span class="uoj-score" data-max="' + max_total_score + '" style="color:' + getColOfScore(row['total_score'] / n_problems) + '">' + row['total_score'] + '</span>' +
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
	},
	{
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

<aside class="col-lg-3 mt-3 mt-lg-0">
<!-- right col -->

<?php uojIncludeView('sidebar', ['assignments_hidden' => true]); ?>

</aside>

</div>

<?php echoUOJPageFooter() ?>
