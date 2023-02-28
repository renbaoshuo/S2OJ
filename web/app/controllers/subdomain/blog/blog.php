<?php
requireLib('mathjax');
requireLib('hljs');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
UOJBlog::cur()->userCanView(Auth::user()) || UOJResponse::page403();

$blog = UOJBlog::info();
$purifier = HTML::purifier();
$parsedown = HTML::parsedown([
	'username_with_color' => true,
]);

function getCommentContentToDisplay($comment) {
	global $purifier, $parsedown;

	$rendered = $purifier->purify($parsedown->text($comment['content']));

	if ($comment['is_hidden']) {
		$esc_hide_reason = $comment['reason_to_hide'];
		$res = <<<EOD
		<div class="alert alert-warning d-flex align-items-center my-0" role="alert">
			<div class="flex-shrink-0 me-3">
				<i class="fs-4 bi bi-exclamation-triangle-fill"></i>
			</div>
			<div>
				<div class="fw-bold mb-2">该评论被隐藏</div>
				<div class="small">{$esc_hide_reason}</div>
			</div>
		</div>
		EOD;

		if (UOJUserBlog::userHasManagePermission(Auth::user())) {
			$res .= <<<EOD
			<div class="mt-2">{$rendered}</div>
			EOD;
		}

		return $res;
	}

	return $rendered;
}

$comment_form = new UOJForm('comment');
$comment_form->addTextArea('comment', [
	'label' => '内容',
	'help' => '评论支持 Markdown 语法。可以用 <code>@mike</code> 来提到 <code>mike</code> 这个用户，<code>mike</code> 会被高亮显示。如果你真的想打 <code>@</code> 这个字符，请用 <code>@@</code>。',
	'validator_php' => function ($comment) {
		if (!Auth::check()) {
			return '请先登录';
		}
		if (!$comment) {
			return '评论不能为空';
		}
		if (strlen($comment) > 2000) {
			return '不能超过 2000 个字节';
		}
		return '';
	},
]);
$comment_form->handle = function () {
	global $blog, $comment_form;
	$comment = HTML::escape($_POST['comment']);

	list($comment, $referrers) = uojHandleAtSign($comment, "/post/{$blog['id']}");

	DB::insert([
		"insert into blogs_comments",
		"(poster, blog_id, content, reply_id, post_time)",
		"values", DB::tuple([Auth::id(), $blog['id'], $comment, 0, DB::now()])
	]);
	$comment_id = DB::insert_id();

	$rank = DB::selectCount([
		"select count(*) from blogs_comments",
		"where", [
			"blog_id" => $blog['id'],
			"reply_id" => 0,
			["id", "<", $comment_id]
		]
	]);
	$page = floor($rank / 20) + 1;

	$uri = getLongTablePageUri($page) . '#' . "comment-{$comment_id}";
	$user_link = UOJUser::getLink(Auth::user(), ['color' => false]);

	foreach ($referrers as $referrer) {
		$content = $user_link . ' 在博客 ' . $blog['title'] . ' 的评论里提到你：<a href="' . $uri . '">点击此处查看</a>';
		sendSystemMsg($referrer, '有人提到你', $content);
	}

	if ($blog['poster'] !== Auth::id()) {
		$content = $user_link . ' 回复了您的博客 ' . $blog['title'] . ' ：<a href="' . $uri . '">点击此处查看</a>';
		sendSystemMsg($blog['poster'], '博客新回复通知', $content);
	}

	UOJBlog::cur()->updateActiveTime();
	$comment_form->succ_href = getLongTablePageRawUri($page);
};
$comment_form->config['ctrl_enter_submit'] = true;
$comment_form->runAtServer();

$reply_form = new UOJForm('reply');
$reply_form->addHidden(
	'reply_id',
	'0',
	function ($reply_id, &$vdata) {
		global $blog;
		if (!validateUInt($reply_id) || $reply_id == 0) {
			return '您要回复的对象不存在';
		}
		$comment = UOJBlogComment::query($reply_id);
		if (!$comment || $comment->info['blog_id'] != $blog['id']) {
			return '您要回复的对象不存在';
		}
		$vdata['parent'] = $comment;
		return '';
	},
	null
);
$reply_form->addTextArea('reply_comment', [
	'label' => '内容',
	'validator_php' => function ($comment) {
		if (!Auth::check()) {
			return '请先登录';
		}
		if (!$comment) {
			return '评论不能为空';
		}
		if (strlen($comment) > 1000) {
			return '不能超过 1000 个字节';
		}
		return '';
	},
]);
$reply_form->handle = function (&$vdata) {
	global $blog, $reply_form;
	$comment = HTML::escape($_POST['reply_comment']);

	list($comment, $referrers) = uojHandleAtSign($comment, "/post/{$blog['id']}");

	$reply_id = $_POST['reply_id'];

	DB::insert([
		"insert into blogs_comments",
		"(poster, blog_id, content, reply_id, post_time)",
		"values", DB::tuple([Auth::id(), $blog['id'], $comment, $reply_id, DB::now()])
	]);
	$comment_id = DB::insert_id();

	$rank = DB::selectCount([
		"select count(*) from blogs_comments",
		"where", [
			"blog_id" => $blog['id'],
			"reply_id" => 0,
			["id", "<", $reply_id]
		]
	]);
	$page = floor($rank / 20) + 1;

	$uri = getLongTablePageUri($page) . '#' . "comment-{$reply_id}";
	$user_link = UOJUser::getLink(Auth::user(), ['color' => false]);

	foreach ($referrers as $referrer) {
		$content = $user_link . ' 在博客 ' . $blog['title'] . ' 的评论里提到你：<a href="' . $uri . '">点击此处查看</a>';
		sendSystemMsg($referrer, '有人提到你', $content);
	}

	$parent = $vdata['parent'];
	$notified = [];
	if ($parent->info['poster'] !== Auth::id()) {
		$notified[] = $parent->info['poster'];
		$content = $user_link . ' 回复了您在博客 ' . $blog['title'] . ' 下的评论 ：<a href="' . $uri . '">点击此处查看</a>';
		sendSystemMsg($parent->info['poster'], '评论新回复通知', $content);
	}
	if ($blog['poster'] !== Auth::id() && !in_array($blog['poster'], $notified)) {
		$notified[] = $blog['poster'];
		$content = $user_link . ' 回复了您的博客 ' . $blog['title'] . ' ：<a href="' . $uri . '">点击此处查看</a>';
		sendSystemMsg($blog['poster'], '博客新回复通知', $content);
	}

	UOJBlog::cur()->updateActiveTime();

	$reply_form->succ_href = getLongTablePageRawUri($page);
};
$reply_form->config['ctrl_enter_submit'] = true;
$reply_form->runAtServer();

if (UOJUserBlog::userHasManagePermission(Auth::user())) {
	$hide_form = new UOJForm('hide');
	$hide_form->addHidden('comment_hide_id', '', 'validateCommentId', null);
	$hide_form->addSelect('comment_hide_type', [
		'label' => '隐藏理由',
		'options' => UOJBlogComment::HIDE_REASONS,
		'default_value' => 'spam',
	]);
	$hide_form->addInput('comment_hide_reason', [
		'div_class' => 'mt-3',
		'label' => '自定义隐藏理由（当上方隐藏理由为自定义时有效）',
		'default_value' => '该评论由于违反社区规定，已被管理员隐藏',
		'validator_php' => 'validateString',
	]);
	$hide_form->handle = function (&$vdata) {
		$comment = $vdata['comment_hide_id'];

		if ($_POST['comment_hide_type'] == 'unhide') {
			$reason = '';
		} else if ($_POST['comment_hide_type'] == 'other') {
			$reason = $_POST['comment_hide_reason'];
		} else {
			$reason = '该评论由于' . UOJBlogComment::HIDE_REASONS[$_POST['comment_hide_type']] . '，已被管理员隐藏';
		}

		$comment->hide($reason);

		if ($_POST['comment_hide_type'] != 'unhide') {
			sendSystemMsg(
				$comment->info['poster'],
				'评论隐藏通知',
				"您为博客 " . UOJBlog::cur()->getLink() . " 回复的评论 “" . substr($comment->info['content'], 0, 30) . "……” 已被管理员隐藏，隐藏原因为 “{$reason}”。"
			);
		}
	};
	$hide_form->runAtServer();
}

$comments_pag = new Paginator([
	'col_names' => ['*'],
	'table_name' => 'blogs_comments',
	'cond' => 'blog_id = ' . $blog['id'] . ' and reply_id = 0',
	'tail' => 'order by id asc',
	'page_len' => 20
]);
?>

<?php echoUOJPageHeader(HTML::stripTags($blog['title']) . ' - 博客') ?>

<script>
	var user_can_hide_comment = <?= json_encode(isset($hide_form)) ?>;
</script>

<?php UOJBlog::cur()->echoView(['show_title_only' => isset($_GET['page']) && $_GET['page'] != 1]) ?>

<?php if (isset($hide_form)) : ?>
	<div class="modal fade" id="HideCommentModal" tabindex="-1" aria-labelledby="HideCommentModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h1 class="modal-title fs-5" id="HideCommentModalLabel">隐藏评论</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<div class="mb-2">原评论（ID: <span id="span-comment_hide_id"></span>）：</div>
						<blockquote id="HideCommentModalOriginalComment" class="border-start border-3 ps-3 text-muted"></blockquote>
					</div>

					<hr>

					<?php $hide_form->printHTML(); ?>
				</div>
			</div>
		</div>
	</div>
<?php endif ?>

<h2>
	评论
	<i class="bi bi-chat-fill"></i>
</h2>

<div class="list-group">
	<?php if ($comments_pag->isEmpty()) : ?>
		<div class="list-group-item text-muted">暂无评论</div>
	<?php else : ?>
		<?php foreach ($comments_pag->get() as $comment) :
			$poster = UOJUser::query($comment['poster']);
			$esc_email = HTML::escape($poster['email']);
			$asrc = HTML::avatar_addr($poster, 80);

			$replies = DB::selectAll([
				"select id, poster, content, post_time, is_hidden, reason_to_hide, zan from blogs_comments",
				"where", ["reply_id" => $comment['id']],
				"order by id"
			]);
			foreach ($replies as $idx => $reply) {
				$reply_user = UOJUser::query($reply['poster']);
				$replies[$idx]['poster_avatar'] = HTML::avatar_addr($reply_user, 80);
				$replies[$idx]['poster_realname'] = $reply_user['realname'];
				$replies[$idx]['poster_username_color'] = UOJUser::getUserColor($reply_user);
				$replies[$idx]['content'] = getCommentContentToDisplay($reply);
				$replies[$idx]['click_zan_block'] = ClickZans::getBlock('BC', $reply['id'], $reply['zan']);
			}
			$replies_json = json_encode($replies);
		?>
			<div id="comment-<?= $comment['id'] ?>" class="list-group-item">
				<div class="d-flex">
					<div class="d-none d-sm-block mr-3 flex-shrink-0">
						<a href="<?= HTML::url('/user/' . $poster['username']) ?>">
							<img class="rounded uoj-user-avatar" src="<?= $asrc ?>" alt="Avatar of <?= $poster['username'] ?>" width="64" height="64" />
						</a>
					</div>
					<div id="comment-body-<?= $comment['id'] ?>" class="flex-grow-1 ms-3">
						<div class="row justify-content-between flex-wrap">
							<div class="col-auto">
								<?= UOJUser::getLink($poster['username']) ?>
							</div>
							<div class="col-auto">
								<?= ClickZans::getBlock('BC', $comment['id'], $comment['zan']) ?>
							</div>
						</div>
						<div class="comment-content markdown-body my-2" id="comment-content-<?= $comment['id'] ?>"><?= getCommentContentToDisplay($comment) ?></div>
						<ul class="list-inline mb-0 text-end">
							<li class="list-inline-item small text-muted">
								<?= $comment['post_time'] ?>
							</li>
							<?php if (isset($hide_form)) : ?>
								<li class="list-inline-item">
									<a href="#" class="text-warning-emphasis text-decoration-none p-0 uoj-blog-hide-comment-btn" data-comment-id="<?= $comment['id'] ?>">
										隐藏
									</a>
								</li>
							<?php endif ?>
							<li class="list-inline-item">
								<a id="reply-to-<?= $comment['id'] ?>" href="#">
									回复
								</a>
							</li>
						</ul>
						<?php if ($replies) : ?>
							<div id="replies-<?= $comment['id'] ?>" class="rounded bg-secondary-subtle mt-2 border"></div>
						<?php endif ?>
						<script>
							showCommentReplies('<?= $comment['id'] ?>', <?= $replies_json ?>);
						</script>
					</div>
				</div>
			</div>
		<?php endforeach ?>
	<?php endif ?>
</div>
<?= $comments_pag->pagination() ?>

<h3 class="mt-4">发表评论</h3>
<?php $comment_form->printHTML() ?>

<div id="div-form-reply" style="display:none">
	<?php $reply_form->printHTML() ?>
</div>

<script>
	$('.uoj-blog-hide-comment-btn').each(function() {
		$(this).click(function(event) {
			var comment_id = $(this).data('comment-id');

			event.preventDefault();
			toggleModalHideComment(comment_id, $('#comment-content-' + comment_id).html());
		});
	})
</script>

<?php echoUOJPageFooter() ?>
