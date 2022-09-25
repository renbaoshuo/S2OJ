<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	$list_id = $_GET['id'];

	if (!validateUInt($list_id) || !($list = queryProblemList($list_id))) {
		become404Page();
	}

	function echoProblem($problem) {
		global $myUser, $removeProblemForms;

		if (isProblemVisibleToUser($problem, $myUser)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo '#', $problem['id'], '</td>';
			echo '<td class="text-left">';
			if ($problem['is_hidden']) {
				echo ' <span class="text-danger">[隐藏]</span> ';
			}
			if ($problem['uploader'] == $myUser['username']) {
				echo ' <span class="text-info">[我的题目]</span> ';
			}
			echo '<a href="/problem/', $problem['id'], '">', $problem['title'], '</a>';
			
			if (isset($_COOKIE['show_tags_mode'])) {
				echo ' <span class="text-info" style="font-size: 10px">' . $problem["uploader"] . '</span> ';

				foreach (queryProblemTags($problem['id']) as $tag) {
					echo '<a class="uoj-problem-tag">', '<span class="badge badge-pill badge-secondary">', HTML::escape($tag), '</span>', '</a>';
				}
			}
			echo '</td>';
			if (isset($_COOKIE['show_submit_mode'])) {
				$perc = $problem['submit_num'] > 0 ? round(100 * $problem['ac_num'] / $problem['submit_num']) : 0;
				echo <<<EOD
				<td><a href="/submissions?problem_id={$problem['id']}&min_score=100&max_score=100">&times;{$problem['ac_num']}</a></td>
				<td><a href="/submissions?problem_id={$problem['id']}">&times;{$problem['submit_num']}</a></td>
				<td>
					<div class="progress bot-buffer-no">
						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$perc" aria-valuemin="0" aria-valuemax="100" style="width: $perc%; min-width: 20px;">{$perc}%</div>
					</div>
				</td>
EOD;
			}
			echo '<td class="text-left">', getClickZanBlock('P', $problem['id'], $problem['zan']), '</td>';
			echo '</tr>';
		}
	}
	
	$header = '<tr>';
	$header .= '<th class="text-center" style="width:5em;">ID</th>';
	$header .= '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:150px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	$header .= '<th class="text-center" style="width:180px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';

	$pag_config = array('page_len' => 40);
	$pag_config['col_names'] = array('best_ac_submissions.submission_id as submission_id', 'problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.submit_num as submit_num', 'problems.ac_num as ac_num', 'problems.zan as zan', 'problems.extra_config as extra_config', 'problems.uploader as uploader');

	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id inner join lists_problems lp on lp.list_id = {$list_id} and lp.problem_id = problems.id";

	$pag_config['cond'] = '1';
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);

	$div_classes = array('table-responsive');
	$table_classes = array('table', 'table-bordered', 'table-hover', 'table-striped');
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems lists')); ?>

<h1 class="h2">
	<?= $list['title'] ?>
</h1>

<?php if (isSuperUser($myUser)): ?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/problem_list/<?= $list['id'] ?>/manage" role="tab">管理</a></li>
</ul>
<?php endif ?>

<div class="row">
	<div class="col-sm-4"></div>
	<div class="col-sm-4 order-sm-9 checkbox text-right fine-tune-down">
		<label class="checkbox-inline" for="input-show_tags_mode"><input type="checkbox" id="input-show_tags_mode" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show tags') ?></label>
		<label class="checkbox-inline" for="input-show_submit_mode"><input type="checkbox" id="input-show_submit_mode" <?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show statistics') ?></label>
	</div>
	<div class="col-sm-4 order-sm-5">
	<?php echo $pag->pagination(); ?>
	</div>
</div>
<div class="top-buffer-sm"></div>
<script type="text/javascript">
$('#input-show_tags_mode').click(function() {
	if (this.checked) {
		$.cookie('show_tags_mode', '', {path: '/'});
	} else {
		$.removeCookie('show_tags_mode', {path: '/'});
	}
	location.reload();
});
$('#input-show_submit_mode').click(function() {
	if (this.checked) {
		$.cookie('show_submit_mode', '', {path: '/'});
	} else {
		$.removeCookie('show_submit_mode', {path: '/'});
	}
	location.reload();
});
$('#input-show_difficulty').click(function() {
	if (this.checked) {
		$.cookie('show_difficulty', '', {path: '/'});
	} else {
		$.removeCookie('show_difficulty', {path: '/'});
	}
	location.reload();
});
</script>

<div class="<?= join($div_classes, ' ') ?>">
<table class="<?= join($table_classes, ' ') ?>">
<thead>
	<?= $header ?>
</thead>
<tbody>
<?php
	foreach ($pag->get() as $idx => $row) {
		echoProblem($row);
		echo "\n";
	}
	?>

<?php if ($pag->isEmpty()): ?>
	<tr>
		<td class="text-center" colspan="233">
			<?= UOJLocale::get('none') ?>
		</td>
	</tr>
<?php endif ?>
	
</tbody>
</table>
</div>

<?= $pag->pagination();	?>

<?php echoUOJPageFooter() ?>
