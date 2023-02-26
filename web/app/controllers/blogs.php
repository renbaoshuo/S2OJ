<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();

function echoBlog($info) {
	$blog = new UOJBlog($info);
	$poster = UOJUser::query($blog->info['poster']);

	echo '<div class="list-group-item">';
	echo     '<div class="row gy-2">';

	echo         '<div class="col-md-3 d-flex gap-2">';
	echo             '<div class="">';
	echo HTML::tag('a', [
		'href' => HTML::url('/user/' . $poster['username']),
		'class' => 'd-inline-block me-2',
	], HTML::empty_tag('img', [
		'src' => HTML::avatar_addr($poster, 64),
		'class' => 'uoj-user-avatar rounded',
		'style' => 'width: 3rem; height: 3rem;',
	]));
	echo             '</div>';
	echo             '<div class="d-flex flex-column gap-1">';
	echo                 '<div>', UOJUser::getLink($poster), '</div>';
	echo                 '<div class="hstack gap-2 flex-wrap small text-muted">';
	echo                     '<span>', '<i class="bi bi-chat-dots"></i> ', $blog->getReplyCnt(), ' </span>';
	echo                     '<span class="uoj-click-zan-cnt-inline" title="', $blog->info['zan'], '">', '<i class="bi bi-hand-thumbs-up"></i> ', $blog->getDisplayZanCnt(), ' </span>';
	echo                 '</div>';
	echo             '</div>';
	echo         '</div>';

	echo         '<div class="col-md-6">';
	echo             '<div>';
	echo                 $blog->getLink();
	if ($blog->info['is_hidden']) {
		echo             ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
	}
	echo             '</div>';
	echo             '<div class="mt-1 text-muted small">', '<i class="bi bi-clock"></i> ', $blog->info['post_time'], '</div>';
	echo         '</div>';

	$newest = $blog->queryNewestComment();

	echo         '<div class="col-md-3 small vstack gap-1">';
	if ($newest) {
		echo         '<div>最新评论: ', UOJUser::getLink($newest['poster']), '</div>';
		echo         '<div class="text-muted" style="line-height: 1.5rem">', '<i class="bi bi-clock"></i> ', $newest['post_time'], '</div>';
	} else {
		echo         '<div>暂无评论</div>';
	}
	echo         '</div>';

	echo     '</div>';
	echo '</div>';
}

$pag = new Paginator([
	'page_len' => 10,
	'table_name' => 'blogs',
	'col_names' => ['id', 'poster', 'title', 'post_time', 'active_time', 'zan', 'is_hidden'],
	'cond' => '1',
	'tail' => 'order by post_time desc, id desc',
	'post_filter' => function ($info) {
		return (new UOJBlog($info))->userCanView(Auth::user());
	},
]);
?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<!-- title container -->
		<div class="d-flex flex-wrap justify-content-between align-items-center">
			<h1>
				<?= UOJLocale::get("blogs overview") ?>
			</h1>

			<?php if (Auth::check()) : ?>
				<div class="text-end">
					<div class="btn-group">
						<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-secondary btn-sm">
							我的博客首页
						</a>
						<?php if (UOJUser::checkPermission(Auth::user(), 'blogs.create')) : ?>
							<a href="<?= HTML::blog_url(Auth::id(), '/post/new/write') ?>" class="btn btn-primary btn-sm">
								<i class="bi bi-pencil"></i>
								写新博客
							</a>
						<?php endif ?>
					</div>
				</div>
			<?php endif ?>
		</div>
		<!-- end title container -->

		<div class="card mt-3">
			<div class="list-group list-group-flush">
				<?php if ($pag->isEmpty()) : ?>
					<div class="list-group-item text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>

				<?php foreach ($pag->get() as $idx => $row) : ?>
					<?php echoBlog($row) ?>
				<?php endforeach ?>
			</div>
		</div>

		<div class="mt-3">
			<?= $pag->pagination() ?>
		</div>
	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
