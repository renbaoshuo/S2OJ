<?php
requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJGroup::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJGroup::cur()->userCanView(Auth::user(), ['ensure' => true]);
?>

<?php echoUOJPageHeader('小组：' . UOJGroup::info('title')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<!-- title -->
		<div class="d-flex justify-content-between">
			<h1>
				<?= UOJGroup::info('title') ?>
				<span class="fs-5">(ID: #<?= UOJGroup::info('id') ?>)</span>
				<?php if (UOJGroup::info('is_hidden')) : ?>
					<span class="badge text-bg-danger fs-6">
						<i class="bi bi-eye-slash-fill"></i>
						<?= UOJLocale::get('hidden') ?>
					</span>
				<?php endif ?>
			</h1>

			<?php if (UOJGroup::cur()->userCanManage(Auth::user())) : ?>
				<div class="text-end">
					<?=
					UOJGroup::cur()->getLink([
						'where' => '/manage',
						'class' => 'btn btn-primary',
						'text' => UOJLocale::get('problems::manage'),
					]);
					?>
				</div>
			<?php endif ?>
		</div>
		<!-- end title -->

		<!-- main content -->
		<div class="card mb-3">
			<div class="card-body">
				<h2 class="h3">
					<?= UOJLocale::get('group announcement') ?>
				</h2>
				<?php if (UOJGroup::info('announcement')) : ?>
					<div class="text-break">
						<?= HTML::purifier_inline()->purify(HTML::parsedown()->line(UOJGroup::info('announcement'))) ?>
					</div>
				<?php else : ?>
					<div class="text-muted">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</div>
		</div>

		<div class="card mb-3">
			<div class="card-body">
				<h2 class="card-title h3">
					<?= UOJLocale::get('news') ?>
				</h2>
				<ul class="mb-0">
					<?php foreach (UOJGroup::cur()->getLatestGroupmatesAcceptedSubmissionIds(Auth::user()) as $id) : ?>
						<?php
						$submission = UOJSubmission::query($id);
						$submission->setProblem();
						$user = UOJUser::query($submission->info['submitter']);
						?>
						<li>
							<?= UOJUser::getLink($user) ?>
							解决了问题
							<?= $submission->problem->getLink(['with' => 'id']) ?>
							(<time><?= $submission->info['submit_time'] ?></time>)
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>

		<div class="card card-default mb-3">
			<div class="card-body">
				<h2 class="card-title h3">
					<?= UOJLocale::get('assignments') ?>
				</h2>
				<?php
				echoLongTable(
					['*'],
					[
						"groups_assignments",
						"left join lists",
						"on", [
							"lists.id" => DB::raw("groups_assignments.list_id"),
						]
					],
					[
						"groups_assignments.group_id" => UOJGroup::info('id'),
						["groups_assignments.end_time", ">", DB::raw("addtime(now(), '-168:00:00')")]
					],
					'order by groups_assignments.end_time desc, groups_assignments.list_id desc',
					<<<EOD
						<tr>
							<th style="width:3em" class="text-center">ID</th>
							<th style="width:12em">标题</th>
							<th style="width:4em">状态</th>
							<th style="width:8em">结束时间</th>
						</tr>
					EOD,
					function ($info) {
						$assignment = new UOJGroupAssignment($info, UOJGroup::cur());

						echo HTML::tag_begin('tr');
						echo HTML::tag('td', ['class' => 'text-center'], $assignment->info['id']);
						echo HTML::tag('td', [], $assignment->getLink());
						if ($assignment->info['end_time'] < UOJTime::$time_now) {
							echo HTML::tag('td', ['class' => 'text-danger'], '已结束');
						} else {
							echo HTML::tag('td', ['class' => 'text-success'], '进行中');
						}
						echo HTML::tag('td', [], $assignment->info['end_time_str']);
						echo HTML::tag_end('tr');
					},
					[
						'echo_full' => true,
						'div_classes' => ['table-responsive'],
						'table_classes' => ['table', 'align-middle', 'mb-0'],
					],
				);
				?>
			</div>
		</div>

		<div class="card card-default mb-3">
			<div class="card-body">
				<h2 class="card-title h3">
					<?= UOJLocale::get('top solver') ?>
				</h2>
				<?php UOJRanklist::printHTML([
					'page_len' => 15,
					'group_id' => UOJGroup::info('id'),
				]) ?>
			</div>
		</div>
		<!-- end left col -->
	</div>

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>

</div>

<?php echoUOJPageFooter() ?>
