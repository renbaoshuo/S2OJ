<?php

// this class depends on getUOJConf from uoj-judger-lib.php sometimes
// be sure to include the lib
// TODO: move getUOJConf into a static class independent of uoj-judger-lib.php

class UOJProblem {
	use UOJDataTrait;
	use UOJArticleTrait;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from problems",
			"where", ['id' => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJProblem($info);
	}

	public static function upgradeToContestProblem() {
		return (new UOJContestProblem(self::cur()->info, UOJContest::cur()))->setAsCur()->valid();
	}

	public static function userCanManageSomeProblem(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from problems_permissions",
			"where", [
				'username' => $user['username']
			], DB::limit(1)
		]) != null || DB::selectFirst([
			DB::lc(), "select 1 from problems",
			"where", [
				"uploader" => $user['username'],
			], DB::limit(1),
		]) != null;
	}

	public static function userCanCreateProblem(array $user = null) {
		if (!$user) {
			return false;
		}

		return isSuperUser($user) || UOJUser::checkPermission($user, 'problems.create');
	}

	public function __construct($info) {
		$this->info = $info;
	}

	public function getTitle(array $cfg = []) {
		$cfg += [
			'with' => 'id',
			'simplify' => false
		];
		$title = $this->info['title'];
		if ($cfg['simplify']) {
			$title = trim($title);
			$title = mb_ereg_replace('^(\[[^\]]*\]|【[^】]*】)', '', $title);
			$title = trim($title);
		}
		if ($cfg['with'] == 'id') {
			return "#{$this->info['id']}. {$title}";
		} else {
			return $title;
		}
	}

	public function getUri($where = '') {
		return "/problem/{$this->info['id']}{$where}";
	}

	public function getLink(array $cfg = []) {
		return HTML::link($this->getUri(), $this->getTitle($cfg));
	}

	public function getAttachmentUri() {
		return '/download/problem/' . $this->info['id'] . '/attachment.zip';
	}

	public function getMainDataUri() {
		return '/download/problem/' . $this->info['id'] . '/data.zip';
	}

	public function getUploaderLink() {
		return UOJUser::getLink($this->info['uploader'] ?: "root");
	}

	public function findInContests() {
		$res = DB::selectAll([
			"select contest_id from contests_problems",
			"where", ['problem_id' => $this->info['id']]
		]);
		$cps = [];
		foreach ($res as $row) {
			$cp = new UOJContestProblem($this->info, UOJContest::query($row['contest_id']));
			if ($cp->valid()) {
				$cps[] = $cp;
			}
		}
		return $cps;
	}

	public function userCanClickZan(array $user = null) {
		if ($this->userCanView($user)) {
			return true;
		}
		foreach ($this->findInContests() as $cp) {
			if ($cp->userCanClickZan($user)) {
				return true;
			}
		}
		return false;
	}

	public function getZanBlock() {
		return ClickZans::getBlock('P', $this->info['id'], $this->info['zan']);
	}

	public function getSubmissionRequirement() {
		return json_decode($this->info['submission_requirement'], true);
	}
	public function getExtraConfig($key = null) {
		$extra_config = json_decode($this->info['extra_config'], true);

		$extra_config += [
			'view_content_type' => 'ALL',
			'view_all_details_type' => 'ALL',
			'view_details_type' => 'ALL',
			'view_solution_type' => 'ALL',
			'submit_solution_type' => 'ALL_AFTER_AC',
			'need_to_review_hack' => false,
			'add_hack_as' => 'ex_test',
		];

		return $key === null ? $extra_config : $extra_config[$key];
	}
	public function getCustomTestRequirement() {
		$extra_config = json_decode($this->info['extra_config'], true);
		if (isset($extra_config['custom_test_requirement'])) {
			return $extra_config['custom_test_requirement'];
		} else {
			$answer = [
				'name' => 'answer',
				'type' => 'source code',
				'file_name' => 'answer.code'
			];
			foreach ($this->getSubmissionRequirement() as $req) {
				if ($req['name'] == 'answer' && $req['type'] == 'source code' && isset($req['languages'])) {
					$answer['languages'] = $req['languages'];
				}
			}
			return [
				$answer, [
					'name' => 'input',
					'type' => 'text',
					'file_name' => 'input.txt'
				]
			];
		}
	}

	public function userCanView(array $user = null, array $cfg = []) {
		$cfg += ['ensure' => false];

		if ($this->info['is_hidden'] && !$this->userCanManage($user)) {
			$cfg['ensure'] && UOJResponse::page404();
			return false;
		}

		if (!UOJUser::checkPermission($user, 'problems.view')) {
			$cfg['ensure'] && UOJResponse::page403();
			return false;
		}

		return true;
	}

	/**
	 * Get a SQL cause to determine whether a user can view a problem
	 * Need to be consistent with the member function userCanView
	 */
	public static function sqlForUserCanView(array $user = null) {
		if (isSuperUser($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return "(1)";
		} elseif (UOJProblem::userCanManageSomeProblem($user)) {
			return DB::lor([
				"problems.is_hidden" => false,
				DB::land([
					"problems.is_hidden" => true,
					DB::lor([
						[
							"problems.id", "in", DB::rawbracket([
								"select problem_id from problems_permissions",
								"where", ["username" => $user['username']]
							])
						],
						[
							"problems.id", "in", DB::rawbracket([
								"select problem_id from problems",
								"where", ["uploader" => $user['username']]
							])
						],
					])
				])
			]);
		} else {
			return "(problems.is_hidden = false)";
		}
	}

	public function isUserOwnProblem(array $user = null) {
		if (!$user) {
			return false;
		}
		return $user['username'] === $this->info['uploader'];
	}

	public function userPermissionCodeCheck(array $user = null, $perm_code) {
		switch ($perm_code) {
			case 'ALL':
				return true;
			case 'ALL_AFTER_AC':
				return $this->userHasAC($user);
			case 'NONE':
				return false;
			default:
				return null;
		}
	}

	public function userCanUploadSubmissionViaZip(array $user = null) {
		foreach ($this->getSubmissionRequirement() as $req) {
			if ($req['type'] == 'source code') {
				return false;
			}
		}
		return true;
	}

	public function userCanDownloadAttachments(array $user = null) {
		if ($this->userCanView($user)) {
			return true;
		}
		foreach ($this->findInContests() as $cp) {
			if ($cp->userCanDownloadAttachments($user)) {
				return true;
			}
		}
		return false;
	}

	public function userCanManage(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || $this->isUserOwnProblem($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from problems_permissions",
			"where", [
				'username' => $user['username'],
				'problem_id' => $this->info['id']
			]
		]) != null;
	}

	public function userCanDownloadTestData(array $user = null) {
		if ($this->userCanManage($user)) {
			return true;
		}

		if (!UOJUser::checkPermission($user, 'problems.download_testdata')) {
			return false;
		}

		foreach ($this->findInContests() as $cp) {
			if ($cp->contest->userHasRegistered($user) && $cp->contest->progress() == CONTEST_IN_PROGRESS) {
				return false;
			}
		}

		return true;
	}

	public function preHackCheck(array $user = null) {
		return $this->info['hackable'] && (!$user || $this->userCanView($user));
	}

	public function needToReviewHack() {
		return $this->getExtraConfig('need_to_review_hack');
	}

	public function userHasAC(array $user = null) {
		if (!$user) {
			return false;
		}
		return DB::selectFirst([
			DB::lc(), "select 1 from best_ac_submissions",
			"where", [
				'submitter' => $user['username'],
				'problem_id' => $this->info['id']
			]
		]) != null;
	}

	public function preSubmitCheck() {
		return true;
	}

	public function additionalSubmissionComponentsCannotBeSeenByUser(array $user = null, UOJSubmission $submission) {
		foreach ($this->findInContests() as $cp) {
			if ($cp->contest->userHasRegistered($user) && $cp->contest->progress() == CONTEST_IN_PROGRESS) {
				if ($submission->userIsSubmitter($user)) {
					if ($cp->contest->getJudgeTypeInContest() == 'no-details') {
						return ['low_level_details'];
					} else {
						return [];
					}
				} else {
					return ['content', 'high_level_details', 'low_level_details'];
				}
			}
		}

		return [];
	}

	public function getDataFolderPath() {
		return "/var/uoj_data/{$this->info['id']}";
	}

	public function getDataZipPath() {
		return "/var/uoj_data/{$this->info['id']}.zip";
	}

	public function getDataFilePath($name = '') {
		// return "zip://{$this->getDataZipPath()}#{$this->info['id']}/$name";
		return "{$this->getDataFolderPath()}/$name";
	}

	public function getProblemConfArray(string $where = 'data') {
		if ($where === 'data') {
			return getUOJConf($this->getDataFilePath('problem.conf'));
		} else {
			return null;
		}
	}

	public function getProblemConf(string $where = 'data') {
		if ($where === 'data') {
			return UOJProblemConf::getFromFile($this->getDataFilePath('problem.conf'));
		} else {
			return null;
		}
	}

	public function getNonTraditionalJudgeType() {
		$conf = $this->getProblemConf();
		if (!($conf instanceof UOJProblemConf)) {
			return false;
		}
		return $conf->getNonTraditionalJudgeType();
	}
}

UOJProblem::$table_for_content = 'problems_contents';
UOJProblem::$key_for_content = 'id';
UOJProblem::$fields_for_content = ['*'];
UOJProblem::$table_for_tags = 'problems_tags';
UOJProblem::$key_for_tags = 'problem_id';
