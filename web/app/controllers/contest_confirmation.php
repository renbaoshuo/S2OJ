<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanParticipateNow(Auth::user()) || UOJResponse::page403();
UOJContest::cur()->userHasMarkedParticipated(Auth::user()) && redirectTo(UOJContest::cur()->getUri());

$confirm_form = new UOJForm('confirm');
$confirm_form->config['submit_button']['class'] = 'btn btn-primary';
$confirm_form->config['submit_button']['text'] = '我已核对信息，确认参加比赛';
$confirm_form->handle = function () {
	UOJContest::cur()->markUserAsParticipated(Auth::user());
};
$confirm_form->succ_href = '/contest/' . UOJContest::info('id');
$confirm_form->runAtServer();
?>

<?php echoUOJPageHeader('确认参赛 - ' . UOJContest::info('name')) ?>

<div class="card mw-100 mx-auto" style="width:800px">
	<div class="card-body">
		<h1 class="card-title text-center mb-3">
			确认参赛
		</h1>

		<p class="card-text text-center">
			您即将参加比赛 “<b><?= UOJContest::info('name') ?></b>”，请在正式参赛前仔细核对以下比赛信息：
		</p>

		<div class="table-responsive mx-auto" style="width: 500px">
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
						<td><?= UOJContest::info('name') ?></td>
					</tr>
					<tr>
						<td class="text-center">参赛选手</td>
						<td><?= UOJUser::getLink(Auth::user()) ?></td>
					</tr>
					<tr>
						<td class="text-center">开始时间</td>
						<td><?= UOJContest::info('start_time_str') ?></td>
					</tr>
					<tr>
						<td class="text-center">结束时间</td>
						<td><?= UOJContest::info('end_time_str') ?></td>
					</tr>
					<tr>
						<td class="text-center">比赛赛制</td>
						<td><?= UOJContest::cur()->basicRule() ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php $confirm_form->printHTML() ?>
	</div>
</div>

<?php echoUOJPageFooter() ?>
