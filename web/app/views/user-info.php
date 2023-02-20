<?php $extra = UOJUser::getExtra($user); ?>

<div class="row">
	<div class="col-md-3">
		<div class="card">
			<img class="card-img-top uoj-user-avatar" alt="Avatar of <?= $user['username'] ?>" src="<?= HTML::avatar_addr($user, 512) ?>" />
			<div class="card-body">
				<?php if ($user['usergroup'] == 'S') : ?>
					<span class="badge bg-secondary">
						<?= UOJLocale::get('user::admin') ?>
					</span>
				<?php endif ?>
				<h3>
					<?= $user['username'] ?>
					<span class="fs-6 align-middle" <?php if ($user['sex'] == 'M') : ?> style="color: blue"><i class="bi bi-gender-male"></i>
					<?php elseif ($user['sex'] == 'F') : ?>
						style="color: red"><i class="bi bi-gender-female"></i>
					<?php else : ?>
						>
					<?php endif ?>
					</span>
				</h3>
				<div class="card-text">
					<?= HTML::purifier_inline()->purify(HTML::parsedown(['username_with_color' => true])->line($user['motto'])) ?>
				</div>
			</div>
			<ul class="list-group list-group-flush">
				<li class="list-group-item">
					<i class="bi bi-person-fill me-1"></i>
					<?= $user['realname'] ?>
					<?php if ($user['realname']) : ?>
						<span class="small text-secondary">
							(<?= UOJLocale::get('user::' . $user['usertype']) ?: HTML::escape($user['usertype']) ?>)
						</span>
					<?php else : ?>
						<span class="text-secondary">
							<?= UOJLocale::get('user::' . $user['usertype']) ?: HTML::escape($user['usertype']) ?>
						</span>
					<?php endif ?>
				</li>
				<?php if ($extra['school']) : ?>
					<li class="list-group-item">
						<i class="bi bi-person-badge-fill me-1"></i>
						<?= $extra['school'] ?>
					</li>
				<?php endif ?>
				<?php if ($user['email']) : ?>
					<li class="list-group-item">
						<i class="bi bi-envelope-fill me-1"></i>
						<a class="text-decoration-none text-body" href="mailto:<?= HTML::escape($user['email']) ?>">
							<?= HTML::escape($user['email']) ?>
						</a>
					</li>
				<?php endif ?>
				<?php if ($user['qq']) : ?>
					<li class="list-group-item">
						<i class="align-text-bottom me-1">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="16" height="16">
								<path d="M433.754 420.445c-11.526 1.393-44.86-52.741-44.86-52.741 0 31.345-16.136 72.247-51.051 101.786 16.842 5.192 54.843 19.167 45.803 34.421-7.316 12.343-125.51 7.881-159.632 4.037-34.122 3.844-152.316 8.306-159.632-4.037-9.045-15.25 28.918-29.214 45.783-34.415-34.92-29.539-51.059-70.445-51.059-101.792 0 0-33.334 54.134-44.859 52.741-5.37-.65-12.424-29.644 9.347-99.704 10.261-33.024 21.995-60.478 40.144-105.779C60.683 98.063 108.982.006 224 0c113.737.006 163.156 96.133 160.264 214.963 18.118 45.223 29.912 72.85 40.144 105.778 21.768 70.06 14.716 99.053 9.346 99.704z" fill="currentColor" />
							</svg>
						</i>
						<a class="text-decoration-none text-body" href="http://wpa.qq.com/msgrd?v=3&uin=<?= HTML::escape($user['qq']) ?>&site=qq&menu=yes" target="_blank">
							<?= HTML::escape($user['qq']) ?>
						</a>
					</li>
				<?php endif ?>
				<?php if ($extra['social']['github']) : ?>
					<li class="list-group-item">
						<i class="bi bi-github me-1"></i>
						<a class="text-decoration-none text-body" href="https://github.com/<?= HTML::escape($extra['social']['github']) ?>" target="_blank">
							<?= HTML::escape($extra['social']['github']) ?>
						</a>
					</li>
				<?php endif ?>
				<?php if ($extra['social']['codeforces']) : ?>
					<li class="list-group-item d-flex align-items-center">
						<div class="flex-shrink-0">
							<i class="align-text-bottom me-1">
								<svg xmlns="http://www.w3.org/2000/svg" role="img" viewBox="0 0 24 24" width="16" height="16">
									<title>Codeforces</title>
									<path d="M4.5 7.5C5.328 7.5 6 8.172 6 9v10.5c0 .828-.672 1.5-1.5 1.5h-3C.673 21 0 20.328 0 19.5V9c0-.828.673-1.5 1.5-1.5h3zm9-4.5c.828 0 1.5.672 1.5 1.5v15c0 .828-.672 1.5-1.5 1.5h-3c-.827 0-1.5-.672-1.5-1.5v-15c0-.828.673-1.5 1.5-1.5h3zm9 7.5c.828 0 1.5.672 1.5 1.5v7.5c0 .828-.672 1.5-1.5 1.5h-3c-.828 0-1.5-.672-1.5-1.5V12c0-.828.672-1.5 1.5-1.5h3z" fill="currentColor" />
								</svg>
							</i>&nbsp;
						</div>
						<div>
							<a id="codeforces-profile-link" class="text-decoration-none" href="https://codeforces.com/profile/<?= $extra['social']['codeforces'] ?>" target="_blank" style="color: rgba(var(--bs-body-color-rgb), var(--bs-text-opacity)) !important;">
								<?= $extra['social']['codeforces'] ?>
							</a>
							<div id="codeforces-rating" style="font-family: verdana, arial, sans-serif; line-height: 1.2em; text-transform: capitalize;"></div>
						</div>
						<script>
							function getRatingColor(rating) {
								if (rating >= 2400) return 'ff0000';
								if (rating >= 2100) return 'ff8c00';
								if (rating >= 1900) return 'aa00aa';
								if (rating >= 1600) return '0000ff';
								if (rating >= 1400) return '03a89e';
								if (rating >= 1200) return '008000';
								return '808080';
							}

							function showCodeforcesRating(handle, rating, text) {
								var color = '#' + getRatingColor(rating);

								$('#codeforces-profile-link')
									.html(rating >= 3000 ? ('<span style="color:#000!important">' + handle[0] + '</span>' + handle.substring(1)) : handle)
									.css('color', color)
									.css('font-family', 'Helvetica Neue, Helvetica, Arial, sans-serif')
									.css('font-size', '1.1em')
									.css('font-weight', 'bold');
								$('#codeforces-rating')
									.html(text + ', ' + rating)
									.css('color', color);
							}

							function processCodeforcesInfoData(data) {
								if (!data || data.status !== 'OK' || !data.result || !data.result.length) return;

								var result = data.result[0];

								if (result.rating) {
									showCodeforcesRating(result.handle, result.rating, result.rank);
								} else {
									showCodeforcesRating(result.handle, 0, 'Unrated');
								}
							}

							$(document).ready(function() {
								$.get('https://codeforces.com/api/user.info?handles=<?= $extra['social']['codeforces'] ?>', function(data) {
									processCodeforcesInfoData(data);
								});
							});
						</script>
					</li>
				<?php endif ?>
				<?php if ($extra['social']['website']) : ?>
					<li class="list-group-item">
						<i class="bi bi-link-45deg me-1"></i>
						<a class="text-decoration-none text-body text-break" href="<?= HTML::escape($extra['social']['website']) ?>" target="_blank">
							<?= HTML::escape($extra['social']['website']) ?>
						</a>
					</li>
				<?php endif ?>
			</ul>
			<div class="card-footer bg-transparent">
				<?php $last_visit_time = UOJTime::str2time($user['last_visit_time']) ?>
				<?php if ((clone UOJTime::$time_now)->modify('- 15 mins') <= $last_visit_time) : ?>
					<span class="text-success">
						<i class="bi bi-circle-fill me-1"></i>
						<?= UOJLocale::get('user::online') ?>
					</span>
				<?php else : ?>
					<span class="text-danger">
						<i class="bi bi-circle-fill me-1"></i>
						<?= UOJLocale::get('user::offline') ?>
					</span>
					<?php if ($last_visit_time > 0) : ?>
						<span class="text-muted small">
							(<?= UOJLocale::get('user::last active at') ?>
							<?php if ((clone UOJTime::$time_now)->modify('- 1 day') <= $last_visit_time) : ?>
								<?= HTML::relative_time_str($last_visit_time->getTimestamp(), 0) ?>)
							<?php else : ?>
								<?= $last_visit_time->format('Y-m-d') ?>)
							<?php endif ?>
						</span>
					<?php endif ?>
				<?php endif ?>
			</div>
		</div>
	</div>
	<div class="col-md-9 mt-2 mt-md-0">
		<nav class="nav mb-2">
			<?php if (Auth::check()) : ?>
				<?php if (Auth::id() != $user['username']) : ?>
					<a class="nav-link" href="/user_msg?enter=<?= $user['username'] ?>">
						<i class="bi bi-chat-left-dots"></i>
						<?= UOJLocale::get('send private message') ?>
					</a>
				<?php endif ?>
				<?php if (Auth::id() == $user['username'] || isSuperUser(Auth::user())) : ?>
					<a class="nav-link" href="/user/<?= $user['username'] ?>/edit">
						<i class="bi bi-pencil"></i>
						<?= UOJLocale::get('modify my profile') ?>
					</a>
				<?php endif ?>
			<?php endif ?>

			<?php if (!isset($is_blog_aboutme)) : ?>
				<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/') ?>">
					<i class="bi bi-arrow-right-square"></i>
					<?= UOJLocale::get('visit his blog', $user['username']) ?>
				</a>

				<a class="nav-link" href="<?= HTML::blog_url($user['username'], '/self_reviews') ?>">
					<i class="bi bi-arrow-right-square"></i>
					<?= UOJLocale::get('contests::contest self reviews') ?>
				</a>
			<?php endif ?>
		</nav>

		<?php if (!isset($is_blog_aboutme)) : ?>
			<?php $groups = UOJGroup::queryGroupsOfUser($user) ?>
			<div class="card mb-2">
				<div class="card-header fw-bold">
					<?= UOJLocale::get('user::belongs to these groups') ?>
				</div>
				<ul class="list-group list-group-flush">
					<?php foreach ($groups as $group) : ?>
						<li class="list-group-item">
							<?= $group->getLink(['class' => 'fw-bold']) ?>
						</li>
					<?php endforeach ?>
					<?php if (empty($groups)) : ?>
						<li class="list-group-item text-center">
							<?= UOJLocale::get('none') ?>
						</li>
					<?php endif ?>
				</ul>
			</div>
		<?php endif ?>

		<?php if (isset($extra['acm'])) : ?>
			<div class="card mb-2">
				<div class="card-header fw-bold">ACM 队伍信息</div>
				<div class="list-group list-group-flush">
					<?php foreach ($extra['acm']['members'] as $mem) : ?>
						<li class="list-group-item">
							<b><?= HTML::escape($mem['name']) ?></b>（<?= HTML::escape($mem['organization']) ?>）
						</li>
					<?php endforeach ?>
				</div>
			</div>
		<?php endif ?>

		<?php if (isSuperUser(Auth::user())) : ?>
			<div class="card mb-2">
				<div class="card-header fw-bold">超级管理员可见信息</div>
				<ul class="list-group list-group-flush">
					<li class="list-group-item">
						<div class="fw-bold mb-2">注册时间</div>
						<?= $user['register_time'] ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">最后一次登录的 IP (remote_addr)</div>
						<?= $user['remote_addr'] ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">最后一次登录的 IP (http_x_forwarded_for)</div>
						<?= $user['http_x_forwarded_for'] ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">最后一次登录时间</div>
						<?= $user['last_login_time'] ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">最后一次上线时间</div>
						<?= $user['last_visit_time'] ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">过期时间</div>
						<?= $user['expiration_time'] ?: '永不过期' ?>
					</li>
					<li class="list-group-item">
						<div class="fw-bold mb-2">最近访问时使用的 IP 和终端</div>
						<dl class="row">
							<?php foreach ($extra['history'] as $vis) : ?>
								<dt class="col-sm-3"><?= HTML::escape($vis['last']) ?></dt>
								<dd class="col-sm-9">
									<b>addr</b>: <?= HTML::escape($vis['addr']) ?>
									<br>
									<b>forwarded_addr</b>: <?= HTML::escape($vis['forwarded_addr']) ?>
									<br>
									<b>user_agent</b>: <?= HTML::escape($vis['ua']) ?>
								</dd>
							<?php endforeach ?>
						</dl>
					</li>
				</ul>
			</div>
		<?php endif ?>

		<?php
		$ac_records = DB::selectAll([
			"select", DB::fields([
				"submit_time" => "date_format(submit_time, '%Y-%m-%d')",
				"problem_id",
			]),
			"from submissions",
			"where", [
				"submitter" => $user['username'],
				"score" => 100,
				["date(submit_time)", "between", DB::raw("date_sub(curdate(), interval 1 year) and curdate()")],
			]
		]);
		$last_year_ac_cnt_by_day = [];
		foreach ($ac_records as $record) {
			$last_year_ac_cnt_by_day[$record["submit_time"]]++;
		}
		?>
		<div class="card mb-2">
			<div class="card-header fw-bold"><?= UOJLocale::get('n accepted in last year', count($ac_records)) ?></div>
			<div class="card-body">
				<div id="accepted-graph" style="font-size: 14px"></div>
				<script>
					var accepted_graph_data = [
						<?php foreach ($last_year_ac_cnt_by_day as $key => $val) : ?> {
								date: '<?= $key ?>',
								count: <?= $val ?>
							},
						<?php endforeach ?>
					];

					$(document).ready(function() {
						$('#accepted-graph').CalendarHeatmap(accepted_graph_data, {});
					});
				</script>
			</div>
		</div>

		<?php $ac_problems = DB::selectAll("select problem_id from best_ac_submissions where submitter = '{$user['username']}' order by problem_id") ?>
		<div class="card mb-2">
			<div class="card-header fw-bold">
				<?= UOJLocale::get('accepted problems') . ': ' . UOJLocale::get('n problems in total', count($ac_problems)) ?>
			</div>
			<div class="card-body">
				<ul class="nav uoj-ac-problems-list">
					<?php foreach ($ac_problems as $prob) : ?>
						<?php $problem = UOJProblem::query($prob['problem_id']) ?>
						<?php if ($problem) : ?>
							<?php if ($problem->userCanView(Auth::user())) : ?>
								<li class="nav-item">
									<?= $problem->getLink(['with' => 'id', 'class' => 'nav-link rounded uoj-ac-problems-list-item']) ?>
								</li>
							<?php else : ?>
								<li class="nav-item">
									<a class="nav-link disabled rounded" role="button">
										#<?= $problem->info['id'] ?>. 隐藏的题目
									</a>
								</li>
							<?php endif ?>
						<?php endif ?>
					<?php endforeach ?>

					<?php if (empty($ac_problems)) : ?>
						<?= UOJLocale::get('none'); ?>
					<?php endif ?>
				</ul>
			</div>
		</div>
	</div>
</div>
