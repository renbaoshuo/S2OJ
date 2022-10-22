<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	requireLib('bootstrap5');
	requirePHPLib('form');
	
	function echoBlogCell($blog) {
		$level = $blog['level'];
		
		switch ($level) {
			case 0:
				$level_str = '';
				break;
			case 1:
				$level_str = '<span style="color:red">[三级置顶]</span> ';
				break;
			case 2:
				$level_str = '<span style="color:red">[二级置顶]</span> ';
				break;
			case 3:
				$level_str = '<span style="color:red">[一级置顶]</span> ';
				break;
		}
		
		echo '<tr>';
		echo '<td>' . $level_str . getBlogLink($blog['id']) . '</td>';
		echo '<td>' . getUserLink($blog['poster']) . '</td>';
		echo '<td>' . $blog['post_time'] . '</td>';
		echo '</tr>';
	}
	$header = <<<EOD
	<tr>
		<th width="60%">标题</th>
		<th width="20%">发表者</th>
		<th width="20%">发表日期</th>
	</tr>
EOD;
	$config = [
		'page_len' => 40,
		'div_classes' => ['card', 'my-3'],
		'table_classes' => ['table', 'uoj-table', 'mb-0'],
	];
	?>
<?php echoUOJPageHeader(UOJLocale::get('announcements')) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<h1 class="h2">
	<?= UOJLocale::get('announcements') ?>
</h1>

<?php echoLongTable(array('blogs.id', 'poster', 'title', 'post_time', 'zan', 'level'), 'important_blogs, blogs', 'is_hidden = 0 and important_blogs.blog_id = blogs.id', 'order by level desc, important_blogs.blog_id desc', $header, 'echoBlogCell', $config); ?>

</div>
<!-- end left col -->

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar', array()) ?>
</aside>
<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
