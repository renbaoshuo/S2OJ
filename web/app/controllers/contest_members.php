<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();

$contest = UOJContest::info();

$is_manager = UOJContest::cur()->userCanManage(Auth::user());
$show_ip = isSuperUser(Auth::user());

if ($is_manager) {
	$add_new_contestant_form = new UOJBs4Form('add_new_contestant_form');
	$add_new_contestant_form->addInput(
		'new_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			$user = UOJUser::query($username);

			if (!$user) {
				return '用户不存在';
			}

			if (UOJContest::cur()->userHasRegistered($user)) {
				return '该用户已经报名';
			}

			$vdata['user'] = $user;

			return '';
		},
		null
	);
	$add_new_contestant_form->submit_button_config['align'] = 'compressed';
	$add_new_contestant_form->submit_button_config['text'] = '注册该用户';
	$add_new_contestant_form->handle = function (&$vdata) {
		UOJContest::cur()->userRegister($vdata['user']);
	};
	$add_new_contestant_form->runAtServer();

	$add_group_to_contest_form = new UOJBs4Form('add_group_to_contest');
	$add_group_to_contest_form->addInput(
		'group_id',
		'text',
		'小组 ID',
		'',
		function ($group_id, &$vdata) {
			if (!validateUInt($group_id)) {
				return '小组 ID 不合法';
			}
			$group = queryGroup($group_id);
			if (!$group) {
				return '小组不存在';
			}

			$vdata['group_id'] = $group_id;

			return '';
		},
		null
	);
	$add_group_to_contest_form->submit_button_config['align'] = 'compressed';
	$add_group_to_contest_form->submit_button_config['text'] = '注册该小组中的用户';
	$add_group_to_contest_form->handle = function (&$vdata) {
		$users = queryGroupUsers($vdata['group_id']);

		foreach ($users as $user) {
			UOJContest::cur()->userRegister($user);
		}
	};
	$add_group_to_contest_form->runAtServer();

	$remove_user_from_contest_form = new UOJBs4Form('remove_user_from_contest');
	$remove_user_from_contest_form->addInput(
		'remove_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			$user = UOJUser::query($username);

			if (!$user) {
				return '用户不存在';
			}

			if (!UOJContest::cur()->userHasRegistered($user)) {
				return '该用户未报名';
			}

			$vdata['user'] = $user;

			return '';
		},
		null
	);
	$remove_user_from_contest_form->submit_button_config['align'] = 'compressed';
	$remove_user_from_contest_form->submit_button_config['text'] = '移除该用户';
	$remove_user_from_contest_form->submit_button_config['class_str'] = 'mt-2 btn btn-danger';
	$remove_user_from_contest_form->handle = function (&$vdata) {
		UOJContest::cur()->userUnregister($vdata['user']);
	};
	$remove_user_from_contest_form->runAtServer();

	$force_set_user_participated_form = new UOJBs4Form('force_set_user_participated');
	$force_set_user_participated_form->addInput(
		'force_set_username',
		'text',
		'用户名',
		'',
		function ($username, &$vdata) {
			$user = UOJUser::query($username);

			if (!$user) {
				return '用户不存在';
			}

			if (!UOJContest::cur()->userHasRegistered($user)) {
				return '该用户未报名';
			}

			$vdata['user'] = $user;

			return '';
		},
		null
	);
	$force_set_user_participated_form->submit_button_config['align'] = 'compressed';
	$force_set_user_participated_form->submit_button_config['text'] = '强制参赛';
	$force_set_user_participated_form->submit_button_config['class_str'] = 'mt-2 btn btn-warning';
	$force_set_user_participated_form->handle = function (&$vdata) {
		UOJContest::cur()->markUserAsParticipated($vdata['user']);
	};
	$force_set_user_participated_form->runAtServer();
}

if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
	$iHasRegistered = UOJContest::cur()->userHasRegistered(Auth::user());

	if ($iHasRegistered) {
		if ($iHasRegistered && UOJContest::cur()->freeRegistration()) {
			$unregister_form = new UOJBs4Form('unregister');
			$unregister_form->handle = function () {
				UOJContest::cur()->userUnregister(Auth::user());
			};
			$unregister_form->submit_button_config['class_str'] = 'btn btn-danger btn-xs';
			$unregister_form->submit_button_config['text'] = '取消报名';
			$unregister_form->succ_href = "/contests";

			$unregister_form->runAtServer();
		}
	}
}
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="text-center">
	<?= $contest['name'] ?>
</h1>

<?php if ($contest['cur_progress'] == CONTEST_NOT_STARTED) : ?>
	<?php if ($iHasRegistered) : ?>
		<div class="row">
			<div class="col-6">
				<a class="text-decoration-none text-success">已报名</a>
			</div>
			<div class="col-6 text-end">
				<?php $unregister_form->printHTML(); ?>
			</div>
		</div>
	<?php else : ?>
		<div>当前尚未报名，您可以 <a class="text-decoration-none text-danger" href="/contest/<?= $contest['id'] ?>/register">报名</a>。</div>
	<?php endif ?>
	<div class="mt-2"></div>
<?php endif ?>

<?php
$header_row = '<tr><th>#</th><th>' . UOJLocale::get('username') . '</th>';
if ($show_ip) {
	$header_row .= '<th>remote_addr</th><th>http_x_forwarded_for</th>';
}
if ($is_manager) {
	$header_row .= '<th>是否参赛</th>';
}
$header_row .= '</tr>';

echoLongTable(
	['*'],
	'contests_registrants',
	['contest_id' => $contest['id']],
	'order by username desc',
	$header_row,
	function ($contestant, $num) use ($is_manager, $show_ip) {
		$user = UOJUser::query($contestant['username']);

		echo '<tr>';
		echo '<td>' . $num . '</td>';
		echo '<td>' . getUserLink($user['username']) . '</td>';
		if ($show_ip) {
			echo '<td>' . $user['remote_addr'] . '</td>';
			echo '<td>' . $user['http_x_forwarded_for'] . '</td>';
		}
		if ($is_manager) {
			echo '<td>' . ($contestant['has_participated'] ? 'Yes' : 'No') . '</td>';
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
