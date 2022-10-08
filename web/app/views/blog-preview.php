<?php
	if ($is_preview) {
		$readmore_pos = strpos($blog['content'], '<!-- readmore -->');
		if ($readmore_pos !== false) {
			$content = substr($blog['content'], 0, $readmore_pos).'<p><a href="'.HTML::blog_url(UOJContext::userid(), '/post/'.$blog['id']).'">阅读更多……</a></p>';
		} else {
			$content = $blog['content'];
		}
	} else {
		$content = $blog['content'];
	}
	
	$extra_text = $blog['is_hidden'] ? '<span class="text-muted">[已隐藏]</span> ' : '';
	
	$blog_type = $blog['type'] == 'B' ? 'post' : 'slide';
	?>
<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<link rel="stylesheet" type="text/css" href="<?= HTML::url('/css/markdown.css') ?>">
<?php endif ?>

<h1 class="h2">
	<?= $extra_text ?>
	<a class="header-a
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
text-decoration-none text-body
<?php endif ?>
	" href="<?= HTML::blog_url(UOJContext::userid(), '/post/'.$blog['id']) ?>">
		<?= $blog['title'] ?>
	</a>
</h1>

<div><?= $blog['post_time'] ?> <strong>By</strong> <?= getUserLink($blog['poster']) ?> (<strong>博客 ID: </strong> <?= $blog['id'] ?>)</div>
<?php if (!$show_title_only): ?>
<div class="card mb-4">
	<div class="card-body">
		<?php if ($blog_type == 'post'): ?>

			<!-- content -->
			<article class="markdown-body">
				<?= $content ?>
			</article>

			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			<script>
				$(document).ready(function() {
					$('.markdown-body table').each(function() {
						$(this).addClass('table table-bordered');
					});
				});
			</script>
			<?php endif ?>
			<!-- content end -->

		<?php elseif ($blog_type == 'slide'): ?>
		<article>
			<div class="
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			ratio ratio-16x9
			<?php else: ?>
			embed-responsive embed-responsive-16by9
			<?php endif ?>
			">
				<iframe class="embed-responsive-item" src="<?= HTML::blog_url(UOJContext::userid(), '/slide/'.$blog['id']) ?>"></iframe>
			</div>
			<div class="
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			text-end mt-2
			<?php else: ?>
			text-right top-buffer-sm
			<?php endif ?>">
				<a class="btn btn-secondary btn-md" href="<?= HTML::blog_url(UOJContext::userid(), '/slide/'.$blog['id']) ?>">
					<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
					<i class="bi bi-arrows-fullscreen"></i>
					<?php else: ?>
					<span class="glyphicon glyphicon-fullscreen"></span>
					<?php endif ?>
					全屏
				</a>
			</div>
		</article>
		<?php endif ?>
	</div>
	<div class="card-footer
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
text-end
<?php else: ?>
text-right
<?php endif ?>">
		<ul class="list-inline 
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
mb-0
<?php else: ?>
bot-buffer-no
<?php endif ?>">
			<li class="list-inline-item">
			<?php foreach (queryBlogTags($blog['id']) as $tag): ?>
				<?php echoBlogTag($tag) ?>
			<?php endforeach ?>
			</li>
			<?php if ($is_preview): ?>
  			<li class="list-inline-item">
				<a class="
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
text-decoration-none
<?php endif ?>
	" href="<?= HTML::blog_url(UOJContext::userid(), '/post/'.$blog['id']) ?>">
					阅读全文
				</a>
			</li>
  			<?php endif ?>
  			<?php if (Auth::check() && (isSuperUser(Auth::user()) || Auth::id() == $blog['poster'])): ?>
			<li class="list-inline-item">
				<a class="
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
text-decoration-none
<?php endif ?>
	" href="<?=HTML::blog_url(UOJContext::userid(), '/'.$blog_type.'/'.$blog['id'].'/write')?>">
					修改
				</a>
			</li>
			<li class="list-inline-item">
				<a class="
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
text-decoration-none
<?php endif ?>
	" href="<?=HTML::blog_url(UOJContext::userid(), '/post/'.$blog['id'].'/delete')?>">
					删除
				</a>
			</li>
			<?php endif ?>
  			<li class="list-inline-item"><?= getClickZanBlock('B', $blog['id'], $blog['zan']) ?></li>
		</ul>
	</div>
</div>
<?php endif ?>
