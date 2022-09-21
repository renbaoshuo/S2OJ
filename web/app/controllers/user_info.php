<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	$username = $_GET['username'];

	$REQUIRE_LIB['github_contribution_graph'] = '';
	?>
<?php if (validateUsername($username) && ($user = queryUser($username))): ?>
	<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>
	<?php
		$esc_email = HTML::escape($user['email']);
		$esc_qq = HTML::escape($user['qq'] != 0 ? $user['qq'] : 'Unfilled');
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
	$motto = addslashes($user['motto']);
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
							<p class="list-group-item-text"><?= $esc_email ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('QQ') ?></h4>
							<p class="list-group-item-text"><?= $esc_qq ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('motto') ?></h4><?php
								$motto_id = uniqid("motto-{$user['username']}-");
	$dom_sanitize_config = DOM_SANITIZE_CONFIG;
	?>
							<p class="list-group-item-text" id="<?= $motto_id ?>"></p>
							<script type="text/javascript">
								$(function() { $('#<?= $motto_id ?>').html(DOMPurify.sanitize('<?= $motto ?>', <?= $dom_sanitize_config ?>)); });
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
			<a type="button" class="btn btn-success btn-sm" href="<?= HTML::url('/user/self_reviews/' . $user['username']) ?>"><span class="glyphicon glyphicon-arrow-right"></span> 查看 <?= $username ?> 的所有赛后总结</a>
			
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
<?php else: ?>
	<?php echoUOJPageHeader('不存在该用户' . ' - 用户信息') ?>
	<div class="card border-danger">
		<div class="card-header bg-danger">用户信息</div>
		<div class="card-body">
		<h4>不存在该用户</h4>
		</div>
	</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
