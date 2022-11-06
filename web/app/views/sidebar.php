<?php if (Auth::check()) : ?>
	<?php if (!isset($groups_hidden)) : ?>
		<?php $groups = queryGroupsOfUser(Auth::id()); ?>
		<?php if (!empty($groups)) : ?>
			<div class="card card-default mb-2" id="group-user-announcements">
				<div class="card-header fw-bold bg-transparent">
					<?= UOJLocale::get('group announcements') ?>
				</div>
				<ul class="list-group list-group-flush">
					<?php foreach ($groups as $group) : ?>
						<?php
						$group_announcement = DB::selectSingle("select announcement from `groups` where id = {$group['id']}");
						$purifier = HTML::purifier_inline();
						$parsedown = HTML::parsedown();
						?>
						<li class="list-group-item">
							<a class="fw-bold text-decoration-none" href="<?= HTML::url('/group/' . $group['id']) ?>">
								<?= $group['title'] ?>
							</a>
							<?php if ($group_announcement) : ?>
								<div class="text-break">
									<?= $purifier->purify($parsedown->line($group_announcement)) ?>
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
					$assignments = array_merge($assignments, queryGroupActiveAssignments($group['id']));
				}

				usort($assignments, function ($a, $b) {
					$end_time_a = DateTime::createFromFormat('Y-m-d H:i:s', $a['end_time']);
					$end_time_b = DateTime::createFromFormat('Y-m-d H:i:s', $b['end_time']);

					return $end_time_b->getTimestamp() - $end_time_a->getTimestamp();
				});

				$assignments = array_slice($assignments, 0, 5);
				?>
				<?php if (count($assignments)) : ?>
					<div class="card card-default mb-2" id="group-assignments">
						<div class="card-header fw-bold bg-transparent">
							<?= UOJLocale::get('assignments') ?>
						</div>
						<ul class="list-group list-group-flush">
							<?php foreach ($assignments as $assignment) : ?>
								<li class="list-group-item">
									<?php $end_time = DateTime::createFromFormat('Y-m-d H:i:s', $assignment['end_time']); ?>
									<a href="<?= HTML::url('/group/' . $assignment['group_id'] . '/assignment/' . $assignment['list_id']) ?>" class="fw-bold text-decoration-none">
										<?= $assignment['title'] ?>
										<?php if ($end_time < UOJTime::$time_now) : ?>
											<sup class="fw-normal text-danger">overdue</sup>
										<?php elseif ($end_time->getTimestamp() - UOJTime::$time_now->getTimestamp() < 86400) : ?>
											<sup class="fw-normal text-danger">soon</sup>
										<?php endif ?>
									</a>
									<div class="text-end small text-muted">
										截止时间: <?= $end_time->format('Y-m-d H:i') ?>
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
		<?php
		$upcoming_contests = DB::selectAll("SELECT * FROM contests WHERE status = 'unfinished' ORDER BY start_time ASC, id ASC LIMIT 7");
		?>
		<div class="card card-default mb-2" id="group-user-announcements">
			<div class="card-header fw-bold bg-transparent">
				近期比赛
			</div>
			<?php $count = 0; ?>
			<ul class="list-group list-group-flush">
				<?php foreach ($upcoming_contests as $contest) : ?>
					<?php genMoreContestInfo($contest) ?>
					<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED || $contest['cur_progress'] == CONTEST_IN_PROGRESS) : ?>
						<?php $count++; ?>
						<li class="list-group-item text-center">
							<a class="fw-bold text-decoration-none" href="<?= HTML::url('/contest/' . $contest['id']) ?>">
								<?= $contest['name'] ?>
							</a>
							<div class="small">
								<?php if ($contest['cur_progress'] == CONTEST_IN_PROGRESS) : ?>
									<?= UOJLocale::get('contests::in progress') ?>
								<?php else : ?>
									<?php
									$rest_seconds = $contest['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
									?>
									<?php if ($rest_seconds > 86400) : ?>
										<?= UOJLocale::get('contests::will start in x days', ceil($rest_seconds / 86400)) ?>
									<?php else : ?>
										<div id="contest-<?= $contest['id'] ?>-countdown"></div>
										<script>
											$('#contest-<?= $contest['id'] ?>-countdown').countdown(<?= $rest_seconds ?>, function() {}, 'inherit', false);
										</script>
									<?php endif ?>
								<?php endif ?>
							</div>
						</li>
					<?php endif ?>
				<?php endforeach ?>
				<?php if ($count == 0) : ?>
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
