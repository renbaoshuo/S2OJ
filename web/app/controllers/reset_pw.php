<?php
requireLib('dialog');
requireLib('md5');

if (!isset($_GET['p'])) {
	become404Page();
}

list($username, $check_code) = explode('.', base64url_decode($_GET['p']));
$user = UOJUser::query($username);

if (!$user) become404Page();
if (!isset($check_code) || strlen($check_code) != 32) become404Page();

$extra = UOJUser::getExtra($user);

if ($check_code !== $extra['reset_password_check_code']) {
	become404Page();
}

if (UOJTime::str2time($extra['reset_password_time'])->add(new DateInterval('P3D')) < UOJTime::$time_now) {
	becomeMsgPage('链接已过期');
}

function resetPassword() {
	global $user;

	if (!isset($_POST['newPW']) || !validatePassword($_POST['newPW'])) {
		return '操作失败，无效密码';
	}

	$newPW = $_POST['newPW'];
	$newPW = getPasswordToStore($newPW, $user['username']);

	$oj_name = UOJConfig::$data['profile']['oj-name'];
	$oj_name_short = UOJConfig::$data['profile']['oj-name-short'];
	$name = $user['username'];
	$remote_addr = UOJContext::remoteAddr();
	$http_x_forwarded_for = UOJContext::httpXForwardedFor();
	$user_agent = UOJContext::httpUserAgent();

	if ($user['realname']) {
		$name .= ' (' . $user['realname'] . ')';
	}

	sendEmail($user['username'], "密码被重置", <<<EOD
	<p>您刚刚重置了您在 {$oj_name_short} 上账号的密码。如果这是您进行的操作，请忽略本邮件。如果您没有请求重置密码，请立即联系管理员进行处理。</p>

	<ul>
		<li>请求 IP: {$remote_addr}</li>
		<li>转发源 IP: {$http_x_forwarded_for} </li>
		<li>用户代理: {$user_agent}</li>
	</ul>
	EOD, 5);

	DB::update([
		"update user_info",
		"set", [
			"password" => $newPW,
			"remember_token" => '',
			"extra" => DB::json_remove('extra', '$.reset_password_check_code', '$.reset_password_time'),
		],
		"where", [
			"username" => $user['username'],
		],
	]);

	return 'ok';
}
if (isset($_POST['reset'])) {
	die(resetPassword());
}
?>

<?php echoUOJPageHeader(UOJLocale::get('reset password')) ?>

<form id="form-reset" class="card mw-100 mx-auto" style="width:600px">
	<div class="card-body">
		<h1 class="card-title text-center mb-3">
			<?= UOJLocale::get('reset password') ?>
		</h1>
		<div class="mb-1">
			<label for="input-username" class="form-label"><?= UOJLocale::get('username') ?></label>
			<input type="text" class="form-control" value="<?= $user['username'] ?>" disabled />
		</div>
		<div id="div-password" class="mb-1">
			<label for="input-password" class="form-label">
				<?= UOJLocale::get('new password') ?>
			</label>
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<input type="password" class="form-control mt-2" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your password') ?>" maxlength="20" />
			<span class="help-block invalid-feedback" id="help-password"></span>
		</div>
		<div class="text-center">
			<button type="submit" id="button-submit" class="btn btn-primary">
				<?= UOJLocale::get('submit') ?>
			</button>
		</div>
	</div>
</form>

<script type="text/javascript">
	function validateResetPwPost() {
		var ok = true;
		ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
		return ok;
	}

	$(document).ready(function() {
		$('#form-reset').submit(function(e) {
			if (!validateResetPwPost()) {
				return false;
			}
			$.post(<?= json_encode(UOJContext::requestURI()) ?>, {
				reset: '',
				newPW: md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
			}, function(res) {
				if (res == 'ok') {
					BootstrapDialog.show({
						title: '提示',
						message: '密码更改成功',
						type: BootstrapDialog.TYPE_SUCCESS,
						buttons: [{
							label: '好的',
							action: function(dialog) {
								dialog.close();
							}
						}],
						onhidden: function(dialog) {
							window.location.href = '/login';
						}
					});
				} else {
					BootstrapDialog.show({
						title: '提示',
						message: res,
						type: BootstrapDialog.TYPE_DANGER,
						buttons: [{
							label: '好的',
							action: function(dialog) {
								dialog.close();
							}
						}]
					});
				}
			});
			return false;
		});
	});
</script>

<?php echoUOJPageFooter() ?>
