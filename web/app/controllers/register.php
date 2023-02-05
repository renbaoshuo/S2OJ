<?php
requireLib('md5');
requireLib('dialog');

use Gregwar\Captcha\PhraseBuilder;

if (!UOJConfig::$data['switch']['open-register'] && DB::selectCount("SELECT COUNT(*) FROM user_info")) {
	become404Page();
}

function handleRegisterPost() {
	if (!crsf_check()) {
		return '页面已过期';
	}

	if (!isset($_SESSION['phrase']) || !PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['captcha'])) {
		return "bad_captcha";
	}

	if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['email'])) {
		return "无效表单";
	}

	try {
		$user = UOJUser::register([
			'username' => UOJRequest::post('username'),
			'password' => UOJRequest::post('password'),
			'email' => UOJRequest::post('email')
		]);
	} catch (UOJInvalidArgumentException $e) {
		return "失败：" . $e->getMessage();
	}

	return "欢迎你！" . $user['username'] . "，你已成功注册。";
}

if (isset($_POST['register'])) {
	echo handleRegisterPost();
	unset($_SESSION['phrase']);
	die();
} elseif (isset($_POST['check_username'])) {
	$username = $_POST['username'];
	if (validateUsername($username) && !UOJUser::query($username)) {
		die('{"ok": true}');
	} else {
		die('{"ok": false}');
	}
}
?>
<?php echoUOJPageHeader(UOJLocale::get('register')) ?>


<form id="form-register" class="card mw-100 mx-auto" style="width:600px">
	<div class="card-body">
		<h1 class="card-title text-center mb-3">
			<?= UOJLocale::get('register') ?>
		</h1>
		<div id="div-email" class="form-group">
			<label for="input-email" class="form-label"><?= UOJLocale::get('email') ?></label>
			<input type="email" class="form-control" id="input-email" name="email" placeholder="<?= UOJLocale::get('enter your email') ?>" maxlength="50" />
			<span class="help-block" id="help-email"></span>
		</div>
		<div id="div-username" class="form-group">
			<label for="input-username" class="form-label"><?= UOJLocale::get('username') ?></label>
			<input type="text" class="form-control" id="input-username" name="username" placeholder="<?= UOJLocale::get('enter your username') ?>" maxlength="20" />
			<span class="help-block" id="help-username"></span>
		</div>
		<div id="div-password" class="form-group">
			<label for="input-password" class="form-label"><?= UOJLocale::get('password') ?></label>
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<input type="password" class="form-control mt-2" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
		<div id="div-captcha" class="form-group">
			<label for="input-captcha"><?= UOJLocale::get('verification code') ?></label>
			<div class="input-group">
				<input type="text" class="form-control" id="input-captcha" name="captcha" placeholder="<?= UOJLocale::get('enter verification code') ?>" maxlength="20" />
				<span class="input-group-text p-0">
					<img id="captcha" class="col w-100 h-100" src="/captcha">
				</span>
			</div>
		</div>
		<div class="text-center">
			<button type="submit" id="button-submit" class="btn btn-primary"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
	function refreshCaptcha() {
		var timestamp = new Date().getTime();
		$("#captcha").attr("src", "/captcha" + '?' + timestamp);
	}

	function checkUsernameNotInUse() {
		var ok = false;
		$.ajax({
			url: '/register',
			type: 'POST',
			dataType: 'json',
			async: false,

			data: {
				check_username: '',
				username: $('#input-username').val()
			},
			success: function(data) {
				ok = data.ok;
			},
			error: function(XMLHttpRequest, textStatus, errorThrown) {
				alert(XMLHttpRequest.responseText);
				ok = false;
			}
		});
		return ok;
	}

	function validateRegisterPost() {
		var ok = true;
		ok &= getFormErrorAndShowHelp('email', validateEmail);
		ok &= getFormErrorAndShowHelp('username', function(str) {
			var err = validateUsername(str);
			if (err)
				return err;
			if (!checkUsernameNotInUse())
				return '该用户名已被人使用了。';
			return '';
		})
		ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
		return ok;
	}

	function submitRegisterPost() {
		if (!validateRegisterPost()) {
			return;
		}

		$.post('/register', {
			_token: "<?= crsf_token() ?>",
			register: '',
			username: $('#input-username').val(),
			email: $('#input-email').val(),
			password: md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>"),
			captcha: $('#input-captcha').val(),
		}, function(msg) {
			if (/^欢迎你！/.test(msg)) {
				BootstrapDialog.show({
					title: '注册成功',
					message: msg,
					type: BootstrapDialog.TYPE_SUCCESS,
					buttons: [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
					onhidden: function(dialog) {
						var prevUrl = document.referrer;
						if (!prevUrl) {
							prevUrl = '/';
						};
						window.location.href = prevUrl;
					}
				});
			} else {
				BootstrapDialog.show({
					title: '注册失败',
					message: msg,
					type: BootstrapDialog.TYPE_DANGER,
					buttons: [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
				});
			}
		});
	}
	$(document).ready(function() {
		refreshCaptcha();

		$('#captcha').click(refreshCaptcha);

		$('#form-register').submit(function(e) {
			submitRegisterPost();
			return false;
		});
	});
</script>

<?php echoUOJPageFooter() ?>
