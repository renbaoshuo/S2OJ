<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();

if (!($user = UOJUser::query($_GET['username']))) {
	become404Page();
}

if (!isSuperUser(Auth::user()) && Auth::id() != $user['username']) {
	become403Page();
}

function newDeleteSystemMsgForm($id) {
	$form = new UOJBs4Form('remove_system_msg_' . $id);

	$form->addHidden("msg_id", $id, function ($msg_id) {
		global $user;

		if (!validateUInt($msg_id)) {
			return '消息 ID 不是有效的数字';
		}

		$msg = DB::selectFirst("select * from user_system_msg where id = {$msg_id}");
		if (!$msg || $msg['receiver'] != $user['username']) {
			return '消息不存在';
		}

		return '';
	}, null);
	$form->handle = function () {
		$msg_id = $_POST["msg_id"];
		DB::delete("delete from user_system_msg where id = {$msg_id}");
	};
	$form->submit_button_config['text'] = '删除';
	$form->submit_button_config['class_str'] = 'btn btn-link text-decoration-none text-danger p-0 mt-0';
	$form->submit_button_config['align'] = 'inline';
	$form->submit_button_config['smart_confirm'] = '';

	return $form;
}

$pag_config = [
	'page_len' => 10,
	'col_names' => ['*'],
	'table_name' => 'user_system_msg',
	'cond' => "receiver = '{$user['username']}'",
	'tail' => 'order by send_time desc',
];
$pag = new Paginator($pag_config);

$system_msgs = [];

foreach ($pag->get() as $idx => $msg) {
	$system_msgs[$idx] = $msg;

	if (isSuperUser($myUser)) {
		$delete_form = newDeleteSystemMsgForm($msg['id']);
		$delete_form->runAtServer();
		$system_msgs[$idx]['delete_form'] = $delete_form;
	}
}
?>

<?php echoUOJPageHeader('系统消息') ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>系统消息</h1>

		<div class="card mb-3">
			<ul class="list-group list-group-flush">
				<?php foreach ($system_msgs as $msg) : ?>
					<li class="list-group-item">
						<div class="mb-2 d-flex justify-content-between">
							<div>
								<?php if ($msg['title']) : ?>
									<h4 class="d-inline"><?= $msg['title'] ?></h4>
								<?php endif ?>

								<span class="text-muted small ms-2 d-inline-block">
									发送时间: <time><?= $msg['send_time'] ?></time>
								</span>
							</div>

							<?php if (isset($msg['delete_form'])) : ?>
								<?php $msg['delete_form']->printHTML() ?>
							<?php endif ?>
						</div>

						<div><?= $msg['content'] ?></div>
					</li>
				<?php endforeach ?>
				<?php if ($pag->isEmpty()) : ?>
					<div class="text-center">
						<?= UOJLocale::get('none') ?>
					</div>
				<?php endif ?>
			</ul>
		</div>

		<?= $pag->pagination() ?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php
if (Auth::id() == $user['username']) {
	DB::update("update user_system_msg set read_time = now() where receiver = '" . $user['username'] . "'");
}
?>

<?php echoUOJPageFooter() ?>
