<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	$REQUIRE_LIB['bootstrap5'] = '';
	
	$blogs_cond = "poster = '".UOJContext::userid()."'";
	if (!UOJContext::hasBlogPermission()) {
		$blogs_cond .= " and is_hidden = false";
	}
	
	$display_blogs_cond = $blogs_cond;
	
	if (isset($_GET['tag'])) {
		$blog_tag_required = $_GET['tag'];
		$display_blogs_cond .= " and '".DB::escape($blog_tag_required)."' in (select tag from blogs_tags where blogs_tags.blog_id = blogs.id)";
	} else {
		$blog_tag_required = null;
	}
	
	$blogs_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs',
		'cond' => $display_blogs_cond,
		'tail' => 'order by post_time desc',
		'page_len' => 15
	));
	
	$all_tags = DB::selectAll("select distinct tag from blogs_tags where blog_id in (select id from blogs where $blogs_cond)");
	
	requireLib('mathjax');
	requireLib('shjs');
	?>
<?php echoUOJPageHeader('日志') ?>

<div class="row">
	<div class="col-md-9">
		<?php if (!$blog_tag_required): ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="text-muted">此人很懒，什么博客也没留下。</div>
			<?php else: ?>
			<div class="card">
				<div class="card-body">
					<table class="table uoj-table">
						<thead>
							<tr>
								<th>标题</th>
								<th style="width: 20%">发表时间</th>
								<th class="text-center" style="width: 100px">评价</th>
							</tr>
						</thead>
						<tbody>
						<?php $cnt = 0 ?>
						<?php foreach ($blogs_pag->get() as $blog): ?>
							<?php $cnt++ ?>
							<tr>
								<td>
									<?php if ($blog['is_hidden']): ?>
									<span class="text-danger">[隐藏]</span>
									<?php endif ?>
									<?= getBlogLink($blog['id']) ?>
									<?php foreach (queryBlogTags($blog['id']) as $tag): ?>
										<?php echoBlogTag($tag) ?>
									<?php endforeach ?>
								</td>
								<td><?= $blog['post_time'] ?></td>
								<td><?= getClickZanBlock('B', $blog['id'], $blog['zan'], null, false) ?></td>
							</tr>
						<?php endforeach ?>
						</tbody>
					</table>
				</div>
				<div class="card-footer bg-transparent text-end text-muted">
					第 <?= $blogs_pag->cur_start + 1 ?> - <?= $blogs_pag->cur_start + $cnt ?> 篇，共 <?= $blogs_pag->n_rows ?> 篇博客
				</div>
			</div>
			<?php endif ?>
		<?php else: ?>
			<?php if ($blogs_pag->isEmpty()): ?>
			<div class="alert alert-danger">
				没有找到包含 “<?= HTML::escape($blog_tag_required) ?>” 标签的博客：
			</div>
			<?php else: ?>
			<div class="alert alert-success">
				共找到 <?= $blogs_pag->n_rows ?> 篇包含 “<?= HTML::escape($blog_tag_required) ?>” 标签的博客：
			</div>
			<div class="card">
			<div class="card-body">
			<table class="table uoj-table mb-0">
				<thead>
					<tr>
						<th>标题</th>
						<th style="width: 20%">发表时间</th>
						<th class="text-center" style="width: 180px">评价</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($blogs_pag->get() as $blog): ?>
					<tr>
						<td>
							<?php if ($blog['is_hidden']): ?>
							<span class="text-danger">[隐藏]</span>
							<?php endif ?>
							<?= getBlogLink($blog['id']) ?>
							<?php foreach (queryBlogTags($blog['id']) as $tag): ?>
								<?php echoBlogTag($tag) ?>
							<?php endforeach ?>
						</td>
						<td><?= $blog['post_time'] ?></td>
						<td><?= getClickZanBlock('B', $blog['id'], $blog['zan']) ?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
			</div>
			</div>
			<?php endif ?>
		<?php endif ?>
		
		<div class="text-center mt-3">
			<?= $blogs_pag->pagination() ?>
		</div>
	</div>
	<div class="col-md-3">
		<?php if (UOJContext::hasBlogPermission()): ?>
		<div class="btn-group d-flex">
			<a href="<?= HTML::blog_url(UOJContext::userid(), '/post/new/write') ?>" class="btn btn-primary">
				<i class="bi bi-pencil-square"></i>
				写新博客
			</a>
			<a href="<?= HTML::blog_url(UOJContext::userid(), '/slide/new/write') ?>" class="btn btn-primary">
				<i class="bi bi-file-earmark-slides"></i>
				写新幻灯片
			</a>
		</div>
		<?php endif ?>
		<div class="card border-info mt-3">
			<div class="card-header bg-info">标签</div>
			<div class="card-body">
			<?php if ($all_tags): ?>
			<?php foreach ($all_tags as $tag): ?>
				<?php echoBlogTag($tag['tag']) ?>
			<?php endforeach ?>
			<?php else: ?>
				<div class="text-muted">暂无</div>
			<?php endif ?>
			</div>
		</div>
	</div>
</div>
<?php echoUOJPageFooter() ?>
