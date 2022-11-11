<?php
if ($is_preview) {
	$readmore_pos = strpos($blog->content['content'], '<!-- readmore -->');
	if ($readmore_pos !== false) {
		$content = substr($blog->content['content'], 0, $readmore_pos) . '<p><a href="/blog/' . $blog->info['id'] . '">阅读更多……</a></p>';
	} else {
		$content = $blog->content['content'];
	}
} else {
	$content = $blog->content['content'];
}

$extra_text = $blog->info['is_hidden'] ? '<span class="text-muted">[已隐藏]</span> ' : '';
?>

<h1>
	<?= $extra_text ?>
	<a class="header-a text-decoration-none text-body" href="<?= HTML::blog_url($blog->info['poster'], '/post/' . $blog->info['id']) ?>">
		<?= $blog->info['title'] ?>
	</a>
</h1>

<div><?= $blog->info['post_time'] ?> <strong>By</strong> <?= getUserLink($blog->info['poster']) ?> (<strong>博客 ID: </strong> <?= $blog->info['id'] ?>)</div>
<?php if (!$show_title_only) : ?>
	<div class="card mb-4">
		<div class="card-body">
			<?php if ($blog->isTypeB()) : ?>

				<!-- content -->
				<article class="markdown-body">
					<?= $content ?>
				</article>
				<!-- content end -->

			<?php elseif ($blog->isTypeS()) : ?>

				<!-- slide -->
				<article>
					<div class="ratio ratio-16x9">
						<iframe class="embed-responsive-item" src="<?= HTML::blog_url($blog->info['poster'], '/slide/' . $blog->info['id']) ?>"></iframe>
					</div>
					<div class="text-end mt-2">
						<a class="btn btn-secondary btn-md" href="<?= HTML::blog_url($blog->info['poster'], '/slide/' . $blog->info['id']) ?>">
							<i class="bi bi-arrows-fullscreen"></i>
							全屏
						</a>
					</div>
				</article>
				<!-- slide end -->

			<?php endif ?>
		</div>
		<div class="card-footer text-end text-right">
			<ul class="list-inline mb-0">
				<li class="list-inline-item">
					<?php foreach ($blog->tags as $tag) : ?>
						<?php echoBlogTag($tag) ?>
					<?php endforeach ?>
				</li>
				<?php if ($is_preview) : ?>
					<li class="list-inline-item">
						<a class="text-decoration-none" href="<?= HTML::blog_url($blog->info['poster'], '/post/' . $blog->info['id']) ?>">
							阅读全文
						</a>
					</li>
				<?php endif ?>
				<?php if ($blog->userCanManage(Auth::user())) : ?>
					<li class="list-inline-item">
						<a class="text-decoration-none" href="<?= HTML::blog_url($blog->info['poster'], '/' . ($blog->info['type'] == 'B' ? 'post' : 'slide') . '/' . $blog->info['id'] . '/write') ?>">
							修改
						</a>
					</li>
					<li class="list-inline-item">
						<a class="text-decoration-none" href="<?= HTML::blog_url($blog->info['poster'], '/post/' . $blog->info['id'] . '/delete') ?>">
							删除
						</a>
					</li>
				<?php endif ?>
				<li class="list-inline-item">
					<?= ClickZans::getBlock('B', $blog->info['id'], $blog->info['zan']) ?>
				</li>
			</ul>
		</div>
	</div>
<?php endif ?>
