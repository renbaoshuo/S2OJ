<?php
define('SCRIPT_REFRESH_AS_GET', '<script>;window.location = window.location.origin + window.location.pathname + (window.location.search.length ? window.location.search + "&" : "?") + "_refresh_" + (+new Date()) + "=" + (+new Date()) + window.location.hash;</script>');

function newAddDelCmdForm($form_name, $validate, $handle, $final = null) {
	$form = new UOJForm($form_name);
	$form->addTextArea("{$form_name}_cmds", [
		'label' => '命令',
		'input_class' => 'form-control font-monospace',
		'validator_php' => function ($str, &$vdata) use ($validate) {
			$cmds = array();
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
	]);
	if (!isset($final)) {
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
		global $myUser;

		if ($myUser == null) {
			redirectToLogin();
		}

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
	$form = new UOJForm($form_name);
	$name = "zip_ans_{$form_name}";
	$text = UOJLocale::get('problems::zip file upload introduction', implode(', ', array_map(fn ($req) => $req['file_name'], $requirement)));
	$html = <<<EOD
<div id="div-{$name}">
	<label class="form-label" for="input-{$name}">$text</label>
	<input class="form-control" type="file" id="input-{$name}" name="{$name}" />
	<span class="help-block invalid-feedback" id="help-{$name}"></span>
</div>
EOD;
	$form->addNoVal($name, $html);
	$form->config['is_big'] = true;
	$form->config['has_file'] = true;
	$form->handle = function () use ($name, $requirement, $zip_file_name_gen, $handle) {
		global $myUser;

		if ($myUser == null) {
			redirectToLogin();
		}

		if (!isset($_FILES[$name])) {
			becomeMsgPage('你在干啥……怎么什么都没交过来……？');
		} elseif (!is_uploaded_file($_FILES[$name]['tmp_name'])) {
			becomeMsgPage('上传出错，貌似你什么都没交过来……？');
		}

		$up_zip_file = new ZipArchive();
		if ($up_zip_file->open($_FILES[$name]['tmp_name']) !== true) {
			becomeMsgPage('不是合法的zip压缩文件');
		}

		$tot_size = 0;
		$zip_content = array();
		foreach ($requirement as $req) {
			$stat = $up_zip_file->statName($req['file_name']);
			if ($stat === false) {
				$zip_content[$req['name']] = '';
			} else {
				$tot_size += $stat['size'];
				if ($stat['size'] > 20 * 1024 * 1024) {
					becomeMsgPage("文件 {$req['file_name']} 实际大小过大。");
				}
				$ret = $up_zip_file->getFromName($req['file_name']);
				if ($ret === false) {
					$zip_content[$req['name']] = '';
				} else {
					$zip_content[$req['name']] = $ret;
				}
			}
		}
		$up_zip_file->close();

		$zip_file_name = $zip_file_name_gen();

		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath() . $zip_file_name, ZipArchive::CREATE) !== true) {
			becomeMsgPage('提交失败');
		}

		foreach ($requirement as $req) {
			$zip_file->addFromString($req['file_name'], $zip_content[$req['name']]);
		}
		$zip_file->close();

		$content = array();
		$content['file_name'] = $zip_file_name;
		$content['config'] = array();

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
