<?php
$purifier = HTML::purifier_inline();
$parsedown = HTML::parsedown();
?>

<?php if (Auth::check()) : ?>
	<?php if (!isset($groups_hidden)) : ?>
		<?php $groups = UOJGroup::queryGroupsOfUser(Auth::user()); ?>
		<?php if (!empty($groups)) : ?>
			<div class="card card-default mb-2" id="group-user-announcements">
				<div class="card-header fw-bold bg-transparent">
					<?= UOJLocale::get('group announcements') ?>
				</div>
				<ul class="list-group list-group-flush">
					<?php foreach ($groups as $group) : ?>
						<li class="list-group-item">
							<?= $group->getLink(['class' => 'fw-bold']) ?>
							<?php if ($group->info['announcement']) : ?>
								<div class="text-break">
									<?= $purifier->purify($parsedown->line($group->info['announcement'])) ?>
								</div>
							<?php else : ?>
								<div class="text-muted">
									<?= UOJLocale::get('none') ?>
								</div>
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>

			<?php if (!isset($assignments_hidden)) : ?>
				<?php
				$assignments = [];
				foreach ($groups as $group) {
					$assignments = array_merge($assignments, array_map(fn ($x) => UOJGroupAssignment::query($x, $group), $group->getActiveAssignmentIds()));
				}

				usort($assignments, fn ($a, $b) => $b->info['end_time']->getTimestamp() - $a->info['end_time']->getTimestamp());
				$assignments = array_slice($assignments, 0, 5);
				?>
				<?php if (!empty($assignments)) : ?>
					<div class="card card-default mb-2" id="group-assignments">
						<div class="card-header fw-bold bg-transparent">
							<?= UOJLocale::get('assignments') ?>
						</div>
						<ul class="list-group list-group-flush">
							<?php foreach ($assignments as $assignment) : ?>
								<li class="list-group-item">
									<?= $assignment->getLink(['class' => 'fw-bold', 'with' => 'sup']) ?>
									<div class="text-end small text-muted">
										截止时间: <?= $assignment->info['end_time']->format('Y-m-d H:i') ?>
									</div>
								</li>
							<?php endforeach ?>
						</ul>
					</div>
				<?php endif ?>
			<?php endif ?>
		<?php endif ?>
	<?php endif ?>
<?php endif ?>

<?php if (Auth::check()) : ?>
	<?php if (!isset($upcoming_contests_hidden)) : ?>
		<?php $upcoming_contests = UOJContest::queryUpcomingContestIds(Auth::user(), 5); ?>
		<div class="card card-default mb-2" id="group-user-announcements">
			<div class="card-header fw-bold bg-transparent">
				近期比赛
			</div>
			<ul class="list-group list-group-flush">
				<?php foreach ($upcoming_contests as $id) : ?>
					<?php $contest = UOJContest::query($id); ?>
					<?php if ($contest->info['cur_progress'] == CONTEST_NOT_STARTED || $contest->info['cur_progress'] == CONTEST_IN_PROGRESS) : ?>
						<li class="list-group-item text-center">
							<?= $contest->getLink(['class' => 'fw-bold']) ?>
							<div class="small">
								<?php if ($contest->info['cur_progress'] == CONTEST_IN_PROGRESS) : ?>
									<?= UOJLocale::get('contests::in progress') ?>
								<?php else : ?>
									<?php $rest_seconds = $contest->info['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp(); ?>
									<?php if ($rest_seconds > 86400) : ?>
										<?= UOJLocale::get('contests::will start in x days', ceil($rest_seconds / 86400)) ?>
									<?php else : ?>
										<div id="contest-<?= $contest->info['id'] ?>-countdown"></div>
										<script>
											$('#contest-<?= $contest->info['id'] ?>-countdown').countdown(<?= $rest_seconds ?>, function() {}, 'inherit', false);
										</script>
									<?php endif ?>
								<?php endif ?>
							</div>
						</li>
					<?php endif ?>
				<?php endforeach ?>
				<?php if (empty($upcoming_contests)) : ?>
					<li class="list-group-item text-center">
						<?= UOJLocale::get('none') ?>
					</li>
				<?php endif ?>
			</ul>
			<div class="card-footer bg-transparent text-center small">
				<a class="text-decoration-none" href="<?= HTML::url('/contests?all=true') ?>">
					<?= UOJLocale::get('view all') ?>
				</a>
			</div>
		</div>
	<?php endif	?>
<?php else : ?>
	<div class="card">
		<div class="card-body">
			请 <a href="<?= HTML::url('/login') ?>">登录</a> 查看更多内容。
		</div>
	</div>
<?php endif ?>
