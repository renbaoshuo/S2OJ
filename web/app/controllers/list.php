<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requireLib('bootstrap5');
	requireLib('mathjax');
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	$list_id = $_GET['id'];

	if (!validateUInt($list_id) || !($list = queryProblemList($list_id))) {
		become404Page();
	}

	if ($list['is_hidden'] && !isSuperUser($myUser)) {
		become403Page();
	}

	function echoProblem($problem) {
		global $myUser;

		if (isProblemVisibleToUser($problem, $myUser)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="table-success">';
			} else {
				echo '<td>';
			}
			echo '#', $problem['id'], '</td>';
			echo '<td class="text-start">';
			echo '<a class="text-decoration-none" href="/problem/', $problem['id'], '">', $problem['title'], '</a>';

			if ($problem['uploader'] == $myUser['username']) {
				echo ' <span class="badge text-white bg-info">', UOJLocale::get('problems::my problem') ,'</span> ';
			}
			
			if ($problem['is_hidden']) {
				echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
			}

			if (isset($_COOKIE['show_tags_mode'])) {
				foreach (queryProblemTags($problem['id']) as $tag) {
					echo ' <a class="uoj-problem-tag my-1">';
					echo '<span class="badge bg-secondary">', HTML::escape($tag), '</span>';
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
			if (isset($_COOKIE['show_difficulty'])) {
				$extra_config = getProblemExtraConfig($problem);
				if ($extra_config['difficulty'] == 0) {
					echo "<td></td>";
				} else {
					echo "<td>{$extra_config['difficulty']}</td>";
				}
			}
			echo '<td class="text-start">', ClickZans::getBlock('P', $problem['id'], $problem['zan'], null, false), '</td>';
			echo '</tr>';
		}
	}
	
	$header = '<tr>';
	$header .= '<th class="text-center" style="width:5em;">ID</th>';
	$header .= '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width:4em">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:4em">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:125px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	if (isset($_COOKIE['show_difficulty'])) {
		$header .= '<th class="text-center" style="width:3em;">'.UOJLocale::get('problems::difficulty').'</th>';
	}
	$header .= '<th class="text-center" style="width:100px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';

	$pag_config = [
		'page_len' => 40,
		'col_names' => [
			'best_ac_submissions.submission_id as submission_id',
			'problems.id as id',
			'problems.is_hidden as is_hidden',
			'problems.title as title',
			'problems.submit_num as submit_num',
			'problems.ac_num as ac_num',
			'problems.zan as zan',
			'problems.extra_config as extra_config',
			'problems.uploader as uploader',
		],
		'table_name' => "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id inner join lists_problems lp on lp.list_id = {$list_id} and lp.problem_id = problems.id",
		'cond' => '1',
		'tail' => 'ORDER BY `id` ASC',
	];
	$pag = new Paginator($pag_config);
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems lists')); ?>

<div class="row">

<!-- left col -->
<div class="col-lg-9">

<!-- title container -->
<div class="d-flex justify-content-between">

<h1>
	<?= $list['title'] ?>
	<span class="fs-5">(ID: #<?= $list['id'] ?>)</span>
	<?php if ($list['is_hidden']): ?>
	<span class="badge text-bg-danger fs-6">
		<i class="bi bi-eye-slash-fill"></i>
		<?= UOJLocale::get('hidden') ?>
	</span>
	<?php endif ?>
</h1>

<?php if (isSuperUser($myUser)): ?>
	<div class="text-end">
		<a class="btn btn-primary" href="/list/<?= $list['id'] ?>/edit" role="button">
			<?= UOJLocale::get('problems::manage') ?>
		</a>
	</div>
<?php endif ?>

</div>
<!-- end title container -->

<!-- description -->
<div class="card my-2">
	<div class="card-body">
		<h2 class="h4 mb-3">题单简介</h2>
		<?php $description = HTML::purifier()->purify(HTML::parsedown()->text($list['description'])) ?>
		<?php if ($description): ?>
			<?= $description ?>
		<?php else: ?>
			<p class="text-muted">暂无简介</p>
		<?php endif ?>
	</div>
</div>

<div class="row">
	<div class="col-sm-4 col-12"></div>
	<div class="text-end p-2 col-12 col-sm-8">
		<div class="form-check d-inline-block me-2">
			<input type="checkbox" id="input-show_tags_mode" class="form-check-input"
				<?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_tags_mode">
				<?= UOJLocale::get('problems::show tags') ?>
			</label>
		</div>

		<div class="form-check d-inline-block">
			<input type="checkbox" id="input-show_submit_mode" class="form-check-input"
				<?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_submit_mode">
				<?= UOJLocale::get('problems::show statistics') ?>
			</label>
		</div>

		<div class="form-check d-inline-block">
			<input type="checkbox" id="input-show_difficulty" class="form-check-input"
				<?= isset($_COOKIE['show_difficulty']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_difficulty">
				<?= UOJLocale::get('problems::show difficulty') ?>
			</label>
		</div>
	</div>
</div>

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

<?= $pag->pagination() ?>

<div class="card my-3 table-responsive">
<table class="table uoj-table mb-0">
<thead>
	<?= $header ?>
</thead>
<tbody>
<?php foreach ($pag->get() as $idx => $row): ?>
	<?php echoProblem($row) ?>
<?php endforeach ?>
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

<?= $pag->pagination()	?>

</div>
<!-- end left col -->

<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar'); ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
