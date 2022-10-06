<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	requirePHPLib('form');

	$assignment_id = $_GET['id'];
	$assignment = queryAssignment($assignment_id);

	if (!$assignment) {
		become404Page();
	}

	$group = queryGroup($assignment['group_id']);
	$list = queryProblemList($assignment['list_id']);
	?>

<?php echoUOJPageHeader(UOJLocale::get('assignments')) ?>

<h2 style="margin-top: 24px">作业详细信息</h2>

<div class="row">
	<div class="col-sm-12 mt-4">
		<h5>作业信息</h5>
		<ul>
			<li><b>小组</b>：<a href="/group/<?= $group['id'] ?>"><?= $group['title'] ?></a></li>
			<li><b>题单</b>：<a href="/problem_list/<?= $list['id'] ?>"><?= $list['title'] ?></a></li>
			<li><b>创建时间</b>：<?= $assignment['create_time'] ?></li>
			<li><b>截止时间</b>：<?= $assignment['deadline'] ?></li>
		</ul>
	</div>
</div>

<div class="row">
	<div class="col-sm-12 mt-4">
		<h5>完成状况</h5>

<?php
		$query = DB::query("select problem_id from lists_problems where list_id = {$list['id']}");
	$problem_ids = [];
	while ($row = DB::fetch($query)) {
		$problem_ids[] = $row['problem_id'];
	}

	$query = DB::query("select a.username as username, c.problem_id as problem_id from user_info a inner join groups_users b on a.username = b.username and b.group_id = {$group['id']} inner join best_ac_submissions c on a.username = c.submitter inner join lists_problems d on c.problem_id = d.problem_id and d.list_id = {$list['id']}");
	$finished = [];
	while ($row = DB::fetch($query)) {
		$username = $row['username'];
		$problem_id = $row['problem_id'];

		if (!isset($finished[$username])) {
			$finished[$username] = [];
		}
		$finished[$username][$problem_id] = 1;
	}

	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 14em;">'.UOJLocale::get('username').'</th>';
	foreach ($problem_ids as $problem_id) {
		$header_row .= '<th style="width: 2em;">' . "<a href=\"/problem/{$problem_id}\">#{$problem_id}</a>" . '</th>';
	}
	$header_row .= '</tr>';

	$print_row = function($row) use ($problem_ids, $finished) {
		$username = $row['username'];

		echo '<tr>';
		echo '<td>' . getUserLink($username) . '</td>';
		foreach ($problem_ids as $problem_id) {
			if (!isset($finished[$username]) || !isset($finished[$username][$problem_id])) {
				echo '<td class="failed"><span class="glyphicon glyphicon-remove"></span></td>';
			} else {
				echo '<td class="success"><span class="glyphicon glyphicon-ok"></span></td>';
			}
		}
		echo '</tr>';
	};

	$from = "user_info a inner join groups_users b on (b.group_id = {$group['id']} and a.username = b.username)";
	$col_names = array('a.username as username');
	$cond = "1";
	$tail = "order by a.username asc";
	$config = array('page_len' => 100);

	echoLongTable($col_names, $from, $cond, $tail, $header_row, $print_row, $config);
	?>

	</div>
</div>

<?php echoUOJPageFooter() ?>
