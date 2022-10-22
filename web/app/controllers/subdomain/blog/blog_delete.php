<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!UOJContext::hasBlogPermission()) {
		become403Page();
	}
	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHis($blog)) {
		become404Page();
	}
	
	$delete_form = new UOJForm('delete');
	$delete_form->handle = function() {
		global $myUser, $blog;

		if ($myUser['username'] != $blog['poster']) {
			$poster_user_link = getUserLink($blog['poster']);
			$admin_user_link = isSuperUser($myUser) ? '管理员' : getUserLink($myUser['username']);
			$blog_content = HTML::escape($blog['content_md']);
			$content = <<<EOD
<p>{$poster_user_link} 您好：</p>
<p>您的博客 <b>{$blog['title']}</b>（ID：{$blog['id']}）已经被 {$admin_user_link} 删除，现将博客原文备份发送给您，请查收。</p>
<pre><code class="language-markdown">{$blog_content}</code></pre>
EOD;
			sendSystemMsg($blog['poster'], '博客删除通知', $content);
		}

		deleteBlog($blog['id']);
	};
	$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
	$delete_form->submit_button_config['text'] = '是的，我确定要删除';
	$delete_form->succ_href = HTML::blog_url($blog['poster'], '/archive');
	
	$delete_form->runAtServer();
	?>
<?php echoUOJPageHeader('删除博客 - ' . HTML::stripTags($blog['title'])) ?>
<h1 class="h3 text-center">
	您真的要删除博客 “<?= $blog['title'] ?>” <span class="fs-5">（博客 ID：<?= $blog['id'] ?>）</span>吗？该操作不可逆！
</h1>
<?php $delete_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
