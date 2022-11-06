<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJUserBlog::userCanManage(Auth::user()) || UOJResponse::page403();

if (isset($_GET['id'])) {
	UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
	UOJBlog::info('type') == 'S' || UOJResponse::page404();
	$blog = UOJBlog::info();
	$blog['content'] = UOJBlog::cur()->queryContent()['content'];
	$blog['content_md'] = UOJBlog::cur()->queryContent()['content_md'];
}

$blog_editor = new UOJBlogEditor();
$blog_editor->type = 'slide';
$blog_editor->name = 'blog';
if ($blog) {
	$blog_editor->cur_data = array(
		'title' => $blog['title'],
		'content_md' => $blog['content_md'],
		'content' => $blog['content'],
		'tags' => UOJBlog::cur()->queryTags(),
		'is_hidden' => $blog['is_hidden']
	);
} else {
	$blog_editor->cur_data = array(
		'title' => '新幻灯片',
		'content_md' => '',
		'content' => '',
		'tags' => [],
		'is_hidden' => true
	);
}
if ($blog) {
	$blog_editor->blog_url = HTML::blog_url(UOJUserBlog::id(), "/post/{$blog['id']}");
} else {
	$blog_editor->blog_url = null;
}

function insertBlog($data) {
	DB::insert([
		"insert into blogs",
		"(title, content, content_md, poster, is_hidden, post_time, active_time)",
		"values", DB::tuple([
			$data['title'], $data['content'], $data['content_md'],
			UOJUserBlog::id(), $data['is_hidden'], DB::now(), DB::now()
		])
	]);
}
function insertSlide($data) {
	DB::insert("insert into blogs (type, title, content, content_md, poster, is_hidden, post_time) values ('S', '" . DB::escape($data['title']) . "', '" . DB::escape($data['content']) . "', '" . DB::escape($data['content_md']) . "', '" . Auth::id() . "', {$data['is_hidden']}, now())");
}

$blog_editor->save = function ($data) {
	global $blog;
	$ret = [];
	if ($blog) {
		updateBlog($blog['id'], $data);
	} else {
		insertSlide($data);
		$blog_id = DB::insert_id();
		UOJBlog::query(strval($blog_id))->setAsCur();
		$ret['blog_id'] = $blog_id;
		$ret['blog_write_url'] = UOJBlog::cur()->getUriForWrite();
		$ret['blog_url'] = UOJBlog::cur()->getBlogUri();
	}
	UOJBlog::cur()->updateTags($data['tags']);
	return $ret;
};

$blog_editor->runAtServer();
?>
<?php echoUOJPageHeader('写幻灯片') ?>
<div class="text-end">
	<a class="text-decoration-none" href="http://uoj.ac/blog/75">这玩意儿怎么用？</a>
</div>

<div class="card">
	<div class="card-header bg-transparent d-flex justify-content-between">
		<div class="fw-bold">写幻灯片</div>
		<div id="div-blog-id" <?php if (!$blog) : ?> style="display: none" <?php endif ?>>
			<?php if ($blog) : ?>
				<small>博客 ID：<b><?= $blog['id'] ?></b></small>
			<?php endif ?>
		</div>
	</div>
	<div class="card-body">
		<?php $blog_editor->printHTML() ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
