<?php
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJUser::checkPermission(Auth::user(), 'problems.view') || UOJResponse::page403();

if (UOJProblem::userCanCreateProblem(Auth::user())) {
	$default_statement = <<<'EOD'
## 题目描述

在此处填写题目描述。题目中的图片应上传至 S2OJ 图床中（点击顶栏 应用——图床 进入），以免丢失。

## 输入格式

在此处约定输入数据的格式。

## 输出格式

在此处说明输入数据的格式要求。

## 输入输出样例

### 样例输入 #1

```text
样例 1 的输入内容
```

### 样例输出 #1

```text
样例 1 的输出内容
```

### 样例解释 #1

样例 1 的解释与说明。

### 样例 #2

<!-- 大样例，如无大样例请删除本节。请根据实际情况修改下方的文件名。 -->

见右侧「附件下载」中的 `ex_data2.in/out`。

## 数据范围与约定

<!-- 请根据实际情况修改下方的数据范围。 -->

- 对于 $50\%$ 的数据，【替换此处】。
- 对于 $100\%$ 的数据，【替换此处】。

如有，在此处填写其他于题意或数据相关的说明。

EOD;
	$new_problem_form = new UOJForm('new_problem');
	$new_problem_form->handle = function () use ($default_statement) {
		DB::insert([
			"insert into problems",
			"(title, uploader, is_hidden, submission_requirement, extra_config)",
			"values", DB::tuple(["New Problem", Auth::id(), 1, "{}", "{}"])
		]);
		$id = DB::insert_id();
		DB::insert([
			"insert into problems_contents",
			"(id, statement, statement_md)",
			"values", DB::tuple([
				$id,
				HTML::purifier()->purify(HTML::parsedown()->text($default_statement)),
				$default_statement,
			])
		]);
		dataNewProblem($id);

		redirectTo("/problem/{$id}/manage/statement");
		die();
	};
	$new_problem_form->config['submit_container']['class'] = '';
	$new_problem_form->config['submit_button']['class'] = 'bg-transparent text-body border-0 d-block w-100 px-3 py-2 text-start';
	$new_problem_form->config['submit_button']['text'] = '<i class="bi bi-plus-lg"></i> 新建本地题目';
	$new_problem_form->config['confirm']['text'] = '你真的要添加新题吗？';
	$new_problem_form->runAtServer();
}

function getProblemTR($info) {
	$problem = new UOJProblem($info);

	$html = HTML::tag_begin('tr', ['class' => 'text-center']);
	$html .= HTML::tag('td', ['class' => $info['submission_id'] ? 'table-success' : ''], "#{$info['id']}");
	$html .= HTML::tag_begin('td', ['class' => 'text-start align-middle']);
	$html .= $problem->getLink(['with' => 'none']);
	if ($problem->isUserOwnProblem(Auth::user())) {
		$html .= ' <a href="/problems?my=on"><span class="badge text-white bg-info align-middle">' . UOJLocale::get('problems::my problem') . '</span></a> ';
	}
	if ($info['type'] == 'remote') {
		$html .= ' ' . HTML::tag('a', ['class' => 'badge text-bg-success align-middle', 'href' => '/problems/remote'], '远端评测题');
	}
	if ($info['is_hidden']) {
		$html .= ' <a href="/problems?is_hidden=on"><span class="badge text-bg-danger align-middle"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span></a> ';
	}
	if (isset($_COOKIE['show_tags_mode'])) {
		$html .= HTML::tag_begin('span', ['class' => 'float-end']);
		foreach ($problem->queryTags() as $tag) {
			$html .= ' <a class="uoj-problem-tag">' . '<span class="badge bg-secondary align-middle">' . HTML::escape($tag) . '</span>' . '</a> ';
		}
		$html .= HTML::tag_end('span');
	}
	$html .= HTML::tag_end('td');
	if (isset($_COOKIE['show_submit_mode'])) {
		$perc = $info['submit_num'] > 0 ? round(100 * $info['ac_num'] / $info['submit_num']) : 0;

		$html .= HTML::tag(
			'td',
			[
				'class' => 'align-middle',
			],
			HTML::tag(
				'div',
				[
					'class' => 'progress h-100',
					'data-bs-toggle' => 'tooltip',
					'data-bs-title' => "{$info['ac_num']} / {$info['submit_num']}",
					'data-bs-placement' => 'bottom',
				],
				HTML::tag('div', [
					'class' => 'progress-bar bg-success',
					'role' => 'progressbar',
					'aria-valuenow' => $perc,
					'aria-valuemin' => 0,
					'aria-valuemax' => 100,
					'style' => "width: {$perc}%; min-width: 20px;",
				], "{$perc}%")
			)
		);
	}
	$html .= HTML::tag('td', [], $problem->getDifficultyHTML());
	$html .= HTML::tag('td', [], ClickZans::getCntBlock($problem->info['zan']));
	$html .= HTML::tag_end('tr');
	return $html;
}

$cond = [];
$search_tag = UOJRequest::get('tag', 'is_string', null);
$search_content = UOJRequest::get('search', 'is_string', '');
$search_is_effective = false;
$cur_tab = UOJRequest::get('tab', 'is_string', 'all');
if ($cur_tab == 'template') {
	$search_tag = "模板题";
} else if ($cur_tab == 'remote') {
	$cond['type'] = 'remote';
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

if (isset($_GET['min_difficulty']) && $_GET['min_difficulty']) {
	$cond[] = ['problems.difficulty', '>=', $_GET['min_difficulty']];
}

if (isset($_GET['max_difficulty']) && $_GET['max_difficulty']) {
	$cond[] = ['problems.difficulty', '<=', $_GET['max_difficulty']];
}

if (empty($cond)) {
	$cond = '1';
}

$header = '<tr>';
$header .= '<th class="text-center" style="width:5em;">ID</th>';
$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
if (isset($_COOKIE['show_submit_mode'])) {
	$header .= '<th class="text-center" style="width:125px;">' . UOJLocale::get('problems::ac ratio') . '</th>';
}
$header .= '<th class="text-center" style="width:4em;">' . UOJLocale::get('problems::difficulty') . '</th>';
$header .= '<th class="text-center" style="width:50px;">' . UOJLocale::get('appraisal') . '</th>';
$header .= '</tr>';

$tabs_info = [
	'all' => [
		'name' => UOJLocale::get('problems::all problems'),
		'url' => "/problems"
	],
	'template' => [
		'name' => UOJLocale::get('problems::template problems'),
		'url' => "/problems/template"
	],
	'remote' => [
		'name' => UOJLocale::get('problems::remote problems'),
		'url' => "/problems/remote"
	],
];

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
	'page_len' => 50,
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
	<div class="col-md-9">
		<!-- title -->
		<div class="d-flex justify-content-between flex-wrap">
			<h1>
				<?= UOJLocale::get('problems') ?>
			</h1>

			<div>
				<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
			</div>
		</div>
		<!-- end title -->

		<?= $pag->pagination() ?>

		<div class="text-end">
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
		</div>

		<script type="text/javascript">
			$('#input-show_tags_mode').click(function() {
				if (this.checked) {
					$.cookie('show_tags_mode', '', {
						path: '/problems',
						expires: 365,
					});
				} else {
					$.removeCookie('show_tags_mode', {
						path: '/problems',
					});
				}
				location.reload();
			});
			$('#input-show_submit_mode').click(function() {
				if (this.checked) {
					$.cookie('show_submit_mode', '', {
						path: '/problems',
						expires: 365,
					});
				} else {
					$.removeCookie('show_submit_mode', {
						path: '/problems'
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
	<aside class="col-md-3 mt-3 mt-md-0">
		<!-- search bar -->
		<form method="get" class="mb-3" id="form-problem_search">
			<div class="input-group mb-3">
				<input id="search-input" name="search" type="text" class="form-control" placeholder="搜索">
				<button class="btn btn-outline-secondary" type="submit">
					<i class="bi bi-search"></i>
				</button>
			</div>
			<div>
				<?php if (Auth::check()) : ?>
					<div class="form-check d-inline-block">
						<input type="checkbox" name="my" <?= isset($_GET['my']) ? 'checked="checked"' : '' ?> class="form-check-input" id="input-my">
						<label class="form-check-label" for="input-my">
							我的题目
						</label>
					</div>
				<?php endif ?>
				<?php if (UOJProblem::userCanManageSomeProblem(Auth::user())) : ?>
					<div class="form-check d-inline-block ms-2">
						<input type="checkbox" name="is_hidden" <?= isset($_GET['is_hidden']) ? 'checked="checked"' : '' ?> class="form-check-input" id="input-is_hidden">
						<label class="form-check-label" for="input-is_hidden">
							隐藏题目
						</label>
					</div>
				<?php endif ?>
			</div>

			<div class="card mt-3">
				<div class="card-header fw-bold">
					题目难度
				</div>
				<div class="card-body">
					<div class="input-group input-group-sm">
						<input type="text" class="form-control" name="min_difficulty" id="input-min_difficulty" maxlength="4" style="width:4em" placeholder="800" value="<?= HTML::escape($_GET['min_difficulty']) ?>" autocomplete="off" />
						<span class="input-group-text">~</span>
						<input type="text" class="form-control" name="max_difficulty" id="input-max_difficulty" maxlength="4" style="width:4em" placeholder="3500" value="<?= HTML::escape($_GET['max_difficulty']) ?>" autocomplete="off" />
						<button type="submit" class="btn btn-outline-secondary">
							<i class="bi bi-funnel"></i>
						</button>
					</div>
				</div>
			</div>
		</form>
		<script>
			$('#search-input').val(new URLSearchParams(location.search).get('search'));
			$('#input-my, #input-is_hidden, #input-difficulty').change(function() {
				$('#form-problem_search').submit();
			});
		</script>

		<?php if (UOJProblem::userCanCreateProblem(Auth::user())) : ?>
			<div class="card mb-3">
				<div class="card-header fw-bold">
					新建题目
				</div>
				<div class="list-group list-group-flush">
					<div class="list-group-item list-group-item-action p-0">
						<?php $new_problem_form->printHTML() ?>
					</div>
					<a class="list-group-item list-group-item-action" href="/problems/remote/new">
						<i class="bi bi-cloud-plus"></i>
						新建远端评测题目
					</a>
				</div>
			</div>
		<?php endif ?>

		<!-- sidebar -->
		<?php uojIncludeView('sidebar') ?>
	</aside>

</div>

<?php echoUOJPageFooter() ?>
