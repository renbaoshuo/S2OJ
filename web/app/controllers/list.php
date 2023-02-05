<?php
requireLib('mathjax');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJList::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJList::cur()->userCanView(Auth::user(), ['ensure' => true]);

function getProblemTR($info) {
	$problem = new UOJProblem($info);

	$html = HTML::tag_begin('tr', ['class' => 'text-center']);
	$html .= HTML::tag('td', ['class' => $info['submission_id'] ? 'table-success' : ''], "#{$info['id']}");
	$html .= HTML::tag_begin('td', ['class' => 'text-start']);
	$html .= $problem->getLink(['with' => 'none']);
	if ($problem->isUserOwnProblem(Auth::user())) {
		$html .= ' <span class="badge text-white bg-info">' . UOJLocale::get('problems::my problem') . '</span> ';
	}
	if ($info['type'] == 'remote') {
		HTML::tag('a', ['class' => 'badge text-bg-success', 'href' => '/problems/remote'], '远端评测题');
	}
	if ($info['is_hidden']) {
		$html .= ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span> ';
	}
	if (isset($_COOKIE['show_tags_mode'])) {
		foreach ($problem->queryTags() as $tag) {
			$html .= ' <a class="uoj-problem-tag">' . '<span class="badge text-bg-secondary">' . HTML::escape($tag) . '</span>' . '</a> ';
		}
	}
	$html .= HTML::tag_end('td');
	if (isset($_COOKIE['show_submit_mode'])) {
		$perc = $info['submit_num'] > 0 ? round(100 * $info['ac_num'] / $info['submit_num']) : 0;

		$html .= HTML::tag(
			'td',
			[],
			HTML::tag(
				'div',
				[
					'class' => 'progress',
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

$header = '<tr>';
$header .= '<th class="text-center" style="width:5em;">ID</th>';
$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
if (isset($_COOKIE['show_submit_mode'])) {
	$header .= '<th class="text-center" style="width:125px;">' . UOJLocale::get('problems::ac ratio') . '</th>';
}
$header .= '<th class="text-center" style="width:4em;">' . UOJLocale::get('problems::difficulty') . '</th>';
$header .= '<th class="text-center" style="width:50px;">' . UOJLocale::get('appraisal') . '</th>';
$header .= '</tr>';

$pag_config = [
	'page_len' => 20,
	'col_names' => [
		'best_ac_submissions.submission_id as submission_id',
		'problems.*',
	],
	'table_name' => [
		"problems",
		"left join best_ac_submissions",
		"on", [
			"best_ac_submissions.submitter" => Auth::id(),
			"problems.id" => DB::raw("best_ac_submissions.problem_id")
		],
		"inner join lists_problems",
		"on", [
			"lists_problems.list_id" => UOJList::info('id'),
			"lists_problems.problem_id" => DB::raw("problems.id"),
		],
	],
	'cond' => '1',
	'tail' => "order by id asc",
	'page_len' => 40,
	'post_filter' => function ($problem) {
		return (new UOJProblem($problem))->userCanView(Auth::user());
	}
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
				<?= UOJList::info('title') ?>
				<span class="fs-5">(ID: #<?= UOJList::info('id') ?>)</span>
				<?php if (UOJList::info('is_hidden')) : ?>
					<span class="badge text-bg-danger fs-6">
						<i class="bi bi-eye-slash-fill"></i>
						<?= UOJLocale::get('hidden') ?>
					</span>
				<?php endif ?>
			</h1>

			<?php if (UOJList::cur()->userCanManage(Auth::user())) : ?>
				<div class="text-end">
					<a class="btn btn-primary" href="/list/<?= UOJList::info('id') ?>/manage" role="button">
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
				<div class="markdown-body">
					<?= UOJList::cur()->queryContent()['content'] ?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-4 col-12"></div>
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
			</div>
		</div>

		<script type="text/javascript">
			$('#input-show_tags_mode').click(function() {
				if (this.checked) {
					$.cookie('show_tags_mode', '', {
						path: '/list',
						expires: 365,
					});
				} else {
					$.removeCookie('show_tags_mode', {
						path: '/list',
					});
				}
				location.reload();
			});
			$('#input-show_submit_mode').click(function() {
				if (this.checked) {
					$.cookie('show_submit_mode', '', {
						path: '/list',
						expires: 365,
					});
				} else {
					$.removeCookie('show_submit_mode', {
						path: '/list',
					});
				}
				location.reload();
			});
		</script>

		<?= $pag->pagination() ?>

		<div class="card my-3 table-responsive">
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

		<?= $pag->pagination()	?>

	</div>
	<!-- end left col -->

	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>

</div>

<?php echoUOJPageFooter() ?>
