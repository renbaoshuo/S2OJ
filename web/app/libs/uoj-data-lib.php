<?php
// Actually, these things should be done by main_judger so that the code would be much simpler.
// However, this lib exists due to some history issues.

function dataNewProblem($id) {
	mkdir("/var/uoj_data/upload/$id");
	mkdir("/var/uoj_data/$id");
	mkdir(UOJContext::storagePath() . "/problem_resources/$id");

	UOJLocalRun::execAnd([
		['cd', '/var/uoj_data'],
		['rm', "$id.zip"],
		['zip', "$id.zip", $id, '-r', '-q']
	]);
}

function dataClearProblemData(UOJProblem $problem) {
	$id = $problem->info['id'];
	if (!validateUInt($id)) {
		UOJLog::error("dataClearProblemData: hacker detected");
		return "invalid problem id";
	}

	UOJLocalRun::exec(['rm', $problem->getDataFolderPath(), '-r']);
	UOJLocalRun::exec(['rm', $problem->getUploadFolderPath(), '-r']);
	dataUpdateProblemLimits($problem, null, null);
	dataNewProblem($id);
}

function dataUpdateProblemLimits(UOJProblem $problem, $time_limit, $memory_limit) {
	$extra_config = $problem->getExtraConfig();

	$extra_config['time_limit'] = $time_limit;
	$extra_config['memory_limit'] = $memory_limit;

	DB::update([
		"update problems",
		"set", [
			'extra_config' => json_encode($extra_config, JSON_FORCE_OBJECT),
		],
		"where", [
			"id" => $problem->info['id'],
		],
	]);
}

class SyncProblemDataHandler {
	private UOJProblem $problem;
	private $user;
	private int $id;
	private string $upload_dir, $data_dir, $prepare_dir;
	private $requirement, $problem_extra_config;
	private $problem_conf, $final_problem_conf;
	private $allow_files;

	public function retryMsg() {
		return '请等待上一次数据上传或同步操作结束后重试';
	}

	public function __construct($problem_info, $user = null) {
		$this->problem = new UOJProblem($problem_info);
		$this->user = $user;

		if (!validateUInt($this->problem->info['id'])) {
			UOJLog::error("SyncProblemDataHandler: hacker detected");
			return;
		}
		$this->id = (int)$this->problem->info['id'];

		$this->data_dir = "/var/uoj_data/{$this->id}";
		$this->prepare_dir = "/var/uoj_data/prepare_{$this->id}";
		$this->upload_dir = "/var/uoj_data/upload/{$this->id}";
	}

	/**
	 * $type can be either LOCK_SH or LOCK_EX
	 */
	private function lock($type, $func) {
		$ret = FS::lock_file("/var/uoj_data/{$this->id}_lock", $type, $func);
		return $ret === false ? $this->retryMsg() : $ret;
	}

	private function check_conf_on($name) {
		return isset($this->problem_conf[$name]) && $this->problem_conf[$name] == 'on';
	}

	private function create_prepare_folder() {
		return mkdir($this->prepare_dir, 0755);
	}
	private function remove_prepare_folder() {
		return UOJLocalRun::exec(['rm', $this->prepare_dir, '-rf']);
	}

	private function copy_to_prepare($file_name) {
		if (!isset($this->allow_files[$file_name])) {
			throw new UOJFileNotFoundException($file_name);
		}

		$src = "{$this->upload_dir}/$file_name";
		$dest = "{$this->prepare_dir}/$file_name";

		if (file_exists($dest)) {
			return;
		}

		if (isset($this->problem_extra_config['dont_use_formatter']) || !is_file("{$this->upload_dir}/$file_name")) {
			$ret = UOJLocalRun::exec(['cp', $src, $dest, '-r']);
		} else {
			$ret = UOJLocalRun::formatter($src, $dest);
		}

		if ($ret === false) {
			throw new UOJFileNotFoundException($file_name);
		}
	}

	private function copy_file_to_prepare($file_name) {
		if (!isset($this->allow_files[$file_name]) || !is_file("{$this->upload_dir}/$file_name")) {
			throw new UOJFileNotFoundException($file_name);
		}

		$this->copy_to_prepare($file_name);
	}

	private function copy_source_code_to_prepare($code_name) { // file name without suffix
		$src = UOJLang::findSourceCode($code_name, $this->upload_dir);

		if ($src === false) {
			throw new UOJFileNotFoundException($code_name);
		}

		$this->copy_to_prepare($src['path']);
	}

	private function compile_at_prepare($name, $config = []) {
		$include_path = UOJLocalRun::$judger_include_path;

		$src = UOJLang::findSourceCode($name, $this->prepare_dir);

		if (isset($config['path'])) {
			if (rename("{$this->prepare_dir}/{$src['path']}", "{$this->prepare_dir}/{$config['path']}/{$src['path']}") === false) {
				throw new Exception("<strong>$name</strong> : move failed");
			}
			$work_path = "{$this->prepare_dir}/{$config['path']}";
		} else {
			$work_path = $this->prepare_dir;
		}

		$compile_options = [
			['custom', UOJLocalRun::$judger_run_path]
		];
		$runp_options = [
			['in', '/dev/null'],
			['out', 'stderr'],
			['err', "{$this->prepare_dir}/compiler_result.txt"],
			['tl', 60],
			['ml', 512],
			['ol', 64],
			['type', 'compiler'],
			['work-path', $work_path],
		];
		if (!empty($config['need_include_header'])) {
			$compile_options[] = ['cinclude', $include_path];
			$runp_options[] = ['add-readable-raw', "{$include_path}/"];
		}
		if (!empty($config['implementer'])) {
			$compile_options[] = ['impl', $config['implementer']];
		}
		$res = UOJLocalRun::compile($name, $compile_options, $runp_options);
		$this->final_problem_conf["{$name}_run_type"] = UOJLang::getRunTypeFromLanguage($src['lang']);
		$rstype = isset($res['rstype']) ? $res['rstype'] : 7;

		if ($rstype != 0 || $res['exit_code'] != 0) {
			if ($rstype == 0) {
				throw new Exception("<strong>$name</strong> : compile error<pre>\n" . HTML::escape(uojFilePreview("{$this->prepare_dir}/compiler_result.txt", 10000)) . "\n</pre>");
			} elseif ($rstype == 7) {
				throw new Exception("<strong>$name</strong> : compile error. No comment");
			} else {
				throw new Exception("<strong>$name</strong> : compile error. Compiler " . judgerCodeStr($rstype));
			}
		}

		unlink("{$this->prepare_dir}/compiler_result.txt");

		if (isset($config['path'])) {
			rename("{$this->prepare_dir}/{$config['path']}/{$src['path']}", "{$this->prepare_dir}/{$src['path']}");
			rename("{$this->prepare_dir}/{$config['path']}/$name", "{$this->prepare_dir}/$name");
		}
	}

	private function makefile_at_prepare() {
		$include_path = UOJLocalRun::$judger_include_path;

		$res = UOJLocalRun::exec(['/usr/bin/make', "INCLUDE_PATH={$include_path}"], [
			['in', '/dev/null'],
			['out', 'stderr'],
			['err', "{$this->prepare_dir}/makefile_result.txt"],
			['tl', 60],
			['ml', 512],
			['ol', 64],
			['type', 'compiler'],
			['work-path', $this->prepare_dir],
			['add-readable-raw', "{$include_path}/"]
		]);
		$rstype = isset($res['rstype']) ? $res['rstype'] : 7;

		if ($rstype != 0 || $res['exit_code'] != 0) {
			if ($rstype == 0) {
				throw new Exception("<strong>Makefile</strong> : compile error<pre>\n" . HTML::escape(uojFilePreview("{$this->prepare_dir}/makefile_result.txt", 10000)) . "\n</pre>");
			} elseif ($rstype == 7) {
				throw new Exception("<strong>Makefile</strong> : compile error. No comment");
			} else {
				throw new Exception("<strong>Makefile</strong> : compile error. Compiler " . judgerCodeStr($rstype));
			}
		}

		unlink("{$this->prepare_dir}/makefile_result.txt");
	}

	public function _updateProblemConf($new_problem_conf) {
		try {
			putUOJConf("{$this->upload_dir}/problem.conf", $new_problem_conf);

			$this->_sync();
			return '';
		} catch (Exception $e) {
			return $e->getMessage();
		}
	}
	public function updateProblemConf($new_problem_conf) {
		return $this->lock(LOCK_EX, fn () => $this->_updateProblemConf($new_problem_conf));
	}

	private function _addHackPoint($uploaded_input_file, $uploaded_output_file, $reason) {
		try {
			switch ($this->problem->getExtraConfig('add_hack_as')) {
				case 'test':
					$key_num = 'n_tests';
					$msg = 'add new test';
					$gen_in_name = 'getUOJProblemInputFileName';
					$gen_out_name = 'getUOJProblemOutputFileName';
					break;
				case 'ex_test':
					$key_num = 'n_ex_tests';
					$msg = 'add new extra test';
					$gen_in_name = 'getUOJProblemExtraInputFileName';
					$gen_out_name = 'getUOJProblemExtraOutputFileName';
					break;
				default:
					return 'add hack to data failed: add_hack_as should be either "ex_test" or "test"';
			}

			$new_problem_conf = $this->problem->getProblemConfArray();
			if ($new_problem_conf == -1 || $new_problem_conf == -2) {
				return $new_problem_conf;
			}
			$new_problem_conf[$key_num] = getUOJConfVal($new_problem_conf, $key_num, 0) + 1;

			putUOJConf("{$this->upload_dir}/problem.conf", $new_problem_conf);

			$new_input_name = $gen_in_name($new_problem_conf, $new_problem_conf[$key_num]);
			$new_output_name = $gen_out_name($new_problem_conf, $new_problem_conf[$key_num]);

			if (!copy($uploaded_input_file, "{$this->upload_dir}/$new_input_name")) {
				return "input file not found";
			}
			if (!copy($uploaded_output_file, "{$this->upload_dir}/$new_output_name")) {
				return "output file not found";
			}
		} catch (Exception $e) {
			return $e->getMessage();
		}

		$ret = $this->_sync();
		if ($ret !== '') {
			return "hack successfully but sync failed: $ret";
		}

		if (isset($reason['hack_url'])) {
			UOJSystemUpdate::updateProblem($this->problem, [
				'text' => 'Hack 成功，自动添加数据',
				'url' => $reason['hack_url']
			]);
		}
		UOJSubmission::rejudgeProblemAC($this->problem, [
			'reason_text' => $reason['rejudge'],
			'requestor' => ''
		]);
		return '';
	}

	public function addHackPoint($uploaded_input_file, $uploaded_output_file, $reason = []) {
		return $this->lock(LOCK_EX, fn () => $this->_addHackPoint($uploaded_input_file, $uploaded_output_file, $reason));
	}

	public function fast_hackable_check() {
		if (!$this->problem->info['hackable']) {
			return;
		}
		if (!$this->check_conf_on('use_builtin_judger')) {
			return;
		}

		if ($this->check_conf_on('submit_answer')) {
			throw new UOJProblemConfException("提交答案题不可 Hack，请先停用本题的 Hack 功能。");
		} else {
			if (UOJLang::findSourceCode('std', $this->upload_dir) === false) {
				throw new UOJProblemConfException("找不到本题的 std。请上传 std 代码文件，或停用本题的 Hack 功能。");
			}
			if (UOJLang::findSourceCode('val', $this->upload_dir) === false) {
				throw new UOJProblemConfException("找不到本题的 val。请上传 val 代码文件，或停用本题的 Hack 功能。");
			}
		}
	}

	private function _sync() {
		try {
			if (!$this->create_prepare_folder()) {
				throw new UOJSyncFailedException('创建临时文件夹失败');
			}

			$this->requirement = [];
			$this->problem_extra_config = $this->problem->getExtraConfig();

			if (!is_file("{$this->upload_dir}/problem.conf")) {
				throw new UOJFileNotFoundException("problem.conf");
			}

			$this->problem_conf = getUOJConf("{$this->upload_dir}/problem.conf");
			$this->final_problem_conf = $this->problem_conf;

			if ($this->problem_conf === -1) {
				throw new UOJFileNotFoundException("problem.conf");
			} elseif ($this->problem_conf === -2) {
				throw new UOJProblemConfException("syntax error: duplicate keys");
			}

			$this->allow_files = array_flip(FS::scandir($this->upload_dir));

			$zip_file = new ZipArchive();
			if ($zip_file->open("{$this->prepare_dir}/download.zip", ZipArchive::CREATE) !== true) {
				throw new Exception("<strong>download.zip</strong> : failed to create the zip file");
			}

			if (isset($this->allow_files['require']) && is_dir("{$this->upload_dir}/require")) {
				$this->copy_to_prepare('require');
			}

			if (isset($this->allow_files['testlib.h']) && is_file("{$this->upload_dir}/testlib.h")) {
				$this->copy_file_to_prepare('testlib.h');
			}

			$this->fast_hackable_check();

			if ($this->check_conf_on('use_builtin_judger')) {
				$n_tests = getUOJConfVal($this->problem_conf, 'n_tests', 10);
				if (!validateUInt($n_tests) || $n_tests <= 0) {
					throw new UOJProblemConfException("n_tests must be a positive integer");
				}
				for ($num = 1; $num <= $n_tests; $num++) {
					$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
					$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);

					$this->copy_file_to_prepare($input_file_name);
					$this->copy_file_to_prepare($output_file_name);
				}

				if (!$this->check_conf_on('interaction_mode')) {
					if (isset($this->problem_conf['use_builtin_checker'])) {
						if (!preg_match('/^[a-zA-Z0-9_]{1,20}$/', $this->problem_conf['use_builtin_checker'])) {
							throw new Exception("<strong>" . HTML::escape($this->problem_conf['use_builtin_checker']) . "</strong> is not a valid checker");
						}
					} else {
						$this->copy_source_code_to_prepare('chk');
						$this->compile_at_prepare('chk', ['need_include_header' => true]);
					}
				}

				if ($this->check_conf_on('submit_answer')) {
					if (!isset($this->problem_extra_config['dont_download_input'])) {
						for ($num = 1; $num <= $n_tests; $num++) {
							$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
							$zip_file->addFile("{$this->prepare_dir}/$input_file_name", "$input_file_name");
						}
					}

					$n_output_files = 0;
					for ($num = 1; $num <= $n_tests; $num++) {
						$output_file_id = getUOJConfVal($this->problem_conf, ["output_file_id_{$num}", "output_file_id"], "$num");
						if (!validateUInt($output_file_id) || $output_file_id < 0 || $output_file_id > $n_tests) {
							throw new UOJProblemConfException("output_file_id/output_file_id_{$num} must be in [1, n_tests]");
						}
						$n_output_files = max($n_output_files, $output_file_id);
					}
					for ($num = 1; $num <= $n_output_files; $num++) {
						$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);
						$this->requirement[] = ['name' => "output$num", 'type' => 'text', 'file_name' => $output_file_name];
					}
				} else {
					$n_ex_tests = getUOJConfVal($this->problem_conf, 'n_ex_tests', 0);
					if (!validateUInt($n_ex_tests) || $n_ex_tests < 0) {
						throw new UOJProblemConfException('n_ex_tests must be a non-negative integer. Current value: ' . HTML::escape($n_ex_tests));
					}

					for ($num = 1; $num <= $n_ex_tests; $num++) {
						$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
						$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);

						$this->copy_file_to_prepare($input_file_name);
						$this->copy_file_to_prepare($output_file_name);
					}

					if ($this->problem->info['hackable']) {
						$this->copy_source_code_to_prepare('std');
						if (isset($this->problem_conf['with_implementer']) && $this->problem_conf['with_implementer'] == 'on') {
							$this->compile_at_prepare('std', [
								'implementer' => 'implementer',
								'path' => 'require'
							]);
						} else {
							$this->compile_at_prepare('std');
						}
						$this->copy_source_code_to_prepare('val');
						$this->compile_at_prepare('val', ['need_include_header' => true]);
					}

					if ($this->check_conf_on('interaction_mode')) {
						$this->copy_source_code_to_prepare('interactor');
						$this->compile_at_prepare('interactor', ['need_include_header' => true]);
					}

					$n_sample_tests = getUOJConfVal($this->problem_conf, 'n_sample_tests', $n_tests);
					if (!validateUInt($n_sample_tests) || $n_sample_tests < 0) {
						throw new UOJProblemConfException('n_sample_tests must be a non-negative integer. Current value: ' . HTML::escape($n_sample_tests));
					}
					if ($n_sample_tests > $n_ex_tests) {
						throw new UOJProblemConfException("n_sample_tests can't be greater than n_ex_tests");
					}

					if (!isset($this->problem_extra_config['dont_download_sample'])) {
						for ($num = 1; $num <= $n_sample_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);
							$zip_file->addFile("{$this->prepare_dir}/{$input_file_name}", "$input_file_name");
							if (!isset($this->problem_extra_config['dont_download_sample_output'])) {
								$zip_file->addFile("{$this->prepare_dir}/{$output_file_name}", "$output_file_name");
							}
						}
					}

					$this->requirement[] = ['name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code'];
				}
			} else {
				if (!isSuperUser($this->user)) {
					throw new UOJProblemConfException("use_builtin_judger must be on.");
				} else {
					foreach ($this->allow_files as $file_name => $file_num) {
						$this->copy_to_prepare($file_name);
					}
					$this->makefile_at_prepare();

					$this->requirement[] = ['name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code'];
				}
			}
			putUOJConf("{$this->prepare_dir}/problem.conf", $this->final_problem_conf);

			if (isset($this->allow_files['download']) && is_dir("{$this->upload_dir}/download")) {
				$download_dir = "{$this->upload_dir}/download";
				foreach (FS::scandir_r($download_dir) as $file_name) {
					if (is_file("{$download_dir}/{$file_name}")) {
						$zip_file->addFile("{$download_dir}/{$file_name}", $file_name);
					}
				}
			}

			$zip_file->close();

			$orig_requirement = $this->problem->getSubmissionRequirement();
			if (!$orig_requirement) {
				DB::update([
					"update problems",
					"set", ["submission_requirement" => json_encode($this->requirement)],
					"where", ["id" => $this->id]
				]);
			}

			UOJSystemUpdate::updateProblemInternally($this->problem, [
				'text' => 'sync',
				'requestor' => Auth::check() ? Auth::id() : null
			]);
		} catch (Exception $e) {
			$this->remove_prepare_folder();
			return $e->getMessage();
		}

		UOJLocalRun::exec(['rm', $this->data_dir, '-r']);
		rename($this->prepare_dir, $this->data_dir);

		UOJLocalRun::execAnd([
			['cd', '/var/uoj_data'],
			['zip', "{$this->id}.next.zip", $this->id, '-r', '-q'],
			['mv', "{$this->id}.next.zip", "{$this->id}.zip", '-f'],
		]);

		dataUpdateProblemLimits(
			$this->problem,
			$this->final_problem_conf['time_limit'] ? (float)$this->final_problem_conf['time_limit'] : 1,
			$this->final_problem_conf['memory_limit'] ? (int)$this->final_problem_conf['memory_limit'] : 256
		);

		return '';
	}

	public function sync() {
		return $this->lock(LOCK_EX, fn () => $this->_sync());
	}
}

function dataSyncProblemData($problem, $user = null) {
	return (new SyncProblemDataHandler($problem, $user))->sync();
}

function dataAddHackPoint($problem, $uploaded_input_file, $uploaded_output_file, $reason = null, $user = null) {
	if ($reason === null) {
		if (UOJHack::cur()) {
			$reason = [
				'rejudge' => '自动重测本题所有获得 100 分的提交记录',
				'hack_url' => HTML::url(UOJHack::cur()->getUri())
			];
		} else {
			$reason = [];
		}
	}

	return (new SyncProblemDataHandler($problem, $user))->addHackPoint($uploaded_input_file, $uploaded_output_file, $reason);
}

function dataUpdateProblemConf($problem, $new_problem_conf) {
	return (new SyncProblemDataHandler($problem))->updateProblemConf($new_problem_conf);
}
