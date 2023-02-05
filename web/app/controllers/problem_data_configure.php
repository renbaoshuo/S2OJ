<?php
requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$problem_configure = new UOJProblemConfigure(UOJProblem::cur());
$problem_configure->runAtServer();
?>

<?php echoUOJPageHeader('数据配置 - ' . HTML::stripTags(UOJProblem::cur()->getTitle(['with' => 'id']))) ?>

<h1>
	<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?> 数据配置
</h1>

<?php $problem_configure->printHTML() ?>

<?php echoUOJPageFooter() ?>
