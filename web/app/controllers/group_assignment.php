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
	?>

<?php echoUOJPageHeader(UOJLocale::get('assignments')) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<h1 class="h2">
	<small class="fs-4">作业：</small><?= $list['title'] ?>
</h1>
<ul class="mt-3">
	<li>所属小组：<a class="text-decoration-none" href="<?= HTML::url('/group/'.$group['id']) ?>"><?= $group['title'] ?></a></li>
	<li>开始时间：<?= $assignment['create_time'] ?></li>
	<li>结束时间：<?= $assignment['deadline'] ?></li>
</ul>

<?php
		$query = DB::query("select problem_id from lists_problems where list_id = {$list['id']}");
	$problem_ids = [];
	while ($row = DB::fetch($query)) {
		$problem_ids[] = $row['problem_id'];
	}

	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 10em;">'.UOJLocale::get('username').'</th>';
	$header_row .= '<th style="width: 2em;">'.UOJLocale::get('contests::total score').'</th>';
	foreach ($problem_ids as $problem_id) {
		$header_row .= '<th style="width: 2em;">' . '<a class="text-decoration-none" href="'.HTML::url('/problem/'.$problem_id).'">#'.$problem_id.'</a>' . '</th>';
	}
	$header_row .= '</tr>';

	$print_row = function($row) use ($problem_ids) {
		$username = $row['username'];

		$scores = [];
		$sum = 0;
		$total_score = count($problem_ids) * 100;
		$query = DB::query("SELECT MAX(id), problem_id, MAX(score) FROM submissions WHERE (problem_id, score) IN (SELECT problem_id, MAX(score) FROM submissions WHERE submitter = '{$username}' AND problem_id IN (".implode(',', $problem_ids).") GROUP BY problem_id) AND submitter = '{$username}' GROUP BY problem_id");

		while ($row = DB::fetch($query)) {
			$scores[$row['problem_id']] = [
				'submission_id' => $row['MAX(id)'],
				'score' => $row['MAX(score)'],
			];

			$sum += $row['MAX(score)'];
		}

		if ($sum == $total_score) {
			echo '<tr class="table-success">';
		} else {
			echo '<tr>';
		}
		echo '<td>' . getUserLink($username) . '</td>';
		echo '<td>';
		echo '<span class="uoj-score" data-max="', $total_score, '">', $sum, '</span>';
		echo '</td>';

		foreach ($problem_ids as $problem_id) {
			if (!isset($scores[$problem_id])) {
				echo '<td>';
			} else {
				if ($scores[$problem_id]['score'] == 100) {
					echo '<td class="table-success">';
				} else {
					echo '<td>';
				}
				echo '<a class="text-decoration-none uoj-score" href="'.HTML::url('/submission/'.$scores[$problem_id]['submission_id']).'">'.$scores[$problem_id]['score'].'</a>';
			}
			echo '</td>';
		}
		echo '</tr>';
	};

	$from = "user_info a inner join groups_users b on (b.group_id = {$group['id']} and a.username = b.username)";
	$col_names = array('a.username as username');
	$cond = "1";
	$tail = "order by a.username asc";
	$config = [
		'page_len' => 50,
		'div_classes' => ['card', 'my-3', 'table-responsive', 'text-center'],
		'table_classes' => ['table', 'uoj-table', 'mb-0'],
	];

	echoLongTable($col_names, $from, $cond, $tail, $header_row, $print_row, $config);
	?>

<!-- end left col -->
</div>

<aside class="col mt-3 mt-lg-0">
<!-- right col -->

<?php uojIncludeView('sidebar', ['assignments_hidden' => true]); ?>

</aside>

</div>

<?php echoUOJPageFooter() ?>
