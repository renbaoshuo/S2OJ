<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}
	
	if (isSuperUser($myUser) || isProblemManager($myUser) || isProblemUploader($myUser)) {
		$new_problem_form = new UOJForm('new_problem');
		$new_problem_form->handle = function() {
			global $myUser;

			DB::query("insert into problems (title, uploader, is_hidden, submission_requirement) values ('New Problem', '{$myUser['username']}', 1, '{}')");
			$id = DB::insert_id();
			DB::query("insert into problems_contents (id, statement, statement_md) values ($id, '', '')");
			dataNewProblem($id);
		};
		$new_problem_form->submit_button_config['align'] = 'right';
		$new_problem_form->submit_button_config['class_str'] = 'btn btn-primary';
		$new_problem_form->submit_button_config['text'] = UOJLocale::get('problems::add new');
		$new_problem_form->submit_button_config['smart_confirm'] = '';
		
		$new_problem_form->runAtServer();
	}
	
	function echoProblem($problem) {
		global $myUser, $REQUIRE_LIB;

		if (isProblemVisibleToUser($problem, $myUser)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="table-success">';
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
				$a_class = '';
				if (isset($REQUIRE_LIB['bootstrap5'])) {
					$a_class .= ' text-decoration-none ';
				}
				$perc = $problem['submit_num'] > 0 ? round(100 * $problem['ac_num'] / $problem['submit_num']) : 0;
				echo <<<EOD
				<td><a class="{$a_class}" href="/submissions?problem_id={$problem['id']}&min_score=100&max_score=100">&times;{$problem['ac_num']}</a></td>
				<td><a class="{$a_class}" href="/submissions?problem_id={$problem['id']}">&times;{$problem['submit_num']}</a></td>
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
	
	$cond = array();
	
	$search_tag = null;
	
	$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
	if ($cur_tab == 'template') {
		$search_tag = "模板题";
	}
	if (isset($_GET['tag'])) {
		$search_tag = $_GET['tag'];
	}
	if ($search_tag) {
		$cond[] = "'".DB::escape($search_tag)."' in (select tag from problems_tags where problems_tags.problem_id = problems.id)";
	}
	if (isset($_GET["search"])) {
		$cond[]="title like '%".DB::escape($_GET["search"])."%' or id like '%".DB::escape($_GET["search"])."%'";
	}
	
	if ($cond) {
		$cond = join($cond, ' and ');
	} else {
		$cond = '1';
	}
	
	$header = '<tr>';
	$header .= '<th class="text-center" style="width:5em;">ID</th>';
	$header .= '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:150px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	$header .= '<th class="text-center" style="width:190px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';
	
	$tabs_info = array(
		'all' => array(
			'name' => UOJLocale::get('problems::all problems'),
			'url' => "/problems"
		),
		'template' => array(
			'name' => UOJLocale::get('problems::template problems'),
			'url' => "/problems/template"
		)
	);

	$pag_config = array('page_len' => 40);
	$pag_config['col_names'] = array('best_ac_submissions.submission_id as submission_id', 'problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.submit_num as submit_num', 'problems.ac_num as ac_num', 'problems.zan as zan', 'problems.extra_config as extra_config', 'problems.uploader as uploader');
	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id";
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);

	$div_classes = isset($REQUIRE_LIB['bootstrap5'])
		? array('card', 'mb-3')
		: array('table-responsive');
	$table_classes = isset($REQUIRE_LIB['bootstrap5'])
		? array('table', 'uoj-table', 'mb-0')
		: array('table', 'table-bordered', 'table-hover', 'table-striped');
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems')) ?>
<?php if (isSuperUser($myUser) || isProblemManager($myUser) || isProblemUploader($myUser)): ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="text-end">
	<?php endif ?>
		<?php $new_problem_form->printHTML(); ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	</div>
	<?php endif ?>
<?php endif ?>
<div class="row">
	<div class="col-sm-4">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
	</div>
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
<div class="top-buffer-sm"></div>
<script type="text/javascript">
$('#input-show_tags_mode').click(function() {
	if (this.checked) {
		$.cookie('show_tags_mode', '', {path: '/problems'});
	} else {
		$.removeCookie('show_tags_mode', {path: '/problems'});
	}
	location.reload();
});
$('#input-show_submit_mode').click(function() {
	if (this.checked) {
		$.cookie('show_submit_mode', '', {path: '/problems'});
	} else {
		$.removeCookie('show_submit_mode', {path: '/problems'});
	}
	location.reload();
});
</script>
<div class="<?= join($div_classes, ' ') ?>">
	<table class="<?= join($table_classes, ' ') ?>">
		<thead><?= $header ?></thead>
		<tbody>
<?php

		foreach ($pag->get() as $idx => $row) {
			echoProblem($row);
			echo "\n";
		}
		if ($pag->isEmpty()) {
			echo '<tr><td class="text-center" colspan="233">'.UOJLocale::get('none').'</td></tr>';
		}
	?>
		</tbody>
	</table>
</div>
<?= $pag->pagination() ?>
<?php echoUOJPageFooter() ?>
