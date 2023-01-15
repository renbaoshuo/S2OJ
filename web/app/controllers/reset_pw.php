<?php
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

	DB::update([
		"update user_info",
		"set", [
			"password" => $newPW,
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
<?php
$REQUIRE_LIB['dialog'] = '';
$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader('更改密码') ?>
<h2 class="page-header">更改密码</h2>
<form id="form-reset" class="form-horizontal">
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label">新密码</label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="输入新密码" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="再次输入新密码" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-secondary">提交</button>
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
			$.post(<?= json_encode($_SERVER['REQUEST_URI']) ?>, {
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
