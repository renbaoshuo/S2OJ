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
		$mailer->addCC(UOJConfig::$data['mail']['noreply']['username'], UOJConfig::$data['profile']['oj-name-short']);
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
		$oj_email_address = UOJConfig::$data['mail']['noreply']['username'];

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
						<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAYAAADimHc4AAAACXBIWXMAAAsTAAALEwEAmpwYAAAGUmlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSLvu78iIGlkPSJXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQiPz4gPHg6eG1wbWV0YSB4bWxuczp4PSJhZG9iZTpuczptZXRhLyIgeDp4bXB0az0iQWRvYmUgWE1QIENvcmUgNi4wLWMwMDIgNzkuMTY0NDYwLCAyMDIwLzA1LzEyLTE2OjA0OjE3ICAgICAgICAiPiA8cmRmOlJERiB4bWxuczpyZGY9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkvMDIvMjItcmRmLXN5bnRheC1ucyMiPiA8cmRmOkRlc2NyaXB0aW9uIHJkZjphYm91dD0iIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtbG5zOnhtcE1NPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvbW0vIiB4bWxuczpzdEV2dD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL3NUeXBlL1Jlc291cmNlRXZlbnQjIiB4bWxuczpwaG90b3Nob3A9Imh0dHA6Ly9ucy5hZG9iZS5jb20vcGhvdG9zaG9wLzEuMC8iIHhtbG5zOmRjPSJodHRwOi8vcHVybC5vcmcvZGMvZWxlbWVudHMvMS4xLyIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgMjEuMiAoTWFjaW50b3NoKSIgeG1wOkNyZWF0ZURhdGU9IjIwMjAtMDgtMTlUMjE6NTk6MjkrMDg6MDAiIHhtcDpNZXRhZGF0YURhdGU9IjIwMjAtMDgtMTlUMjE6NTk6MjkrMDg6MDAiIHhtcDpNb2RpZnlEYXRlPSIyMDIwLTA4LTE5VDIxOjU5OjI5KzA4OjAwIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjA1OTRiZThkLWY1NTktNGRjMi05MThhLWViZDVlMWQ3YWE5MyIgeG1wTU06RG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjExNjYwOThkLTg5ZDEtMjQ0Yy05OTczLTNhNTIwOGIwYzQ5MCIgeG1wTU06T3JpZ2luYWxEb2N1bWVudElEPSJ4bXAuZGlkOjZiZWI1YjRjLTcyNDYtNGFlMi05NWEyLTFjMDYxM2QzYjc4NiIgcGhvdG9zaG9wOkNvbG9yTW9kZT0iMyIgZGM6Zm9ybWF0PSJpbWFnZS9wbmciPiA8eG1wTU06SGlzdG9yeT4gPHJkZjpTZXE+IDxyZGY6bGkgc3RFdnQ6YWN0aW9uPSJjcmVhdGVkIiBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjZiZWI1YjRjLTcyNDYtNGFlMi05NWEyLTFjMDYxM2QzYjc4NiIgc3RFdnQ6d2hlbj0iMjAyMC0wOC0xOVQyMTo1OToyOSswODowMCIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIDIxLjIgKE1hY2ludG9zaCkiLz4gPHJkZjpsaSBzdEV2dDphY3Rpb249InNhdmVkIiBzdEV2dDppbnN0YW5jZUlEPSJ4bXAuaWlkOjA1OTRiZThkLWY1NTktNGRjMi05MThhLWViZDVlMWQ3YWE5MyIgc3RFdnQ6d2hlbj0iMjAyMC0wOC0xOVQyMTo1OToyOSswODowMCIgc3RFdnQ6c29mdHdhcmVBZ2VudD0iQWRvYmUgUGhvdG9zaG9wIDIxLjIgKE1hY2ludG9zaCkiIHN0RXZ0OmNoYW5nZWQ9Ii8iLz4gPC9yZGY6U2VxPiA8L3htcE1NOkhpc3Rvcnk+IDxwaG90b3Nob3A6RG9jdW1lbnRBbmNlc3RvcnM+IDxyZGY6QmFnPiA8cmRmOmxpPjdDQURFMDhDMEYzODA0RDUyRTM5MTM1QjJENDQ3MDVDPC9yZGY6bGk+IDwvcmRmOkJhZz4gPC9waG90b3Nob3A6RG9jdW1lbnRBbmNlc3RvcnM+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+dbFv4wAAGexJREFUeJzVnXl0XMWV/z+v9037bsmSbdnG+84WGzJA2BwmQMgwYZLfOEBIGDJZJuHgCUySYX5AfkAmIYQJBwiEhMkkISFhGUIMBAcbTGzAuyRLli3J2nep1Xu/1+/3x+1GrZYsdau7ZfI95x11vaVe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" height="32" width="32" style="margin: 0; vertical-align: bottom;" />
						<div style="font-size: 24px; font-weight: bold; display: inline-block; vertical-align: baseline; margin-left: 6px; line-height: 1">{$oj_name_short}</div>
					</div>
					<hr />

					<h1 style="margin: 20px auto;"><center>{$email['subject']}</center></h1>
					<div style="font-size: 18px">{$name} 您好，</div>
					<br />

					<div>
					{$email['content']}
					</div>

					<br />
					<br />

					<div style="text-align: right; margin-bottom: 16px;">
						<a href="{$oj_url}">{$oj_name}</a>
						<br />
						{$email['created_at']}
					</div>

					<hr />
					<div style="font-size: 12px; color: grey; text-align: center;">
						本邮件由系统自动发送，请勿回复。
						<br />
						您之所以收到本邮件，是因为您是 {$oj_name} 的用户。
						<br />
						若本邮件没有出现在正常收件箱内，请将我们的发信邮箱地址 {$oj_email_address} 添加到您的邮箱白名单内，以免错过重要通知。
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
			} else {
				DB::update("update emails set send_time = now() where id = {$email['id']}");
				echo '[UOJMail::cronSendEmail] ID: ' . $email['id'] . ' - empty email address.' . "\n";
			}
		}

		echo '[UOJMail::cronSendEmail] Done.' . "\n";
	}
}
