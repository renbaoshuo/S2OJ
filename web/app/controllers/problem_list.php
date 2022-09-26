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

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	function echoProblem($problem) {
		global $myUser, $removeProblemForms, $REQUIRE_LIB;

		if (isProblemVisibleToUser($problem, $myUser)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo '#', $problem['id'], '</td>';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<td class="text-start">';
			} else {
				echo '<td class="text-left">';
			}
			if ($problem['is_hidden']) {
				echo ' <span class="text-danger">[隐藏]</span> ';
			}
			if ($problem['uploader'] == $myUser['username']) {
				echo ' <span class="text-info">[我的题目]</span> ';
			}
			echo '<a ';
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo ' class="text-decoration-none" ';
			}
			echo ' href="/problem/', $problem['id'], '">', $problem['title'], '</a>';
			
			if (isset($_COOKIE['show_tags_mode'])) {
				echo ' <span class="text-info" style="font-size: 10px">' . $problem["uploader"] . '</span> ';

				foreach (queryProblemTags($problem['id']) as $tag) {
					
					if (isset($REQUIRE_LIB['bootstrap5'])) {
						echo '<a class="uoj-problem-tag my-1">';
						echo '<span class="badge bg-secondary">';
					} else {
						echo '<a class="uoj-problem-tag">';
						echo '<span class="badge badge-pill badge-secondary">';
					}
					echo HTML::escape($tag), '</span>';
					echo '</a> ';
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
		$header .= '<th class="text-center" style="width:' . (isset($REQUIRE_LIB['bootstrap5']) ? '4' : '5') . 'em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:' . (isset($REQUIRE_LIB['bootstrap5']) ? '4' : '5') . 'em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:' . (isset($REQUIRE_LIB['bootstrap5']) ? '125' : '150') . 'px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	$header .= '<th class="text-center" style="width:190px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';

	$pag_config = array('page_len' => 40);
	$pag_config['col_names'] = array('best_ac_submissions.submission_id as submission_id', 'problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.submit_num as submit_num', 'problems.ac_num as ac_num', 'problems.zan as zan', 'problems.extra_config as extra_config', 'problems.uploader as uploader');

	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id inner join lists_problems lp on lp.list_id = {$list_id} and lp.problem_id = problems.id";

	$pag_config['cond'] = '1';
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);

	$div_classes = isset($REQUIRE_LIB['bootstrap5'])
		? array('card', 'my-3', 'overflow-auto')
		: array('table-responsive');
	$table_classes = isset($REQUIRE_LIB['bootstrap5'])
		? array('table', 'uoj-table', 'mb-0')
		: array('table', 'table-bordered', 'table-hover', 'table-striped');
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems lists')); ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-sm-12 col-md-9">
<div class="d-flex justify-content-between">
<?php endif ?>
<h1 class="h2">
	<?php if ($list['is_hidden']): ?>
	<span class="fs-5 text-danger">[隐藏]</span>
	<?php endif ?>
	<?= $list['title'] ?>
	<span class="fs-5">(ID: #<?= $list['id'] ?>)</span>
</h1>

<?php if (isSuperUser($myUser)): ?>
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="text-end">
		<a class="btn btn-primary" href="/problem_list/<?= $list['id'] ?>/manage" role="button">
			<?= UOJLocale::get('problems::manage') ?>
		</a>
	</div>
<?php else: ?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link" href="/problem_list/<?= $list['id'] ?>/manage" role="tab">
			<?= UOJLocale::get('problems::manage') ?>
		</a>
	</li>
</ul>
<?php endif ?>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
<?php endif ?>

<div class="row">
	<div class="col-sm-4"></div>
	<div class="col-sm-4 order-sm-5
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	text-end p-2
	<?php else: ?>
	text-right checkbox
	<?php endif ?>
	">
		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
		<div class="form-check d-inline-block me-2">
		<?php else: ?>
		<label class="checkbox-inline" for="input-show_tags_mode">
			<?php endif ?>
		<input type="checkbox" id="input-show_tags_mode"
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			class="form-check-input"
			<?php endif ?>
			<?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>
		/>
		
		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			<label class="form-check-label" for="input-show_tags_mode">
		<?php endif ?>
			<?= UOJLocale::get('problems::show tags') ?>

		</label>
		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
		</div>
		<?php endif ?>

		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
		<div class="form-check d-inline-block">
		<?php else: ?>
		<label class="checkbox-inline" for="input-show_submit_mode">
		<?php endif ?>
			<input type="checkbox" id="input-show_submit_mode" 
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				class="form-check-input"
				<?php endif ?>
				<?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>
			/>
		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			<label class="form-check-label" for="input-show_submit_mode">
		<?php endif ?>
			<?= UOJLocale::get('problems::show statistics') ?>
		</label>
		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
		</div>
		<?php endif ?>
	</div>
	<div class="col-sm-4 order-sm-3">
	<?php echo $pag->pagination(); ?>
	</div>
</div>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="top-buffer-sm"></div>
<?php endif ?>

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
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-md-0">

<?php uojIncludeView('sidebar', array()); ?>
</aside>

</div>
<?php endif ?>
<?php echoUOJPageFooter() ?>
