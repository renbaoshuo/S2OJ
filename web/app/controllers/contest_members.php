<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();

$contest = UOJContest::info();

$is_manager = UOJContest::cur()->userCanManage(Auth::user());
$show_ip = isSuperUser(Auth::user());

if ($is_manager) {
	$add_new_contestant_form = new UOJForm('add_new_contestant_form');
	$add_new_contestant_form->addInput(
		'new_username',
		[
			'placeholder' => '用户名',
			'validator_php' => function ($username, &$vdata) {
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
		]
	);
	$add_new_contestant_form->config['submit_container']['class'] = 'mt-3 text-center';
	$add_new_contestant_form->config['submit_button']['class'] = 'btn btn-secondary';
	$add_new_contestant_form->config['submit_button']['text'] = '注册该用户';
	$add_new_contestant_form->handle = function (&$vdata) {
		UOJContest::cur()->userRegister($vdata['user']);
	};
	$add_new_contestant_form->runAtServer();

	$add_group_to_contest_form = new UOJForm('add_group_to_contest');
	$add_group_to_contest_form->addInput(
		'group_id',
		[
			'placeholder' => '小组 ID',
			'validator_php' => function ($group_id, &$vdata) {
				$group = UOJGroup::query($group_id);
				if (!$group) {
					return '小组不存在';
				}

				$vdata['group'] = $group;

				return '';
			},
		]
	);
	$add_group_to_contest_form->config['submit_container']['class'] = 'mt-3 text-center';
	$add_group_to_contest_form->config['submit_button']['class'] = 'btn btn-secondary';
	$add_group_to_contest_form->config['submit_button']['text'] = '注册该小组中的用户';
	$add_group_to_contest_form->handle = function (&$vdata) {
		$usernames = $vdata['group']->getUsernames();

		foreach ($usernames as $username) {
			$user = UOJUser::query($username);

			UOJContest::cur()->userRegister($user);
		}
	};
	$add_group_to_contest_form->runAtServer();

	$remove_user_from_contest_form = new UOJForm('remove_user_from_contest');
	$remove_user_from_contest_form->addInput(
		'remove_username',
		[
			'placeholder' => '用户名',
			'validator_php' => function ($username, &$vdata) {
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
		]
	);
	$remove_user_from_contest_form->config['submit_container']['class'] = 'mt-3 text-center';
	$remove_user_from_contest_form->config['submit_button']['class'] = 'btn btn-danger';
	$remove_user_from_contest_form->config['submit_button']['text'] = '移除该用户';
	$remove_user_from_contest_form->handle = function (&$vdata) {
		UOJContest::cur()->userUnregister($vdata['user']);
	};
	$remove_user_from_contest_form->runAtServer();

	$force_set_user_participated_form = new UOJForm('force_set_user_participated');
	$force_set_user_participated_form->addInput(
		'force_set_username',
		[
			'placeholder' => '用户名',
			'validator_php' => function ($username, &$vdata) {
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
		]
	);
	$force_set_user_participated_form->config['submit_container']['class'] = 'mt-3 text-center';
	$force_set_user_participated_form->config['submit_button']['class'] = 'btn btn-warning';
	$force_set_user_participated_form->config['submit_button']['text'] = '强制参赛';
	$force_set_user_participated_form->handle = function (&$vdata) {
		UOJContest::cur()->markUserAsParticipated($vdata['user']);
	};
	$force_set_user_participated_form->runAtServer();
}

if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
	$iHasRegistered = UOJContest::cur()->userHasRegistered(Auth::user());

	if ($iHasRegistered) {
		if ($iHasRegistered && UOJContest::cur()->freeRegistration()) {
			$unregister_form = new UOJForm('unregister');
			$unregister_form->handle = function () {
				UOJContest::cur()->userUnregister(Auth::user());
			};
			$unregister_form->config['submit_container']['class'] = 'text-end';
			$unregister_form->config['submit_button']['class'] = 'btn btn-danger btn-xs';
			$unregister_form->config['submit_button']['text'] = '取消报名';
			$unregister_form->succ_href = "/contests";
			$unregister_form->runAtServer();
		}
	}
}
?>
<?php echoUOJPageHeader(HTML::stripTags(UOJContest::info('name')) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="text-center">
	<?= UOJContest::info('name') ?>
</h1>

<?php if (UOJContest::cur()->progress() == CONTEST_NOT_STARTED) : ?>
	<?php if ($iHasRegistered) : ?>
		<div class="row mb-3">
			<div class="col-6">
				<a class="text-decoration-none text-success">已报名</a>
			</div>
			<div class="col-6">
				<?php $unregister_form->printHTML() ?>
			</div>
		</div>
	<?php else : ?>
		<div class="mb-3">
			当前尚未报名，您可以 <a class="text-decoration-none text-danger" href="/contest/<?= UOJContest::info('id') ?>/register">报名</a>。
		</div>
	<?php endif ?>
<?php endif ?>

<?php
$header_row = '<tr>';
$header_row .= '<th>#</th><th>' . UOJLocale::get('username') . '</th>';
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
		echo '<td>' . UOJUser::getLink($user) . '</td>';
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

<div class="row gy-2 gx-3 align-items-center">
	<?php if (isset($add_new_contestant_form)) : ?>
		<div class="col-auto">
			<div class="card">
				<div class="card-header fw-bold">添加参赛者</div>
				<div class="card-body">
					<?php $add_new_contestant_form->printHTML() ?>
				</div>
			</div>
		</div>
	<?php endif ?>

	<?php if (isset($add_group_to_contest_form)) : ?>
		<div class="col-auto">
			<div class="card">
				<div class="card-header fw-bold">小组报名</div>
				<div class="card-body">
					<?php $add_group_to_contest_form->printHTML() ?>
				</div>
			</div>
		</div>
	<?php endif ?>

	<?php if (isset($remove_user_from_contest_form)) : ?>
		<div class="col-auto">
			<div class="card border-danger">
				<div class="card-header fw-bold text-bg-danger">移除选手</div>
				<div class="card-body">
					<?php $remove_user_from_contest_form->printHTML() ?>
				</div>
			</div>
		</div>
	<?php endif ?>

	<?php if (isset($force_set_user_participated_form)) : ?>
		<div class="col-auto">
			<div class="card">
				<div class="card-header fw-bold">强制参赛</div>
				<div class="card-body">
					<?php $force_set_user_participated_form->printHTML() ?>
				</div>
			</div>
		</div>
	<?php endif ?>
</div>

<?php echoUOJPageFooter() ?>
