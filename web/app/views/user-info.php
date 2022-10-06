<?php
function fTime($time, $gran = -1) {
	$d[0] = array(1, "seconds");
	$d[1] = array(60, "minutes");
	$d[2] = array(3600, "hours");
	$d[3] = array(86400, "days");
	$d[4] = array(604800, "weeks");
	$d[5] = array(2592000, "months");
	$d[6] = array(31104000, "years");

	$w = array();

	$return = "";
	$now = time();
	$diff = $now - $time;
	$secondsLeft = $diff;
	$stopat = 0;
	for ($i = 6; $i > $gran; $i--) {
		$w[$i] = intval($secondsLeft / $d[$i][0]);
		$secondsLeft -= ($w[$i] * $d[$i][0]);
		if ($w[$i] != 0) {
			$return .= UOJLocale::get('time::x ' . $d[$i][1], abs($w[$i])) . " ";
			switch ($i) {
				case 6: // shows years and months
					if ($stopat == 0) {
						$stopat = 5;
					}
					break;
				case 5: // shows months and weeks
					if ($stopat == 0) {
						$stopat = 4;
					}
					break;
				case 4: // shows weeks and days
					if ($stopat == 0) {
						$stopat = 3;
					}
					break;
				case 3: // shows days and hours
					if ($stopat == 0) {
						$stopat = 2;
					}
					break;
				case 2: // shows hours and minutes
					if ($stopat == 0) {
						$stopat = 1;
					}
					break;
				case 1: // shows minutes and seconds if granularity is not set higher
					break;
			}
			if ($i === $stopat) {
				break;
			}
		}
	}

	$return .= ($diff > 0) ? UOJLocale::get('time::ago') : UOJLocale::get('time::left');

	return $return;
}
?>

<div class="row">
	<div class="col-md-3">
		<div class="card">
			<img class="card-img-top" alt="Avatar of <?= $user['username'] ?>" src="<?= HTML::avatar_addr($user, 512) ?>" />
			<div class="card-body">
				<?php if ($user['usergroup'] == 'S'): ?>
				<span class="badge bg-secondary">
					<?= UOJLocale::get('admin') ?>
				</span>
				<?php endif ?>
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
				<?php if ($user['school']): ?>
				<li class="list-group-item">
					<i class="bi bi-person-badge-fill me-1"></i>
					<?= $user['school'] ?>
				</li>
				<?php endif ?>
				<?php if ($user['usertype']): ?>
				<li class="list-group-item">
					<i class="bi bi-key-fill me-1"></i>
					<?php foreach (explode(',', $user['usertype']) as $idx => $type): ?>
						<?php if ($idx): ?>, <?php endif ?>
						<?php if ($type == 'teacher'): ?>
							<?= UOJLocale::get('teacher') ?>
						<?php elseif ($type == 'student'): ?>
							<?= UOJLocale::get('student') ?>
						<?php elseif ($type == 'problem_uploader'): ?>
							<?= UOJLocale::get('problem uploader') ?>
						<?php elseif ($type == 'problem_manager'): ?>
							<?= UOJLocale::get('problem manager') ?>
						<?php elseif ($type == 'contest_judger'): ?>
							<?= UOJLocale::get('contest judger') ?>
						<?php elseif ($type == 'contest_only'): ?>
							<?= UOJLocale::get('contest only') ?>
						<?php else: ?>
							<?= HTML::escape($type) ?>
						<?php endif ?>
					<?php endforeach ?>
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
			<div class="card-footer bg-transparent">
				<?php $last_visited = strtotime($user['last_visited']) ?>
				<?php if (time() - $last_visited < 60 * 15): // 15 mins ?>
					<span class="text-success">
						<i class="bi bi-circle-fill me-1"></i>
						<?= UOJLocale::get('online') ?>
					</span>
				<?php else: ?>
					<span class="text-danger">
						<i class="bi bi-circle-fill me-1"></i>
						<?= UOJLocale::get('offline') ?>
					</span>
					<?php if ($last_visited > 0): ?>
						<span class="text-muted small">
							, <?= UOJLocale::get('last active at') ?>
							<?= fTime($last_visited, 0) ?>
						</span>
					<?php endif ?>
				<?php endif ?>
			</div>
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
			
			<?php if (!isset($is_blog_aboutme)): ?>
			<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/') ?>">
				<i class="bi bi-arrow-right-square"></i>
				<?= UOJLocale::get('visit his blog', $user['username']) ?>
			</a>
			<?php endif ?>

			<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/self_reviews') ?>">
				<i class="bi bi-arrow-right-square"></i>
				赛后总结
			</a>
		</nav>
		<div class="card card-default mb-2">
			<div class="card-body">
<?php
$_result = DB::query("select date_format(submit_time, '%Y-%m-%d'), problem_id from submissions where submitter = '{$user['username']}' and score = 100 and date(submit_time) between date_sub(curdate(), interval 1 year) and curdate()");
$result = [];
$vis = [];
$cnt = 0;
while ($row = DB::fetch($_result)) {
	$cnt++;
	$result[$row["date_format(submit_time, '%Y-%m-%d')"]]++;
}
?>
				<h4 class="card-title h5">
					<?= UOJLocale::get('n accepted in last year', $cnt) ?>
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
			<?php $ac_problems = DB::selectAll("select a.problem_id as problem_id, b.title as title from best_ac_submissions a inner join problems b on a.problem_id = b.id where submitter = '{$user['username']}' order by id") ?>
			<h4 class="card-title h5">
				<?= UOJLocale::get('accepted problems').': '.UOJLocale::get('n problems in total', count($ac_problems))?>
			</h4>
			<ul class="nav uoj-ac-problems-list">
				<?php foreach ($ac_problems as $problem): ?>
					<li class="nav-item">
						<a class="nav-link rounded uoj-ac-problems-list-item" href="/problem/<?= $problem['problem_id'] ?>" role="button">
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
					<li class="list-group-item">
						<h5 class="list-group-item-heading">last_login</h5>
						<p class="list-group-item-text"><?= $user['last_login'] ?></p>
					</li>
					<li class="list-group-item">
						<h5 class="list-group-item-heading">last_visited</h5>
						<p class="list-group-item-text"><?= $user['last_visited'] ?></p>
					</li>
				</ul>
			</div>
		<?php endif ?>
	</div>
</div>
