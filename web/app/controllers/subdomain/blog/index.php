<?php
requireLib('hljs');
requireLib('mathjax');

Auth::check() || redirectToLogin();
UOJUserBlog::userIsOwner(Auth::user()) || UOJUser::checkPermission(Auth::user(), 'blogs.view') || UOJResponse::page403();

$blogs_pag = new Paginator([
	'col_names' => ['*'],
	'table_name' => 'blogs',
	'cond' => "poster = '" . UOJUserBlog::id() . "' and is_hidden = 0",
	'tail' => 'order by post_time desc',
	'page_len' => 5
]);

$all_tags = DB::selectAll("select distinct tag from blogs_tags where blog_id in (select id from blogs where $blogs_cond)");
?>
<?php echoUOJPageHeader(UOJUserBlog::id() . '的博客') ?>

<div class="row">
	<div class="col-lg-9">
		<?php if ($blogs_pag->isEmpty()) : ?>
			<div class="text-muted">此人很懒，什么博客也没留下。</div>
		<?php else : ?>
			<?php
			foreach ($blogs_pag->get() as $blog_info) {
				$blog = new UOJBlog($blog_info);
				$blog->echoView(['is_preview' => true]);
			}
			?>
		<?php endif ?>
		<?= $blogs_pag->pagination() ?>
	</div>
	<div class="col-lg-3">
		<img class="media-object img-thumbnail center-block uoj-user-avatar" alt="<?= UOJUserBlog::id() ?> Avatar" src="<?= HTML::avatar_addr(UOJUserBlog::user(), 512) ?>" />
		<?php if (UOJUserBlog::userCanManage(Auth::user()) && UOJUser::checkPermission(Auth::user(), 'blogs.create')) : ?>
			<div class="btn-group d-flex mt-3">
				<a href="<?= HTML::blog_url(UOJUserBlog::id(), '/post/new/write') ?>" class="btn btn-primary">
					<i class="bi bi-pencil-square"></i>
					写新博客
				</a>
				<a href="<?= HTML::blog_url(UOJUserBlog::id(), '/slide/new/write') ?>" class="btn btn-primary">
					<i class="bi bi-file-earmark-slides"></i>
					写新幻灯片
				</a>
			</div>
		<?php endif ?>
		<div class="card border-info mt-3">
			<div class="card-header bg-info">标签</div>
			<div class="card-body">
				<?php if ($all_tags) : ?>
					<?php foreach ($all_tags as $tag) : ?>
						<?php echoBlogTag($tag['tag']) ?>
					<?php endforeach ?>
				<?php else : ?>
					<div class="text-muted">暂无</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>

<?php echoUOJPageFooter() ?>
