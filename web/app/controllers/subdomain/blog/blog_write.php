<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJUserBlog::userCanManage(Auth::user()) || UOJResponse::page403();

if (isset($_GET['id'])) {
	UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
	UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
	UOJBlog::info('type') == 'B' || UOJResponse::page404();
	$blog = UOJBlog::info();
	$blog['content'] = UOJBlog::cur()->queryContent()['content'];
	$blog['content_md'] = UOJBlog::cur()->queryContent()['content_md'];
} else {
	UOJUser::checkPermission(Auth::user(), 'blogs.create') || UOJResponse::page403();
	isSuperUser(Auth::user()) || UOJUserBlog::userIsOwner(Auth::user()) || UOJResponse::page403();
}

$blog_editor = new UOJBlogEditor();
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
		'title' => $_GET['title'] ?: '新博客',
		'content_md' => '',
		'content' => '',
		'tags' => [],
		'is_hidden' => isset($_GET['is_hidden']) ? $_GET['is_hidden'] : true,
	);
}
if ($blog) {
	$blog_editor->blog_url = HTML::blog_url(UOJUserBlog::id(), "/post/{$blog['id']}");
} else {
	$blog_editor->blog_url = null;
}

function updateBlog($id, $data) {
	DB::update([
		"update blogs",
		"set", [
			"title" => $data['title'],
			"content" => $data['content'],
			"content_md" => $data['content_md'],
			"is_hidden" => $data['is_hidden']
		],
		"where", ["id" => $id]
	]);
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

$blog_editor->save = function ($data) {
	global $blog;
	$ret = [];
	if ($blog) {
		updateBlog($blog['id'], $data);
	} else {
		insertBlog($data);
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
<?php echoUOJPageHeader('写博客') ?>

<div class="card">
	<div class="card-header bg-transparent d-flex justify-content-between">
		<div class="fw-bold">写博客</div>
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

<!-- 提示信息 -->
<div class="card mt-3">
	<div class="card-body">
		<h2 class="h3 card-title">提示</h2>
		<ol>
			<li>题解发布后还需要返回对应题目的题解页面 <b>手动输入博客 ID</b> 来将本文添加到题目的题解列表中（博客 ID 可以在右上角找到）；</li>
			<li>请勿引用不稳定的外部资源（如来自个人服务器的图片或文档等），以便备份及后期维护；</li>
			<li>请勿在博文中直接插入大段 HTML 代码，这可能会破坏页面的显示，可以考虑使用 <a class="text-decoration-none" href="/html2markdown" target="_blank">转换工具</a> 转换后再作修正；</li>
			<li>图片上传推荐使用 <a class="text-decoration-none" href="/image_hosting" target="_blank">S2OJ 图床</a>，以免后续产生外链图片大量失效的情况。</li>
		</ol>
		<p class="card-text">
			帮助：<a class="text-decoration-none" href="http://uoj.ac/blog/7">UOJ 博客使用教程</a>。
		</p>
	</div>
</div>

<?php echoUOJPageFooter() ?>
