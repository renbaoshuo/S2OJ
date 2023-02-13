<?php

use PHPMailer\PHPMailer\PHPMailer;

class UOJMail {
	public static function noreply() {
		$mailer = new PHPMailer();
		$mailer->isSMTP();
		$mailer->Host = UOJConfig::$data['mail']['noreply']['host'];
		$mailer->Port = UOJConfig::$data['mail']['noreply']['port'];
		$mailer->SMTPAuth = true;
		$mailer->SMTPSecure = UOJConfig::$data['mail']['noreply']['secure'];
		$mailer->Username = UOJConfig::$data['mail']['noreply']['username'];
		$mailer->Password = UOJConfig::$data['mail']['noreply']['password'];
		$mailer->setFrom(UOJConfig::$data['mail']['noreply']['username'], UOJConfig::$data['profile']['oj-name-short']);
		$mailer->CharSet = "utf-8";
		$mailer->Encoding = "base64";
		return $mailer;
	}

	public static function cronSendEmail() {
		$emails = DB::selectAll([
			"select * from emails",
			"where", DB::land([
				["created_at", ">=", DB::raw("addtime(now(), '-24:00:00')")],
				"send_time" => null,
			]),
			"order by priority desc",
		]);

		$oj_name = UOJConfig::$data['profile']['oj-name'];
		$oj_name_short = UOJConfig::$data['profile']['oj-name-short'];
		$oj_url = HTML::url('/');
		$oj_logo_url = HTML::url('/images/logo_small.png');

		foreach ($emails as $email) {
			$user = UOJUser::query($email['receiver']);
			$name = $user['username'];

			if ($user['realname']) {
				$name .= ' (' . $user['realname'] . ')';
			}

			if ($user['email']) {
				$mailer = UOJMail::noreply();
				$mailer->addAddress($user['email'], $user['username']);
				$mailer->Subject = $email['subject'];
				$mailer->msgHTML(<<<EOD
				<base target="_blank" />

				<div style="padding: 48px; margin: 60px auto 60px auto; box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.15), inset 0px 0px 1px rgba(0, 0, 0, 0.5); max-width: 700px">
					<div style="display: block">
						<div style="font-size: 20px; font-weight: bold; display: inline-block">{$oj_name_short}</div>
						<img style="float: right" src="{$oj_logo_url}" height="32" width="32" />
					</div>
					<hr />
					<br />

					<h1><center>{$email['subject']}</center></h1>
					<div style="font-size: 18px">{$name} 您好，</div>
					<br />

					<div>
					{$email['content']}
					</div>

					<br />
					<br />

					<div style="text-align: right;">
						<a href="{$oj_url}">{$oj_name}</a>
					</div>

					<hr />
					<div style="font-size: 12px; color: grey; text-align: center;">
						您之所以收到本邮件，是因为您是 {$oj_name} 的用户。
						<br />
						本邮件由系统自动发送，请勿回复。
					</div>
				</div>
				EOD);

				$res = retry_loop(function () use (&$mailer) {
					$res = $mailer->send();

					if ($res) return true;

					UOJLog::error($mailer->ErrorInfo);

					return false;
				});

				if ($res) {
					DB::update("update emails set send_time = now() where id = {$email['id']}");
					echo '[UOJMail::cronSendEmail] ID: ' . $email['id'] . ' sent.' . "\n";
				}
			}
		}

		echo '[UOJMail::cronSendEmail] Done.' . "\n";
	}
}
