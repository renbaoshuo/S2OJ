<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	$username = $_GET['username'];

	if (!validateUsername($username) || !($user = queryUser($username))) {
		become404Page();
	}
	?>
<?php
	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
		$REQUIRE_LIB['calendar_heatmap'] = '';
	} else {
		$REQUIRE_LIB['github_contribution_graph'] = '';
	}
	?>
<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="row">
		<div class="col-md-3">
			<div class="card">
				<img class="card-img-top" alt="Avatar of <?= $user['username'] ?>" src="<?= HTML::avatar_addr($user, 512) ?>" />
				<div class="card-body">
					<h3>
						<?= $user['username'] ?>
						<span class="fs-6 align-middle"
						<?php if ($user['sex'] == 'M'): ?>
							style="color: blue"><i class="bi bi-gender-male"></i>
						<?php elseif ($user['sex' == 'F']): ?>
							style="color: red"><i class="bi bi-gender-female"></i>
						<?php endif ?>
						</span>
					</h3>
					<?php $motto_id = uniqid("motto-{$user['username']}-"); ?>
					<div class="card-text" id="<?= $motto_id ?>"></div>
					<script type="text/javascript">
						$(function() { $('#<?= $motto_id ?>').html(DOMPurify.sanitize(decodeURIComponent("<?= rawurlencode($user['motto']) ?>"), <?= DOM_SANITIZE_CONFIG ?>)); });
					</script>
				</div>
				<ul class="list-group list-group-flush">
					<?php if ($user['realname']): ?>
					<li class="list-group-item">
						<i class="bi bi-person-fill me-1"></i>
						<?= $user['realname'] ?>
					</li>
					<?php endif ?>
					<li class="list-group-item">
						<i class="bi bi-envelope-fill me-1"></i>
						<a class="text-decoration-none text-body" href="mailto:<?= HTML::escape($user['email']) ?>">
							<?= HTML::escape($user['email']) ?>
						</a>
					</li>
					<?php if ($user['qq']): ?>
					<li class="list-group-item">
						<i class="align-text-bottom me-1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16"><path d="M433.754 420.445c-11.526 1.393-44.86-52.741-44.86-52.741 0 31.345-16.136 72.247-51.051 101.786 16.842 5.192 54.843 19.167 45.803 34.421-7.316 12.343-125.51 7.881-159.632 4.037-34.122 3.844-152.316 8.306-159.632-4.037-9.045-15.25 28.918-29.214 45.783-34.415-34.92-29.539-51.059-70.445-51.059-101.792 0 0-33.334 54.134-44.859 52.741-5.37-.65-12.424-29.644 9.347-99.704 10.261-33.024 21.995-60.478 40.144-105.779C60.683 98.063 108.982.006 224 0c113.737.006 163.156 96.133 160.264 214.963 18.118 45.223 29.912 72.85 40.144 105.778 21.768 70.06 14.716 99.053 9.346 99.704z" fill="currentColor"/></svg></i>
						<?= HTML::escape($user['qq']) ?>
					</li>
					<?php endif ?>
				</ul>
			</div>
		</div>
		<div class="col-md-9 mt-2 mt-md-0">
			<nav class="nav mb-2">
				<?php if (Auth::check()): ?>
					<?php if (Auth::id() != $user['username']): ?>
						<a class="nav-link" href="/user/msg?enter=<?= $user['username'] ?>">
							<i class="bi bi-chat-left-dots"></i>
							<?= UOJLocale::get('send private message') ?>
						</a>
					<?php else: ?>
						<a class="nav-link" href="/user/modify-profile">
							<i class="bi bi-pencil"></i>
							<?= UOJLocale::get('modify my profile') ?>
						</a>
					<?php endif ?>
				<?php endif ?>
					
				<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/') ?>">
					<i class="bi bi-arrow-right-square"></i>
					<?= UOJLocale::get('visit his blog', $username) ?>
				</a>
				<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/self_reviews') ?>">
					<i class="bi bi-arrow-right-square"></i>
					赛后总结
				</a>
			</nav>
			<div class="card card-default mb-2">
				<div class="card-body">
	<?php
	$_result = DB::query("select date_format(submit_time, '%Y-%m-%d'), problem_id from submissions where submitter = '{$username}' and score = 100 and date(submit_time) between date_sub(curdate(), interval 1 year) and curdate()");
	$result = [];
	$vis = [];
	while ($row = DB::fetch($_result)) {
		$id = $row["date_format(submit_time, '%Y-%m-%d')"] . ':' . $row['problem_id'];
		if (!$vis[$id]) {
			$vis[$id] = 1;
			$result[$row["date_format(submit_time, '%Y-%m-%d')"]]++;
		}
	}
	?>
					<h4 class="card-title h5">
						<?= UOJLocale::get('n accepted in last year', count($result)) ?>
					</h4>
					<div id="accepted-graph" style="font-size: 14px"></div>
					<script>
						var accepted_graph_data = [
							<?php foreach ($result as $key => $val): ?>
								{ date: '<?= $key ?>', count: <?= $val ?> },
							<?php endforeach ?>
						];

						$(document).ready(function () {
							$('#accepted-graph').CalendarHeatmap(accepted_graph_data, {});
						});
					</script>
				</div>
			</div>
			<div class="card card-default mb-2">
				<div class="card-body">
				<?php $ac_problems = DB::selectAll("select a.problem_id as problem_id, b.title as title from best_ac_submissions a inner join problems b on a.problem_id = b.id where submitter = '{$user['username']}';") ?>
				<h4 class="card-title h5">
					<?= UOJLocale::get('accepted problems').': '.UOJLocale::get('n problems in total', count($ac_problems))?>
				</h4>
				<ul class="nav">
					<?php foreach ($ac_problems as $problem): ?>
						<li class="nav-item">
							<a class="nav-link" href="/problem/<?= $problem['problem_id'] ?>" role="button">
								#<?= $problem['problem_id'] ?>. <?= $problem['title'] ?>
							</a>
						</li>
					<?php endforeach ?>

					<?php if (empty($ac_problems)): ?>
						<?= UOJLocale::get('none'); ?>
					<?php endif ?>
				</ul>
				</div>
			</div>

			<?php if (isSuperUser($myUser)): ?>
				<div class="card card-default">
					<ul class="list-group list-group-flush">
						<li class="list-group-item">
							<h5 class="list-group-item-heading">register time</h5>
							<p class="list-group-item-text"><?= $user['register_time'] ?></p>
						</li>
						<li class="list-group-item">
							<h5 class="list-group-item-heading">remote_addr</h5>
							<p class="list-group-item-text"><?= $user['remote_addr'] ?></p>
						</li>
						<li class="list-group-item">
							<h5 class="list-group-item-heading">http_x_forwarded_for</h5>
							<p class="list-group-item-text"><?= $user['http_x_forwarded_for'] ?></p>
						</li>
					</ul>
				</div>
			<?php endif ?>
		</div>
	</div>
<?php else: ?>
<?php
	$esc_sex = HTML::escape($user['sex']);
	$col_sex="color:blue";
	if ($esc_sex == "M") {
		$esc_sex="♂";
		$col_sex="color:blue";
	} elseif ($esc_sex == "F") {
		$esc_sex="♀";
		$col_sex="color:red";
	} else {
		$esc_sex="";
		$col_sex="color:black";
	}
	?>
<div class="card border-info">
	<h5 class="card-header bg-info"><?= UOJLocale::get('user profile') ?></h5>
	<div class="card-body">
		<div class="row mb-4">
			<div class="col-md-4 order-md-9">
				<img class="media-object img-thumbnail d-block mx-auto" alt="<?= $user['username'] ?> Avatar" src="<?= HTML::avatar_addr($user, 256) ?>" />
			</div>
			<div class="col-md-8 order-md-1">
				<h2><span class="uoj-honor" data-realname="<?= $user['realname'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
				<div class="list-group">
					<div class="list-group-item">
						<h4 class="list-group-item-heading"><?= UOJLocale::get('email') ?></h4>
						<p class="list-group-item-text"><?= HTML::escape($user['email']) ?></p>
					</div>
					<div class="list-group-item">
						<h4 class="list-group-item-heading"><?= UOJLocale::get('QQ') ?></h4>
						<p class="list-group-item-text"><?= HTML::escape($user['qq'] != 0 ? $user['qq'] : 'Unfilled') ?></p>
					</div>
					<div class="list-group-item">
						<h4 class="list-group-item-heading"><?= UOJLocale::get('motto') ?></h4>
						<?php $motto_id = uniqid("motto-{$user['username']}-"); ?>
						<p class="list-group-item-text" id="<?= $motto_id ?>"></p>
						<script type="text/javascript">
							$(function() { $('#<?= $motto_id ?>').html(DOMPurify.sanitize('<?= addslashes($user['motto']) ?>', <?= DOM_SANITIZE_CONFIG ?>)); });
						</script>
					</div>
					
					<?php if (isSuperUser($myUser)): ?>
					<div class="list-group-item">
						<h4 class="list-group-item-heading">register time</h4>
						<p class="list-group-item-text"><?= $user['register_time'] ?></p>
					</div>
					<div class="list-group-item">
						<h4 class="list-group-item-heading">remote_addr</h4>
						<p class="list-group-item-text"><?= $user['remote_addr'] ?></p>
					</div>
					<div class="list-group-item">
						<h4 class="list-group-item-heading">http_x_forwarded_for</h4>
						<p class="list-group-item-text"><?= $user['http_x_forwarded_for'] ?></p>
					</div>
					<?php endif ?>
				</div>
			</div>
		</div>
		<?php if (Auth::check()): ?>
		<?php if (Auth::id() != $user['username']): ?>
		<a type="button" class="btn btn-info btn-sm" href="/user/msg?enter=<?= $user['username'] ?>"><span class="glyphicon glyphicon-envelope"></span> <?= UOJLocale::get('send private message') ?></a>
		<?php else: ?>
		<a type="button" class="btn btn-info btn-sm" href="/user/modify-profile"><span class="glyphicon glyphicon-pencil"></span> <?= UOJLocale::get('modify my profile') ?></a>
		<?php endif ?>
		<?php endif ?>
		
		<a type="button" class="btn btn-success btn-sm" href="<?= HTML::blog_url($user['username'], '/') ?>"><span class="glyphicon glyphicon-arrow-right"></span> <?= UOJLocale::get('visit his blog', $username) ?></a>
		<a type="button" class="btn btn-success btn-sm" href="<?= HTML::blog_url($user['username'], '/self_reviews') ?>"><span class="glyphicon glyphicon-arrow-right"></span> 查看 <?= $username ?> 的所有赛后总结</a>
		
		<div class="top-buffer-lg"></div>
		<div class="list-group">
			<div class="list-group-item">
				<?php
					$_result = DB::query("select date(submit_time), problem_id from submissions where submitter = '{$username}' and score = 100 and date(submit_time) between date_sub(curdate(), interval 1 year) and curdate()");
	$result = [];
	$vis = [];
	while ($row = DB::fetch($_result)) {
		$id = $row['date(submit_time)'] . ':' . $row['problem_id'];
		if (!$vis[$id]) {
			$vis[$id] = 1;
			$result[strtotime($row['date(submit_time)']) * 1000]++;
		}
	}
	?>
				<h4 class="list-group-item-heading"><?= UOJLocale::get('n accepted in last year', count($result)) ?></h4>
				<div id="accepted-graph"></div>
				<script>
					var accepted_graph_data = [
						<?php
			foreach ($result as $key => $val) {
				echo "{ timestamp: {$key}, count: {$val} }, ";
			}
	?>
					];

					$(document).ready(function () {
						$('#accepted-graph').github_graph({
							data: accepted_graph_data,
							texts: ['AC', 'AC'],
							h_days: ['Tue', 'Thu', 'Sat'],
						});
					});
				</script>
			</div>
			<div class="list-group-item">
				<?php
						$ac_problems = DB::selectAll("select a.problem_id as problem_id, b.title as title from best_ac_submissions a inner join problems b on a.problem_id = b.id where submitter = '{$user['username']}';");
	?>
				<h4 class="list-group-item-heading"><?= UOJLocale::get('accepted problems').'：'.UOJLocale::get('n problems in total', count($ac_problems))?> </h4>
				<ul class="list-group-item-text nav">
				<?php
						foreach ($ac_problems as $problem) {
							echo '<li class="mr-1 mb-1"><a href="/problem/', $problem['problem_id'], '" role="button" class="btn btn-light h-100" style="width: 12rem;">#', $problem['problem_id'], '. ', $problem['title'], '</a></li>';
						}

						if (empty($ac_problems)) {
							echo UOJLocale::get('none');
						}
	?>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
