<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);
	
	if (!Auth::check()) {
		redirectToLogin();
	} elseif (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	} elseif (hasRegistered($myUser, $contest)) {
		if ($contest['cur_progress'] < CONTEST_IN_PROGRESS) {
			redirectTo('/contests');
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			redirectTo("/contest/{$contest['id']}/confirm");
		} else {
			redirectTo("/contest/{$contest['id']}");
		}
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		redirectTo("/contest/{$contest['id']}");
	}
	
	$register_form = new UOJForm('register');
	$register_form->handle = function() use ($myUser, $contest) {
		DB::query("replace into contests_registrants (username, contest_id, has_participated) values ('{$myUser['username']}', {$contest['id']}, 0)");

		updateContestPlayerNum($contest);
	};
	$register_form->submit_button_config['class_str'] = 'btn btn-primary';
	$register_form->submit_button_config['text'] = '我已阅读规则，确认报名比赛';
	
	if ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
		$register_form->succ_href = "/contest/{$contest['id']}/confirm";
	} else {
		$register_form->succ_href = "/contests";
	}
	
	$register_form->runAtServer();
	?>
<?php echoUOJPageHeader('报名 - ' . HTML::stripTags($contest['name'])) ?>

<div class="card mw-100 mx-auto" style="width:800px">
<div class="card-body">
	<h1 class="h2 card-title text-center mb-3">比赛规则</h1>

	<p class="card-text">您即将报名比赛 “<b><?= $contest['name'] ?></b>”，请在报名前仔细阅读以下比赛规则：</p>

	<ul>
		<?php if ($contest['cur_progress'] == CONTEST_IN_PROGRESS): ?>
		<li class="text-danger">本场比赛正在进行中，将于 <b><?= $contest['end_time_str'] ?></b> 结束。</li>
		<?php else: ?>
		<li>本场比赛将于 <b><?= $contest['start_time_str'] ?></b> 开始，并于 <b><?= $contest['end_time_str'] ?></b> 结束。</li>
		<?php endif ?>
		<li>比赛开始后点击 “<b>确认参赛</b>” 按钮才会被视为正式参赛，未正式参赛的选手不会显示在排行榜上。</li>
		<?php if (!isset($contest['extra_config']['contest_type']) || $contest['extra_config']['contest_type'] == 'OI'): ?>
		<li>本场比赛为 OI 赛制。比赛中途可以提交代码，但 <b>只显示测样例的结果</b>。</li>
		<?php elseif ($contest['extra_config']['contest_type'] == 'IOI'): ?>
		<li>本场比赛为 IOI 赛制。比赛时的提交会测试题目的全部数据，但无法查看数据点详情。</li>
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
