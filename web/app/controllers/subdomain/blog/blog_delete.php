<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();

UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJBlog::cur()->belongsToUserBlog() || UOJResponse::page404();
UOJBlog::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$delete_form = new UOJBs4Form('delete');
$delete_form->handle = function () {
	UOJBlog::cur()->delete();
};
$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
$delete_form->submit_button_config['text'] = '是的，我确定要删除';
$delete_form->succ_href = HTML::blog_url(UOJBlog::info('poster'), '/archive');

$delete_form->runAtServer();
?>
<?php echoUOJPageHeader('删除博客 - ' . HTML::stripTags(UOJBlog::info('title'))) ?>

<h1 class="h2 text-center">
	您真的要删除博客 “<?= UOJBlog::info('title') ?>” <span class="fs-5">（博客 ID：<?= UOJBlog::info('id') ?>）</span>吗？该操作不可逆！
</h1>

<?php $delete_form->printHTML(); ?>

<?php echoUOJPageFooter() ?>
