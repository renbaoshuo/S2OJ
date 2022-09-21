<?php
	$blogs = DB::selectAll("select blogs.id, title, poster, post_time from important_blogs, blogs where is_hidden = 0 and important_blogs.blog_id = blogs.id order by level desc, important_blogs.blog_id desc limit 5");
	$countdowns = DB::selectAll("select * from countdowns order by endtime asc");
	$friend_links = DB::selectAll("select * from friend_links order by level desc, id asc");
	?>
<?php echoUOJPageHeader(UOJConfig::$data['profile']['oj-name-short']) ?>
<div class="row">
	<div class="col-sm-12 col-md-9">
		<div class="card card-default">
			<div class="card-body">
				<table class="table table-sm">
					<thead>
						<tr>
							<th style="width:60%"><?= UOJLocale::get('announcements') ?></th>
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
							<td><a href="/blogs/<?= $blog['id'] ?>"><?= $blog['title'] ?></a><?= $new_tag ?></td>
							<td>by <?= getUserLink($blog['poster']) ?></td>
							<td><small><?= $blog['post_time'] ?></small></td>
						</tr>
					<?php endforeach ?>
					<?php for ($i = $now_cnt + 1; $i <= 5; $i++): ?>
						<tr><td colspan="233">&nbsp;</td></tr>
					<?php endfor ?>
						<tr><td class="text-right" colspan="233"><a href="/announcements"><?= UOJLocale::get('all the announcements') ?></a></td></tr>
					</tbody>
				</table>
			</div>
		</div>
		<?php if (Auth::check() && isNormalUser($myUser)): ?>
			<div class="mt-4">
				<h3><?= UOJLocale::get('top solver') ?></h3>
				<?php echoRanklist(array('echo_full' => true, 'top10' => true, 'by_accepted' => true)) ?>
				<div class="text-center">
					<a href="/solverlist"><?= UOJLocale::get('view all') ?></a>
				</div>
			</div>
		<?php else: ?>
			<div class="mt-4 card card-default">
				<div class="card-body text-center">
					请 <a role="button" class="btn btn-outline-primary" href="<?= HTML::url('/login') ?>">登录</a> 以查看更多内容。
				</div>
			</div>
		<?php endif ?>
	</div>
	<div class="col-xs-6 col-sm-4 col-md-3">
		<div class="d-none d-md-block">
			<img class="media-object img-thumbnail" src="/images/logo.png" alt="Logo" />
		</div>
		<div class="card card-default mt-4">
			<div class="card-body">
				<h3 class="card-title" style="font-size: 1.25rem">倒计时</h3>
				<ul class="pl-4 mb-0">
					<?php foreach ($countdowns as $countdown): ?>
						<?php
							$enddate = strtotime($countdown['endtime']);
						$nowdate = time();
						$diff = floor(($enddate - $nowdate) / (24 * 60 * 60));
						?>
						<li>
							<?php if ($diff > 0): ?>
								距离 <b><?= $countdown['title'] ?></b> 还有 <b><?= $diff ?></b> 天。
							<?php else: ?>
								<b><?= $countdown['title'] ?></b> 已开始。
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
		<div class="card card-default mt-4">
			<div class="card-body">
				<h3 class="card-title" style="font-size: 1.25rem">友情链接</h3>
				<ul class="pl-4 mb-0">
					<?php foreach ($friend_links as $friend_link): ?>
						<li>
							<a href="<?= $friend_link['url'] ?>" target="_blank"><?= $friend_link['title'] ?></a>
						</li>
					<?php endforeach ?>
				</ul>
			</div>
		</div>
	</div>
</div>


<?php echoUOJPageFooter() ?>
