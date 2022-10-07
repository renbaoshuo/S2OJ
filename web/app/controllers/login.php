<?php
	use Gregwar\Captcha\PhraseBuilder;
	use Gregwar\Captcha\CaptchaBuilder;

	if (Auth::check()) {
		redirectTo('/');
	}

	function handleLoginPost() {
		if (!crsf_check()) {
			return 'expired';
		}
		if (!isset($_POST['username'])) {
			return "failed";
		}
		if (!isset($_POST['password'])) {
			return "failed";
		}
		$username = $_POST['username'];
		$password = $_POST['password'];
		$captcha = $_POST['captcha'];

		if (!isset($_SESSION['phrase']) || !PhraseBuilder::comparePhrases($_SESSION['phrase'], $captcha)) {
			return "bad_captcha";
		}
		
		if (!validateUsername($username)) {
			return "failed";
		}
		if (!validatePassword($password)) {
			return "failed";
		}
		
		$user = queryUser($username);
		if (!$user || !checkPassword($user, $password)) {
			return "failed";
		}
		
		if ($user['usergroup'] == 'B') {
			return "banned";
		}
		
		Auth::login($user['username']);
		return "ok";
	}
	
	if (isset($_POST['login'])) {
		echo handleLoginPost();
		unset($_SESSION['phrase']);
		die();
	}
	?>
<?php
	$REQUIRE_LIB['bootstrap5'] = '';
	$REQUIRE_LIB['md5'] = '';
	?>
<?php echoUOJPageHeader(UOJLocale::get('login')) ?>

<style>
	.login-container {
		max-width: 400px;
		padding: 15px;
	}

	.login-input-group-item {
		margin-bottom: -1px;
	}
</style>

<main class="login-container mx-auto w-100 text-center">
	<form id="form-login" method="post">
		<img class="mb-4" src="<?= HTML::url('/images/sjzez.png') ?>">

		<div class="login-input-group mb-3">
			<div id="div-username" class="input-group">
				<div class="form-floating">
					<input type="text" class="form-control rounded-0 rounded-top login-input-group-item" id="input-username" name="username" placeholder="<?= UOJLocale::get('enter your username') ?>" maxlength="20" />
					<label for="input-username"><?= UOJLocale::get('username') ?></label>
				</div>
			</div>

			<div id="div-password" class="input-group">
				<div class="form-floating">
					<input type="password" class="form-control rounded-0 login-input-group-item" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
					<label for="input-password"><?= UOJLocale::get('password') ?></label>
				</div>
			</div>

			<div id="div-captcha" class="input-group">
				<div class="form-floating">
					<input type="text" class="form-control rounded-0" style="border-bottom-left-radius: var(--bs-border-radius) !important" id="input-captcha" name="captcha" placeholder="<?= UOJLocale::get('enter verification code') ?>" maxlength="20" />
					<label for="input-captcha"><?= UOJLocale::get('verification code') ?></label>
				</div>
				<span class="input-group-text p-0 overflow-hidden rounded-0" style="border-bottom-right-radius: var(--bs-border-radius) !important">
					<img id="captcha" class="col w-100 h-100" src="/captcha">
				</span>
			</div>
		</div>

		<div class="text-danger">
			<span id="help-username"></span>
			<span id="help-password"></span>
			<span id="help-captcha"></span>
		</div>

		<button type="submit" id="button-submit" class="mt-4 w-100 btn btn-lg btn-primary"><?= UOJLocale::get('login') ?></button>
	</form>
</main>

<script type="text/javascript">
function validateLoginPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('username', validateUsername);
	ok &= getFormErrorAndShowHelp('password', validatePassword);
	return ok;
}

function refreshCaptcha() {
	var timestamp = new Date().getTime();
	$("#captcha").attr("src", "/captcha" + '?' + timestamp);
}

function submitLoginPost() {
	if (!validateLoginPost()) {
		return false;
	}
	
	$.post('/login', {
		_token : "<?= crsf_token() ?>",
		login : '',
		username : $('#input-username').val(),
		password : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>"),
		captcha: $('#input-captcha').val(),
	}, function(msg) {
		$('#div-username, #div-password, #div-captcha').removeClass('has-validation');
		$('#input-username, #input-password, #input-captcha').removeClass('is-invalid');
		$('#help-username, #help-passwor, #help-captcha').html('');

		if (msg == 'ok') {
			var prevUrl = document.referrer;
			if (prevUrl == '' || /.*\/login.*/.test(prevUrl) || /.*\/logout.*/.test(prevUrl) || /.*\/register.*/.test(prevUrl) || /.*\/reset-password.*/.test(prevUrl)) {
				prevUrl = '/';
			};
			window.location.href = prevUrl;
		} else if (msg == 'bad_captcha') {
			$('#div-captcha').addClass('has-validation');
			$('#div-captcha > .form-floating, #input-captcha').addClass('is-invalid');
			$('#help-captcha').html('验证码错误。');
			refreshCaptcha();
		} else if (msg == 'banned') {
			$('#div-username').addClass('has-validation');
			$('#div-username > .form-floating, #input-username').addClass('is-invalid');
			$('#help-username').html('该用户已被封停，请联系管理员。');
			refreshCaptcha();
		} else if (msg == 'expired') {
			$('#div-username').addClass('has-validation');
			$('#div-username > .form-floating, #input-username').addClass('is-invalid');
			$('#help-username').html('页面会话已过期。');
			refreshCaptcha();
		} else {
			$('#div-username').addClass('has-validation');
			$('#div-username > .form-floating, #input-username').addClass('is-invalid');
			$('#div-password').addClass('has-validation');
			$('#div-password > .form-floating, #input-password').addClass('is-invalid');
			$('#help-password').html('用户名或密码错误。<a href="/forgot-password">忘记密码？</a>');
			refreshCaptcha();
		}
	});
	return true;
}

$(document).ready(function() {
	refreshCaptcha();

	$('#form-login').submit(function(e) {
		e.preventDefault();
		submitLoginPost();
	});
	$("#captcha").click(function(e) {
		refreshCaptcha();
	});
});

</script>

<?php echoUOJPageFooter() ?>
