<?php
	requireLib('bootstrap5');
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	if (!hasProblemPermission($myUser, $problem)) {
		become403Page();
	}

	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "不存在名为{$username}的用户";
			}
			return '';
		},
		function($type, $username) {
			global $problem;
			if ($type == '+') {
				DB::query("insert into problems_permissions (problem_id, username) values (${problem['id']}, '$username')");
			} elseif ($type == '-') {
				DB::query("delete from problems_permissions where problem_id = ${problem['id']} and username = '$username'");
			}
		}
	);
	
	$managers_form->runAtServer();

	
	if (isSuperUser($myUser)) {
		$update_uploader_form = new UOJForm('update_uploader');
		$update_uploader_form->addInput('new_uploader_username', 'text', '用户名', $problem['uploader'] ?: 'root', 
			function ($x) {
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
		$update_uploader_form->submit_button_config['align'] = 'compressed';
		$update_uploader_form->submit_button_config['text'] = '修改上传者';
		$update_uploader_form->submit_button_config['class_str'] = 'mt-2 btn btn-warning';
		$update_uploader_form->handle = function() {
			global $problem;

			$username = $_POST['new_uploader_username'];

			DB::query("update problems set uploader = '{$username}' where id = {$problem['id']}");
		};
		$update_uploader_form->runAtServer();
	}
	?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 管理者 - 题目管理') ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<h1 class="h2">
	#<?= $problem['id'] ?>. <?= $problem['title'] ?> 管理
</h1>

<ul class="nav nav-pills my-3" role="tablist">
	<li class="nav-item">
		<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
			题面
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">
			管理者
		</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">
			数据
		</a>
	</li>
</ul>

<div class="card card-default">
<div class="card-body">

<table class="table">
	<thead>
		<tr>
			<th>#</th>
			<th>用户名</th>
		</tr>
	</thead>
	<tbody>
<?php
		$row_id = 0;
	$result = DB::query("select username from problems_permissions where problem_id = ${problem['id']}");
	while ($row = DB::fetch($result, MYSQLI_ASSOC)) {
		$row_id++;
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
	}
	?>
	</tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
<?php $managers_form->printHTML(); ?>

<?php if (isset($update_uploader_form)): ?>
<hr>

<?php $update_uploader_form->printHTML(); ?>
<?php endif ?>

</div>
</div>

<!-- end left col -->
</div>

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">

<div class="card card-default mb-2">
	<ul class="nav nav-pills nav-fill flex-column" role="tablist">
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>" class="nav-link" role="tab">
				<i class="bi bi-journal-text"></i>
				<?= UOJLocale::get('problems::statement') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
				<i class="bi bi-journal-bookmark"></i>
				<?= UOJLocale::get('problems::solutions') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/statistics">
				<i class="bi bi-graph-up"></i>
				<?= UOJLocale::get('problems::statistics') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a class="nav-link active" href="#" role="tab">
				<i class="bi bi-sliders"></i>
				<?= UOJLocale::get('problems::manage') ?>
			</a>
		</li>
	</ul>
	<div class="card-footer bg-transparent">
		评价：<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
	</div>
</div>

<?php uojIncludeView('sidebar', array()) ?>
</aside>
<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
