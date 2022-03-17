<?php
	requirePHPLib('form');
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);

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
			'print_after_table' => function() {}
		)
	);
?>
<?php echoUOJPageFooter() ?>
