<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	genMoreContestInfo($contest);

	if (isSuperUser($myUser)) {
		$add_new_contestant_form = new UOJForm('add_new_contestant_form');
		$add_new_contestant_form->addInput('new_username', 'text', '用户名', '', 
			function ($x) {
				global $contest;

				if (!validateUsername($x)) {
					return '用户名不合法';
				}
				$user = queryUser($x);
				if (!$user) {
					return '用户不存在';
				}

				if (hasRegistered($user, $contest)) {
					return '该用户已经报名';
				}
				return '';
			},
			null
		);
		$add_new_contestant_form->submit_button_config['align'] = 'compressed';
		$add_new_contestant_form->submit_button_config['text'] = '注册该用户';
		$add_new_contestant_form->handle = function() {
			global $contest;

			$username = $_POST['new_username'];

			DB::query("replace into contests_registrants (username, contest_id, has_participated) values ('{$username}', {$contest['id']}, 0)");

			updateContestPlayerNum($contest);
		};
		$add_new_contestant_form->runAtServer();

		$add_group_to_contest_form = new UOJForm('add_group_to_contest');
		$add_group_to_contest_form->addInput('group_id', 'text', '小组 ID', '', 
			function ($x) {
				global $contest;

				if (!validateUInt($x)) {
					return '小组 ID 不合法';
				}
				$group = queryGroup($x);
				if (!$group) {
					return '小组不存在';
				}

				return '';
			},
			null
		);
		$add_group_to_contest_form->submit_button_config['align'] = 'compressed';
		$add_group_to_contest_form->submit_button_config['text'] = '注册该小组中的用户';
		$add_group_to_contest_form->handle = function() {
			global $contest;
			$group_id = $_POST['group_id'];

			$users = DB::selectAll("select b.username as username from groups_users a inner join user_info b on a.username = b.username where a.group_id = $group_id");

			foreach ($users as $user) {
				DB::query("replace into contests_registrants (username, contest_id, has_participated) values ('{$user['username']}', {$contest['id']}, 0)");
			}

			updateContestPlayerNum($contest);
		};
		$add_group_to_contest_form->runAtServer();

		$remove_user_from_contest_form = new UOJForm('remove_user_from_contest');
		$remove_user_from_contest_form->addInput('remove_username', 'text', '用户名', '', 
			function ($x) {
				global $contest;
				if (!validateUsername($x)) {
					return '用户名不合法';
				}

				$user = queryUser($x);
				if (!$user) {
					return '用户不存在';
				}

				if (!hasRegistered($user, $contest)) {
					return '该用户未报名';
				}

				return '';
			},
			null
		);
		$remove_user_from_contest_form->submit_button_config['align'] = 'compressed';
		$remove_user_from_contest_form->submit_button_config['text'] = '移除该用户';
		$remove_user_from_contest_form->submit_button_config['class_str'] = 'mt-2 btn btn-danger';
		$remove_user_from_contest_form->handle = function() {
			global $contest;
			$username = $_POST['remove_username'];

			DB::query("delete from contests_registrants where username = '{$username}' and contest_id = {$contest['id']}");
			updateContestPlayerNum($contest);
		};
		$remove_user_from_contest_form->runAtServer();

		$force_set_user_participated_form = new UOJForm('force_set_user_participated');
		$force_set_user_participated_form->addInput('force_set_username', 'text', '用户名', '', 
			function ($x) {
				global $contest;

				if (!validateUsername($x)) {
					return '用户名不合法';
				}

				$user = queryUser($x);
				if (!$user) {
					return '用户不存在';
				}

				if (!hasRegistered($user, $contest)) {
					return '该用户未报名';
				}

				return '';
			},
			null
		);
		$force_set_user_participated_form->submit_button_config['align'] = 'compressed';
		$force_set_user_participated_form->submit_button_config['text'] = '强制参赛';
		$force_set_user_participated_form->submit_button_config['class_str'] = 'mt-2 btn btn-warning';
		$force_set_user_participated_form->handle = function() {
			global $contest;
			$username = $_POST['force_set_username'];

			DB::query("update contests_registrants set has_participated = 1 where username = '{$username}' and contest_id = {$contest['id']}");
			updateContestPlayerNum($contest);
		};
		$force_set_user_participated_form->runAtServer();
	}

	$has_contest_permission = hasContestPermission($myUser, $contest);
	$show_ip = $has_contest_permission;
	
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		$iHasRegistered = $myUser != null && hasRegistered($myUser, $contest);
	
		if ($iHasRegistered) {
			$unregister_form = new UOJForm('unregister');
			$unregister_form->handle = function() {
				global $myUser, $contest;
				DB::query("delete from contests_registrants where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
				updateContestPlayerNum($contest);
			};
			$unregister_form->submit_button_config['align'] = 'right';
			$unregister_form->submit_button_config['class_str'] = 'btn btn-danger btn-xs';
			$unregister_form->submit_button_config['text'] = '取消报名';
			$unregister_form->succ_href = "/contests";
		
			$unregister_form->runAtServer();
		}
	}
	?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="h2 text-center">
	<?= $contest['name'] ?>
</h1>

<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED): ?>
	<?php if ($iHasRegistered): ?>
		<div class="row">
			<div class="col-6">
				<a class="text-decoration-none text-success">已报名</a>
			</div>
			<div class="col-6 text-end">
				<?php $unregister_form->printHTML(); ?>
			</div>
		</div>
	<?php else: ?>
		<div>当前尚未报名，您可以 <a class="text-decoration-none text-danger" href="/contest/<?= $contest['id'] ?>/register">报名</a>。</div>
	<?php endif ?>
<div class="mt-2"></div>
<?php endif ?>

<?php
		$header_row = '<tr><th>#</th><th>'.UOJLocale::get('username').'</th>';
	if ($show_ip) {
		$header_row .= '<th>remote_addr</th><th>http_x_forwarded_for</th>';
	}
	if ($has_contest_permission) {
		$header_row .= '<th>是否参赛</th>';
	}
	$header_row .= '</tr>';

	echoLongTable(
		['*'],
		'contests_registrants',
		"contest_id = {$contest['id']}",
		'order by username desc',
		$header_row,
		function($contestant, $num) use ($myUser, $has_contest_permission, $show_ip, $has_participated) {
			$user = queryUser($contestant['username']);

			echo '<tr>';
			echo '<td>'.$num.'</td>';
			echo '<td>'.getUserLink($contestant['username']).'</td>';
			if ($show_ip) {
				echo '<td>'.$user['remote_addr'].'</td>';
				echo '<td>'.$user['http_x_forwarded_for'].'</td>';
			}
			if ($has_contest_permission) {
				echo '<td>'.($contestant['has_participated'] ? 'Yes' : 'No').'</td>';
			}
			echo '</tr>';
		},
		[
			'page_len' => 50,
			'get_row_index' => '',
			'div_classes' => ['table-responsive', 'card', 'mb-3'],
			'table_classes' => ['table', 'uoj-table', 'mb-0', 'text-center'],
		]
	);
	?>

<?php
	if (isset($add_new_contestant_form)) {
		$add_new_contestant_form->printHTML();
	}
	if (isset($add_group_to_contest_form)) {
		$add_group_to_contest_form->printHTML();
	}
	if (isset($remove_user_from_contest_form)) {
		$remove_user_from_contest_form->printHTML();
	}
	if (isset($force_set_user_participated_form)) {
		$force_set_user_participated_form->printHTML();
	}
	?>
<?php echoUOJPageFooter() ?>
