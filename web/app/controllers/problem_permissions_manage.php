<?php
requireLib('bootstrap5');
requirePHPLib('form');

Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();

$tabs_info = [
	'statement' => [
		'name' => '题面',
		'url' => UOJProblem::cur()->getUri('/manage/statement'),
	],
	'permissions' => [
		'name' => '权限',
		'url' => UOJProblem::cur()->getUri('/manage/permissions'),
	],
];

if (UOJProblem::info('type') === 'local') {
	$tabs_info['data'] = [
		'name' => '数据',
		'url' => UOJProblem::cur()->getUri('/manage/data'),
	];
} else if (UOJProblem::info('type') === 'remote') {
	//
}

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
	},
	null,
	[
		'help' => '命令格式：命令一行一个，<code>+mike</code> 表示把 <code>mike</code> 加入管理者，<code>-mike</code> 表示把 <code>mike</code> 从管理者中移除。',
	]
);

$managers_form->runAtServer();

if (isSuperUser(Auth::user())) {
	$update_uploader_form = new UOJForm('update_uploader');
	$update_uploader_form->addInput('new_uploader_username', [
		'div_class' => 'col-auto',
		'label' => '上传者',
		'default_value' => UOJProblem::info('uploader') ?: 'root',
		'validator_php' => function ($username, &$vdata) {
			if (!UOJUser::query($username)) {
				return '用户不存在';
			}

			$vdata['username'] = $username;

			return '';
		},
	]);
	$update_uploader_form->config['submit_button']['class'] = 'btn btn-warning';
	$update_uploader_form->config['submit_button']['text'] = '修改上传者';
	$update_uploader_form->config['confirm']['smart'] = true;
	$update_uploader_form->handle = function (&$vdata) {
		DB::update([
			"update problems",
			"set", ["uploader" => $vdata['username']],
			"where", ["id" => UOJProblem::info('id')]
		]);
	};
	$update_uploader_form->runAtServer();
}

$view_type_form = new UOJForm('view_type');
$view_type_form->addSelect('view_content_type', [
	'div_class' => 'row align-items-center g-0',
	'label_class' => 'form-label col-auto m-0 flex-grow-1 me-2',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看提交文件',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => UOJProblem::cur()->getExtraConfig('view_content_type'),
]);
$view_type_form->addSelect('view_all_details_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1 me-2',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看全部详细信息',
	'options' => [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人'
	],
	'default_value' => UOJProblem::cur()->getExtraConfig('view_all_details_type'),
]);
$view_type_form->addSelect('view_details_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1 me-2',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看测试点详细信息',
	'options' => [
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => UOJProblem::cur()->getExtraConfig('view_details_type'),
]);
$view_type_form->handle = function () {
	$config = UOJProblem::cur()->getExtraConfig();
	$config['view_content_type'] = $_POST['view_content_type'];
	$config['view_all_details_type'] = $_POST['view_all_details_type'];
	$config['view_details_type'] = $_POST['view_details_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => UOJProblem::info('id')]
	]);
};
$view_type_form->runAtServer();

$solution_view_type_form = new UOJForm('solution_view_type');
$solution_view_type_form->addSelect('view_solution_type', [
	'div_class' => 'row align-items-center g-0',
	'label_class' => 'form-label col-auto m-0 flex-grow-1 me-2',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '查看题解',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' => UOJProblem::cur()->getExtraConfig('view_solution_type'),
]);
$solution_view_type_form->addSelect('submit_solution_type', [
	'div_class' => 'row align-items-center g-0 mt-3',
	'label_class' => 'form-label col-auto m-0 flex-grow-1 me-2',
	'select_class' => 'col-auto form-select w-auto',
	'label' => '提交题解',
	'options' => [
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC 后',
		'ALL' => '所有人',
	],
	'default_value' =>  UOJProblem::cur()->getExtraConfig('submit_solution_type'),
]);
$solution_view_type_form->handle = function () {
	$config = UOJProblem::cur()->getExtraConfig();
	$config['view_solution_type'] = $_POST['view_solution_type'];
	$config['submit_solution_type'] = $_POST['submit_solution_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => UOJProblem::info('id')]
	]);
};
$solution_view_type_form->runAtServer();
?>
<?php echoUOJPageHeader('权限管理 - ' . HTML::stripTags(UOJProblem::info('title'))) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<h1>
			<?= UOJProblem::cur()->getTitle() ?> 管理
		</h1>

		<div class="my-3">
			<?= HTML::tablist($tabs_info, 'permissions', 'nav-pills') ?>
		</div>

		<div class="card">
			<div class="card-header fw-bold">管理者</div>
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
							echo '<tr>', '<td>', $row_id, '</td>', '<td>', UOJUser::getLink($row['username']), '</td>', '</tr>';
						}
						?>
					</tbody>
				</table>
				<p class="text-center">
					命令格式：命令一行一个，<code>+mike</code> 表示把 <code>mike</code> 加入管理者，<code>-mike</code> 表示把 <code>mike</code> 从管理者中移除。
				</p>

				<?php $managers_form->printHTML() ?>
			</div>
		</div>

		<div class="row mt-3 gy-2 gx-3">
			<div class="col-auto">
				<div class="card">
					<div class="card-header fw-bold">
						提交记录可视权限
					</div>
					<div class="card-body">
						<?php $view_type_form->printHTML() ?>
					</div>
				</div>
			</div>

			<div class="col-auto">
				<div class="card">
					<div class="card-header fw-bold">
						题解可视权限
					</div>
					<div class="card-body">
						<?php $solution_view_type_form->printHTML() ?>
					</div>
				</div>
			</div>

			<?php if (isset($update_uploader_form)) : ?>
				<div class="col-auto">
					<div class="card border-danger">
						<div class="card-header fw-bold text-bg-danger">
							题目上传者
						</div>
						<div class="card-body">
							<?php $update_uploader_form->printHTML() ?>
						</div>
					</div>
				</div>
			<?php endif ?>
		</div>
	</div>
	<!-- end left col -->

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
					<a href="/problem/<?= UOJProblem::info('id') ?>#submit" class="nav-link" role="tab">
						<i class="bi bi-upload"></i>
						<?= UOJLocale::get('problems::submit') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="/problem/<?= UOJProblem::info('id') ?>/solutions" class="nav-link" role="tab">
						<i class="bi bi-journal-bookmark"></i>
						<?= UOJLocale::get('problems::solutions') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a class="nav-link" href="/submissions?problem_id=<?= UOJProblem::info('id') ?>">
						<i class="bi bi-list-ul"></i>
						<?= UOJLocale::get('submissions') ?>
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
		</div>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
