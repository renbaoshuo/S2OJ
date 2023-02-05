<?php
requirePHPLib('form');

Auth::check() || redirectToLogin();
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

		<?php
		echoLongTable(
			['id', 'poster', 'title', 'post_time', 'zan', 'is_hidden'],
			'blogs',
			'1',
			'order by post_time desc',
			<<<EOD
				<tr>
					<th>标题</th>
					<th style="width:200px">发表者</th>
					<th style="width:200px">发表日期</th>
					<th style="width:50px" class="text-center">评价</th>
				</tr>
			EOD,
			function ($info) {
				$blog = new UOJBlog($info);

				echo '<tr>';
				echo '<td>';
				echo $blog->getLink();
				if ($blog->info['is_hidden']) {
					echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
				}
				echo '</td>';
				echo '<td>' . UOJUser::getLink($blog->info['poster']) . '</td>';
				echo '<td>' . $blog->info['post_time'] . '</td>';
				echo '<td class="text-center">' . ClickZans::getCntBlock($blog->info['zan']) . '</td>';
				echo '</tr>';
			},
			[
				'page_len' => 10,
				'div_classes' => ['card', 'my-3', 'table-responsive'],
				'table_classes' => ['table', 'uoj-table', 'mb-0'],
				'post_filter' => function ($info) {
					return (new UOJBlog($info))->userCanView(Auth::user());
				},
			]
		);
		?>

	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
