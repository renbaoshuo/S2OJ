<?php
	requireLib('bootstrap5');
	requireLib('hljs');
	requireLib('mathjax');
	requirePHPLib('form');
	requirePHPLib('judger');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}

	if (!isProblemVisibleToUser($problem, $myUser)) {
		become404Page();
	}

	$problem_extra_config = getProblemExtraConfig($problem);
	$solution_viewable = hasViewSolutionPermission($problem_extra_config['view_solution_type'], $myUser, $problem);
	$solution_submittable = hasViewSolutionPermission($problem_extra_config['submit_solution_type'], $myUser, $problem);

	if (!$solution_viewable) {
		become403Page();
	}

	if (!hasProblemPermission($myUser, $problem) && isRegisteredRunningContestProblem($myUser, $problem)) {
		become403Page();
	}

	function removeSolutionForm($blog_id) {
		$res_form = new UOJForm("remove_solution_{$blog_id}");
		$res_form->addHidden("blog_id", $blog_id, function($blog_id) {
			global $myUser, $problem;

			if (!validateUInt($blog_id)) {
				return '博客 ID 不是有效的数字';
			}

			$blog = queryBlog($blog_id);
			if (!$blog) {
				return '博客不存在';
			}

			if (!hasProblemPermission($myUser, $problem)) {
				if ($blog['poster'] != $myUser['username']) {
					return '您只能删除自己的题解';
				}
			}

			return '';
		}, null);
		$res_form->handle = function() {
			global $myUser, $problem;

			$blog_id = $_POST["blog_id"];
			DB::query("delete from problems_solutions where problem_id = {$problem['id']} and blog_id = {$blog_id}");
			$blog = queryBlog($blog_id);

			if ($blog['poster'] != $myUser['username']) {
				$blog_link = getBlogLink($blog['id']);
				$poster_user_link = getUserLink($blog['poster']);
				$admin_user_link = isSuperUser($myUser) ? '管理员' : getUserLink($myUser['username']);
				$content = <<<EOD
<p>{$poster_user_link} 您好：</p>
<p>您为问题 <a href="/problem/{$problem['id']}">#{$problem['id']} ({$problem['title']})</a> 提交的题解 {$blog_link} 已经被 {$admin_user_link} 移除。 </p>
EOD;
				sendSystemMsg($blog['poster'], '题解移除通知', $content);
			}
		};
		$res_form->submit_button_config['margin_class'] = 'mt-0';
		$res_form->submit_button_config['class_str'] = 'btn btn-link text-decoration-none text-danger p-0';
		$res_form->submit_button_config['text'] = '移除';
		$res_form->submit_button_config['confirm_text'] = '你真的要移除这篇题解吗？';
		$res_form->submit_button_config['align'] = 'inline';

		return $res_form;
	}

	if ($solution_submittable) {
		$add_new_solution_form = new UOJForm('add_new_solution');
		$add_new_solution_form->addVInput('blog_id_2', 'text', '博客 ID', '', 
			function ($x) {
				global $myUser, $problem, $solution_submittable;

				if (!validateUInt($x)) {
					return 'ID 不合法';
				}

				$blog = queryBlog($x);
				if (!$blog) {
					return '博客不存在';
				}

				if (!isSuperUser($myUser)) {
					if ($blog['poster'] != $myUser['username']) {
						if ($blog['is_hidden']) {
							return '博客不存在';
						}

						return '只能提交本人撰写的博客';
					}
				}

				if (!hasProblemPermission($myUser, $problem)) {
					if ($blog['is_hidden']) {
						return '只能提交公开的博客';
					}
				}

				if (querySolution($problem['id'], $x)) {
					return '该题解已提交';
				}

				if (!$solution_submittable) {
					return '您无权提交题解';
				}

				return '';
			},
			null
		);
		$add_new_solution_form->submit_button_config['text'] = '发布';
		$add_new_solution_form->submit_button_config['align'] = 'center';
		$add_new_solution_form->handle = function() {
			global $problem, $myUser;

			$blog_id_2 = $_POST['blog_id_2'];
			$problem_id = $problem['id'];

			DB::insert("insert into problems_solutions (problem_id, blog_id) values ({$problem_id}, {$blog_id_2})");
		};
		$add_new_solution_form->runAtServer();
	}

	$pag_config = array('page_len' => 5);
	$pag_config['col_names'] = array('blog_id', 'content', 'poster', 'post_time', 'zan', 'is_hidden');
	$pag_config['table_name'] = "problems_solutions inner join blogs on problems_solutions.blog_id = blogs.id";
	$pag_config['cond'] = "problem_id = {$problem['id']}";

	// 根据实际使用需要，题目管理员可以通过题解页面看到其他用户提交的题解，并且即使该题解对应的博客是隐藏状态也会照常显示
	// 如需仅允许超级管理员查看，请将下一行中 if 语句的条件改为 (!isSuperUser($myUser))
	if (!hasProblemPermission($myUser, $problem)) {
		$pag_config['cond'] .= " and (is_hidden = 0 or poster = '{$myUser['username']}')";
	}

	$pag_config['tail'] = "order by zan desc, post_time desc, id asc";
	$pag = new Paginator($pag_config);

	$rows = [];

	foreach ($pag->get() as $idx => $row) {
		$rows[$idx] = $row;
		if ($row['poster'] == $myUser['username'] || hasProblemPermission($myUser, $problem)) {
			$removeForm = removeSolutionForm($row['blog_id']);
			$removeForm->runAtServer();
			$rows[$idx]['removeForm'] = $removeForm;
		}
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('problems::solutions') . ' - ' . HTML::stripTags($problem['title'])) ?>

<div class="row">

<!-- Left col -->
<div class="col-lg-9">

<div class="card card-default mb-2">
	<div class="card-body">
		<h1 class="h2 card-title text-center">
			#<?= $problem['id']?>. <?= $problem['title'] ?>
		</h1>
	</div>

	<ul class="list-group list-group-flush">
		<?php foreach ($rows as $row): ?>
			<li class="list-group-item">
				<?php $poster = queryUser($row['poster']); ?>
				<div class="mb-3">
					<span class="me-2 d-inline-block">
						<a class="text-decoration-none" href="<?= HTML::url('/user/'.$poster['username']) ?>">
							<img src="<?= HTML::avatar_addr($poster, 64) ?>" width="32" height="32" class="rounded" />
						</a>
						<?= getUserLink($poster['username']) ?>
					</span>
					<span class="text-muted small d-inline-block">
						<?= UOJLocale::get('post time') ?>:
						<time class="text-muted"><?= $row['post_time'] ?></time>
					</span>
					<?php if ($row['is_hidden']): ?>
					<span class="badge text-bg-danger ms-2">
						<i class="bi bi-eye-slash-fill"></i>
						<?= UOJLocale::get('hidden') ?>
					</span>
					<?php endif ?>
				</div>

				<div class="markdown-body">
					<?= $row['content'] ?>
				</div>

				<ul class="mt-3 text-end list-inline">
					<?php if (isset($row['removeForm'])): ?>
					<li class="list-inline-item">
						<?php $row['removeForm']->printHTML(); ?>
					</li>
					<?php endif ?>
					<?php if (Auth::check() && (isSuperUser(Auth::user()) || Auth::id() == $row['poster'])): ?>
					<li class="list-inline-item">
						<a class="text-decoration-none" href="<?= HTML::blog_url($row['poster'], '/post/'.$row['blog_id'].'/write') ?>">
							修改
						</a>
					</li>
					<?php endif ?>
					<li class="list-inline-item">
						<a class="text-decoration-none" href="/blogs/<?= $row['blog_id'] ?>">在 Ta 的博客上查看</a>
					<li class="list-inline-item">
						<?= getClickZanBlock('B', $row['blog_id'], $row['zan']) ?>
					</li>
				</ul>
			</li>
		<?php endforeach ?>
		<?php if ($pag->isEmpty()): ?>
			<div class="text-center py-2">
				暂无题解
			</div>
		<?php endif ?>
	</ul>
</div>

<!-- Pagination -->
<?= $pag->pagination() ?>

<!-- End left col -->
</div>

<!-- Right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">

<div class="card card-default mb-2">
	<ul class="nav nav-pills nav-fill flex-column" role="tablist">
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>" class="nav-link" role="tab">
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
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/statistics">
				<i class="bi bi-graph-up"></i>
				<?= UOJLocale::get('problems::statistics') ?>
			</a>
		</li>
		<?php if (hasProblemPermission($myUser, $problem)): ?>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
				<i class="bi bi-sliders"></i>
				<?= UOJLocale::get('problems::manage') ?>
			</a>
		</li>
		<?php endif ?>
	</ul>
	<div class="card-footer bg-transparent">
		评价：<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
	</div>
</div>

<div class="card card-default mb-2">
	<div class="card-header bg-transparent fw-bold">
		增加题解
	</div>
	<div class="card-body">
		<?php if (isset($add_new_solution_form)): ?>
			<?php $add_new_solution_form->printHTML(); ?>
		<?php else: ?>
			您当前无法为本题新增题解。
		<?php endif ?>
	</div>
	<div class="card-footer bg-transparent">
		<a target="_blank" class="text-decoration-none" href="<?= HTML::blog_url(Auth::id(), '/post/new/write?title=' . urlencode('【题解】#' . $problem['id'] . '. ' . $problem['title']) . '&is_hidden=0&add_solution_for=' . $problem['id']) ?>">
			快速新建文章
		</a>
		<div class="small text-muted mt-1">发布文章后，请返回本页输入博客 ID</div>
	</div>
</div>

<?php uojIncludeView('sidebar', array()); ?>

<!-- End right col -->
</aside>

</div>

<?php echoUOJPageFooter() ?>
