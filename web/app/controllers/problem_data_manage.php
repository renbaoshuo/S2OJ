<?php
if (!Auth::check()) {
	redirectToLogin();
}

requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJProblem::cur()->userCanManage(Auth::user()) || UOJResponse::page403();
$problem = UOJProblem::info();
$problem_extra_config = UOJProblem::cur()->getExtraConfig();

$data_dir = "/var/uoj_data/${problem['id']}";

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

// 添加配置文件
if ($_POST['problem_settings_file_submit'] == 'submit') {
	if ($_POST['use_builtin_checker'] and $_POST['n_tests']) {
		$set_filename = "/var/uoj_data/upload/{$problem['id']}/problem.conf";
		$has_legacy = false;
		if (file_exists($set_filename)) {
			$has_legacy = true;
			unlink($set_filename);
		}
		$setfile = fopen($set_filename, "w");
		fwrite($setfile, "use_builtin_judger on\n");
		if ($_POST['use_builtin_checker'] != 'ownchk') {
			fwrite($setfile, "use_builtin_checker " . $_POST['use_builtin_checker'] . "\n");
		}
		fwrite($setfile, "n_tests " . $_POST['n_tests'] . "\n");
		if ($_POST['n_ex_tests']) {
			fwrite($setfile, "n_ex_tests " . $_POST['n_ex_tests'] . "\n");
		} else {
			fwrite($setfile, "n_ex_tests 0\n");
		}
		if ($_POST['n_sample_tests']) {
			fwrite($setfile, "n_sample_tests " . $_POST['n_sample_tests'] . "\n");
		} else {
			fwrite($setfile, "n_sample_tests 0\n");
		}
		if (isset($_POST['input_pre'])) {
			fwrite($setfile, "input_pre " . $_POST['input_pre'] . "\n");
		}
		if (isset($_POST['input_suf'])) {
			fwrite($setfile, "input_suf " . $_POST['input_suf'] . "\n");
		}
		if (isset($_POST['output_pre'])) {
			fwrite($setfile, "output_pre " . $_POST['output_pre'] . "\n");
		}
		if (isset($_POST['output_suf'])) {
			fwrite($setfile, "output_suf " . $_POST['output_suf'] . "\n");
		}
		fwrite($setfile, "time_limit " . ($_POST['time_limit'] ?: 1) . "\n");
		fwrite($setfile, "memory_limit " . ($_POST['memory_limit'] ?: 256) . "\n");
		fclose($setfile);
		if (!$has_legacy) {
			echo "<script>alert('添加成功！请点击「检验配置并同步数据」按钮以应用新配置文件。')</script>";
		} else {
			echo "<script>alert('替换成功！请点击「检验配置并同步数据」按钮以应用新配置文件。')</script>";
		}
	} else {
		$errmsg = "添加配置文件失败，请检查是否所有必填输入框都已填写！";
		becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
	}
}


$info_form = new UOJBs4Form('info');
$http_host = HTML::escape(UOJContext::httpHost());
$attachment_url = HTML::url("/download.php?type=attachment&id={$problem['id']}");
$info_form->appendHTML(
	<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">problem_{$problem['id']}_attachment.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="$attachment_url">$attachment_url</a>
		</div>
	</div>
</div>
EOD
);
$download_url = HTML::url("/download.php?type=problem&id={$problem['id']}");
$info_form->appendHTML(
	<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">problem_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="$download_url">$download_url</a>
		</div>
	</div>
</div>
EOD
);
$info_form->appendHTML(
	<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">testlib.h</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a class="text-decoration-none" href="/download.php?type=testlib.h">下载</a>
		</div>
	</div>
</div>
EOD
);

$esc_submission_requirement = HTML::escape(json_encode(json_decode($problem['submission_requirement']), JSON_PRETTY_PRINT));
$info_form->appendHTML(
	<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">提交文件配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre class="uoj-pre bg-light rounded">
$esc_submission_requirement
</pre>
		</div>
	</div>
</div>
EOD
);
$esc_extra_config = HTML::escape(json_encode(json_decode($problem['extra_config']), JSON_PRETTY_PRINT));
$info_form->appendHTML(
	<<<EOD
<div class="form-group row">
	<label class="col-sm-3 control-label">其它配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre class="uoj-pre bg-light rounded">
$esc_extra_config
</pre>
		</div>
	</div>
</div>
EOD
);
if (isSuperUser($myUser)) {
	$info_form->addVInput(
		'submission_requirement',
		'text',
		'提交文件配置',
		$problem['submission_requirement'],
		function ($submission_requirement, &$vdata) {
			$submission_requirement = json_decode($submission_requirement, true);
			if ($submission_requirement === null) {
				return '不是合法的JSON';
			}
			$vdata['submission_requirement'] = json_encode($submission_requirement);
		},
		null
	);
	$info_form->addVInput(
		'extra_config',
		'text',
		'其它配置',
		$problem['extra_config'],
		function ($extra_config, &$vdata) {
			$extra_config = json_decode($extra_config, true);
			if ($extra_config === null) {
				return '不是合法的JSON';
			}
			$vdata['extra_config'] = json_encode($extra_config);
		},
		null
	);
	$info_form->handle = function (&$vdata) use ($problem) {
		DB::update([
			"update problems",
			"set", [
				"submission_requirement" => $vdata['submission_requirement'],
				"extra_config" => $vdata['extra_config']
			], "where", ["id" => $problem['id']]
		]);
	};
} else {
	$info_form->no_submit = true;
}

$problem_conf = getUOJConf("$data_dir/problem.conf");

function displayProblemConf(UOJProblemDataDisplayer $self) {
	global $info_form;
	$info_form->printHTML();

	echo '<hr class="my-3">';

	$self->echoProblemConfTable();

	$self->echoFilePre('problem.conf');
}

function addTestsTab(UOJProblemDataDisplayer $disp, array $problem_conf) {
	$n_tests = getUOJConfVal($problem_conf, 'n_tests', 10);
	if (!validateUInt($n_tests)) {
		$disp->setProblemConfRowStatus('n_tests', 'danger');
		return false;
	}

	$inputs = [];
	$outputs = [];
	for ($num = 1; $num <= $n_tests; $num++) {
		$inputs[$num] = getUOJProblemInputFileName($problem_conf, $num);
		$outputs[$num] = getUOJProblemOutputFileName($problem_conf, $num);
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

function addExTestsTab(UOJProblemDataDisplayer $disp, array $problem_conf) {
	$has_extra_tests = !(isset($problem_conf['submit_answer']) && $problem_conf['submit_answer'] == 'on');

	if (!$has_extra_tests) {
		return false;
	}

	$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', 0);
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
		$inputs[$num] = getUOJProblemExtraInputFileName($problem_conf, $num);
		$outputs[$num] = getUOJProblemExtraOutputFileName($problem_conf, $num);
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

	$problem_conf = UOJProblem::cur()->getProblemConfArray();
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

	$disp->setProblemConf($problem_conf);
	unset($disp->rest_data_files['problem.conf']);
	unset($disp->rest_data_files['download.zip']);
	$disp->addTab('problem.conf', 'displayProblemConf');
	addTestsTab($disp, $problem_conf);
	addExTestsTab($disp, $problem_conf);

	$judger_name = getUOJConfVal($problem_conf, 'use_builtin_judger', null);
	if ($judger_name === null) {
		return $disp;
	} elseif ($judger_name === 'on') {
		if (!isset($problem_conf['interaction_mode'])) {
			if (isset($problem_conf['use_builtin_checker'])) {
				$disp->addTab('checker', function ($self) {
					echo '<h4>use builtin checker : ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
				});
			} else {
				addSrcTab($disp, 'checker', 'chk');
			}
		}
		if (UOJProblem::info('hackable')) {
			addSrcTab($disp, 'standard', 'std');
			addSrcTab($disp, 'validator', 'val');
		}
		if (isset($problem_conf['interaction_mode'])) {
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

$hackable_form = new UOJBs4Form('hackable');
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
$hackable_form->submit_button_config['class_str'] = 'btn btn-warning d-block w-100';
$hackable_form->submit_button_config['text'] = $problem['hackable'] ? '禁用 Hack 功能' : '启用 Hack 功能';
$hackable_form->submit_button_config['smart_confirm'] = '';

$data_form = new UOJBs4Form('data');
$data_form->handle = function () use ($problem) {
	set_time_limit(60 * 5);
	$ret = dataSyncProblemData($problem, Auth::user());
	if ($ret) {
		becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
	}
};
$data_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
$data_form->submit_button_config['text'] = '检验配置并同步数据';
$data_form->submit_button_config['smart_confirm'] = '';

$clear_data_form = new UOJBs4Form('clear_data');
$clear_data_form->handle = function () {
	global $problem;
	dataClearProblemData($problem);
};
$clear_data_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
$clear_data_form->submit_button_config['text'] = '清空题目数据';
$clear_data_form->submit_button_config['smart_confirm'] = '';

$rejudge_form = new UOJBs4Form('rejudge');
$rejudge_form->handle = function () {
	UOJSubmission::rejudgeProblem(UOJProblem::cur());
};
$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudge_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
$rejudge_form->submit_button_config['text'] = '重测该题';
$rejudge_form->submit_button_config['smart_confirm'] = '';

$rejudgege97_form = new UOJBs4Form('rejudgege97');
$rejudgege97_form->handle = function () {
	UOJSubmission::rejudgeProblemGe97(UOJProblem::cur());
};
$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
$rejudgege97_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
$rejudgege97_form->submit_button_config['text'] = '重测 >=97 的程序';
$rejudgege97_form->submit_button_config['smart_confirm'] = '';

$view_type_form = new UOJBs4Form('view_type');
$view_type_form->addVSelect(
	'view_content_type',
	array(
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	),
	'查看提交文件:',
	$problem_extra_config['view_content_type']
);
$view_type_form->addVSelect(
	'view_all_details_type',
	array(
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	),
	'查看全部详细信息:',
	$problem_extra_config['view_all_details_type']
);
$view_type_form->addVSelect(
	'view_details_type',
	array(
		'NONE' => '禁止',
		'SELF' => '仅自己',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	),
	'查看测试点详细信息:',
	$problem_extra_config['view_details_type']
);
$view_type_form->handle = function () {
	global $problem, $problem_extra_config;

	$config = $problem_extra_config;
	$config['view_content_type'] = $_POST['view_content_type'];
	$config['view_all_details_type'] = $_POST['view_all_details_type'];
	$config['view_details_type'] = $_POST['view_details_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => $problem['id']]
	]);
};
$view_type_form->submit_button_config['class_str'] = 'btn btn-warning d-block w-100 mt-2';

$solution_view_type_form = new UOJBs4Form('solution_view_type');
$solution_view_type_form->addVSelect(
	'view_solution_type',
	array(
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	),
	'查看题解:',
	$problem_extra_config['view_solution_type']
);
$solution_view_type_form->addVSelect(
	'submit_solution_type',
	array(
		'NONE' => '禁止',
		'ALL_AFTER_AC' => 'AC后',
		'ALL' => '所有人'
	),
	'提交题解:',
	$problem_extra_config['submit_solution_type']
);
$solution_view_type_form->handle = function () {
	global $problem, $problem_extra_config;

	$config = $problem_extra_config;
	$config['view_solution_type'] = $_POST['view_solution_type'];
	$config['submit_solution_type'] = $_POST['submit_solution_type'];
	$esc_config = json_encode($config);

	DB::update([
		"update problems",
		"set", ["extra_config" => $esc_config],
		"where", ["id" => $problem['id']]
	]);
};
$solution_view_type_form->submit_button_config['class_str'] = 'btn btn-warning d-block w-100 mt-2';

$difficulty_form = new UOJBs4Form('difficulty');
$difficulty_form->addVInput(
	'difficulty',
	'text',
	'难度系数',
	$problem_extra_config['difficulty'],
	function ($str) {
		if (!is_numeric($str)) {
			return '难度系数必须是一个数字';
		}
		return '';
	},
	null
);
$difficulty_form->handle = function () {
	global $problem, $problem_extra_config;
	$config = $problem_extra_config;
	$config['difficulty'] = $_POST['difficulty'] + 0;
	$esc_config = DB::escape(json_encode($config));
	DB::query("update problems set extra_config = '$esc_config' where id = '{$problem['id']}'");
};
$difficulty_form->submit_button_config['class_str'] = 'btn btn-warning d-block w-100 mt-2';

if ($problem['hackable']) {
	$test_std_form = new UOJBs4Form('test_std');
	$test_std_form->handle = function () use ($problem, $data_disp) {
		$user_std = UOJUser::query('std');
		if (!$user_std) {
			UOJResponse::message('Please create an user named "std"');
		}

		$requirement = json_decode($problem['submission_requirement'], true);


		$src_std = UOJLang::findSourceCode('std', '', [$data_disp, 'isFile']);
		if ($src_std === false) {
			UOJResponse::message('未找到std！');
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
	$test_std_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
	$test_std_form->submit_button_config['text'] = '检验数据正确性';
	$test_std_form->runAtServer();
}

$hackable_form->runAtServer();
$view_type_form->runAtServer();
$solution_view_type_form->runAtServer();
$difficulty_form->runAtServer();
$data_form->runAtServer();
$clear_data_form->runAtServer();
$rejudge_form->runAtServer();
$rejudgege97_form->runAtServer();
$info_form->runAtServer();
?>

<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 数据 - 题目管理') ?>

<div class="row">
	<!-- left col -->
	<div class="col-12 col-lg-9">

		<h1>
			#<?= $problem['id'] ?>. <?= $problem['title'] ?> 管理
		</h1>

		<ul class="nav nav-pills my-3" role="tablist">
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
					题面
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">
					管理者
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link active" href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">
					数据
				</a>
			</li>
		</ul>

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
				评价：<?= UOJProblem::cur()->getZanBlock() ?>
			</div>
		</div>

		<div class="order-1 order-lg-2">
			<div>
				<?php if ($problem['hackable']) : ?>
					<i class="bi bi-check-lg text-success"></i> Hack 功能已启用
				<?php else : ?>
					<i class="bi bi-x-lg text-danger"></i> Hack 功能已禁用
				<?php endif ?>
				<?php $hackable_form->printHTML() ?>
			</div>
			<?php if ($problem['hackable']) : ?>
				<div class="mt-2">
					<?php $test_std_form->printHTML() ?>
				</div>
			<?php endif ?>
			<div class="mt-2">
				<button id="button-display_view_type" type="button" class="btn btn-primary d-block w-100" onclick="$('#div-view_type').toggle('fast');">提交记录可视权限</button>
				<div class="mt-2" id="div-view_type" style="display:none; padding-left:5px; padding-right:5px;">
					<?php $view_type_form->printHTML(); ?>
				</div>
			</div>
			<div class="mt-2">
				<button id="button-solution_view_type" type="button" class="btn btn-primary d-block w-100" onclick="$('#div-solution_view_type').toggle('fast');">题解可视权限</button>
				<div class="mt-2" id="div-solution_view_type" style="display:none; padding-left:5px; padding-right:5px;">
					<?php $solution_view_type_form->printHTML(); ?>
				</div>
			</div>
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
				<button type="button" class="btn d-block w-100 btn-primary" data-bs-toggle="modal" data-bs-target="#ProblemSettingsFileModal">试题配置</button>
			</div>

			<div class="mt-2">
				<button id="button-difficulty" type="button" class="btn d-block w-100 btn-primary" onclick="$('#div-difficulty').toggle('fast');">难度系数</button>
				<div class="mt-2" id="div-difficulty" style="display:none; padding-left:5px; padding-right:5px;">
					<?php $difficulty_form->printHTML(); ?>
				</div>
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

<div class="modal fade" id="ProblemSettingsFileModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title" id="myModalLabel">试题配置</h4>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form class="form-horizontal" action="" method="post" role="form">
				<div class="modal-body">
					<div class="form-group row">
						<label for="use_builtin_checker" class="col-sm-5 control-label">比对函数</label>
						<div class="col-sm-7">
							<?php $checker_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'use_builtin_checker', 'ownchk') : ""; ?>
							<select class="form-select" id="use_builtin_checker" name="use_builtin_checker">
								<option value="ncmp" <?= $checker_value == "ncmp" ? 'selected' : '' ?>>ncmp: 整数序列</option>
								<option value="wcmp" <?= $checker_value == "wcmp" ? 'selected' : '' ?>>wcmp: 字符串序列</option>
								<option value="lcmp" <?= $checker_value == "lcmp" ? 'selected' : '' ?>>lcmp: 多行数据（忽略行内与行末的多余空格，同时忽略文末回车）</option>
								<option value="fcmp" <?= $checker_value == "fcmp" ? 'selected' : '' ?>>fcmp: 多行数据（不忽略行末空格，但忽略文末回车）</option>
								<option value="rcmp4" <?= $checker_value == "rcmp4" ? 'selected' : '' ?>>rcmp4: 浮点数序列（误差不超过 1e-4）</option>
								<option value="rcmp6" <?= $checker_value == "rcmp6" ? 'selected' : '' ?>>rcmp6: 浮点数序列（误差不超过 1e-6）</option>
								<option value="rcmp9" <?= $checker_value == "rcmp9" ? 'selected' : '' ?>>rcmp9: 浮点数序列（误差不超过 1e-9）</option>
								<option value="yesno" <?= $checker_value == "yesno" ? 'selected' : '' ?>>yesno: Yes、No（不区分大小写）</option>
								<option value="uncmp" <?= $checker_value == "uncmp" ? 'selected' : '' ?>>uncmp: 整数集合</option>
								<option value="bcmp" <?= $checker_value == "bcmp" ? 'selected' : '' ?>>bcmp: 二进制文件</option>
								<option value="ownchk" <?= $checker_value == "ownchk" ? 'selected' : '' ?>>自定义校验器（需上传 chk.cpp）</option>
							</select>
						</div>
					</div>
					<div class="form-group row">
						<label for="n_tests" class="col-sm-5 control-label">n_tests</label>
						<div class="col-sm-7">
							<?php $n_tests_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'n_tests', '') : ""; ?>
							<input type="number" class="form-control" id="n_tests" name="n_tests" placeholder="数据点个数（必填）" value="<?= $n_tests_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="n_ex_tests" class="col-sm-5 control-label">n_ex_tests</label>
						<div class="col-sm-7">
							<?php $n_ex_tests_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'n_ex_tests', 0) : ""; ?>
							<input type="number" class="form-control" id="n_ex_tests" name="n_ex_tests" placeholder="额外数据点个数（默认为 0）" value="<?= $n_ex_tests_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="n_sample_tests" class="col-sm-5 control-label">n_sample_tests</label>
						<div class="col-sm-7">
							<?php $n_sample_tests_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'n_sample_tests', 0) : ""; ?>
							<input type="number" class="form-control" id="n_sample_tests" name="n_sample_tests" placeholder="样例测试点个数（默认为 0）" value="<?= $n_sample_tests_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="input_pre" class="col-sm-5 control-label">input_pre</label>
						<div class="col-sm-7">
							<?php $input_pre_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'input_pre', 'input') : ""; ?>
							<input type="text" class="form-control" id="input_pre" name="input_pre" placeholder="输入文件名称（默认为 input）" value="<?= $input_pre_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="input_suf" class="col-sm-5 control-label">input_suf</label>
						<div class="col-sm-7">
							<?php $input_suf_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'input_suf', 'txt') : ""; ?>
							<input type="text" class="form-control" id="input_suf" name="input_suf" placeholder="输入文件后缀（默认为 txt）" value="<?= $input_suf_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="output_pre" class="col-sm-5 control-label">output_pre</label>
						<div class="col-sm-7">
							<?php $output_pre_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'output_pre', 'output') : ""; ?>
							<input type="text" class="form-control" id="output_pre" name="output_pre" placeholder="输出文件名称（默认为 output）" value="<?= $output_pre_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="output_suf" class="col-sm-5 control-label">output_suf</label>
						<div class="col-sm-7">
							<?php $output_suf_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'output_suf', 'txt') : ""; ?>
							<input type="text" class="form-control" id="output_suf" name="output_suf" placeholder="输出文件后缀（默认为 txt）" value="<?= $output_suf_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="time_limit" class="col-sm-5 control-label">time_limit</label>
						<div class="col-sm-7">
							<?php $time_limit_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'time_limit', 1) : ""; ?>
							<input type="text" class="form-control" id="time_limit" name="time_limit" placeholder="时间限制（默认为 1s）" value="<?= $time_limit_value ?>">
						</div>
					</div>
					<div class="form-group row">
						<label for="memory_limit" class="col-sm-5 control-label">memory_limit</label>
						<div class="col-sm-7">
							<?php $memory_limit_value = is_array($problem_conf) ? getUOJConfVal($problem_conf, 'memory_limit', 256) : ""; ?>
							<input type="number" class="form-control" id="memory_limit" name="memory_limit" placeholder="内存限制（默认为 256 MB）" value="<?= $memory_limit_value ?>">
						</div>
					</div>
					<input type="hidden" name="problem_settings_file_submit" value="submit">
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-success">确定</button>
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php echoUOJPageFooter() ?>
