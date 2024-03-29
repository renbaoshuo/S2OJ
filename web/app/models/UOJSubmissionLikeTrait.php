<?php

trait UOJSubmissionLikeTrait {
	use UOJDataTrait;

	/**
	 * @var UOJProblem|UOJContestProblem
	 */
	public $problem = null;

	public static function getAndRememberSubmissionLanguage(array $content) {
		$language = '/';
		foreach ($content['config'] as $row) {
			if (strEndWith($row[0], '_language')) {
				$language = $row[1];
				break;
			}
		}
		if ($language != '/') {
			Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
		}
		return $language;
	}

	public function setProblem(array $cfg = []) {
		$cfg += ['problem' => 'auto'];
		$problem = $cfg['problem'] === 'auto' ? UOJProblem::query($this->info['problem_id']) : $cfg['problem'];
		if (!($problem instanceof UOJProblem && $problem->info['id'] == $this->info['problem_id'])) {
			return false;
		}
		if (isset($this->info['contest_id'])) {
			if (!($problem instanceof UOJContestProblem && $problem->contest->info['id'] == $this->info['contest_id'])) {
				$problem = new UOJContestProblem($problem->info, UOJContest::query($this->info['contest_id']));
				if (!$problem->valid()) {
					return false;
				}
			}
		} else {
			if ($problem instanceof UOJContestProblem) {
				$problem = new UOJProblem($problem->info);
			}
		}
		$this->problem = $problem;
		return true;
	}

	public function userIsSubmitter(array $user = null) {
		return $user && $this->info['submitter'] === $user['username'];
	}

	public function userCanView(array $user = null, array $cfg = []) {
		$cfg += ['ensure' => false];

		if (!$this->problem->userCanView($user) && !$this->userIsSubmitter($user)) {
			$cfg['ensure'] && UOJResponse::page403();
			return false;
		} elseif (!$this->info['is_hidden']) {
			return true;
		} elseif ($this->userCanManageProblemOrContest($user)) {
			return true;
		} else {
			$cfg['ensure'] && UOJResponse::page404();
			return false;
		}
	}

	public function userCanManageProblemOrContest(array $user = null) {
		if (!$this->problem instanceof UOJContestProblem && $this->problem->userCanManage($user)) {
			return true;
		} elseif ($this->problem instanceof UOJContestProblem && $this->problem->contest->userCanManage($user)) {
			return true;
		} else {
			return false;
		}
	}

	public function userCanDelete(array $user = null) {
		return isSuperUser($user);
	}

	public function publicStatus() {
		return explode(', ', $this->info['status'])[0];
	}

	public function isWaiting() {
		$status = $this->publicStatus();
		return $status === 'Waiting' || $status === 'Waiting Rejudge';
	}

	public function hasJudged() {
		return $this->publicStatus() === 'Judged';
	}

	public function userPermissionCodeCheck(array $user = null, $perm_code) {
		switch ($perm_code) {
			case 'SELF':
				return $this->userIsSubmitter($user);
			default:
				return $this->problem->userPermissionCodeCheck($user, $perm_code);
		}
	}

	public function viewerCanSeeStatusDetailsHTML(array $user = null) {
		return $this->userIsSubmitter($user) && !$this->hasJudged();
	}
	public function getStatusDetailsHTML() {
		return getSubmissionStatusDetailsHTML($this->publicStatus(), $this->info['status_details']);
	}

	public function getUri() {
		return $this->info['id'];
	}
	public function getLink() {
		return '<a class="text-decoration-none" href="' . HTML::url($this->getUri()) . '">#' . $this->info['id'] . '</a>';
	}

	public function getResult($key = null) {
		if (!isset($this->info['result'])) {
			return null;
		}
		$result = json_decode($this->info['result'], true);
		if ($key === null) {
			return $result;
		}
		return isset($result[$key]) ? $result[$key] : null;
	}

	public function getContent($key = null) {
		if (!isset($this->info['content'])) {
			return null;
		}
		$content = json_decode($this->info['content'], true);
		if ($key === null) {
			return $content;
		}
		return isset($content[$key]) ? $content[$key] : null;
	}

	public function echoContent($cfg = []) {
		$cfg += [
			'list_group' => false,
		];

		$content = $this->getContent();
		if (!$content) {
			return false;
		}

		$card_class = 'card mb-3';
		$card_header_class = 'card-header fw-bold';
		$card_body_class = 'card-body';
		$card_footer_class = 'card-footer';

		if ($cfg['list_group']) {
			$card_class = 'list-group-item';
			$card_header_class = 'fw-bold mb-2';
			$card_body_class = '';
			$card_footer_class = 'text-end mt-2';
		}

		$zip_file = new ZipArchive();
		if ($zip_file->open(UOJContext::storagePath() . $content['file_name'], ZipArchive::RDONLY) !== true) {
			echo <<<EOD
				<div class="{$card_class}">
					<div class="{$card_header_class}">
						提交内容
					</div>
					<div class="{$card_body_class} text-danger">
						木有
					</div>
				</div>
			EOD;
			return false;
		}

		$config = [];
		foreach ($content['config'] as $val) {
			$config[$val[0]] = $val[1];
		}

		foreach ($this->problem->getSubmissionRequirement() as $req) {
			if ($req['type'] == "source code") {
				$file_content = $zip_file->getFromName("{$req['name']}.code");
				if ($file_content === false) {
					$file_content = '';
				}

				if (isset($config["{$req['name']}_language"])) {
					$file_language = $config["{$req['name']}_language"];
				} else {
					$file_language = '?';
				}

				$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
				$footer_text = UOJLocale::get('problems::source code') . ': ';
				$footer_text .= UOJLang::getLanguageDisplayName($file_language);
				$sh_class = UOJLang::getLanguagesCSSClass($file_language);
				echo <<<EOD
					<div class="{$card_class}">
						<div class="{$card_header_class}">
							{$req['name']}
						</div>
						<div class="{$card_body_class} copy-button-container">
							<pre class="mb-0"><code class="$sh_class bg-body-tertiary rounded p-3">{$file_content}\n</code></pre>
						</div>
						<div class="{$card_footer_class}">$footer_text</div>
					</div>
				EOD;
			} elseif ($req['type'] == "text") {
				$file_content = $zip_file->getFromName("{$req['file_name']}", 504);
				if ($file_content === false) {
					$file_content = '';
				}

				$file_content = strOmit($file_content, 500);
				$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
				$footer_text = UOJLocale::get('problems::text file');
				echo <<<EOD
					<div class="{$card_class}">
						<div class="{$card_header_class}">
							{$req['file_name']}
						</div>
						<div class="{$card_body_class}">
							<pre class="mb-0 bg-body-tertiary rounded p-3">\n{$file_content}\n</pre>
						</div>
						<div class="{$card_footer_class}">{$footer_text}</div>
					</div>
				EOD;
			}
		}
		$zip_file->close();

		return true;
	}


	protected function echoStatusBarTDBase($name, array $cfg) {
		$cfg += [
			'time_format' => 'normal',
			'time_font_size' => 'small',
			'unknown_char' => '/',
			'contest_problem_letter' => false,
			'problem_title' => [],
		];

		switch ($name) {
			case 'id':
				echo $this->getLink();
				break;
			case 'problem':
				if ($this->problem) {
					if ($cfg['contest_problem_letter'] && $this->problem instanceof UOJContestProblem) {
						echo $this->problem->getLink($cfg['problem_title'] + ['with' => 'letter', 'simplify' => true]);
					} else {
						echo $this->problem->getLink($cfg['problem_title']);
					}
				} else {
					echo '<span class="text-danger">?</span>';
				}
				break;
			case 'contest':
				if ($this->problem && $this->problem instanceof UOJContestProblem) {
					echo $this->problem->contest->getLink();
				} else {
					echo '<span class="text-danger">?</span>';
				}
			case 'submitter':
			case 'owner':
			case 'hacker':
				echo UOJUser::getLink($this->info[$name]);
				break;
			case 'used_time':
				if ($cfg['show_actual_score']) {
					if ($this->info['used_time'] < 1000) {
						echo $this->info['used_time'] . ' ms';
					} else {
						echo sprintf("%.2f", $this->info['used_time'] / 1000) . ' s';
					}
				} else {
					echo $cfg['unknown_char'];
				}
				break;
			case 'used_memory':
				if ($cfg['show_actual_score']) {
					if ($this->info['used_memory'] < 1024) {
						echo $this->info['used_memory'] . ' KB';
					} else {
						echo sprintf("%.2f", $this->info['used_memory'] / 1024) . ' MB';
					}
				} else {
					echo $cfg['unknown_char'];
				}
				break;
			case 'tot_size':
				if ($this->info['tot_size'] < 1024) {
					echo $this->info['tot_size'] . ' B';
				} else {
					echo sprintf("%.2f", $this->info['tot_size'] / 1024) . ' KB';
				}
				break;
			case 'submit_time':
			case 'judge_time':
				if ($cfg['time_font_size'] == 'small') {
					echo '<small>';
				}
				if ($cfg['time_format'] == 'friendly') {
					echo UOJTime::userFriendlyFormat($this->info[$name]);
				} else {
					echo $this->info[$name];
				}
				if ($cfg['time_font_size'] == 'small') {
					echo '</small>';
				}
				break;
			default:
				echo '?';
				break;
		}
	}
}
