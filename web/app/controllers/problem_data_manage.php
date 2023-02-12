<?php
if (!Auth::check()) {
	redirectToLogin();
}

requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();
UOJProblem::info('type') === 'local' || UOJResponse::page404();

$tabs_info = [
	'statement' => [
		'name' => '题面',
		'url' => UOJProblem::cur()->getUri('/manage/statement'),
	],
	'permissions' => [
		'name' => '权限',
		'url' => UOJProblem::cur()->getUri('/manage/permissions'),
	],
	'data' => [
		'name' => '数据',
		'url' => UOJProblem::cur()->getUri('/manage/data'),
	],
];

$problem = UOJProblem::info();
$problem_extra_config = UOJProblem::cur()->getExtraConfig();

$data_dir = "/var/uoj_data/{$problem['id']}";

function echoFileNotFound($file_name) {
	echo '<h5>', htmlspecialchars($file_name), '</h5>';
	echo '<div class="small text-danger"> ', '文件未找到', '</div>';
}

function echoFilePre($file_name) {
	global $data_dir;
	$file_full_name = $data_dir . '/' . $file_name;

	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_full_name);
	if ($mimetype === false) {
		echoFileNotFound($file_name);
		return;
	}
	finfo_close($finfo);

	echo '<h5 class="mb-1">', htmlspecialchars($file_name), '</h5>';
	echo '<div class="text-muted small mb-1 font-monospace">', $mimetype, '</div>';
	echo '<pre class="bg-light rounded uoj-pre">', "\n";

	$output_limit = 1000;
	if (strStartWith($mimetype, 'text/')) {
		echo htmlspecialchars(uojFilePreview($file_full_name, $output_limit));
	} else {
		echo htmlspecialchars(uojFilePreview($file_full_name, $output_limit, 'binary'));
	}
	echo "\n</pre>";
}

// 上传数据
if ($_POST['problem_data_file_submit'] == 'submit') {
	if ($_FILES["problem_data_file"]["error"] > 0) {
		$errmsg = "Error: " . $_FILES["problem_data_file"]["error"];
		becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
	} else {
		$zip_mime_types = array('application/zip', 'application/x-zip', 'application/x-zip-compressed');
		if (in_array($_FILES["problem_data_file"]["type"], $zip_mime_types) || $_FILES["problem_data_file"]["type"] == 'application/octet-stream' && substr($_FILES["problem_data_file"]["name"], -4) == '.zip') {
			$up_filename = "/tmp/" . rand(0, 100000000) . "data.zip";
			move_uploaded_file($_FILES["problem_data_file"]["tmp_name"], $up_filename);
			$zip = new ZipArchive;
			if ($zip->open($up_filename) === TRUE) {
				$zip->extractTo("/var/uoj_data/upload/{$problem['id']}");
				$zip->close();
				exec("cd /var/uoj_data/upload/{$problem['id']}; if [ -z \"`find . -maxdepth 1 -type f`\" ]; then for sub_dir in `find -maxdepth 1 -type d ! -name .`; do mv -f \$sub_dir/* . && rm -rf \$sub_dir; done; fi");
				echo "<script>alert('上传成功！')</script>";
			} else {
				$errmsg = "解压失败！";
				becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
			}
			unlink($up_filename);
		} else {
			$errmsg = "请上传zip格式！";
			becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
		}
	}
}

$info_form = new UOJForm('info');
$attachment_url = UOJProblem::cur()->getAttachmentUri();
$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">problem_{$problem['id']}_attachment.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="$attachment_url">$attachment_url</a>
		</div>
	</div>
</div>
EOD);
$download_url = UOJProblem::cur()->getMainDataUri();
$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">problem_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="$download_url">$download_url</a>
		</div>
	</div>
</div>
EOD);
$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">testlib.h</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="/download/testlib.h">下载</a>
		</div>
	</div>
</div>
EOD);

$esc_submission_requirement = HTML::escape(json_encode(json_decode($problem['submission_requirement']), JSON_PRETTY_PRINT));
$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">提交文件配置</label>
	<div class="col-sm-9">
		<pre class="uoj-pre bg-light rounded">
$esc_submission_requirement
</pre>
	</div>
</div>
EOD);
$esc_extra_config = HTML::escape(json_encode(json_decode($problem['extra_config']), JSON_PRETTY_PRINT));
$info_form->appendHTML(<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">其它配置</label>
	<div class="col-sm-9">
		<pre class="uoj-pre bg-light rounded">
$esc_extra_config
</pre>
	</div>
</div>
EOD);
if (isSuperUser(Auth::user())) {
	$info_form->addTextArea('submission_requirement', [
		'label' => '提交文件配置',
		'input_class' => 'form-control font-monospace',
		'default_value' => $problem['submission_requirement'],
		'validator_php' => function ($submission_requirement, &$vdata) {
			$submission_requirement = json_decode($submission_requirement, true);
			if ($submission_requirement === null) {
				return '不是合法的JSON';
			}
			$vdata['submission_requirement'] = json_encode($submission_requirement);
		},
	]);
	$info_form->addTextArea('extra_config', [
		'label' => '其他配置',
		'input_class' => 'form-control font-monospace',
		'default_value' => $problem['extra_config'],
		'validator_php' => function ($extra_config, &$vdata) {
			$extra_config = json_decode($extra_config, true);
			if ($extra_config === null) {
				return '不是合法的JSON';
			}
			$vdata['extra_config'] = json_encode($extra_config, JSON_FORCE_OBJECT);
		},
	]);
	$info_form->handle = function (&$vdata) use ($problem) {
		DB::update([
			"update problems",
			"set", [
				"submission_requirement" => $vdata['submission_requirement'],
				"extra_config" => $vdata['extra_config'],
			], "where", [
				"id" => $problem['id'],
			]
		]);
	};
} else {
	$info_form->config['no_submit'] = true;
}
$info_form->runAtServer();

function displayProblemConf(UOJProblemDataDisplayer $self) {
	global $info_form;
	$info_form->printHTML();

	echo '<hr class="my-3">';

	$self->echoProblemConfTable();

	$self->echoFilePre('problem.conf');
}

function addTestsTab(UOJProblemDataDisplayer $disp, UOJProblemConf $problem_conf) {
	$n_tests = $problem_conf->getVal('n_tests', 10);
	if (!validateUInt($n_tests)) {
		$disp->setProblemConfRowStatus('n_tests', 'danger');
		return false;
	}

	$inputs = [];
	$outputs = [];
	for ($num = 1; $num <= $n_tests; $num++) {
		$inputs[$num] = $problem_conf->getInputFileName($num);
		$outputs[$num] = $problem_conf->getOutputFileName($num);
		unset($disp->rest_data_files[$inputs[$num]]);
		unset($disp->rest_data_files[$outputs[$num]]);
	}

	$disp->addTab('tests', function ($self) use ($inputs, $outputs, $n_tests) {
		for ($num = 1; $num <= $n_tests; $num++) {
			echo '<div class="row">';
			echo '<div class="col-md-6">';
			$self->echoFilePre($inputs[$num]);
			echo '</div>';
			echo '<div class="col-md-6">';
			$self->echoFilePre($outputs[$num]);
			echo '</div>';
			echo '</div>';
		}
	});
	return true;
}

function addExTestsTab(UOJProblemDataDisplayer $disp, UOJProblemConf $problem_conf) {
	$has_extra_tests = $problem_conf->getNonTraditionalJudgeType() != 'submit_answer';

	if (!$has_extra_tests) {
		return false;
	}

	$n_ex_tests = $problem_conf->getVal('n_ex_tests', 0);
	if (!validateUInt($n_ex_tests)) {
		$disp->setProblemConfRowStatus('n_ex_tests', 'danger');
		return false;
	}

	if ($n_ex_tests == 0) {
		return false;
	}

	$inputs = [];
	$outputs = [];
	for ($num = 1; $num <= $n_ex_tests; $num++) {
		$inputs[$num] = $problem_conf->getExtraInputFileName($num);
		$outputs[$num] = $problem_conf->getExtraOutputFileName($num);
		unset($disp->rest_data_files[$inputs[$num]]);
		unset($disp->rest_data_files[$outputs[$num]]);
	}

	$disp->addTab('extra tests', function ($self) use ($inputs, $outputs, $n_ex_tests) {
		for ($num = 1; $num <= $n_ex_tests; $num++) {
			echo '<div class="row">';
			echo '<div class="col-md-6">';
			$self->echoFilePre($inputs[$num]);
			echo '</div>';
			echo '<div class="col-md-6">';
			$self->echoFilePre($outputs[$num]);
			echo '</div>';
			echo '</div>';
		}
	});
	return true;
}

function addSrcTab(UOJProblemDataDisplayer $disp, $tab_name, string $name) {
	$src = UOJLang::findSourceCode($name, '', [$disp, 'isFile']);
	if ($src !== false) {
		unset($disp->rest_data_files[$src['path']]);
	}
	unset($disp->rest_data_files[$name]);

	$disp->addTab($tab_name, function ($self) use ($name, $src) {
		if ($src !== false) {
			$self->echoFilePre($src['path']);
		}
		$self->echoFilePre($name);
	});
	return true;
}

function getDataDisplayer() {
	$disp = new UOJProblemDataDisplayer(UOJProblem::cur());

	$problem_conf = UOJProblem::cur()->getProblemConf();
	if ($problem_conf === -1) {
		return $disp->addTab('problem.conf', function ($self) {
			global $info_form;

			$info_form->printHTML();

			echo '<hr class="my-3">';

			$self->echoFileNotFound('problem.conf');
		});
	} elseif ($problem_conf === -2) {
		return $disp->addTab('problem.conf', function ($self) {
			global $info_form;

			$info_form->printHTML();

			echo '<hr class="my-3">';

			echo '<div class="fw-bold text-danger">problem.conf 文件格式有误</div>';
			$self->echoFilePre('problem.conf');
		});
	}

	$disp->setProblemConf($problem_conf->conf);
	unset($disp->rest_data_files['problem.conf']);
	unset($disp->rest_data_files['download.zip']);
	$disp->addTab('problem.conf', 'displayProblemConf');
	addTestsTab($disp, $problem_conf);
	addExTestsTab($disp, $problem_conf);

	$judger_name = $problem_conf->getVal('use_builtin_judger', null);
	if ($judger_name === null) {
		return $disp;
	} elseif ($judger_name === 'on') {
		if ($problem_conf->isOn('interaction_mode')) {
			if ($problem_conf->getVal('use_builtin_checker', null)) {
				$disp->addTab('checker', function ($self) {
					echo '<h4>use builtin checker: ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
				});
			} else {
				addSrcTab($disp, 'checker', 'chk');
			}
		}
		if (UOJProblem::info('hackable')) {
			addSrcTab($disp, 'standard', 'std');
			addSrcTab($disp, 'validator', 'val');
		}
		if ($problem_conf->isOn('interaction_mode')) {
			addSrcTab($disp, 'interactor', 'interactor');
		}
		return $disp;
	} else {
		return $disp->setProblemConfRowStatus('use_builtin_judger', 'danger');
	}
}

$data_disp = getDataDisplayer();

if (isset($_GET['display_file'])) {
	if (!isset($_GET['file_name'])) {
		echoFileNotFound('');
	} else {
		$data_disp->displayFile($_GET['file_name']);
	}
	die();
}

$hackable_form = new UOJForm('hackable');
$hackable_form->handle = function () use ($problem) {
	$problem['hackable'] = !$problem['hackable'];
	$ret = dataSyncProblemData($problem);
	if ($ret) {
		becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
	}

	$hackable = $problem['hackable'] ? 1 : 0;
	DB::update([
		"update problems",
		"set", ["hackable" => $hackable],
		"where", ["id" => $problem['id']]
	]);
};
$hackable_form->config['submit_container']['class'] = '';
$hackable_form->config['submit_button']['class'] = 'btn btn-warning d-block w-100';
$hackable_form->config['submit_button']['text'] = $problem['hackable'] ? '禁用 Hack 功能' : '启用 Hack 功能';
$hackable_form->config['confirm']['smart'] = true;
$hackable_form->runAtServer();

$data_form = new UOJForm('data');
$data_form->handle = function () use ($problem) {
	set_time_limit(60 * 5);
	$ret = dataSyncProblemData($problem, Auth::user());
	if ($ret) {
		becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
	}
};
$data_form->config['submit_container']['class'] = '';
$data_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
$data_form->config['submit_button']['text'] = '检验配置并同步数据';
$data_form->config['confirm']['smart'] = true;
$data_form->runAtServer();

$clear_data_form = new UOJForm('clear_data');
$clear_data_form->handle = function () {
	dataClearProblemData(UOJProblem::cur());
};
$clear_data_form->config['submit_container']['class'] = '';
$clear_data_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
$clear_data_form->config['submit_button']['text'] = '清空题目数据';
$clear_data_form->config['confirm']['smart'] = true;
$clear_data_form->runAtServer();

$rejudge_form = new UOJForm('rejudge');
$rejudge_form->handle = function () {
	UOJSubmission::rejudgeProblem(UOJProblem::cur());
};
$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudge_form->config['submit_container']['class'] = '';
$rejudge_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
$rejudge_form->config['submit_button']['text'] = '重测该题';
$rejudge_form->config['confirm']['smart'] = true;
$rejudge_form->runAtServer();

$rejudgege97_form = new UOJForm('rejudgege97');
$rejudgege97_form->handle = function () {
	UOJSubmission::rejudgeProblemGe97(UOJProblem::cur());
};
$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudgege97_form->config['submit_container']['class'] = '';
$rejudgege97_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
$rejudgege97_form->config['submit_button']['text'] = '重测 >=97 的程序';
$rejudgege97_form->config['confirm']['smart'] = true;
$rejudgege97_form->runAtServer();

if ($problem['hackable']) {
	$test_std_form = new UOJForm('test_std');
	$test_std_form->handle = function () use ($problem, $data_disp) {
		$user_std = UOJUser::query('std');
		if (!$user_std) {
			UOJResponse::message('Please create an user named "std"');
		}

		$requirement = json_decode($problem['submission_requirement'], true);


		$src_std = UOJLang::findSourceCode('std', '', [$data_disp, 'isFile']);
		if ($src_std === false) {
			UOJResponse::message('未找到 std！');
		}

		$zip_file_name = FS::randomAvailableSubmissionFileName();
		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath() . $zip_file_name, ZipArchive::CREATE) !== true) {
			UOJResponse::message('提交失败');
		}

		$content = [];
		$content['file_name'] = $zip_file_name;
		$content['config'] = [];
		$tot_size = 0;
		foreach ($requirement as $req) {
			if ($req['type'] == "source code") {
				$content['config'][] = ["{$req['name']}_language", $src_std['lang']];
				if ($zip_file->addFromString($req['file_name'], $data_disp->getFile($src_std['path'])) === false) {
					$zip_file->close();
					unlink(UOJContext::storagePath() . $zip_file_name);
					UOJResponse::message('提交失败');
				}
				$tot_size += $zip_file->statName($req['file_name'])['size'];
			}
		}

		$zip_file->close();

		$content['config'][] = ['validate_input_before_test', 'on'];
		$content['config'][] = ['problem_id', $problem['id']];
		$esc_content = json_encode($content);

		$result = [];
		$result['status'] = "Waiting";
		$result_json = json_encode($result);
		$is_hidden = $problem['is_hidden'] ? 1 : 0;

		DB::insert([
			"insert into submissions",
			"(problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden)",
			"values", DB::tuple([
				$problem['id'], DB::now(), $user_std['username'], $esc_content,
				$src_std['lang'], $tot_size, $result['status'], $result_json, $is_hidden
			])
		]);
	};
	$test_std_form->succ_href = "/submissions?problem_id={$problem['id']}";
	$test_std_form->config['submit_container']['class'] = '';
	$test_std_form->config['submit_button']['class'] = 'btn btn-warning d-block w-100';
	$test_std_form->config['submit_button']['text'] = '检验数据正确性';
	$test_std_form->runAtServer();
}
?>

<?php echoUOJPageHeader('数据管理 - ' . UOJProblem::cur()->getTitle(['with' => 'id'])) ?>

<div class="row">
	<!-- left col -->
	<div class="col-12 col-lg-9">
		<h1>
			<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?> 管理
		</h1>

		<div class="my-3">
			<?= HTML::tablist($tabs_info, 'data', 'nav-pills') ?>
		</div>

		<div class="card">
			<div class="card-header" id="div-file_list">
				<ul class="nav nav-tabs card-header-tabs">
					<?php $data_disp->echoAllTabs('problem.conf'); ?>
				</ul>
			</div>

			<div class="card-body" id="div-file_content">
				<?php $data_disp->displayFile('problem.conf'); ?>
			</div>

			<script type="text/javascript">
				curFileName = '';
				$('#div-file_list a').click(function(e) {
					$('#div-file_content').html('<h3>Loading...</h3>');
					$(this).tab('show');

					var fileName = $(this).text();
					curFileName = fileName;
					$.get('/problem/<?= $problem['id'] ?>/manage/data', {
							display_file: '',
							file_name: fileName
						},
						function(data) {
							if (curFileName != fileName) {
								return;
							}
							$('#div-file_content').html(data);
						},
						'html'
					);
					return false;
				});
			</script>
		</div>


	</div>

	<!-- right col -->
	<aside class="col-12 col-lg-3 mt-3 mt-lg-0 d-flex flex-column">
		<div class="card card-default mt-3 mt-lg-0 mb-2 order-2 order-lg-1">
			<ul class="nav nav-pills nav-fill flex-column" role="tablist">
				<li class="nav-item text-start">
					<a href="/problem/<?= $problem['id'] ?>" class="nav-link" role="tab">
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
					<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
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
		</div>

		<div class="order-1 order-lg-2">
			<div>
				<div class="mb-2">
					<?php if ($problem['hackable']) : ?>
						<i class="bi bi-check-lg text-success"></i> Hack 功能已启用
					<?php else : ?>
						<i class="bi bi-x-lg text-danger"></i> Hack 功能已禁用
					<?php endif ?>
				</div>
				<?php $hackable_form->printHTML() ?>
			</div>
			<?php if ($problem['hackable']) : ?>
				<div class="mt-2">
					<?php $test_std_form->printHTML() ?>
				</div>
			<?php endif ?>
			<div class="mt-2">
				<?php $data_form->printHTML(); ?>
			</div>
			<div class="mt-2">
				<?php $clear_data_form->printHTML(); ?>
			</div>
			<div class="mt-2">
				<?php $rejudge_form->printHTML(); ?>
			</div>
			<div class="mt-2">
				<?php $rejudgege97_form->printHTML(); ?>
			</div>

			<div class="mt-2">
				<button type="button" class="btn d-block w-100 btn-primary" data-bs-toggle="modal" data-bs-target="#UploadDataModal">上传数据</button>
			</div>
			<div class="mt-2">
				<a role="button" class="btn d-block w-100 btn-primary" href="<?= UOJProblem::cur()->getUri('/manage/data/configure') ?>">数据配置</a>
			</div>
		</div>
	</aside>

</div>

<div class="modal fade" id="UploadDataModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel">上传数据</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form action="" method="post" enctype="multipart/form-data" role="form">
				<div class="modal-body">
					<label class="form-label" for="problem_data_file">上传 zip 文件</label>
					<input class="form-control" type="file" name="problem_data_file" id="problem_data_file" accept=".zip">

					<p class="form-text">
						说明：请将所有数据放置于压缩包根目录内。若压缩包内仅存在文件夹而不存在文件，则会将这些一级子文件夹下的内容移动到根目录下，然后这些一级子文件夹删除；若这些子文件夹内存在同名文件，则会发生随机替换，仅保留一个副本。
					</p>

					<!-- hidden input for server-side check -->
					<input type="hidden" name="problem_data_file_submit" value="submit">
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success">上传</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php echoUOJPageFooter() ?>
