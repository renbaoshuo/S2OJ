<?php
requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();

if (isSuperUser($myUser) || isProblemManager($myUser) || isProblemUploader($myUser)) {
	$new_problem_form = new UOJBs4Form('new_problem');
	$new_problem_form->handle = function () {
		DB::insert([
			"insert into problems",
			"(title, uploader, is_hidden, submission_requirement)",
			"values", DB::tuple(["New Problem", Auth::id(), 1, "{}"])
		]);
		$id = DB::insert_id();
		DB::insert([
			"insert into problems_contents",
			"(id, statement, statement_md)",
			"values", DB::tuple([$id, "", "## 题目描述\n\n## 输入格式\n\n## 输出格式\n\n## 输入输出样例\n\n### 输入样例 #1\n\n<!-- 请将样例用代码块包裹起来 -->\n\n### 输出样例 #1\n\n<!-- 请将样例用代码块包裹起来 -->\n\n### 样例解释 #1\n\n<!--\n后续添加样例时格式类似，如果声明大样例的话可以使用这种格式：\n\n### 样例 #2\n\n见右侧「附件下载」中的 `ex_data2.in/out`。\n\n-->\n\n## 数据范围与约定\n\n<!-- 数据范围与一些其他提示 -->\n"])
		]);
		dataNewProblem($id);
	};
	$new_problem_form->submit_button_config['align'] = 'right';
	$new_problem_form->submit_button_config['class_str'] = 'btn btn-primary';
	$new_problem_form->submit_button_config['text'] = UOJLocale::get('problems::add new');
	$new_problem_form->submit_button_config['smart_confirm'] = '';

	$new_problem_form->runAtServer();
}

function getProblemTR($info) {
	$problem = new UOJProblem($info);

	$html = '<tr class="text-center">';
	if ($info['submission_id']) {
		$html .= '<td class="table-success">';
	} else {
		$html .= '<td>';
	}
	$html .= "#{$info['id']}</td>";
	$html .= '<td class="text-start">';
	$html .= $problem->getLink(['with' => 'none']);
	if ($problem->isUserOwnProblem(Auth::user())) {
		$html .= ' <span class="badge text-white bg-info">' . UOJLocale::get('problems::my problem') . '</span> ';
	}
	if ($info['is_hidden']) {
		$html .= ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span> ';
	}
	if (isset($_COOKIE['show_tags_mode'])) {
		foreach ($problem->queryTags() as $tag) {
			$html .= ' <a class="uoj-problem-tag">' . '<span class="badge bg-secondary">' . HTML::escape($tag) . '</span>' . '</a> ';
		}
	}
	$html .= '</td>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$perc = $info['submit_num'] > 0 ? round(100 * $info['ac_num'] / $info['submit_num']) : 0;
		$html .= '<td><a href="/submissions?problem_id=' . $info['id'] . '&min_score=100&max_score=100">&times;' . $info['ac_num'] . '</a></td>';
		$html .= '<td><a href="/submissions?problem_id=' . $info['id'] . '">&times;' . $info['submit_num'] . '</a></td>';
		$html .= '<td>';
		$html .= '<div class="progress bot-buffer-no">';
		$html .= '<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="' . $perc . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $perc . '%; min-width: 20px;">';
		$html .= $perc . '%';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</td>';
	}
	$html .= '<td class="text-center">' . ClickZans::getCntBlock($problem->info['zan']) . '</td>';
	$html .= '</tr>';
	return $html;
}

$cond = [];
$search_tag = UOJRequest::get('tag', 'is_string', null);
$search_content = UOJRequest::get('search', 'is_string', '');
$search_is_effective = false;
$cur_tab = UOJRequest::get('tab', 'is_string', 'all');
if ($cur_tab == 'template') {
	$search_tag = "模板题";
}
if (is_string($search_tag)) {
	$cond[] = [
		DB::rawvalue($search_tag), "in", DB::rawbracket([
			"select tag from problems_tags",
			"where", ["problems_tags.problem_id" => DB::raw("problems.id")]
		])
	];
}
if ($search_content !== '') {
	foreach (explode(' ', $search_content) as $key) {
		if (strlen($key) > 0) {
			$cond[] = DB::lor([
				[DB::instr(DB::raw('title'), $key), '>', 0],
				DB::exists([
					"select tag from problems_tags",
					"where", [
						[DB::instr(DB::raw('tag'), $key), '>', 0],
						"problems_tags.problem_id" => DB::raw("problems.id")
					]
				]),
				"id" => $key,
			]);
			$search_is_effective = true;
		}
	}
}

if (isset($_GET['is_hidden'])) {
	$cond['problems.is_hidden'] = true;
}

if (Auth::check() && isset($_GET['my'])) {
	$cond['problems.uploader'] = Auth::id();
}

if (empty($cond)) {
	$cond = '1';
}

$header = '<tr>';
$header .= '<th class="text-center" style="width:5em;">ID</th>';
$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
if (isset($_COOKIE['show_submit_mode'])) {
	$header .= '<th class="text-center" style="width:4em;">' . UOJLocale::get('problems::ac') . '</th>';
	$header .= '<th class="text-center" style="width:4em;">' . UOJLocale::get('problems::submit') . '</th>';
	$header .= '<th class="text-center" style="width:125px;">' . UOJLocale::get('problems::ac ratio') . '</th>';
}
if (isset($_COOKIE['show_difficulty'])) {
	$header .= '<th class="text-center" style="width:3em;">' . UOJLocale::get('problems::difficulty') . '</th>';
}
$header .= '<th class="text-center" style="width:50px;">' . UOJLocale::get('appraisal') . '</th>';
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

$pag = new Paginator([
	'col_names' => ['*'],
	'table_name' => [
		"problems left join best_ac_submissions",
		"on", [
			"best_ac_submissions.submitter" => Auth::id(),
			"problems.id" => DB::raw("best_ac_submissions.problem_id")
		],
	],
	'cond' => $cond,
	'tail' => "order by id asc",
	'page_len' => 40,
	'post_filter' => function ($problem) {
		return (new UOJProblem($problem))->userCanView(Auth::user());
	}
]);

// if ($search_is_effective) {
// 	$search_summary = [
// 		'count_in_cur_page' => $pag->countInCurPage(),
// 		'first_a_few' => []
// 	];
// 	foreach ($pag->get(5) as $info) {
// 		$problem = new UOJProblem($info);
// 		$search_summary['first_a_few'][] = [
// 			'type' => 'problem',
// 			'id' => $problem->info['id'],
// 			'title' => $problem->getTitle()
// 		];
// 	}
// 	DB::insert([
// 		"insert into search_requests",
// 		"(created_at, remote_addr, type, cache_id, q, content, result)",
// 		"values", DB::tuple([DB::now(), UOJContext::remoteAddr(), 'search', 0, $search_content, UOJContext::requestURI(), json_encode($search_summary)])
// 	]);
// }
?>
<?php echoUOJPageHeader(UOJLocale::get('problems')) ?>

<div class="row">

	<!-- left col -->
	<div class="col-lg-9">

		<!-- title -->
		<div class="d-flex justify-content-between">

			<h1>
				<?= UOJLocale::get('problems') ?>
			</h1>

			<?php if (isSuperUser($myUser) || isProblemManager($myUser) || isProblemUploader($myUser)) : ?>
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
					<input type="checkbox" id="input-show_tags_mode" class="form-check-input" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ' : '' ?> />
					<label class="form-check-label" for="input-show_tags_mode">
						<?= UOJLocale::get('problems::show tags') ?>
					</label>
				</div>

				<div class="form-check d-inline-block">
					<input type="checkbox" id="input-show_submit_mode" class="form-check-input" <?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ' : '' ?> />
					<label class="form-check-label" for="input-show_submit_mode">
						<?= UOJLocale::get('problems::show statistics') ?>
					</label>
				</div>

				<div class="form-check d-inline-block">
					<input type="checkbox" id="input-show_difficulty" class="form-check-input" <?= isset($_COOKIE['show_difficulty']) ? 'checked="checked" ' : '' ?> />
					<label class="form-check-label" for="input-show_difficulty">
						<?= UOJLocale::get('problems::show difficulty') ?>
					</label>
				</div>
			</div>
		</div>

		<?= $pag->pagination() ?>

		<script type="text/javascript">
			$('#input-show_tags_mode').click(function() {
				if (this.checked) {
					$.cookie('show_tags_mode', '', {
						path: '/problems'
					});
				} else {
					$.removeCookie('show_tags_mode', {
						path: '/problems'
					});
				}
				location.reload();
			});
			$('#input-show_submit_mode').click(function() {
				if (this.checked) {
					$.cookie('show_submit_mode', '', {
						path: '/problems'
					});
				} else {
					$.removeCookie('show_submit_mode', {
						path: '/problems'
					});
				}
				location.reload();
			});
			$('#input-show_difficulty').click(function() {
				if (this.checked) {
					$.cookie('show_difficulty', '', {
						path: '/'
					});
				} else {
					$.removeCookie('show_difficulty', {
						path: '/'
					});
				}
				location.reload();
			});
		</script>

		<div class="card my-3">
			<?=
			HTML::responsive_table($header, $pag->get(), [
				'table_attr' => [
					'class' => ['table', 'uoj-table', 'mb-0'],
				],
				'tr' => function ($row, $idx) {
					return getProblemTR($row);
				}
			]);
			?>
		</div>

		<?= $pag->pagination() ?>

	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">

		<!-- search bar -->
		<form method="get" class="mb-3" id="form-problem_search">
			<div class="input-group mb-3">
				<input id="search-input" name="search" type="text" class="form-control" placeholder="搜索">
				<button class="btn btn-outline-secondary" type="submit">
					<i class="bi bi-search"></i>
				</button>
			</div>
			<?php if (Auth::check()) : ?>
				<div class="form-check d-inline-block">
					<input type="checkbox" name="my" <?= isset($_GET['my']) ? 'checked="checked"' : '' ?> class="form-check-input" id="input-my">
					<label class="form-check-label" for="input-my">
						我的题目
					</label>
				</div>
			<?php endif ?>
			<?php if (isProblemManager(Auth::user())) : ?>
				<div class="form-check d-inline-block ms-2">
					<input type="checkbox" name="is_hidden" <?= isset($_GET['is_hidden']) ? 'checked="checked"' : '' ?> class="form-check-input" id="input-is_hidden">
					<label class="form-check-label" for="input-is_hidden">
						隐藏题目
					</label>
				</div>
			<?php endif ?>
		</form>
		<script>
			$('#search-input').val(new URLSearchParams(location.search).get('search'));
			$('#input-my, #input-is_hidden').click(function() {
				$('#form-problem_search').submit();
			});
		</script>

		<!-- sidebar -->
		<?php uojIncludeView('sidebar') ?>
	</aside>

</div>

<?php echoUOJPageFooter() ?>
