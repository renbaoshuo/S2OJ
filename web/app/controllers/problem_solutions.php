<?php
requireLib('bootstrap5');
requireLib('hljs');
requireLib('mathjax');
requirePHPLib('form');
requirePHPLib('judger');

Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanView(Auth::user()) || UOJResponse::page403();

if (!UOJProblem::cur()->userCanManage(Auth::user())) {
	UOJProblem::cur()->userPermissionCodeCheck(Auth::user(), UOJProblem::cur()->getExtraConfig('view_solution_type')) || UOJResponse::page403();

	foreach (UOJProblem::cur()->findInContests() as $cp) {
		if ($cp->contest->progress() == CONTEST_IN_PROGRESS && $cp->contest->userHasRegistered(Auth::user())) {
			UOJResponse::page403();
		}
	}
}

if (UOJRequest::post('submit-remove_solution') === 'remove_solution') {
	crsf_defend();

	$blog = UOJBlog::query(UOJRequest::post('blog_id'));

	if (!$blog || !$blog->userCanView(Auth::user())) {
		dieWithAlert('博客不存在。');
	}

	if (!UOJProblem::cur()->userCanManage(Auth::user()) && $blog->info['poster'] != Auth::id()) {
		dieWithAlert('您没有权限移除该题解。');
	}

	if ($blog->info['poster'] != Auth::id()) {
		sendSystemMsg(
			$blog->info['poster'],
			'题解移除通知',
			"<p>" . UOJUser::getLink($blog->info['poster']) . " 您好：</p>" .
				"<p>您为问题 " . UOJProblem::cur()->getLink(['with' => 'id']) . " 提交的题解 " . $blog->getLink() . " 已被" . (isSuperUser(Auth::user()) ? "管理员" : " " . UOJUser::getLink(Auth::user()) . " ") . "移除。</p>"
		);
	}

	DB::delete([
		"delete from problems_solutions",
		"where", [
			"problem_id" => UOJProblem::info('id'),
			"blog_id" => $blog->info['id'],
		],
	]);

	dieWithAlert('移除成功！');
}

if (UOJProblem::cur()->userCanManage(Auth::user()) || UOJProblem::cur()->userPermissionCodeCheck(Auth::user(), UOJProblem::cur()->getExtraConfig('submit_solution_type'))) {
	$add_new_solution_form = new UOJBs4Form('add_new_solution');
	$add_new_solution_form->addVInput(
		'blog_id_2',
		'text',
		'博客 ID',
		'',
		function ($blog_id) {
			$blog = UOJBlog::query($blog_id);
			if (!$blog) {
				return '博客不存在';
			}

			if (!$blog->userCanManage(Auth::user())) {
				if ($blog->info['poster'] != Auth::id()) {
					if ($blog->info['is_hidden']) {
						return '博客不存在';
					}

					return '只能提交本人撰写的博客';
				}
			}

			if (!UOJProblem::cur()->userCanManage(Auth::user())) {
				if ($blog->info['is_hidden']) {
					return '只能提交公开的博客';
				}
			}

			if (querySolution(UOJProblem::info('id'), $blog_id)) {
				return '该题解已提交';
			}

			return '';
		},
		null
	);
	$add_new_solution_form->submit_button_config['text'] = '发布';
	$add_new_solution_form->submit_button_config['align'] = 'center';
	$add_new_solution_form->submit_button_config['class_str'] = 'btn btn-secondary';
	$add_new_solution_form->handle = function () {
		DB::insert([
			"insert into problems_solutions",
			DB::bracketed_fields(["problem_id", "blog_id"]),
			"values", DB::tuple([UOJProblem::info('id'), $_POST['blog_id_2']]),
		]);
	};
	$add_new_solution_form->runAtServer();
}

$pag_config = [
	'page_len' => 5,
	'col_names' => ['blog_id', 'zan'],
	'table_name' => 'problems_solutions inner join blogs on blogs.id = problems_solutions.blog_id',
	'cond' => ["problem_id" => UOJProblem::info('id')],
	'post_filter' => function ($row) {
		$blog = UOJBlog::query($row['blog_id']);

		// 根据实际使用需要，题目管理员可以通过题解页面看到其他用户提交的题解，并且即使该题解对应的博客是隐藏状态也会照常显示
		// 如需仅允许超级管理员查看，请将下一行改为 return $blog->userCanView(Auth::user());
		return $blog->userCanView(Auth::user()) || UOJProblem::cur()->userCanManage(Auth::user());
	},
];

$pag_config['tail'] = "order by zan desc, post_time desc, id asc";
$pag = new Paginator($pag_config);
?>

<?php echoUOJPageHeader(UOJLocale::get('problems::solutions') . ' - ' . HTML::stripTags($problem['title'])) ?>

<div class="row">
	<!-- Left col -->
	<div class="col-lg-9">
		<div class="card card-default mb-2">
			<div class="card-body">
				<h1 class="card-title text-center">
					<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?>
				</h1>
			</div>

			<ul class="list-group list-group-flush">
				<?php foreach ($pag->get() as $row) : ?>
					<?php
					$blog = UOJBlog::query($row['blog_id']);
					$poster = UOJUser::query($blog->info['poster']);
					?>
					<li class="list-group-item">
						<div class="mb-3">
							<span class="me-2 d-inline-block">
								<a class="text-decoration-none" href="<?= HTML::url('/user/' . $poster['username']) ?>">
									<img src="<?= HTML::avatar_addr($poster, 64) ?>" width="32" height="32" class="rounded" />
								</a>
								<?= UOJUser::getLink($poster) ?>
							</span>
							<span class="text-muted small d-inline-block">
								<?= UOJLocale::get('post time') ?>:
								<time class="text-muted"><?= $blog->info['post_time'] ?></time>
							</span>
							<?php if ($blog->info['is_hidden']) : ?>
								<span class="badge text-bg-danger ms-2">
									<i class="bi bi-eye-slash-fill"></i>
									<?= UOJLocale::get('hidden') ?>
								</span>
							<?php endif ?>
						</div>

						<div class="markdown-body">
							<?= $blog->queryContent()['content'] ?>
						</div>

						<ul class="mt-3 text-end list-inline">
							<?php if (UOJProblem::cur()->userCanManage(Auth::user()) || $poster['username'] == Auth::id()) : ?>
								<li class="list-inline-item">
									<form class="d-inline-block" method="POST" onsubmit="return confirm('你真的要移除这篇题解吗？移除题解不会删除对应博客。')">
										<input type="hidden" name="_token" value="<?= crsf_token() ?>">
										<input type="hidden" name="blog_id" value="<?= $blog->info['id'] ?>">
										<button class="btn btn-link text-decoration-none text-danger p-0 mt-0" type="submit" name="submit-remove_solution" value="remove_solution">
											移除
										</button>
									</form>
								</li>
							<?php endif ?>
							<?php if ($blog->userCanManage(Auth::user())) : ?>
								<li class="list-inline-item">
									<a class="d-inline-block align-middle" href="<?= $blog->getUriForWrite() ?>">
										修改
									</a>
								</li>
							<?php endif ?>
							<li class="list-inline-item">
								<a class="d-inline-block align-middle" href="<?= $blog->getBlogUri() ?>">
									在 Ta 的博客上查看
								</a>
							</li>
							<li class="list-inline-item">
								<?= ClickZans::getBlock('B', $blog->info['id'], $blog->info['zan']) ?>
							</li>
						</ul>
					</li>
				<?php endforeach ?>
				<?php if ($pag->isEmpty()) : ?>
					<div class="text-center text-muted py-4">
						暂无题解
					</div>
				<?php endif ?>
			</ul>
		</div>

		<!-- Pagination -->
		<?= $pag->pagination() ?>

	</div>
	<!-- End left col -->

	<!-- Right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">

		<div class="card card-default mb-2">
			<ul class="nav nav-pills nav-fill flex-column" role="tablist">
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>" class="nav-link" role="tab">
						<i class="bi bi-journal-text"></i>
						<?= UOJLocale::get('problems::statement') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="#" class="nav-link active" role="tab">
						<i class="bi bi-journal-bookmark"></i>
						<?= UOJLocale::get('problems::solutions') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/statistics">
						<i class="bi bi-graph-up"></i>
						<?= UOJLocale::get('problems::statistics') ?>
					</a>
				</li>
				<?php if (UOJProblem::cur()->userCanManage(Auth::user())) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/statement" role="tab">
							<i class="bi bi-sliders"></i>
							<?= UOJLocale::get('problems::manage') ?>
						</a>
					</li>
				<?php endif ?>
			</ul>
			<div class="card-footer bg-transparent">
				评价：<?= ClickZans::getBlock('P', UOJProblem::info('id'), UOJProblem::info('zan')) ?>
			</div>
		</div>

		<div class="card card-default mb-2">
			<div class="card-header bg-transparent fw-bold">
				增加题解
			</div>
			<div class="card-body">
				<?php if (isset($add_new_solution_form)) : ?>
					<?php $add_new_solution_form->printHTML(); ?>
				<?php else : ?>
					您当前无法为本题新增题解。
				<?php endif ?>
			</div>
			<div class="card-footer bg-transparent">
				<a target="_blank" class="text-decoration-none" href="<?= HTML::blog_url(Auth::id(), '/post/new/write?title=' . urlencode('【题解】#' . UOJProblem::info('id') . '. ' . UOJProblem::info('title')) . '&is_hidden=0') ?>">
					快速新建文章
				</a>
				<div class="small text-muted mt-1">发布文章后，请返回本页输入博客 ID</div>
			</div>
		</div>

		<?php uojIncludeView('sidebar'); ?>
		<!-- End right col -->
	</aside>

</div>

<?php echoUOJPageFooter() ?>
