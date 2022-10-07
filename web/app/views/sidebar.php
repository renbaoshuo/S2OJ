<?php if (Auth::check()): ?>
<?php if (!isset($group_announcements_hidden)): ?>
<?php $groups = queryGroupsOfUser(Auth::id()); ?>
<?php if (count($groups)): ?>
<div class="card card-default mb-2" id="group-user-announcements">
	<div class="card-header fw-bold bg-transparent">
		小组公告
	</div>
	<ul class="list-group list-group-flush">
		<?php foreach ($groups as $group): ?>
			<?php
				$group_detail = DB::selectFirst("select * from groups where id = {$group['id']}");
			$group_announcement = $group_detail['announcement'];
			?>
			<li class="list-group-item">
				<a class="fw-bold text-decoration-none" href="<?= HTML::url('/group/'.$group['id']) ?>">
					<?= $group['title'] ?>
				</a>
				<?php if ($group_announcement): ?>
				<div id="announcement-content-<?= $group['id'] ?>" class="text-break"></div>
				<script>(function(){
					$('#announcement-content-<?= $group['id'] ?>')
						.html(DOMPurify.sanitize(decodeURIComponent("<?= urlencode($group_announcement) ?>"), <?= DOM_SANITIZE_CONFIG ?>)); 
				})();</script>
				<?php else: ?>
				<div>（暂无公告）</div>
				<?php endif ?>
			</li>
		<?php endforeach ?>
	</ul>
</div>
<?php endif // count($groups) ?>
<?php endif // !isset($group_announcements_hidden) ?>
<?php endif // Auth::check() ?>

<?php if (!UOJConfig::$data['switch']['force-login'] || Auth::check()): ?>
<?php if (!isset($upcoming_contests_hidden)): ?>
<?php
	$upcoming_contests = DB::selectAll("SELECT * FROM contests WHERE status = 'unfinished' ORDER BY start_time ASC, id ASC LIMIT 7");
	?>
<div class="card card-default mb-2" id="group-user-announcements">
	<div class="card-header fw-bold bg-transparent">
		近期比赛
	</div>
	<?php $count = 0; ?>
	<ul class="list-group list-group-flush">
		<?php foreach ($upcoming_contests as $contest): ?>
			<?php genMoreContestInfo($contest) ?>
			<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED || $contest['cur_progress'] == CONTEST_IN_PROGRESS): ?>
			<?php $count++; ?>
			<li class="list-group-item text-center">
				<a class="fw-bold text-decoration-none" href="<?= HTML::url('/contest/'.$contest['id']) ?>">
					<?= $contest['name'] ?>
				</a>
				<div class="small">
				<?php if ($contest['cur_progress'] == CONTEST_IN_PROGRESS): ?>
				<?= UOJLocale::get('contests::in progress') ?>
				<?php else: ?>
				<?php
						$rest_seconds = $contest['start_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
					?>
				<?php if ($rest_seconds > 86400): ?>
				<?= UOJLocale::get('contests::will start in x days', ceil($rest_seconds / 86400)) ?>
				<?php else: ?>
				<div id="contest-<?= $contest['id'] ?>-countdown"></div>
				<script>$('#contest-<?= $contest['id'] ?>-countdown').countdown(<?= $rest_seconds ?>, function(){}, 'inherit', false);</script>
				<?php endif ?>
				<?php endif ?>
				</div>
			</li>
			<?php endif ?>
		<?php endforeach ?>
		<?php if ($count == 0): ?>
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
<?php endif // !isset($upcoming_contests_hidden) ?>
<?php endif // !UOJConfig::$data['switch']['force-login'] || Auth::check() ?>
