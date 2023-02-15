<?php

class UOJContest {
	use UOJDataTrait;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from contests",
			"where", ['id' => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJContest($info);
	}

	public static function queryUpcomingContests(array $user = null, $limit = -1) {
		return array_filter(array_map(fn ($x) => UOJContest::query($x['id']), DB::selectAll([
			"select id from contests",
			"where", [
				"status" => "unfinished",
			],
			"order by start_time asc, id asc",
			$limit == -1 ? "" : DB::limit($limit),
		])), fn ($contest) => $contest->userCanView($user));
	}

	public static function userCanManageSomeContest(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || UOJUser::checkPermission($user, 'contests.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from contests_permissions",
			"where", [
				'username' => $user['username']
			], DB::limit(1)
		]) != null;
	}

	public static function userCanCreateContest(array $user = null) {
		if (!$user) {
			return false;
		}

		return isSuperUser($user) || UOJUser::checkPermission($user, 'contests.create');
	}

	public static function announceOfficialResults() {
		// time config
		set_time_limit(0);
		ignore_user_abort(true);

		$contest = self::info();

		$data = queryContestData($contest);
		$n_problems = count($data['problems']);
		$total_score = $n_problems * 100;
		calcStandings($contest, $data, $score, $standings, ['update_contests_submissions' => true]);

		for ($i = 0; $i < count($standings); $i++) {
			$tail = $standings[$i][0] == $total_score ? '，请继续保持。' : '，请继续努力！';

			sendSystemMsg($standings[$i][2][0], '比赛成绩公布通知', '您参与的比赛 <a href="' . HTML::url('/contest/' . $contest['id']) . '">' . $contest['name'] . '</a> 现已公布成绩，您的成绩为 <a class="uoj-score" data-max="' . $total_score . '">' . $standings[$i][0] . '</a>' . $tail);
			DB::update([
				"update contests_registrants",
				"set", ["final_rank" => $standings[$i][3]],
				"where", [
					"contest_id" => $contest['id'],
					"username" => $standings[$i][2][0]
				]
			]);
		}
		DB::update([
			"update contests",
			"set", ["status" => 'finished'],
			"where", ["id" => $contest['id']]
		]);
	}

	public function __construct($info) {
		$this->info = $info;
		$this->completeInfo();
	}

	public function completeInfo() {
		if (isset($this->info['cur_progress'])) {
			return;
		}
		$this->info['start_time_str'] = $this->info['start_time'];
		$this->info['start_time'] = new DateTime($this->info['start_time']);
		$this->info['end_time_str'] = $this->info['end_time'];
		$this->info['end_time'] = new DateTime($this->info['end_time']);

		$this->info['extra_config'] = json_decode($this->info['extra_config'], true);

		if (!isset($this->info['extra_config']['standings_version'])) {
			$this->info['extra_config']['standings_version'] = 2;
		}
		if (!isset($this->info['extra_config']['basic_rule'])) {
			$this->info['extra_config']['basic_rule'] = 'OI';
		}
		if (!isset($this->info['extra_config']['free_registration'])) {
			$this->info['extra_config']['free_registration'] = 1;
		}
		if (!isset($this->info['extra_config']['extra_registration'])) {
			$this->info['extra_config']['extra_registration'] = 1;
		}
		if (!isset($this->info['extra_config']['individual_or_team'])) {
			$this->info['extra_config']['individual_or_team'] = 'individual';
		}
		if (!isset($this->info['extra_config']['bonus'])) {
			$this->info['extra_config']['bonus'] = [];
		}
		if (!isset($this->info['extra_config']['submit_time_limit'])) {
			$this->info['extra_config']['submit_time_limit'] = [];
		}
		if (!isset($this->info['extra_config']['max_n_submissions_per_problem'])) {
			$this->info['extra_config']['max_n_submissions_per_problem'] = -1;
		}

		if ($this->info['status'] == 'unfinished') {
			if (UOJTime::$time_now < $this->info['start_time']) {
				$this->info['cur_progress'] = CONTEST_NOT_STARTED;
			} elseif (UOJTime::$time_now < $this->info['end_time']) {
				$this->info['cur_progress'] = CONTEST_IN_PROGRESS;
			} else {
				if ($this->info['extra_config']['basic_rule'] == 'IOI') {
					$this->info['cur_progress'] = CONTEST_TESTING;
				} else {
					$this->info['cur_progress'] = CONTEST_PENDING_FINAL_TEST;
				}
			}
		} elseif ($this->info['status'] == 'testing') {
			$this->info['cur_progress'] = CONTEST_TESTING;
		} elseif ($this->info['status'] == 'finished') {
			$this->info['cur_progress'] = CONTEST_FINISHED;
		}

		$this->info['frozen_time'] = false;
		$this->info['frozen'] = false;

		if ($this->info['extra_config']['basic_rule'] == 'ACM') {
			$this->info['frozen_time'] = clone $this->info['end_time'];

			$frozen_min = min($this->info['last_min'] / 5, 60);

			$this->info['frozen_time']->sub(new DateInterval("PT{$frozen_min}M"));
			$this->info['frozen'] = $this->info['cur_progress'] < CONTEST_TESTING && UOJTime::$time_now > $this->info['frozen_time'];
		}
	}

	public function basicRule() {
		return $this->info['extra_config']['basic_rule'];
	}

	public function progress() {
		return $this->info['cur_progress'];
	}

	public function maxSubmissionCountPerProblem() {
		return $this->info['extra_config']['max_n_submissions_per_problem'];
	}

	public function freeRegistration() {
		return $this->info['extra_config']['free_registration'];
	}

	public function allowExtraRegistration() {
		return $this->info['extra_config']['extra_registration'];
	}

	public function labelForFinalTest() {
		if ($this->basicRule() === 'ACM') {
			$label = '揭榜';
		} else {
			$label = '开始最终测试';
		}

		return $label;
	}

	public function finalTest() {
		ignore_user_abort(true);
		set_time_limit(0);

		DB::update([
			"update contests",
			"set", ["status" => 'testing'],
			"where", ["id" => $this->info['id']]
		]);

		if (DB::affected_rows() !== 1) {
			// 已经有其他人开始评测了，不进行任何操作
			return;
		}

		$res = DB::selectAll([
			"select id, problem_id, content, result, submitter, hide_score_to_others from submissions",
			"where", ["contest_id" => $this->info['id']]
		]);
		foreach ($res as $submission) {
			$content = json_decode($submission['content'], true);

			if (isset($content['final_test_config'])) {
				$content['config'] = $content['final_test_config'];
				unset($content['final_test_config']);
			}

			if (isset($content['first_test_config'])) {
				unset($content['first_test_config']);
			}

			$q = [
				'content' => json_encode($content),
			];

			$problem_judge_type = $this->info['extra_config']["problem_{$submission['problem_id']}"] ?: $this->defaultProblemJudgeType();
			$result = json_decode($submission['result'], true);

			switch ($problem_judge_type) {
				case 'sample':
					if (isset($result['final_result']) && $result['final_result']['status'] == 'Judged') {
						$q += [
							'result' => json_encode($result['final_result']),
							'score' => $result['final_result']['score'],
							'used_time' => $result['final_result']['time'],
							'used_memory' => $result['final_result']['memory'],
							'judge_time' => $this->info['end_time_str'],
							'status' => 'Judged',
						];

						if ($submission['hide_score_to_others']) {
							$q['hidden_score'] = $q['score'];
							$q['score'] = null;
						}
					}

					break;

				case 'no-details':
				case 'full':
					if ($result['status'] == 'Judged' && !isset($result['final_result'])) {
						$q += [
							'result' => $submission['result'],
							'score' => $result['score'],
							'used_time' => $result['time'],
							'used_memory' => $result['memory'],
							'judge_time' => $this->info['end_time_str'],
							'status' => 'Judged',
						];

						if ($submission['hide_score_to_others']) {
							$q['hidden_score'] = $q['score'];
							$q['score'] = null;
						}
					}

					break;
			}

			UOJSubmission::rejudgeById($submission['id'], [
				'reason_text' => HTML::stripTags($this->info['name']) . ' 最终测试',
				'reason_url' => HTML::url(UOJContest::cur()->getUri()),
				'set_q' => $q,
			]);
		}

		// warning: check if this command works well when the database is not MySQL
		DB::update([
			"update submissions",
			"set", [
				"score = hidden_score",
				"hidden_score = NULL",
				"hide_score_to_others = 0"
			], "where", [
				"contest_id" => $this->info['id'],
				"hide_score_to_others" => 1
			]
		]);

		$updated = [];
		foreach ($res as $submission) {
			$submitter = $submission['submitter'];
			$pid = $submission['problem_id'];
			if (isset($updated[$submitter]) && isset($updated[$submitter][$pid])) {
				continue;
			}
			updateBestACSubmissions($submitter, $pid);
			if (!isset($updated[$submitter])) {
				$updated[$submitter] = [];
			}
			$updated[$submitter][$pid] = true;
		}
	}

	public function queryJudgeProgress() {
		if ($this->basicRule() == 'OI' && $this->progress() < CONTEST_TESTING) {
			$rop = 0;
			$title = UOJLocale::get('contests::contest pending final test');
			$fully_judged = false;
		} else {
			$total = DB::selectCount([
				"select count(*) from submissions",
				"where", ["contest_id" => $this->info['id']]
			]);
			$n_judged = DB::selectCount([
				"select count(*) from submissions",
				"where", [
					"contest_id" => $this->info['id'],
					"status" => 'Judged'
				]
			]);
			$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);

			$title = UOJLocale::get('contests::contest final testing');
			$fully_judged = $n_judged == $total;
			if ($this->basicRule() != 'OI' && $fully_judged) {
				$title = UOJLocale::get('contests::contest official results to be announced');
			}
		}
		return [
			'rop' => $rop,
			'title' => $title,
			'fully_judged' => $fully_judged,
		];
	}

	public function queryResult($cfg = []) {
		$contest_data = queryContestData($this->info, $cfg);
		calcStandings($this->info, $contest_data, $score, $standings, $cfg);

		return [
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data,
		];
	}

	public function managerCanSeeFinalStandingsTab(array $user = null) {
		if ($this->basicRule() == 'IOI') {
			return false;
		}
		return $this->progress() < CONTEST_TESTING;
	}

	public function userCanSeeProblemStatistics($user) {
		return $this->userCanManage($user) || $this->progress() > CONTEST_IN_PROGRESS;
	}

	public function userCanRegister(array $user = null, $cfg = []) {
		$cfg += ['ensure' => false];

		if (!$user) {
			$cfg['ensure'] && redirectToLogin();
			return false;
		}
		if (!$this->freeRegistration()) {
			$cfg['ensure'] && $this->redirectToAnnouncementBlog();
			return false;
		}
		if (!UOJUser::checkPermission($user, 'contests.register')) {
			$cfg['ensure'] && UOJResponse::page403();
			return false;
		}
		if ($this->progress() == CONTEST_IN_PROGRESS && !$this->allowExtraRegistration()) {
			$cfg['ensure'] && redirectTo('/contests');
			return false;
		}
		if (
			/* $this->userCanManage($user) || */ // 在 S2OJ 中，具有管理员权限的用户也可报名参赛。
			$this->userHasRegistered($user) || $this->progress() > CONTEST_IN_PROGRESS
		) {
			$cfg['ensure'] && redirectTo('/contests');
			return false;
		}
		if (isTmpUser($user)) {
			$cfg['ensure'] && UOJResponse::message("<h1>临时账号无法报名该比赛</h1><p>换个自己注册的账号试试吧~</p>");
			return false;
		}
		return true;
	}

	public function userCanView(array $user = null, $cfg = []) {
		$cfg += [
			'ensure' => false,
			'check-register' => false
		];

		if ($this->userCanManage($user)) {
			if ($this->userHasRegistered($user) && $this->progress() == CONTEST_IN_PROGRESS && !$this->userHasMarkedParticipated($user)) {
				$cfg['ensure'] && redirectTo($this->getUri('/confirm'));
				return false;
			}

			return true;
		}
		if ($this->progress() == CONTEST_NOT_STARTED) {
			$cfg['ensure'] && redirectTo($this->getUri('/register'));
			return false;
		} elseif ($this->progress() <= CONTEST_IN_PROGRESS) {
			if ($cfg['check-register']) {
				if ($user && $this->userHasRegistered($user)) {
					if (!$this->userHasMarkedParticipated($user)) {
						$cfg['ensure'] && redirectTo($this->getUri('/confirm'));
						return false;
					}

					return true;
				}

				if ($cfg['ensure']) {
					if ($this->info['extra_config']['extra_registration']) {
						redirectTo($this->getUri('/register'));
					} else {
						UOJResponse::message("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
					}
				}

				return false;
			} else {
				return true;
			}
		} else {
			if (!$this->userHasRegistered($user) && !UOJUser::checkPermission($user, 'contests.view')) {
				$cfg['ensure'] && UOJResponse::page403();
				return false;
			}

			return true;
		}
	}

	public function userCanParticipateNow(array $user = null) {
		// 在 S2OJ 中，具有管理员权限的用户在报名后也可参赛。
		//
		// if ($this->userCanManage($user)) {
		//     return false;
		// }

		return $this->progress() == CONTEST_IN_PROGRESS && $user && $this->userHasRegistered($user);
	}

	public function userCanManage(array $user = null) {
		if (!$user) {
			return false;
		}

		if (isSuperUser($user) || UOJUser::checkPermission($user, 'contests.manage')) {
			return true;
		}

		return DB::selectFirst([
			DB::lc(), "select 1 from contests_permissions",
			"where", [
				'username' => $user['username'],
				'contest_id' => $this->info['id']
			]
		]) != null;
	}

	public function userCanStartFinalTest(array $user = null) {
		return $this->userCanManage($user) || UOJUser::checkPermission($user, 'contests.start_final_test');
	}

	public function userHasRegistered(array $user = null) {
		if (!$user) {
			return false;
		}
		return DB::selectFirst([
			DB::lc(), "select 1 from contests_registrants",
			"where", [
				'username' => $user['username'],
				'contest_id' => $this->info['id']
			]
		]) != null;
	}

	public function defaultProblemJudgeType() {
		if ($this->basicRule() == 'OI') {
			return 'sample';
		} else {
			return 'no-details';
		}
	}

	public function getProblemIDs() {
		return array_map(fn ($x) => $x['problem_id'], DB::selectAll([
			DB::lc(), "select problem_id from contests_problems",
			"where", ['contest_id' => $this->info['id']],
			"order by level, problem_id"
		]));
	}

	public function hasProblem(UOJProblem $problem) {
		return DB::selectFirst([
			DB::lc(), "select 1 from contests_problems",
			"where", [
				'contest_id' => $this->info['id'],
				'problem_id' => $problem->info['id']
			]
		]) != null;
	}

	public function userHasMarkedParticipated(array $user = null) {
		if (!$user) {
			return false;
		}
		return DB::selectExists([
			"select 1 from contests_registrants",
			"where", [
				"username" => $user['username'],
				"contest_id" => $this->info['id'],
				"has_participated" => 1
			]
		]);
	}

	public function markUserAsParticipated(array $user = null) {
		if (!$user) {
			return false;
		}
		return DB::update([
			"update contests_registrants",
			"set", ["has_participated" => 1],
			"where", [
				"username" => $user['username'],
				"contest_id" => $this->info['id']
			]
		]);
	}

	public function getUri($where = '') {
		return "/contest/{$this->info['id']}{$where}";
	}

	public function getLink($cfg = []) {
		$cfg += [
			'where' => '',
			'class' => '',
		];

		return HTML::tag('a', ['class' => $cfg['class'], 'href' => $this->getUri($cfg['where'])], $this->info['name']);
	}

	public function getZanBlock() {
		return ClickZans::getBlock('C', $this->info['id'], $this->info['zan']);
	}

	public function redirectToAnnouncementBlog() {
		$url = getContestBlogLink($this->info, '公告');
		if ($url !== null) {
			redirectTo($url);
		} else {
			redirectTo('/contests');
		}
	}

	public function userRegister(array $user = null) {
		if (!$user) {
			return false;
		}
		DB::insert([
			"replace into contests_registrants",
			"(username, contest_id, has_participated)",
			"values", DB::tuple([$user['username'], $this->info['id'], 0])
		]);
		updateContestPlayerNum($this->info);
		return true;
	}

	public function userUnregister(array $user = null) {
		if (!$user) {
			return false;
		}
		DB::delete([
			"delete from contests_registrants",
			"where", [
				"username" => $user['username'],
				"contest_id" => $this->info['id']
			]
		]);
		updateContestPlayerNum($this->info);
		return true;
	}

	public function getResourcesFolderPath() {
		return UOJContext::storagePath() . "/contest_resources/" . $this->info['id'];
	}

	public function getResourcesPath($name = '') {
		return "{$this->getResourcesFolderPath()}/$name";
	}

	public function getResourcesBaseUri() {
		return "/contest/{$this->info['id']}/resources";
	}

	public function getResourcesUri($name = '') {
		return "{$this->getResourcesBaseUri()}/{$name}";
	}

	public function getAdditionalLinks() {
		return $this->info['extra_config']['links'] ?: [];
	}
}
