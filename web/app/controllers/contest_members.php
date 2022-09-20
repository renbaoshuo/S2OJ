<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
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

			$user = queryUser($username);
			if (!$user) {
				return;
			}

			DB::query("replace into contests_registrants (username, contest_id, has_participated) values ('{$user['username']}', {$contest['id']}, 0)");

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
				if (!queryUser($x)) {
					return '用户不存在';
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
			DB::query("delete from contests_registrants where username = '{$_POST['remove_username']}' and contest_id = {$contest['id']}");
			updateContestPlayerNum($contest);
		};
		$remove_user_from_contest_form->runAtServer();
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
			$unregister_form->submit_button_config['class_str'] = 'btn btn-danger btn-xs';
			$unregister_form->submit_button_config['text'] = '取消报名';
			$unregister_form->succ_href = "/contests";
		
			$unregister_form->runAtServer();
		}
	}
	?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="text-center"><?= $contest['name'] ?></h1>
<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED): ?>
	<?php if ($iHasRegistered): ?>
		<div class="float-right">
			<?php $unregister_form->printHTML(); ?>
		</div>
		<div><a style="color:green">已报名</a></div>
	<?php else: ?>
		<div>当前尚未报名，您可以<a style="color:red" href="/contest/<?= $contest['id'] ?>/register">报名</a>。</div>
	<?php endif ?>
<div class="top-buffer-sm"></div>
<?php endif ?>

<?php
		if ($show_ip) {
			$header_row = '<tr><th>#</th><th>'.UOJLocale::get('username').'</th><th>remote_addr</th></tr>';
	
			$ip_owner = array();
			foreach (DB::selectAll("select * from contests_registrants where contest_id = {$contest['id']} order by username desc") as $reg) {
				$user = queryUser($reg['username']);
				$ip_owner[$user['remote_addr']] = $reg['username'];
			}
		} else {
			$header_row = '<tr><th>#</th><th>'.UOJLocale::get('username').'</th></tr>';
		}
	
		echoLongTable(array('*'), 'contests_registrants', "contest_id = {$contest['id']}", 'order by username desc',
			$header_row,
			function($contest, $num) {
				global $myUser;
				global $show_ip, $ip_owner;
			
				$user = queryUser($contest['username']);
				$user_link = getUserLink($contest['username']);
				if (!$show_ip) {
					echo '<tr>';
				} else {
					if ($ip_owner[$user['remote_addr']] != $user['username']) {
						echo '<tr class="danger">';
					} else {
						echo '<tr>';
					}
				}
				echo '<td>'.$num.'</td>';
				echo '<td>'.$user_link.'</td>';
				if ($show_ip) {
					echo '<td>'.$user['remote_addr'].'</td>';
				}
				echo '</tr>';
			},
			array('page_len' => 100,
				'get_row_index' => '',
				'print_after_table' => function() {
					global $add_new_contestant_form, $add_group_to_contest_form;

					if (isset($add_new_contestant_form)) {
						$add_new_contestant_form->printHTML();
					}
					if (isset($add_group_to_contest_form)) {
						$add_group_to_contest_form->printHTML();
					}
					if (isset($remove_user_from_contest_form)) {
						$remove_user_from_contest_form->printHTML();
					}
				}
			)
		);
	?>
<?php echoUOJPageFooter() ?>
