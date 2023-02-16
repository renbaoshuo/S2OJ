<?php
$blogs = DB::selectAll([
	"select blogs.id, title, poster, post_time from important_blogs inner join blogs on important_blogs.blog_id = blogs.id",
	"where", [
		"is_hidden" => 0,
	], "order by level desc, important_blogs.blog_id desc",
	DB::limit(5)
]);
$countdowns = DB::selectAll([
	"select title, end_time from countdowns",
	"order by end_time asc",
]);
$friend_links = DB::selectAll([
	"select title, url from friend_links",
	"order by level desc, id asc",
]);
?>
<?php echoUOJPageHeader(UOJConfig::$data['profile']['oj-name-short']) ?>
<div class="row">
	<div class="col-lg-9">
		<div class="card card-default">
			<div class="card-body">
				<h4 class="card-title">
					<?= UOJLocale::get('announcements') ?>
				</h4>
				<table class="table table-sm">
					<thead>
						<tr>
							<th style="width:60%"></th>
							<th style="width:20%"></th>
							<th style="width:20%"></th>
						</tr>
					</thead>
					<tbody>
						<?php $now_cnt = 0; ?>
						<?php foreach ($blogs as $blog_info) : ?>
							<?php
							$blog = new UOJBlog($blog_info);
							$now_cnt++;
							?>
							<tr>
								<td><?= $blog->getLink(['show_new_tag' => true]) ?></td>
								<td>by <?= UOJUser::getLink($blog->info['poster']) ?></td>
								<td><small><?= $blog->info['post_time'] ?></small></td>
							</tr>
						<?php endforeach ?>
						<?php for ($i = $now_cnt + 1; $i <= 5; $i++) : ?>
							<tr>
								<td colspan="233">&nbsp;</td>
							</tr>
						<?php endfor ?>
					</tbody>
				</table>
				<div class="text-end">
					<a class="text-decoration-none" href="/announcements">
						<?= UOJLocale::get('all the announcements') ?>
					</a>
				</div>
			</div>
		</div>
		<?php if (Auth::check()) : ?>
			<div class="card mt-4">
				<div class="card-header bg-transparent">
					<h4 class="mb-0"><?= UOJLocale::get('top solver') ?></h4>
				</div>
				<?php UOJRanklist::printHTML(['top10' => true, 'flush' => true]) ?>
				<div class="card-footer bg-transparent text-center">
					<a href="/solverlist">
						<?= UOJLocale::get('view all') ?>
					</a>
				</div>
			</div>
		<?php else : ?>
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
			<div class="card-header fw-bold">
				<?= UOJLocale::get('countdowns') ?>
			</div>
			<div class="card-body">
				<ul class="list-unstyled mb-0">
					<?php foreach ($countdowns as $countdown) : ?>
						<?php
						$enddate = strtotime($countdown['end_time']);
						$nowdate = time();
						$diff = ceil(($enddate - $nowdate) / (24 * 60 * 60));
						?>
						<li>
							<?php if ($diff > 0) : ?>
								<?= UOJLocale::get('x days until countdown title', $countdown['title'], $diff) ?>
							<?php else : ?>
								<?= UOJLocale::get("countdown title has begun", $countdown['title']) ?>
							<?php endif ?>
						</li>
					<?php endforeach ?>
				</ul>
				<?php if (count($countdowns) == 0) : ?>
					<div class="text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</div>
		</div>

		<?php if (Auth::check()) : ?>
			<?php uojIncludeView('sidebar', ['assignments_hidden' => '', 'groups_hidden' => '']) ?>
		<?php endif ?>

		<div class="card card-default mb-2">
			<div class="card-header fw-bold">
				<?= UOJLocale::get('friend links') ?>
			</div>
			<div class="card-body">
				<ul class="ps-3 mb-0">
					<?php foreach ($friend_links as $friend_link) : ?>
						<li>
							<a class="text-decoration-none" href="<?= $friend_link['url'] ?>" target="_blank">
								<?= $friend_link['title'] ?>
							</a>
						</li>
					<?php endforeach ?>
				</ul>
				<?php if (count($friend_links) == 0) : ?>
					<div class="text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</div>
		</div>
	</div>
</div>


<?php echoUOJPageFooter() ?>
