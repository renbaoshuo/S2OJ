<?php
	$blogs = DB::selectAll("select blogs.id, title, poster, post_time from important_blogs, blogs where is_hidden = 0 and important_blogs.blog_id = blogs.id order by level desc, important_blogs.blog_id desc limit 5");
	$countdowns = DB::selectAll("select * from countdowns order by endtime asc");
	$friend_links = DB::selectAll("select * from friend_links order by level desc, id asc");

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}
	?>
<?php echoUOJPageHeader(UOJConfig::$data['profile']['oj-name-short']) ?>
<div class="row">
	<div class="col-lg-9">
		<div class="card card-default">
			<div class="card-body">
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
					<h4 class="card-title">
						<?= UOJLocale::get('announcements') ?>
					</h4>
				<?php endif ?>
				<table class="table table-sm">
					<thead>
						<tr>
							<th style="width:60%">
								<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
									<?= UOJLocale::get('announcements') ?>
								<?php endif ?>
							</th>
							<th style="width:20%"></th>
							<th style="width:20%"></th>
						</tr>
					</thead>
						<tbody>
						<?php $now_cnt = 0; ?>
						<?php foreach ($blogs as $blog): ?>
							<?php
									$now_cnt++;
							$new_tag = '';
							if ((time() - strtotime($blog['post_time'])) / 3600 / 24 <= 7) {
								$new_tag = '<sup style="color:red">&nbsp;new</sup>';
							}
							?>
							<tr>
								<td>
									<a href="/blogs/<?= $blog['id'] ?>"
										<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
										class="text-decoration-none"
										<?php endif ?>
										><?= $blog['title'] ?></a>
									<?= $new_tag ?>
								</td>
								<td>by <?= getUserLink($blog['poster']) ?></td>
								<td><small><?= $blog['post_time'] ?></small></td>
							</tr>
						<?php endforeach ?>
						<?php for ($i = $now_cnt + 1; $i <= 5; $i++): ?>
							<tr><td colspan="233">&nbsp;</td></tr>
						<?php endfor ?>
						<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
							<tr><td class="text-right" colspan="233"><a href="/announcements"><?= UOJLocale::get('all the announcements') ?></a></td></tr>
						<?php endif ?>
					</tbody>
				</table>
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
					<div class="text-end">
						<a class="text-decoration-none" href="/announcements"><?= UOJLocale::get('all the announcements') ?></a>
					</div>
				<?php endif ?>
			</div>
		</div>
		<?php if (!UOJConfig::$data['switch']['force-login'] || Auth::check()): ?>
			<?php if (!UOJConfig::$data['switch']['force-login'] || isNormalUser($myUser)): ?>
			
			<div class="mt-4
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				card
				<?php endif ?>
				">
				
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<div class="card-body">
					<h4 class="card-title"><?= UOJLocale::get('top solver') ?></h4>
				<?php else: ?>
					<h3><?= UOJLocale::get('top solver') ?></h3>
				<?php endif ?>
					<?php echoRanklist(array(
						'echo_full' => true,
						'top10' => true,
						'by_accepted' => true,
						'table_classes' => isset($REQUIRE_LIB['bootstrap5'])
							? array('table', 'text-center')
							: array('table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center'),
					)) ?>
					<div class="text-center">
						<a href="/solverlist"
							<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							class="text-decoration-none"
							<?php endif ?>
							><?= UOJLocale::get('view all') ?></a>
					</div>
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				</div>
				<?php endif ?>
			</div>
			<?php endif ?>
		<?php else: ?>
			<div class="mt-4 card card-default">
				<div class="card-body text-center">
					请 <a role="button" class="btn btn-outline-primary" href="<?= HTML::url('/login') ?>">登录</a> 以查看更多内容。
				</div>
			</div>
		<?php endif ?>
	</div>
	<div class="col mt-4 mt-lg-0">
		<div class="d-none d-lg-block mb-4">
			<img class="media-object img-thumbnail" src="/images/logo.png" alt="Logo" />
		</div>
		<div class="card card-default mb-2">
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
			<div class="card-header bg-white">
				<b><?= UOJLocale::get('countdowns') ?></b>
			</div>
			<?php endif ?>
			<div class="card-body">
				<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
					<h4 class="card-title" style="font-size: 1.25rem">
						<?= UOJLocale::get('countdowns') ?>
					</h4>
				<?php endif ?>
				<ul class="
					<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
						list-unstyled
					<?php else: ?>
						pl-4
					<?php endif ?> mb-0">
					<?php foreach ($countdowns as $countdown): ?>
						<?php
							$enddate = strtotime($countdown['endtime']);
						$nowdate = time();
						$diff = floor(($enddate - $nowdate) / (24 * 60 * 60));
						?>
						<li>
							<?php if ($diff > 0): ?>
								<?= UOJLocale::get('x days until countdown title', $countdown['title'], $diff) ?>
							<?php else: ?>
								<?= UOJLocale::get("countdown title has begun", $countdown['title']) ?>
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
				<?php if (count($countdowns) == 0): ?>
					<div class="text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</div>
		</div>

		<?php if (Auth::check()): ?>
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<?php uojIncludeView('sidebar', ['assignments_hidden' => '', 'groups_hidden' => '']) ?>
			<?php endif ?>
		<?php endif ?>

		<div class="card card-default mb-2">
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<div class="card-header bg-white">
					<b><?= UOJLocale::get('friend links') ?></b>
				</div>
			<?php endif ?>
			<div class="card-body">
				<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
				<h4 class="card-title" style="font-size: 1.25rem">
					<?= UOJLocale::get('friend links') ?>
				</h4>
				<?php endif ?>
				<ul class="
					<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
						ps-3
					<?php else: ?>
						pl-4
					<?php endif ?>
					mb-0">
					<?php foreach ($friend_links as $friend_link): ?>
						<li>
							<a
							<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							class="text-decoration-none"
							<?php endif ?>
							href="<?= $friend_link['url'] ?>" target="_blank"><?= $friend_link['title'] ?></a>
						</li>
					<?php endforeach ?>
				</ul>
				<?php if (count($friend_links) == 0): ?>
					<div class="text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>


<?php echoUOJPageFooter() ?>
