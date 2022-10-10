<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$REQUIRE_LIB['bootstrap5'] = '';
	
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
					echo '<span class="badge bg-secondary">';
					echo HTML::escape($tag), '</span>';
					echo '</a> ';
				}
			}
			echo '</td>';
			if (isset($_COOKIE['show_submit_mode'])) {
				$perc = $problem['submit_num'] > 0 ? round(100 * $problem['ac_num'] / $problem['submit_num']) : 0;
				echo <<<EOD
				<td><a class="text-decoration-none" href="/submissions?problem_id={$problem['id']}&min_score=100&max_score=100">&times;{$problem['ac_num']}</a></td>
				<td><a class="text-decoration-none" href="/submissions?problem_id={$problem['id']}">&times;{$problem['submit_num']}</a></td>
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
			echo '<td class="text-center">', getClickZanBlock('P', $problem['id'], $problem['zan'], null, false), '</td>';
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
		$header .= '<th class="text-center" style="width:4em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:4em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:125px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	if (isset($_COOKIE['show_difficulty'])) {
		$header .= '<th class="text-center" style="width:3em;">'.UOJLocale::get('problems::difficulty').'</th>';
	}
	$header .= '<th class="text-center" style="width:100px;">'.UOJLocale::get('appraisal').'</th>';
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
	$pag_config['col_names'] = array('best_ac_submissions.submission_id as submission_id', 'problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.submit_num as submit_num', 'problems.ac_num as ac_num', 'problems.zan as zan', 'problems.extra_config as extra_config', 'problems.uploader as uploader', 'problems.extra_config as extra_config');
	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id";
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);

	$div_classes = ['card', 'my-3', 'table-responsive'];
	$table_classes = ['table', 'uoj-table', 'mb-0'];
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems')) ?>

<div class="row">

<!-- left col -->
<div class="col-lg-9">

<!-- title -->
<div class="d-flex justify-content-between">

<h1 class="h2">
	<?= UOJLocale::get('problems') ?>
</h1>

<?php if (isSuperUser($myUser) || isProblemManager($myUser) || isProblemUploader($myUser)): ?>
	<div class="text-end">
		<?php $new_problem_form->printHTML(); ?>
	</div>
<?php endif ?>

</div>
<!-- end title -->

<div class="row">
	<div class="col-sm-4 col-12">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
	</div>
	<div class="text-end p-2 col-12 col-sm-8">
		<div class="form-check d-inline-block me-2">
			<input
				type="checkbox" id="input-show_tags_mode"
				class="form-check-input"
				<?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_tags_mode">
				<?= UOJLocale::get('problems::show tags') ?>
			</label>
		</div>

		<div class="form-check d-inline-block">
			<input
				type="checkbox" id="input-show_submit_mode" 
				class="form-check-input"
				<?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_submit_mode">
				<?= UOJLocale::get('problems::show statistics') ?>
			</label>
		</div>

		<div class="form-check d-inline-block">
			<input
				type="checkbox" id="input-show_difficulty"
				class="form-check-input"
				<?= isset($_COOKIE['show_difficulty']) ? 'checked="checked" ': ''?>
			/>
			<label class="form-check-label" for="input-show_difficulty">
					<?= UOJLocale::get('problems::show difficulty') ?>
			</label>
		</div>
	</div>
</div>

<div class="text-center">
	<?= $pag->pagination() ?>
</div>

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

<div class="text-center">
	<?= $pag->pagination() ?>
</div>

</div>
<!-- end left col -->

<!-- right col -->
<aside class="col mt-3 mt-lg-0">

<!-- search bar -->
<form method="get">
	<div class="input-group mb-3">
		<input id="search-input" name="search" type="text" class="form-control" placeholder="搜索">
		<button class="btn btn-outline-secondary" type="submit">
			<i class="bi bi-search"></i>
		</button>
	</div>
</form>
<script>$('#search-input').val(new URLSearchParams(location.search).get('search'));</script>

<!-- sidebar -->
<?php uojIncludeView('sidebar', []) ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
