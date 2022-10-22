<?php
	requirePHPLib('form');
	requireLib('bootstrap5');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}
	
	if (!UOJContext::hasBlogPermission()) {
		become403Page();
	}
	if (isset($_GET['id'])) {
		if (!validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHisSlide($blog)) {
			become404Page();
		}
	}
	
	$blog_editor = new UOJBlogEditor();
	$blog_editor->type = 'slide';
	$blog_editor->name = 'blog';
	if ($blog) {
		$blog_editor->cur_data = array(
			'title' => $blog['title'],
			'content_md' => $blog['content_md'],
			'content' => $blog['content'],
			'tags' => queryBlogTags($blog['id']),
			'is_hidden' => $blog['is_hidden']
		);
	} else {
		$blog_editor->cur_data = array(
			'title' => '新幻灯片',
			'content_md' => '',
			'content' => '',
			'tags' => array(),
			'is_hidden' => true
		);
	}
	if ($blog) {
		$blog_editor->blog_url = HTML::blog_url(UOJContext::user()['username'], "/post/{$blog['id']}");
	} else {
		$blog_editor->blog_url = null;
	}
	
	function updateBlog($id, $data) {
		DB::update("update blogs set title = '".DB::escape($data['title'])."', content = '".DB::escape($data['content'])."', content_md = '".DB::escape($data['content_md'])."', is_hidden = {$data['is_hidden']} where id = {$id}");
	}
	function insertSlide($data) {
		DB::insert("insert into blogs (type, title, content, content_md, poster, is_hidden, post_time) values ('S', '".DB::escape($data['title'])."', '".DB::escape($data['content'])."', '".DB::escape($data['content_md'])."', '".Auth::id()."', {$data['is_hidden']}, now())");
	}
	
	$blog_editor->save = function($data) {
		global $blog;
		$ret = array();
		if ($blog) {
			updateBlog($blog['id'], $data);
		} else {
			insertSlide($data);
			$blog = array('id' => DB::insert_id(), 'tags' => array());
			$ret['blog_write_url'] = HTML::blog_url(UOJContext::user()['username'], "/slide/{$blog['id']}/write");
			$ret['blog_url'] = HTML::blog_url(UOJContext::user()['username'], "/slide/{$blog['id']}");
		}
		if ($data['tags'] !== $blog['tags']) {
			DB::delete("delete from blogs_tags where blog_id = {$blog['id']}");
			foreach ($data['tags'] as $tag) {
				DB::insert("insert into blogs_tags (blog_id, tag) values ({$blog['id']}, '".DB::escape($tag)."')");
			}
		}
		return $ret;
	};
	
	$blog_editor->runAtServer();
	?>
<?php echoUOJPageHeader('写幻灯片') ?>
<div class="text-end">
<a class="text-decoration-none" href="http://uoj.ac/blog/75">这玩意儿怎么用？</a>
</div>
<?php $blog_editor->printHTML() ?>
<?php echoUOJPageFooter() ?>
