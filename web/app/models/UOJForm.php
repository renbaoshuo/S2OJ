<?php

class UOJForm {
	public $form_name;
	public $succ_href;
	public $back_href = null;
	public $no_submit = false;
	public $ctrl_enter_submit = false;
	public $extra_validator = null;
	public $is_big = false;
	public $has_file = false;
	public $ajax_submit_js = null;
	public $run_at_server_handler = [];
	private $data = [];
	private $vdata = [];
	private $main_html = '';
	public $max_post_size = 15728640; // 15M
	public $max_file_size_mb = 10; // 10M

	public $handle;

	public $config = [
		'container' => [
			'class' => '',
		],
		'submit_button' => [
			'class' => 'btn btn-secondary',
		],
	];
	public $submit_button_config = [];
	public $control_label_config = ['class' => 'col-sm-2'];
	public $input_config = ['class' => 'col-sm-3'];
	public $textarea_config = ['class' => 'col-sm-10'];

	public function __construct($form_name) {
		$this->form_name = $form_name;
		$this->succ_href = UOJContext::requestURI();
		$this->handle = function (&$vdata) {
		};

		$this->run_at_server_handler["check-{$this->form_name}"] = function () {
			die(json_encode($this->validateAtServer()));
		};
		$this->run_at_server_handler["submit-{$this->form_name}"] = function () {
			if ($this->no_submit) {
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
				} elseif ($len > $this->max_post_size) {
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
		$this->data[] = array(
			'name' => $name,
			'validator_php' => $validator_php,
			'validator_js' => $validator_js
		);
	}
	public function appendHTML($html) {
		$this->main_html .= $html;
	}

	public function addNoVal($name, $html) {
		$this->main_html .= $html;
		$this->data[] = array(
			'name' => $name,
			'validator_js' => 'always_ok',
			'no_val' => ''
		);
	}

	public function addHidden($name, $default_value, $validator_php, $validator_js) {
		$default_value = HTML::escape($default_value);
		$html = <<<EOD
		<input type="hidden" name="$name" id="input-$name" value="$default_value" />
		EOD;
		$this->add($name, $html, $validator_php, $validator_js);
	}

	public function printHTML() {
		$form_entype_str = $this->is_big ? ' enctype="multipart/form-data"' : '';

		echo '<form action="', $_SERVER['REQUEST_URI'], '" method="post" class="" id="form-', $this->form_name, '"', $form_entype_str, '>';
		echo HTML::hiddenToken();
		echo $this->main_html;

		if (!$this->no_submit) {
			if (!isset($this->submit_button_config['align'])) {
				$this->submit_button_config['align'] = 'center';
			}
			if (!isset($this->submit_button_config['text'])) {
				$this->submit_button_config['text'] = UOJLocale::get('submit');
			}
			if (!isset($this->submit_button_config['class_str'])) {
				$this->submit_button_config['class_str'] = 'btn btn-secondary';
			}
			if ($this->submit_button_config['align'] == 'offset') {
				echo '<div class="form-group">';
				echo '<div class="col-sm-offset-2 col-sm-3">';
			} else {
				echo '<div class="text-', $this->submit_button_config['align'], '">';
			}

			if ($this->back_href !== null) {
				echo '<div class="btn-toolbar">';
			}
			echo HTML::tag('button', [
				'type' => 'submit', 'id' => "button-submit-{$this->form_name}", 'name' => "submit-{$this->form_name}",
				'value' => $this->form_name, 'class' => $this->submit_button_config['class_str']
			], $this->submit_button_config['text']);
			if ($this->back_href !== null) {
				echo HTML::tag('a', [
					'class' => 'btn btn-secondary', 'href' => $this->back_href
				], '返回');
			}
			if ($this->back_href !== null) {
				echo '</div>';
			}

			if ($this->submit_button_config['align'] == 'offset') {
				echo '</div>';
			}
			echo '</div>';
		}

		echo '</form>';

		if ($this->no_submit) {
			return;
		}

		echo <<<EOD
					<script type="text/javascript">
					$(document).ready(function() {

					EOD;
		if ($this->ctrl_enter_submit) {
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
								url : '{$_SERVER['REQUEST_URI']}',
								type : 'POST',
								dataType : 'json',
								async : false,

								data : post_data,
								success : function(data) {

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
							if (${field['name']}_err) {
								$('#div-${field['name']}').addClass('has-validation has-error');
								$('#div-${field['name']}').addClass('is-invalid');
								$('#input-${field['name']}').addClass('is-invalid');
								$('#help-${field['name']}').text(${field['name']}_err);
								ok = false;
							} else {
								$('#div-${field['name']}').removeClass('has-validation has-error');
								$('#div-${field['name']}').removeClass('is-invalid');
								$('#input-${field['name']}').removeClass('is-invalid');
								$('#help-${field['name']}').text('');
							}
					EOD;
			}
		}

		if (isset($this->submit_button_config['smart_confirm'])) {
			$this->submit_button_config['confirm_text'] = '你真的要' . $this->submit_button_config['text'] . '吗？';
		}
		if (isset($this->submit_button_config['confirm_text'])) {
			echo <<<EOD
							if (!confirm('{$this->submit_button_config['confirm_text']}')) {
								ok = false;
							}

					EOD;
		}
		if ($this->has_file) {
			echo <<<EOD
							$(this).find("input[type='file']").each(function() {
								for (var i = 0; i < this.files.length; i++) {
									if (this.files[i].size > {$this->max_file_size_mb} * 1024 * 1024) {
										$('#div-' + $(this).attr('name')).addClass('has-validation has-error');
										$('#div-' + $(this).attr('name')).addClass('is-invalid');
										$('#input-' + $(this).attr('name')).addClass('is-invalid');
										$('#help-' + $(this).attr('name')).text('文件大小不能超过{$this->max_file_size_mb}M');
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
									success : {$this->ajax_submit_js}
								});
							}

					EOD;
		} else {
			echo <<<EOD
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
}
