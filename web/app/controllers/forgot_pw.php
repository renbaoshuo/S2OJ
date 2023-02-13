<?php
requirePHPLib('form');

use Gregwar\Captcha\PhraseBuilder;

$forgot_form = new UOJForm('forgot');
$forgot_form->addInput('username', [
	'div_class' => '',
	'label' => '用户名',
	'validator_php' => function ($username, &$vdata) {
		if (!validateUsername($username)) {
			return '用户名不合法';
		}

		$vdata['user'] = UOJUser::query($username);

		if (!$vdata['user']) {
			return '该用户不存在';
		}

		return '';
	},
]);
$enter_verification_code_text = UOJLocale::get('enter verification code');
$forgot_form->appendHTML(<<<EOD
	<div id="div-captcha" class="form-group mt-3">
		<label for="input-captcha" class="col-sm-2 control-label">验证码</label>
		<div class="input-group">
			<input type="text" class="form-control" id="input-captcha" name="captcha" placeholder="{$enter_verification_code_text}" maxlength="20" />
			<span class="input-group-text p-0">
				<img id="captcha" class="col w-100 h-100" src="/captcha">
			</span>
		</div>
	</div>
EOD);
$forgot_form->handle = function (&$vdata) {
	$user = $vdata['user'];
	$password = $user["password"];

	if (!isset($_SESSION['phrase']) || !PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['captcha'])) {
		unset($_SESSION['phrase']);

		becomeMsgPage('验证码错误！');
	}

	unset($_SESSION['phrase']);

	if (!$user['email']) {
		becomeMsgPage('用户未填写邮件地址，请联系管理员重置密码！');
	}

	$oj_name = UOJConfig::$data['profile']['oj-name'];
	$oj_name_short = UOJConfig::$data['profile']['oj-name-short'];
	$check_code = md5($user['username'] . "+" . $password . '+' . UOJTime::$time_now_str);
	$sufs = base64url_encode($user['username'] . "." . $check_code);
	$url = HTML::url("/reset_password", ['params' => ['p' => $sufs]]);
	$name = $user['username'];
	$remote_addr = UOJContext::remoteAddr();
	$http_x_forwarded_for = UOJContext::httpXForwardedFor();
	$user_agent = UOJContext::httpUserAgent();

	if ($user['realname']) {
		$name .= ' (' . $user['realname'] . ')';
	}

	sendEmail($user['username'], $oj_name_short . ' 密码找回', <<<EOD
	<p>您最近告知我们需要重置您在 {$oj_name_short} 上账号的密码。请访问以下链接：<a href="{$url}">{$url}</a> (如果无法点击链接，请试着复制链接并粘贴至浏览器中打开。)</p>
	<p>如果您没有请求重置密码，则忽略此信息。该链接将在 72 小时后自动过期失效。</p>

	<ul>
		<li>请求 IP: {$remote_addr}</li>
		<li>转发源 IP:{$http_x_forwarded_for} </li>
		<li>用户代理: {$user_agent}</li>
	</ul>
	EOD);

	DB::update([
		"update user_info",
		"set", [
			'extra' => DB::json_set('extra', '$.reset_password_check_code', $check_code, '$.reset_password_time', UOJTime::$time_now_str),
		],
		"where", [
			"username" => $user['username'],
		],
	]);

	becomeMsgPage('<div class="text-center"><h2>邮件已发送，请检查收件箱！</h2><span>如果邮件未出现在收件箱中，请检查垃圾箱。</span></div>');
};
$forgot_form->runAtServer();
?>

<?php echoUOJPageHeader('找回密码') ?>

<div class="card mw-100 mx-auto" style="width:600px">
	<div class="card-body">
		<h1 class="text-center mb-3">找回密码</h1>

		<?php $forgot_form->printHTML() ?>
	</div>
</div>

<script>
	function refreshCaptcha() {
		var timestamp = new Date().getTime();
		$("#captcha").attr("src", "/captcha" + '?' + timestamp);
	}

	$(document).ready(function() {
		$("#captcha").click(function(e) {
			refreshCaptcha();
		});
	});
</script>

<?php echoUOJPageFooter() ?>
