<?php
define('SCRIPT_REFRESH_AS_GET', '<script>;window.location = window.location.origin + window.location.pathname + (window.location.search.length ? window.location.search + "&" : "?") + "_refresh_" + (+new Date()) + "=" + (+new Date()) + window.location.hash;</script>');

function newAddDelCmdForm($form_name, $validate, $handle, $final = null, $cfg = []) {
	$form = new UOJForm($form_name);
	$form->config['ctrl_enter_submit'] = true;
	$form->addTextArea("{$form_name}_cmds", [
		'label' => '命令',
		'input_class' => 'form-control font-monospace',
		'validator_php' => function ($str, &$vdata) use ($validate) {
			$cmds = [];
			foreach (explode("\n", $str) as $line_id => $raw_line) {
				$line = trim($raw_line);
				if ($line == '') {
					continue;
				}
				if ($line[0] != '+' && $line[0] != '-') {
					return '第' . ($line_id + 1) . '行：格式错误';
				}
				$obj = trim(substr($line, 1));

				if ($err = $validate($obj, $vdata)) {
					return '第' . ($line_id + 1) . '行：' . $err;
				}
				$cmds[] = array('type' => $line[0], 'obj' => $obj);
			}
			$vdata['cmds'] = $cmds;
			return '';
		},
	] + $cfg);
	if (!isset($final) || !$final) {
		$form->handle = function (&$vdata) use ($handle) {
			foreach ($vdata['cmds'] as $cmd) {
				$handle($cmd['type'], $cmd['obj'], $vdata);
			}
		};
	} else {
		$form->handle = function (&$vdata) use ($handle, $final) {
			foreach ($vdata['cmds'] as $cmd) {
				$handle($cmd['type'], $cmd['obj'], $vdata);
			}
			$final();
		};
	}
	return $form;
}

function newSubmissionForm($form_name, $requirement, $zip_file_name_gen, $handle) {
	$form = new UOJForm($form_name);

	foreach ($requirement as $req) {
		if ($req['type'] == "source code") {
			$languages = UOJLang::getAvailableLanguages(isset($req['languages']) ? $req['languages'] : null);
			$form->addSourceCodeInput("{$form_name}_{$req['name']}", [
				'filename' => $req['name'],
				'languages' => $languages,
			]);
		} else if ($req['type'] == "text") {
			$form->addTextFileInput("{$form_name}_{$req['name']}", [
				'filename' => $req['file_name'],
			]);
		}
	}

	$form->handle = function (&$vdata) use ($form_name, $requirement, $zip_file_name_gen, $handle) {
		Auth::check() || UOJResponse::page406('请登录后再提交');

		$tot_size = 0;
		$zip_file_name = $zip_file_name_gen();

		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath() . $zip_file_name, ZipArchive::CREATE) !== true) {
			UOJResponse::message('提交失败');
		}

		$content = [];
		$content['file_name'] = $zip_file_name;
		$content['config'] = [];

		foreach ($requirement as $req) {
			if ($req['type'] == "source code") {
				$content['config'][] = ["{$req['name']}_language", $_POST["{$form_name}_{$req['name']}_language"]];
			}
		}

		foreach ($requirement as $req) {
			if ($_POST["{$form_name}_{$req['name']}_upload_type"] == 'editor') {
				$zip_file->addFromString($req['file_name'], $_POST["{$form_name}_{$req['name']}_editor"]);
			} else {
				$tmp_name = UOJForm::uploadedFileTmpName("{$form_name}_{$req['name']}_file");
				if ($tmp_name == null) {
					$zip_file->addFromString($req['file_name'], '');
				} else {
					$zip_file->addFile($tmp_name, $req['file_name']);
				}
			}
			$stat = $zip_file->statName($req['file_name']);

			if ($req['type'] == 'source code') {
				$max_size = isset($req['size']) ? (int)$req['size'] : 100;
				if ($stat['size'] > $max_size * 1024) {
					$zip_file->close();
					unlink(UOJContext::storagePath() . $zip_file_name);
					UOJResponse::message("源代码长度不能超过 {$max_size} kB。");
				}
			}

			$tot_size += $stat['size'];
		}

		$zip_file->close();

		$handle($zip_file_name, $content, $tot_size);
	};
	return $form;
}

function newZipSubmissionForm($form_name, $requirement, $zip_file_name_gen, $handle) {
	$form = new DropzoneForm(
		$form_name,
		[],
		[
			'accept' => <<<EOD
				function(file, done) {
					if (file.size > 0) {
						done();
					} else {
						done('请不要上传空文件！');
					}
				}
			EOD,
		]
	);
	$form->introduction = HTML::tag('p', [], UOJLocale::get(
		'problems::zip file upload introduction',
		'<b>' . implode(', ', array_map(fn ($req) => $req['file_name'], $requirement)) . '</b>'
	));

	$form->handler = function ($form) use ($requirement, $zip_file_name_gen, $handle) {
		Auth::check() || UOJResponse::page406('请登录后再提交');

		$files = $form->getFiles();
		if (count($files) == 0) {
			UOJResponse::page406('上传出错：请提交至少一个文件');
		}

		$reqset = [];
		foreach ($requirement as $req) {
			$file_name = strtolower($req['file_name']);
			$reqset[$file_name] = true;
		}

		$fdict = [];
		$single_file_size_limit = 20 * 1024 * 1024;

		$invalid_zip_msg = '不是合法的 zip 压缩文件（压缩包里的文件名是否包含特殊字符？或者换个压缩软件试试？）';

		foreach ($files as $name => $file) {
			if (strEndWith(strtolower($name), '.zip')) {
				$up_zip_file = new ZipArchive();
				if ($up_zip_file->open($files[$name]['tmp_name']) !== true) {
					UOJResponse::page406("{$name} {$invalid_zip_msg}");
				}
				for ($i = 0; $i < $up_zip_file->numFiles; $i++) {
					$stat = $up_zip_file->statIndex($i);
					if ($stat === false) {
						UOJResponse::page406("{$name} {$invalid_zip_msg}");
					}
					$file_name = strtolower(basename($stat['name']));
					if ($stat['size'] > $single_file_size_limit) {
						UOJResponse::page406("压缩包内文件 {$file_name} 实际大小过大。");
					}
					if ($stat['size'] == 0) { // skip empty files and directories
						continue;
					}
					if (empty($reqset[$file_name])) {
						UOJResponse::page406("压缩包内包含了题目不需要的文件：{$file_name}");
					}
					if (isset($fdict[$file_name])) {
						UOJResponse::page406("压缩包内的文件出现了重复的文件名：{$file_name}");
					}
					$fdict[$file_name] = [
						'zip' => $up_zip_file,
						'zip_name' => $name,
						'size' => $stat['size'],
						'index' => $i
					];
				}
			}
		}

		foreach ($files as $name => $file) {
			if (!strEndWith(strtolower($name), '.zip')) {
				$file_name = strtolower($name);
				if ($file['size'] > $single_file_size_limit) {
					UOJResponse::page406("文件 {$file_name} 大小过大。");
				}
				if ($file['size'] == 0) { // skip empty files
					continue;
				}
				if (empty($reqset[$name])) {
					UOJResponse::page406("上传了题目不需要的文件：{$file_name}");
				}
				if (isset($fdict[$file_name])) {
					UOJResponse::page406("压缩包内的文件和直接上传的文件中出现了重复的文件名：{$file_name}");
				}
				$fdict[$file_name] = [
					'zip' => false,
					'size' => $file['size'],
					'name' => $name
				];
			}
		}

		$tot_size = 0;
		$up_content = [];
		$is_empty = true;
		foreach ($requirement as $req) {
			$file_name = strtolower($req['file_name']);
			if (empty($fdict[$file_name])) {
				$up_content[$req['name']] = '';
				continue;
			}

			$is_empty = false;
			$tot_size += $fdict[$file_name]['size'];

			if ($fdict[$file_name]['zip']) {
				$ret = $fdict[$file_name]['zip']->getFromIndex($fdict[$file_name]['index']);
				if ($ret === false) {
					UOJResponse::page406("{$fdict[$file_name]['zip_name']} {$invalid_zip_msg}");
				}
				$up_content[$req['name']] = $ret;
			} else {
				$up_content[$req['name']] = file_get_contents($files[$fdict[$file_name]['name']]['tmp_name']);
			}
		}

		if ($is_empty) {
			UOJResponse::page406('未上传任何题目要求的文件');
		}

		$zip_file_name = $zip_file_name_gen();

		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath() . $zip_file_name, ZipArchive::CREATE) !== true) {
			UOJResponse::page406('提交失败：可能是服务器空间不足导致的');
		}

		foreach ($requirement as $req) {
			$zip_file->addFromString($req['file_name'], $up_content[$req['name']]);
		}
		$zip_file->close();

		$content = [
			'file_name' => $zip_file_name,
			'config' => []
		];

		$handle($zip_file_name, $content, $tot_size);
	};

	return $form;
}

function dieWithJsonData($data) {
	header('Content-Type: application/json');
	die(json_encode($data));
}

function dieWithAlert($str) {
	die('<script>alert(decodeURIComponent("' . rawurlencode($str) . '"));</script>' . SCRIPT_REFRESH_AS_GET);
}
