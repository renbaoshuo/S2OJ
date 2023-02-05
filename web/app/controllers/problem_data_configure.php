<?php
requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$problem_configure = new UOJProblemConfigure(UOJProblem::cur());
$problem_configure->runAtServer();
$problem_conf_str = '';

foreach ($problem_configure->problem_conf->conf as $key => $val) {
	if ($key == 'use_builtin_judger' && $val == 'off') {
		continue;
	}

	if ($key == 'use_builtin_checker' && $val == 'ownchk') {
		continue;
	}

	$problem_conf_str .= "{$key} {$val}\n";
}
?>

<?php echoUOJPageHeader('数据配置 - ' . HTML::stripTags(UOJProblem::cur()->getTitle(['with' => 'id']))) ?>

<h1>
	<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?> 数据配置
</h1>

<div class="row mt-3">
	<div class="col-12 col-md-4">
		<div class="card">
			<div class="card-header fw-bold">problem.conf 预览</div>

			<div class="card-body p-0" id="problem-conf-preview">
				<pre class="bg-light mb-0 p-3"><code><?= $problem_conf_str ?></code></pre>
			</div>

			<div class="card-footer bg-transparent small text-muted">
				此处显示的 <code>problem.conf</code> 为根据右侧填写的配置信息生成的内容预览，并非题目当前实际配置文件。
			</div>
		</div>
	</div>
	<div class="col-12 col-md-8 mt-3 mt-md-0">
		<?php $problem_configure->printHTML() ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
