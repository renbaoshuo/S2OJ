<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$managers_form = newAddDelCmdForm(
	'managers',
	'validateUserAndStoreByUsername',
	function ($type, $username, &$vdata) {
		$user = $vdata['user'][$username];
		if ($type == '+') {
			DB::insert([
				"insert into problems_permissions",
				"(problem_id, username)",
				"values", DB::tuple([UOJProblem::info('id'), $user['username']])
			]);
		} else if ($type == '-') {
			DB::delete([
				"delete from problems_permissions",
				"where", [
					"problem_id" => UOJProblem::info('id'),
					"username" => $user['username']
				]
			]);
		}
	}
);

$managers_form->runAtServer();

if (isSuperUser($myUser)) {
	$update_uploader_form = new UOJBs4Form('update_uploader');
	$update_uploader_form->addInput(
		'new_uploader_username',
		'text',
		'用户名',
		UOJProblem::info('uploader') ?: 'root',
		function ($username, &$vdata) {
			if (!UOJUser::query($username)) {
				return '用户不存在';
			}

			$vdata['username'] = $username;

			return '';
		},
		null
	);
	$update_uploader_form->submit_button_config['align'] = 'compressed';
	$update_uploader_form->submit_button_config['text'] = '修改上传者';
	$update_uploader_form->submit_button_config['class_str'] = 'mt-2 btn btn-warning';
	$update_uploader_form->handle = function (&$vdata) {
		DB::update([
			"update problems",
			"set", ["uploader" => $vdata['username']],
			"where", ["id" => UOJProblem::info('id')]
		]);
	};
	$update_uploader_form->runAtServer();
}
?>
<?php echoUOJPageHeader('管理者 - ' . HTML::stripTags(UOJProblem::info('title'))) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<?= UOJProblem::cur()->getTitle() ?> 管理
		</h1>

		<ul class="nav nav-pills my-3" role="tablist">
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/statement" role="tab">
					题面
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link active" href="/problem/<?= UOJProblem::info('id') ?>/manage/managers" role="tab">
					管理者
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/manage/data" role="tab">
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
						$res = DB::selectAll([
							"select username from problems_permissions",
							"where", ["problem_id" => UOJProblem::info('id')]
						]);
						foreach ($res as $row) {
							$row_id++;
							echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
						}
						?>
					</tbody>
				</table>
				<p class="text-center">命令格式：命令一行一个，+mike表示把mike加入管理者，-mike表示把mike从管理者中移除</p>
				<?php $managers_form->printHTML(); ?>

				<?php if (isset($update_uploader_form)) : ?>
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
					<a href="/problem/<?= UOJProblem::info('id') ?>" class="nav-link" role="tab">
						<i class="bi bi-journal-text"></i>
						<?= UOJLocale::get('problems::statement') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>/solutions" class="nav-link" role="tab">
						<i class="bi bi-journal-bookmark"></i>
						<?= UOJLocale::get('problems::solutions') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link" href="/problem/<?= UOJProblem::info('id') ?>/statistics">
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
				评价：<?= UOJProblem::cur()->getZanBlock() ?>
			</div>
		</div>

		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->

</div>

<?php echoUOJPageFooter() ?>
