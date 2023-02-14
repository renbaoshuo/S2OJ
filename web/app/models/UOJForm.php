<?php

class UOJForm {
	public ?string $form_name;
	public ?string $succ_href;
	public $extra_validator = null;
	public $handle;
	private $ajax_submit_js = null;
	private $run_at_server_handler = [];
	private $data = [];
	private $vdata = [];
	private $main_html = '';

	public $config = [
		'is_big' => false,
		'has_file' => false,
		'ctrl_enter_submit' => false,
		'max_post_size' => 15728640, // 15M
		'max_file_size_mb' => 10, // 10M
		'form' => [
			'class' => '',
		],
		'submit_container' => [
			'class' => 'mt-3 text-center',
		],
		'submit_button' => [
			'class' => 'btn btn-primary',
			'text' => '提交',
		],
		'back_button' => [
			'href' => null,
			'class' => 'btn btn-secondary',
		],
		'confirm' => [
			'smart' => false,
			'text' => null,
		]
	];

	public function __construct($form_name) {
		$this->form_name = $form_name;
		$this->succ_href = UOJContext::requestURI();
		$this->handle = function (&$vdata) {
		};

		$this->run_at_server_handler["check-{$this->form_name}"] = function () {
			die(json_encode($this->validateAtServer()));
		};
		$this->run_at_server_handler["submit-{$this->form_name}"] = function () {
			if ($this->config['no_submit']) {
				UOJResponse::page404();
			}
			foreach ($this->data as $field) {
				if (!isset($field['no_val']) && !isset($_POST[$field['name']])) {
					UOJResponse::message('The form is incomplete.');
				}
			}

			if (UOJContext::requestMethod() == 'POST') {
				$len = UOJContext::contentLength();
				if ($len === null) {
					UOJResponse::page403();
				} elseif ($len > $this->config['max_post_size']) {
					UOJResponse::message('The form is too large.');
				}
			}

			crsf_defend();
			$errors = $this->validateAtServer();
			if ($errors) {
				$err_str = '';
				foreach ($errors as $name => $err) {
					$esc_err = htmlspecialchars($err);
					$err_str .= "$name: $esc_err<br />";
				}
				UOJResponse::message($err_str);
			}
			$fun = $this->handle;
			$fun($this->vdata);

			if ($this->succ_href !== 'none') {
				redirectTo($this->succ_href);
			}
			die();
		};
	}

	public function setAjaxSubmit($js) {
		$GLOBALS['REQUIRE_LIB']['jquery.form'] = '';
		$this->ajax_submit_js = $js;
	}

	public function add($name, $html, $validator_php, $validator_js) {
		$this->main_html .= $html;
		$this->data[] = [
			'name' => $name,
			'validator_php' => $validator_php,
			'validator_js' => $validator_js
		];
	}

	public function addNoVal($name, $html) {
		$this->main_html .= $html;
		$this->data[] = array(
			'name' => $name,
			'validator_js' => 'always_ok',
			'no_val' => ''
		);
	}

	public function appendHTML($html) {
		$this->main_html .= $html;
	}

	public function addHidden($name, $default_value, $validator_php, $validator_js) {
		$default_value = HTML::escape($default_value);
		$html = <<<EOD
		<input type="hidden" name="$name" id="input-$name" value="$default_value" />
		EOD;
		$this->add($name, $html, $validator_php, $validator_js);
	}

	public function addInput($name, $config) {
		$config += [
			'type' => 'text',
			'div_class' => '',
			'input_class' => 'form-control',
			'input_attrs' => [],
			'input_div_class' => null,
			'default_value' => '',
			'label' => '',
			'label_class' => 'form-label',
			'placeholder' => '',
			'help' => '',
			'help_class' => 'form-text',
			'validator_php' => function ($x) {
				return '';
			},
			'validator_js' => null,
		];

		$html = '';
		$html .= HTML::tag_begin('div', ['class' => $config['div_class'], 'id' => "div-$name"]);

		if ($config['label']) {
			$html .= HTML::tag('label', [
				'class' => $config['label_class'],
				'for' => "input-$name",
				'id' => "label-$name"
			], $config['label']);
		}

		if ($config['input_div_class'] !== null) {
			$html .= HTML::tag_begin('div', ['class' => $config['input_div_class']]);
		}

		$html .= HTML::empty_tag('input', [
			'class' => $config['input_class'],
			'type' => $config['type'],
			'name' => $name,
			'id' => "input-$name",
			'value' => $config['default_value'],
			'placeholder' => $config['placeholder'],
		] + $config['input_attrs']);
		$html .= HTML::tag('div', ['class' => 'invalid-feedback', 'id' => "help-$name"], '');

		if ($config['help']) {
			$html .= HTML::tag('div', ['class' => $config['help_class']], $config['help']);
		}

		if ($config['input_div_class'] !== null) {
			$html .= HTML::tag_end('div');
		}

		$html .= HTML::tag_end('div');

		$this->add($name, $html, $config['validator_php'], $config['validator_js']);
	}

	public function addTextArea($name, $config) {
		$config += [
			'type' => 'text',
			'div_class' => '',
			'input_class' => 'form-control',
			'default_value' => '',
			'label' => '',
			'label_class' => 'form-label',
			'placeholder' => '',
			'help' => '',
			'help_class' => 'form-text',
			'validator_php' => function ($x) {
				return '';
			},
			'validator_js' => null,
		];

		$html = '';
		$html .= HTML::tag_begin('div', ['class' => $config['div_class'], 'id' => "div-$name"]);

		if ($config['label']) {
			$html .= HTML::tag('label', [
				'class' => $config['label_class'],
				'for' => "input-$name",
				'id' => "label-$name"
			], $config['label']);
		}

		$html .= HTML::tag('textarea', [
			'class' => $config['input_class'],
			'type' => $config['type'],
			'name' => $name,
			'id' => "input-$name",
			'placeholder' => $config['placeholder'],
		], HTML::escape($config['default_value']));
		$html .= HTML::tag('div', ['class' => 'invalid-feedback', 'id' => "help-$name"], '');

		if ($config['help']) {
			$html .= HTML::tag('div', ['class' => $config['help_class']], $config['help']);
		}

		$html .= HTML::tag_end('div');

		$this->add($name, $html, $config['validator_php'], $config['validator_js']);
	}

	public function addCheckbox($name, $config) {
		$config += [
			'checked' => false,
			'div_class' => 'form-check',
			'role' => 'checkbox',
			'input_class' => 'form-check-input',
			'label' => '',
			'label_class' => 'form-check-label',
			'help' => '',
			'help_class' => 'form-text',
			'disabled' => false,
		];

		$html = '';
		$html .= HTML::tag_begin('div', ['class' => $config['div_class'], 'id' => "div-$name"]);
		$html .= HTML::empty_tag('input', [
			'class' => $config['input_class'],
			'type' => 'checkbox',
			'name' => $name,
			'id' => "input-$name",
			'checked' => $config['checked'] ? 'checked' : null,
			'value' => '1',
			'disabled' => $config['disabled'] ? 'disabled' : null,
		]);
		$html .= HTML::tag('label', [
			'class' => $config['label_class'],
			'for' => "input-$name",
		], $config['label']);

		if ($config['help']) {
			$html .= HTML::tag('div', ['class' => $config['help_class']], $config['help']);
		}

		$html .= HTML::tag_end('div');

		$this->addNoVal($name, $html);
	}

	public function addSelect($name, $config) {
		$config += [
			'div_class' => '',
			'select_class' => 'form-select',
			'select_div_class' => null,
			'options' => [],
			'default_value' => '',
			'label' => '',
			'label_class' => 'form-label',
			'help' => '',
			'help_class' => 'form-text',
			'disabled' => false,
		];

		$html = '';
		$html .= HTML::tag_begin('div', ['id' => "div-$name", 'class' => $config['div_class']]);

		// Label
		if ($config['label']) {
			$html .= HTML::tag('label', [
				'class' => $config['label_class'],
				'for' => "input-$name",
			], $config['label']);
		}

		if ($config['select_div_class'] !== null) {
			$html .= HTML::tag_begin('div', ['class' => $config['select_div_class']]);
		}

		// Select
		$html .= HTML::tag_begin('select', ['id' => "input-$name", 'name' => $name, 'class' => $config['select_class']]);

		foreach ($config['options'] as $opt_name => $opt_label) {
			if ($opt_name == $config['default_value']) {
				$html .= HTML::tag('option', ['value' => $opt_name, 'selected' => 'selected'], $opt_label);
			} else {
				$html .= HTML::tag('option', ['value' => $opt_name], $opt_label);
			}
		}

		$html .= HTML::tag_end('select');

		// Help text
		if ($config['help']) {
			$html .= HTML::tag('div', ['class' => $config['help_class']], $config['help']);
		}

		if ($config['select_div_class'] !== null) {
			$html .= HTML::tag_end('div');
		}

		$html .= HTML::tag_end('div');

		$this->add(
			$name,
			$html,
			function ($opt) use ($config) {
				return isset($config['options'][$opt]) ? '' : "无效选项";
			},
			null
		);
	}

	public function addCheckboxes($name, $config) {
		$config += [
			'div_class' => '',
			'select_class' => '',
			'label' => '',
			'label_class' => 'form-check-label',
			'options' => [],
			'default_value' => '',
			'option_div_class' => 'form-check',
			'option_class' => 'form-check-input',
			'option_label_class' => 'form-check-label',
			'help' => '',
			'help_class' => 'form-text',
			'disabled' => false,
		];

		$html = '';
		$html .= HTML::tag_begin('div', ['id' => "div-$name", 'class' => $config['div_class']]);

		// Label
		if ($config['label']) {
			$html .= HTML::tag('label', [
				'class' => $config['label_class'],
				'for' => "input-$name",
			], $config['label']);
		}

		// Select
		$html .= HTML::tag_begin('div', ['class' => $config['select_class']]);

		foreach ($config['options'] as $opt_name => $opt_label) {
			$html .= HTML::tag_begin('div', ['class' => $config['option_div_class']]);

			if ($opt_name == $config['default_value']) {
				$html .= HTML::empty_tag('input', [
					'name' => $name,
					'id' => "input-$name-$opt_name",
					'class' => $config['option_class'],
					'type' => 'radio',
					'value' => $opt_name,
					'checked' => 'checked',
				]);
			} else {
				$html .= HTML::empty_tag('input', [
					'name' => $name,
					'id' => "input-$name-$opt_name",
					'class' => $config['option_class'],
					'type' => 'radio',
					'value' => $opt_name,
				]);
			}

			$html .= HTML::tag('label', [
				'class' => $config['option_label_class'],
				'for' => "input-$name-$opt_name",
			], $opt_label);

			$html .= HTML::tag_end('div');
		}

		$html .= HTML::tag_end('div');

		// Help text
		if ($config['help']) {
			$html .= HTML::tag('div', ['class' => $config['help_class']], $config['help']);
		}

		$html .= HTML::tag_end('div');

		$this->add(
			$name,
			$html,
			function ($opt) use ($config) {
				return isset($config['options'][$opt]) ? '' : "无效选项";
			},
			null
		);
	}

	public function addSourceCodeInput($name, $config) {
		$config += [
			'filename' => '',
			'languages' => UOJLang::getAvailableLanguages(),
			'preferred_lang' => null,
		];

		$this->add(
			"{$name}_upload_type",
			'',
			function ($type) use ($name) {
				if ($type == 'editor') {
					if (!isset($_POST["{$name}_editor"])) {
						return '你在干啥……怎么什么都没交过来……？';
					}
				} elseif ($type == 'file') {
				} else {
					return '……居然既不是用编辑器上传也不是用文件上传的……= =……';
				}

				return '';
			},
			'always_ok'
		);
		$this->addNoVal("{$name}_editor", '');
		$this->addNoVal("{$name}_file", '');
		$this->add(
			"{$name}_language",
			'',
			function ($lang) use ($config) {
				if (!isset($config['languages'][$lang])) {
					return '该语言不被支持';
				}

				return '';
			},
			'always_ok'
		);

		if ($config['preferred_lang'] == null || !isset($config['languages'][$config['preferred_lang']])) {
			$config['preferred_lang'] = Cookie::get('uoj_preferred_language');
		}

		if ($config['preferred_lang'] == null || !isset($config['languages'][$config['preferred_lang']])) {
			$config['preferred_lang'] = UOJLang::$default_preferred_language;
		}

		$langs_options_str = '';
		foreach ($config['languages'] as $lang_code => $lang_display) {
			$langs_options_str .= HTML::option($lang_code, $lang_display, $lang_code == $config['preferred_lang']);
		}

		$text = json_encode(UOJLocale::get('problems::source code') . ': ' . HTML::tag('code', [], $config['filename']));
		$langs_options_json = json_encode($langs_options_str);

		$this->appendHTML(<<<EOD
			<div class="form-group mb-3" id="form-group-$name"></div>
			<script type="text/javascript">
				$('#form-group-$name').source_code_form_group('$name', $text, $langs_options_json);
			</script>
		EOD);

		$this->config['is_big'] = true;
		$this->config['has_file'] = true;
	}

	public function addTextFileInput($name, $config) {
		$config += [
			'filename' => '',
		];

		$this->add(
			"{$name}_upload_type",
			'',
			function ($type, &$vdata) use ($name) {
				if ($type == 'editor') {
					if (!isset($_POST["{$name}_editor"])) {
						return '你在干啥……怎么什么都没交过来……？';
					}
				} elseif ($type == 'file') {
				} else {
					return '……居然既不是用编辑器上传也不是用文件上传的……= =……';
				}
			},
			'always_ok'
		);

		$this->addNoVal("{$name}_editor", '');
		$this->addNoVal("{$name}_file", '');

		$text = json_encode(UOJLocale::get('problems::text file') . ': ' . HTML::tag('code', [], $config['filename']));

		$this->appendHTML(<<<EOD
			<div class="form-group mb-3" id="form-group-$name"></div>
			<script type="text/javascript">
				$('#form-group-$name').text_file_form_group('$name', $text);
			</script>
		EOD);

		$this->config['is_big'] = true;
		$this->config['has_file'] = true;
	}

	public function printHTML() {
		echo HTML::tag_begin('form', [
			'action' => UOJContext::requestURI(),
			'method' => 'POST',
			'class' => $this->config['form']['class'],
			'id' => "form-{$this->form_name}",
			'enctype' => $this->config['is_big'] ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
		]);

		echo HTML::hiddenToken();

		echo $this->main_html;

		if (!$this->config['no_submit']) {
			echo HTML::tag_begin('div', ['class' => $this->config['submit_container']['class']]);

			if ($this->config['back_button']['href'] !== null) {
				echo HTML::tag('a', [
					'class' => $this->config['back_button']['class'],
					'href' => $this->config['back_button']['href']
				], '返回');
			}

			echo HTML::tag('button', [
				'type' => 'submit',
				'id' => "button-submit-{$this->form_name}",
				'name' => "submit-{$this->form_name}",
				'value' => $this->form_name,
				'class' => $this->config['submit_button']['class']
			], $this->config['submit_button']['text']);

			echo HTML::tag_end('div');
		}

		echo HTML::tag_end('form');

		if ($this->config['no_submit']) {
			return;
		}

		echo <<<EOD
		<script type="text/javascript">
			$(document).ready(function() {
		EOD;
		if ($this->config['ctrl_enter_submit']) {
			echo <<<EOD
				$('#form-{$this->form_name}').keydown(function(e) {
					if (e.keyCode == 13 && e.ctrlKey) {
						$('#button-submit-{$this->form_name}').click();
					}
				});
		EOD;
		}
		echo <<<EOD
				$('#form-{$this->form_name}').submit(function(e) {
					var ok = true;

		EOD;
		$need_ajax = false;
		if ($this->extra_validator) {
			$need_ajax = true;
		}
		foreach ($this->data as $field) {
			if ($field['validator_js'] != null) {
				if ($field['validator_js'] != 'always_ok') {
					echo <<<EOD
					var {$field['name']}_err = ({$field['validator_js']})($('#input-{$field['name']}').val());
					EOD;
				}
			} else {
				$need_ajax = true;
			}
		}

		if ($need_ajax) {
			echo <<<EOD
					var post_data = {};
			EOD;
			foreach ($this->data as $field) {
				if ($field['validator_js'] == null) {
					echo <<<EOD
						var {$field['name']}_err = 'Unknown error';
						post_data.{$field['name']} = $('#input-{$field['name']}').val();
					EOD;
				}
			}
			echo <<<EOD
				post_data['check-{$this->form_name}'] = "";
				$.ajax({
					url: '{$_SERVER['REQUEST_URI']}',
					type: 'POST',
					dataType: 'json',
					async: false,

					data: post_data,
					success: function(data) {
			EOD;
			foreach ($this->data as $field) {
				if ($field['validator_js'] == null) {
					echo <<<EOD
						{$field['name']}_err = data.${field['name']};
					EOD;
				}
			}
			echo <<<EOD
						if (data.extra != undefined) {
							alert(data.extra);
							ok = false;
						}
					}
				});
			EOD;
		}

		foreach ($this->data as $field) {
			if ($field['validator_js'] != 'always_ok') {
				echo <<<EOD
					if ({$field['name']}_err) {
						$('#div-{$field['name']}').addClass('has-validation has-error');
						$('#div-{$field['name']}').addClass('is-invalid');
						$('#input-{$field['name']}').addClass('is-invalid');
						$('#help-{$field['name']}').text({$field['name']}_err);
						ok = false;
					} else {
						$('#div-{$field['name']}').removeClass('has-validation has-error');
						$('#div-{$field['name']}').removeClass('is-invalid');
						$('#input-{$field['name']}').removeClass('is-invalid');
						$('#help-{$field['name']}').text('');
					}
				EOD;
			}
		}

		if ($this->config['confirm']['smart']) {
			$this->config['confirm']['text'] = '你真的要' . $this->config['submit_button']['text'] . '吗？';
		}
		if ($this->config['confirm']['text']) {
			echo <<<EOD
				if (!confirm('{$this->config['confirm']['text']}')) {
					ok = false;
				}
			EOD;
		}
		if ($this->config['has_file']) {
			echo <<<EOD
				$(this).find("input[type='file']").each(function() {
					for (var i = 0; i < this.files.length; i++) {
						if (this.files[i].size > {$this->config['max_file_size_mb']} * 1024 * 1024) {
							$('#div-' + $(this).attr('name')).addClass('has-validation has-error');
							$('#div-' + $(this).attr('name')).addClass('is-invalid');
							$('#input-' + $(this).attr('name')).addClass('is-invalid');
							$('#help-' + $(this).attr('name')).text('文件大小不能超过 {$this->config['max_file_size_mb']} MB');
							ok = false;
						} else {
							$('#div-' + $(this).attr('name')).removeClass('has-validation has-error');
							$('#div-' + $(this).attr('name')).removeClass('is-invalid');
							$('#input-' + $(this).attr('name')).removeClass('is-invalid');
							$('#help-' + $(this).attr('name')).text('');
						}
					}
				});
			EOD;
		}

		if ($this->ajax_submit_js !== null) {
			echo <<<EOD
				e.preventDefault();
				if (ok) {
					$(this).ajaxSubmit({
						beforeSubmit: function(formData) {
							formData.push({name: 'submit-{$this->form_name}', value: '{$this->form_name}', type: 'submit'});
						},
						success: {$this->ajax_submit_js}
					});
				}
			EOD;
		} else {
			echo <<<EOD
				if (ok) {
					$("#button-submit-{$this->form_name}")
						.addClass('disabled')
						.css('width', $("#button-submit-{$this->form_name}").outerWidth())
						.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

					$(this).submit(function () {
						return false;
					});
				}
				return ok;
			EOD;
		}
		echo <<<EOD
				});
			});
		</script>
		EOD;
	}

	private function validateAtServer() {
		$errors = array();
		if ($this->extra_validator) {
			$fun = $this->extra_validator;
			$err = $fun();
			if ($err) {
				$errors['extra'] = $err;
			}
		}
		foreach ($this->data as $field) {
			if (!isset($field['no_val']) && isset($_POST[$field['name']])) {
				$fun = $field['validator_php'];
				$ret = $fun($_POST[$field['name']], $this->vdata, $field['name']);
				if (is_array($ret) && isset($ret['error'])) {
					$err = $ret['error'];
				} else {
					$err = $ret;
				}
				if ($err) {
					$errors[$field['name']] = $err;
				}
				if (is_array($ret) && isset($ret['store'])) {
					$this->vdata[$field['name']] = $ret['store'];
				}
			}
		}
		return $errors;
	}

	public function runAtServer() {
		foreach ($this->run_at_server_handler as $type => $handler) {
			if (isset($_POST[$type])) {
				$handler();
			}
		}
	}

	static public function uploadedFileTmpName($name) {
		if (isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name'])) {
			return $_FILES[$name]['tmp_name'];
		} else {
			return null;
		}
	}
}
