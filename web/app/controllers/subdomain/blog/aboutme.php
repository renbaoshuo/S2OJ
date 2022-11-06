<?php
requireLib('bootstrap5');
requireLib('calendar_heatmap');

Auth::check() || redirectToLogin();
?>
<?php echoUOJPageHeader('关于我') ?>

<?php uojIncludeView('user-info', ['user' => UOJUserBlog::user(), 'is_blog_aboutme' => true]) ?>

<?php echoUOJPageFooter() ?>
