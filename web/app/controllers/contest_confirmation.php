<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	genMoreContestInfo($contest);
	
	if (!Auth::check()) {
		redirectToLogin();
	} elseif (!hasRegistered($myUser, $contest)) {
		redirectTo("/contest/{$contest['id']}/register");
	} elseif ($contest['cur_progress'] < CONTEST_IN_PROGRESS) {
		redirectTo('/contests');
	} elseif (hasParticipated($myUser, $contest) || $contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		redirectTo("/contest/{$contest['id']}");
	}

	$confirm_form = new UOJForm('confirm');
	$confirm_form->submit_button_config['class_str'] = 'btn btn-primary';
	$confirm_form->submit_button_config['margin_class'] = 'mt-3';
	$confirm_form->submit_button_config['text'] = '我已核对信息，确认参加比赛';
	$confirm_form->handle = function() use ($myUser, $contest) {
		DB::update("update contests_registrants set has_participated = 1 where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
	};
	$confirm_form->succ_href = "/contest/{$contest['id']}";
	$confirm_form->runAtServer();
	?>

<?php echoUOJPageHeader('确认参赛 - ' . HTML::stripTags($contest['name'])) ?>

<div class="card mw-100 mx-auto" style="width:800px">
<div class="card-body">
	<h1 class="h2 card-title text-center mb-3">确认参赛</h1>

	<p class="card-text text-center">您即将参加比赛 “<b><?= $contest['name'] ?></b>”，请在正式参赛前仔细核对以下比赛信息：</p>

	<div class="table-responsive mx-auto" style="width:500px">
		<table class="table">
			<thead>
				<tr>
					<th style="width:40%"></th>
					<th style="width:60%"></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="text-center">比赛名称</td>
					<td><?= $contest['name'] ?></td>
				</tr>
				<tr>
					<td class="text-center">参赛选手</td>
					<td><?= getUserLink($myUser['username']) ?></td>
				</tr>
				<tr>
					<td class="text-center">开始时间</td>
					<td><?= $contest['start_time_str'] ?></td>
				</tr>
				<tr>
					<td class="text-center">结束时间</td>
					<td><?= $contest['end_time_str'] ?></td>
				</tr>
				<tr>
					<td class="text-center">比赛赛制</td>
					<td><?= $contest['extra_config']['contest_type'] ?: 'OI' ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php $confirm_form->printHTML() ?>
</div>
</div>

<?php echoUOJPageFooter() ?>
