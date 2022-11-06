<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanRegister(Auth::user(), ['ensure' => true]);

$register_form = new UOJBs4Form('register');
$register_form->handle = function () {
	UOJContest::cur()->userRegister(Auth::user());
};
$register_form->submit_button_config['class_str'] = 'btn btn-primary';
$register_form->submit_button_config['text'] = '我已阅读规则，确认报名比赛';

if (UOJContest::cur()->progress() == CONTEST_IN_PROGRESS) {
	$register_form->succ_href = '/contest/' . UOJContest::info('id') . '/confirm';
} else {
	$register_form->succ_href = '/contests';
}

$register_form->runAtServer();
?>

<?php echoUOJPageHeader('报名 - ' . UOJContest::info('name')) ?>

<div class="card mw-100 mx-auto" style="width:800px">
	<div class="card-body">
		<h1 class="card-title text-center mb-3">比赛规则</h1>

		<p class="card-text">您即将报名比赛 “<b><?= UOJContest::info('name') ?></b>”，请在报名前仔细阅读以下比赛规则：</p>

		<ul>
			<?php if (UOJContest::cur()->progress() == CONTEST_IN_PROGRESS) : ?>
				<li class="text-danger">本场比赛正在进行中，将于 <b><?= UOJContest::info('end_time_str') ?></b> 结束。</li>
			<?php else : ?>
				<li>本场比赛将于 <b><?= UOJContest::info('start_time_str') ?></b> 开始，并于 <b><?= UOJContest::info('end_time_str') ?></b> 结束。</li>
			<?php endif ?>
			<li>比赛开始后点击 “<b>确认参赛</b>” 按钮才会被视为正式参赛，未正式参赛的选手不会显示在排行榜上。</li>
			<?php if (UOJContest::cur()->basicRule() == 'OI') : ?>
				<li>本场比赛为 OI 赛制。比赛中途可以提交代码，但 <b>只显示测样例的结果</b>。</li>
			<?php elseif (UOJContest::cur()->basicRule() == 'IOI') : ?>
				<li>本场比赛为 IOI 赛制。比赛时的提交会测试题目的全部数据，但无法查看数据点详情。</li>
			<?php elseif (UOJContest::cur()->basicRule() == 'ACM') : ?>
				<li>本场比赛为 ACM 赛制。</li>
			<?php endif ?>
			<li>若选手在比赛中多次提交了同一题，则最后按照 <b>最后一次不是 Compile Error 的提交</b> 计算排行。</li>
			<li>比赛结束后会进行最终测试，最终测试后的排名为最终排名。</li>
			<li>比赛排名按分数为第一关键字，完成题目的总时间为第二关键字。完成题目的总时间等于完成每道题所花时间之和（无视掉爆零的题目）。</li>
			<li>请遵守比赛规则，一位选手在一场比赛内不得报名多个账号，选手之间不能交流或者抄袭代码，如果被检测到将以 0 分处理或者封禁。</li>
		</ul>

		<?php $register_form->printHTML() ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
