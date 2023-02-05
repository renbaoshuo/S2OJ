<?php
requireLib('calendar_heatmap');

Auth::check() || redirectToLogin();
UOJUserBlog::userIsOwner(Auth::user()) || UOJUser::checkPermission(Auth::user(), 'blogs.view') || UOJResponse::page403();
Auth::id() == $user['username'] || UOJUser::checkPermission(Auth::user(), 'users.view') || UOJResponse::page403();
?>
<?php echoUOJPageHeader('关于我') ?>

<?php uojIncludeView('user-info', ['user' => UOJUserBlog::user(), 'is_blog_aboutme' => true]) ?>

<?php echoUOJPageFooter() ?>
