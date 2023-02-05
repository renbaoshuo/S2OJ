<?php
requirePHPLib('form');
?>
<?php echoUOJPageHeader(UOJLocale::get('announcements')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<?= UOJLocale::get('announcements') ?>
		</h1>

		<?php
		echoLongTable(
			['blogs.id', 'poster', 'title', 'post_time', 'zan', 'level'],
			'important_blogs, blogs',
			[
				'is_hidden' => 0,
				'important_blogs.blog_id' => DB::raw('blogs.id')
			],
			'order by level desc, important_blogs.blog_id desc',
			<<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
EOD,
			function ($info) {
				$blog = new UOJBlog($info);

				echo '<tr>';
				echo '<td>' . $blog->getLink(['show_level' => true, 'show_new_tag' => true]) . '</td>';
				echo '<td>' . UOJUser::getLink($blog->info['poster']) . '</td>';
				echo '<td>' . $blog->info['post_time'] . '</td>';
				echo '</tr>';
			},
			[
				'page_len' => 40,
				'div_classes' => ['card', 'my-3'],
				'table_classes' => ['table', 'uoj-table', 'mb-0'],
			]
		);
		?>

	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
