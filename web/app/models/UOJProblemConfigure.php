<?php

class UOJProblemConfigure {
	public UOJProblem $problem;
	public UOJProblemConf $problem_conf;
	public string $href;

	public array $conf_keys;
	public UOJForm $simple_form;

	public static $supported_checkers = [
		'ownchk' => '自定义校验器',
		'ncmp' => 'ncmp: 单行或多行整数序列',
		'wcmp' => 'wcmp: 单行或多行字符串序列',
		'fcmp' => 'fcmp: 单行或多行数据（不忽略行末空格，但忽略文末回车）',
		'bcmp' => 'bcmp: 逐字节比较',
		'uncmp' => 'uncmp: 单行或多行整数集合',
		'yesno' => 'yesno: YES、NO 序列（不区分大小写）',
		'rcmp4' => 'rcmp4: 浮点数序列，绝对或相对误差在 1e-4 以内则视为答案正确',
		'rcmp6' => 'rcmp6: 浮点数序列，绝对或相对误差在 1e-6 以内则视为答案正确',
		'rcmp9' => 'rcmp9: 浮点数序列，绝对或相对误差在 1e-9 以内则视为答案正确',
	];

	public static $supported_score_types = [
		'int' => '整数，每个测试点的部分分向下取整',
		'real-0' => '整数，每个测试点的部分分四舍五入到整数',
		'real-1' => '实数，四舍五入到小数点后 1 位',
		'real-2' => '实数，四舍五入到小数点后 2 位',
		'real-3' => '实数，四舍五入到小数点后 3 位',
		'real-4' => '实数，四舍五入到小数点后 4 位',
		'real-5' => '实数，四舍五入到小数点后 5 位',
		'real-6' => '实数，四舍五入到小数点后 6 位',
		'real-7' => '实数，四舍五入到小数点后 7 位',
		'real-8' => '实数，四舍五入到小数点后 8 位',
	];

	private static function getCardHeader($title, $body_class = 'vstack gap-3') {
		return <<<EOD
		<div class="col-12 col-md-6">
			<div class="card h-100 overflow-hidden">
				<div class="card-header fw-bold">
					{$title}
				</div>
				<div class="card-body {$body_class}">
		EOD;
	}

	private static function getCardFooter() {
		return <<<EOD
				</div>
			</div>
		</div>
		EOD;
	}

	public function __construct(UOJProblem $problem) {
		$this->problem = $problem;
		$problem_conf = $this->problem->getProblemConf('data');
		if (!($problem_conf instanceof UOJProblemConf)) {
			$problem_conf = new UOJProblemConf([]);
		}
		$this->problem_conf = $problem_conf;

		$this->href = "/problem/{$this->problem->info['id']}/manage/data";

		$this->simple_form = new UOJForm('simple');

		$encoded_problem_conf = json_encode($problem_conf->conf, JSON_FORCE_OBJECT);
		$this->simple_form->appendHTML(<<<EOD
		<script>
			var problem_conf = {$encoded_problem_conf};
		</script>
		EOD);

		$this->simple_form->appendHTML(static::getCardHeader('基本信息'));
		$this->addSelect($this->simple_form, 'use_builtin_judger', ['on' => '默认', 'off' => '自定义 Judger'], '测评逻辑', 'on');
		$this->addSelect($this->simple_form, 'use_builtin_checker', self::$supported_checkers, '比对函数', 'ncmp');
		$this->addSelect($this->simple_form, 'score_type', self::$supported_score_types, '测试点分数数值类型', 'int');
		$this->simple_form->appendHTML(static::getCardFooter());

		$this->simple_form->appendHTML(static::getCardHeader('数据配置'));
		$this->addNumberInput($this->simple_form, 'n_tests', '数据点个数', 10);
		$this->addNumberInput($this->simple_form, 'n_ex_tests', '额外数据点个数', 0);
		$this->addNumberInput($this->simple_form, 'n_sample_tests', '样例数据点个数', 0, ['help' => '样例数据点为额外数据点中的前 x 个数据点。']);
		$this->simple_form->appendHTML(static::getCardFooter());

		$this->simple_form->appendHTML(static::getCardHeader('文件配置'));
		$this->addTextInput($this->simple_form, 'input_pre', '输入文件名称', '');
		$this->addTextInput($this->simple_form, 'input_suf', '输入文件后缀', '');
		$this->addTextInput($this->simple_form, 'output_pre', '输出文件名称', '');
		$this->addTextInput($this->simple_form, 'output_suf', '输出文件后缀', '');
		$this->simple_form->appendHTML(static::getCardFooter());

		$this->simple_form->appendHTML(static::getCardHeader('运行时限制'));
		$this->addTimeLimitInput($this->simple_form, 'time_limit', '时间限制', 1, ['help' => '单位为秒，至多三位小数。']);
		$this->addNumberInput($this->simple_form, 'memory_limit', '内存限制', 256, ['help' => '单位为 MiB。']);
		$this->addNumberInput($this->simple_form, 'output_limit', '输出长度限制', 64, ['help' => '单位为 MiB。']);
		$this->simple_form->appendHTML(static::getCardFooter());

		$this->simple_form->appendHTML(static::getCardHeader('测试点分值', ''));
		$this->simple_form->appendHTML(<<<EOD
		<details id="div-point-score-container-outer">
			<summary>展开/收起全部</summary>
			<div id="div-point-score-container" class="row gx-3 gy-2 mt-0"></div>
		</details>
		<div id="div-point-score-unavailable" style="display: none;">在启用 Subtask 时「测试点分值」不可用。</div>
		EOD);
		$this->simple_form->appendHTML(static::getCardFooter());
		$this->simple_form->appendHTML(<<<EOD
		<script>
			$(document).ready(function() {
				$('#input-n_tests').change(function() {
					problem_conf['n_tests'] = $(this).val();
					$('#div-point-score-container').problem_configure_point_scores(problem_conf);
				});

				$('#input-score_type').change(function() {
					var score_type = $(this).val();
					var step = '1';
					problem_conf['score_type'] = score_type;

					if (score_type == 'int') {
						step = '1';
					} else {
						var decimal_places = parseInt(score_type.substring(5));

						if (decimal_places == 0) {
							step = '1';
						} else {
							step = (0).toFixed(decimal_places - 1) + '1';
						}
					}

					$('.uoj-problem-configure-point-score-input', $('#div-point-score-container')).attr('step', step);
					$('.uoj-problem-configure-point-score-input', $('#div-point-score-container')).first().trigger('change');

					$('.uoj-problem-configure-subtask-score-input', $('#div-point-score-container')).attr('step', step);
					$('.uoj-problem-configure-subtask-score-input', $('#div-point-score-container')).first().trigger('change');
				});

				$('#div-point-score-container').problem_configure_point_scores(problem_conf);
			});
		</script>
		EOD);

		$this->simple_form->appendHTML(static::getCardHeader('Subtask 配置', 'p-0'));
		$this->simple_form->appendHTML(<<<EOD
		<div class="form-check form-switch m-3">
			<input class="form-check-input" type="checkbox" role="switch" id="input-enable_subtasks">
			<label class="form-check-label" for="input-enable_subtasks">启用 Subtask</label>
		</div>
		<div id="div-subtasks-container"></div>
		EOD);
		$this->simple_form->appendHTML(static::getCardFooter());
		$this->simple_form->appendHTML(<<<EOD
		<script>
			$(document).ready(function() {
				$('#input-enable_subtasks').change(function() {
					if (this.checked) {
						$('#div-point-score-container-outer').hide();
						$('#div-point-score-unavailable').show();
						$('#div-subtasks-container').problem_configure_subtasks(problem_conf);
					} else {
						$('#div-point-score-container-outer').show();
						$('#div-point-score-unavailable').hide();
						$('#div-subtasks-container').empty();
						$('.uoj-problem-configure-point-score-input').val('');

						var subtask_keys = Object.keys(problem_conf).filter(function(key) {
							return /^subtask_/.test(key);
						});

						for (var i = 0; i < subtask_keys.length; ++i) {
							problem_conf[subtask_keys[i]] = '';
						}

						problem_conf['n_subtasks'] = '';
					}

					$('#problem-conf-preview').problem_conf_preview(problem_conf);
				});

				if (problem_conf['n_subtasks']) {
					$('#input-enable_subtasks').prop('checked', true).trigger('change');
				}
			});
		</script>
		EOD);

		$this->simple_form->appendHTML(<<<EOD
		<script>
			$(document).on("keydown", "form", function(event) { 
				return event.key != "Enter";
			});
		</script>
		EOD);

		$this->simple_form->succ_href = $this->href;
		$this->simple_form->config['form']['class'] = 'row gy-3';
		$this->simple_form->config['submit_container']['class'] = 'col-12 text-center mt-3';
		$this->simple_form->config['back_button']['href'] = $this->href;
		$this->simple_form->config['back_button']['class'] = 'btn btn-secondary me-2';

		$this->simple_form->handle = fn (&$vdata) => $this->onUpload($vdata);
	}

	public function addSelect(UOJForm $form, $key, $options, $label, $default_val = '', $cfg = []) {
		$this->conf_keys[$key] = true;
		$form->addSelect($key, [
			'options' => $options,
			'label' => $label,
			'div_class' => 'row gx-2',
			'label_class' => 'col-form-label col-4',
			'select_div_class' => 'col-8',
			'default_value' => $this->problem_conf->getVal($key, $default_val),
		] + $cfg);
		$form->appendHTML(<<<EOD
		<script>
			$('#input-{$key}').change(function() {
				problem_conf['{$key}'] = $(this).val();
				$('#problem-conf-preview').problem_conf_preview(problem_conf);
			});
		</script>
		EOD);
	}

	public function addNumberInput(UOJForm $form, $key, $label, $default_val = '', $cfg = []) {
		$this->conf_keys[$key] = true;
		$form->addInput($key, [
			'type' => 'number',
			'label' => $label,
			'div_class' => 'row gx-2',
			'label_class' => 'col-form-label col-4',
			'input_div_class' => 'col-8',
			'default_value' => $this->problem_conf->getVal($key, $default_val),
			'validator_php' => function ($x) {
				return validateInt($x) ? '' : '必须为一个整数';
			},
		] + $cfg);
		$form->appendHTML(<<<EOD
		<script>
			$('#input-{$key}').change(function() {
				problem_conf['{$key}'] = $(this).val();
				$('#problem-conf-preview').problem_conf_preview(problem_conf);
			});
		</script>
		EOD);
	}

	public function addTimeLimitInput(UOJForm $form, $key, $label, $default_val = '', $cfg = []) {
		$this->conf_keys[$key] = true;
		$form->addInput($key, [
			'type' => 'number',
			'label' => $label,
			'input_attrs' => ['step' => 0.001],
			'div_class' => 'row gx-2',
			'label_class' => 'col-form-label col-4',
			'input_div_class' => 'col-8',
			'default_value' => $this->problem_conf->getVal($key, $default_val),
			'validator_php' => function ($x) {
				if (!validateUFloat($x)) {
					return '必须为整数或小数，且值大于等于零';
				} elseif (round($x * 1000) != $x * 1000) {
					return '至多包含三位小数';
				} else {
					return '';
				}
			},
		] + $cfg);
		$form->appendHTML(<<<EOD
		<script>
			$('#input-{$key}').change(function() {
				problem_conf['{$key}'] = $(this).val();
				$('#problem-conf-preview').problem_conf_preview(problem_conf);
			});
		</script>
		EOD);
	}

	public function addTextInput(UOJForm $form, $key, $label, $default_val = '', $cfg = []) {
		$this->conf_keys[$key] = true;
		$form->addInput($key, [
			'label' => $label,
			'div_class' => 'row gx-2',
			'label_class' => 'col-form-label col-4',
			'input_div_class' => 'col-8',
			'default_value' => $this->problem_conf->getVal($key, $default_val),
			'validator_php' => function ($x) {
				return ctype_graph($x) ? '' : '必须仅包含除空格以外的可见字符';
			},
		] + $cfg);
		$form->appendHTML(<<<EOD
		<script>
			$('#input-{$key}').change(function() {
				problem_conf['{$key}'] = $(this).val();
				$('#problem-conf-preview').problem_conf_preview(problem_conf);
			});
		</script>
		EOD);
	}

	public function runAtServer() {
		$this->simple_form->runAtServer();
	}

	public function onUpload(array &$vdata) {
		$conf = $this->problem_conf->conf;
		$conf_keys = $this->conf_keys;
		$n_tests = intval(UOJRequest::post('n_tests', 'validateUInt', $this->problem_conf->getVal('n_tests', 10)));
		$n_subtasks = intval(UOJRequest::post('n_subtasks', 'validateUInt', $this->problem_conf->getVal('n_subtasks', 0)));

		for ($i = 1; $i <= $n_tests; $i++) {
			$conf_keys["point_score_$i"] = true;
		}

		$conf_keys['n_subtasks'] = true;
		for ($i = 1; $i <= $n_subtasks; $i++) {
			$conf_keys["subtask_type_$i"] = true;
			$conf_keys["subtask_score_$i"] = true;
			$conf_keys["subtask_end_$i"] = true;
			$conf_keys["subtask_used_time_type_$i"] = true;

			// $conf_keys["subtask_dependence_$i"] = true;

			// $subtask_dependence_str = UOJRequest::post("subtask_dependence_$i", 'is_string', '');

			// if ($subtask_dependence_str == 'many') {
			// 	$subtask_dependence_cnt = 0;

			// 	while (UOJRequest::post("subtask_dependence_{$i}_{$subtask_dependence_cnt}", 'is_string', '') != '') {
			// 		$subtask_dependence_cnt++;
			// 		$conf_keys["subtask_dependence_{$i}_{$subtask_dependence_cnt}"] = true;
			// 	}
			// }
		}

		foreach (array_keys($conf_keys) as $key) {
			$val = UOJRequest::post($key, 'is_string', '');
			if ($key === 'use_builtin_judger') {
				if ($val === 'off') {
					unset($conf[$key]);
				} else {
					$conf[$key] = $val;
				}
			} elseif ($key === 'use_builtin_checker') {
				if ($val === 'ownchk') {
					unset($conf[$key]);
				} else {
					$conf[$key] = $val;
				}
			} else {
				if ($val !== '') {
					$conf[$key] = $val;
				} else if (isset($conf[$key])) {
					unset($conf[$key]);
				}
			}
		}

		$err = dataUpdateProblemConf($this->problem->info, $conf);
		if ($err) {
			UOJResponse::message('<div>' . $err . '</div><a href="' . $this->href . '">返回</a>');
		}
	}

	public function printHTML() {
		$this->simple_form->printHTML();
	}
}
