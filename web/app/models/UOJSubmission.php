<?php

class UOJSubmission {
	use UOJSubmissionLikeTrait;

	/**
	 *   @var array
	 */
	public $latest_version = null;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from submissions",
			"where", ['id' => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJSubmission($info);
	}

	public static function initProblemAndContest() {
		if (!self::cur()->setProblem()) {
			return false;
		}
		self::cur()->problem->setAsCur();
		if (self::cur()->problem instanceof UOJContestProblem) {
			self::cur()->problem->contest->setAsCur();
		}
		return true;
	}

	/**
	 * Get a SQL cause to determine whether a user can view a submission
	 * Need to be consistent with the member function userCanView
	 */
	public static function sqlForUserCanView(array $user = null, UOJProblem $problem = null) {
		if (isSuperUser($user) || UOJUser::checkPermission($user, 'problems.manage')) {
			// MySQL can find appropriate keys to speed up the query if we write "true" in this way.
			return "(submissions.is_hidden = true or submissions.is_hidden = false)";
		} elseif ($problem) {
			if ($problem->userCanManage($user)) {
				// MySQL can find appropriate keys to speed up the query if we write "true" in this way.
				return "(submissions.is_hidden = true or submissions.is_hidden = false)";
			} else {
				return "(submissions.is_hidden = false)";
			}
		} else {
			if (UOJProblem::userCanManageSomeProblem($user)) {
				return DB::lor([
					"submissions.is_hidden" => false,
					DB::land([
						"submissions.is_hidden" => true,
						DB::lor([
							[
								"submissions.problem_id", "in", DB::rawbracket([
									"select problem_id from problems_permissions",
									"where", ["username" => $user['username']]
								])
							],
							[
								"submissions.problem_id", "in", DB::rawbracket([
									"select problem_id from problems",
									"where", ["uploader" => $user['username']]
								])
							],
						])
					])
				]);
			} else {
				return "(submissions.is_hidden = false)";
			}
		}
	}

	public static function sqlForActualScore() {
		return 'if(hide_score_to_others = 1, hidden_score, score)';
	}
	public function getActualScore() {
		return $this->info['hide_score_to_others'] ? $this->info['hidden_score'] : $this->info['score'];
	}

	public static function roundedScore($score): float {
		return round($score, 10);
	}
	public static function roundScoreInArray(&$info, $key) {
		if (isset($info[$key])) {
			$info[$key] = static::roundedScore($info[$key]);
		}
	}

	public static function onUpload($zip_file_name, $content, $tot_size, $is_contest_submission) {
		$judge_reason = '';

		$content['config'][] = ['problem_id', UOJProblem::info('id')];

		if (UOJProblem::info('type') == 'remote') {
			$content['config'][] = ['remote_online_judge', UOJProblem::cur()->getExtraConfig('remote_online_judge')];
			$content['config'][] = ['remote_problem_id', UOJProblem::cur()->getExtraConfig('remote_problem_id')];
		}

		if ($is_contest_submission && UOJContestProblem::cur()->getJudgeTypeInContest() == 'sample') {
			$content['final_test_config'] = $content['config'];
			$content['config'][] = ['test_sample_only', 'on'];
			$judge_reason = json_encode(['text' => '样例测评']);
		}

		$content_json = json_encode($content);

		$language = static::getAndRememberSubmissionLanguage($content);

		$result = ['status' => 'Waiting'];
		$result_json = json_encode($result);

		if ($is_contest_submission) {
			$qs = DB::query_str([
				"insert into submissions",
				DB::bracketed_fields([
					'problem_id', 'contest_id', 'submit_time', 'submitter', 'content', 'judge_reason',
					'language', 'tot_size', 'status', 'result', 'hide_score_to_others', 'is_hidden',
				]),
				"values", DB::tuple([
					UOJProblem::info('id'), UOJContest::info('id'), DB::now(), Auth::id(), $content_json, $judge_reason,
					$language, $tot_size, $result['status'], $result_json, UOJContest::info('frozen'), 0
				])
			]);
		} else {
			$qs = DB::query_str([
				"insert into submissions",
				DB::bracketed_fields([
					'problem_id', 'submit_time', 'submitter', 'content', 'judge_reason',
					'language', 'tot_size', 'status', 'result', 'is_hidden'
				]),
				"values", DB::tuple([
					UOJProblem::info('id'), DB::now(), Auth::id(), $content_json, $judge_reason,
					$language, $tot_size, $result['status'], $result_json, UOJProblem::info('is_hidden')
				])
			]);
		}
		$ret = retry_loop(fn () => DB::insert($qs));
		if ($ret === false) {
			unlink(UOJContext::storagePath() . $zip_file_name);
			UOJLog::error('submission failed.');
			UOJResponse::page500('submission failed.');

			return false;
		} else {
			$id = DB::insert_id();

			return $id;
		}
	}

	public static function onJudged($id, $post_result, $post_judge_time) {
		// TODO: use TID instead of judge_time to ensure the judger submitted to the correct submission!

		DB::startTransaction();
		$submission = static::query($id);
		if (!$submission) {
			UOJLog::error("cannot submit result to submission $id");
			DB::rollback();
			return false;
		}
		if ($submission->info['judge_time'] !== $post_judge_time) {
			if (!$submission->loadHistoryByTime($post_judge_time, UOJTime::FORMAT)) {
				UOJLog::error("cannot submit result to submission $id with judge_time $post_judge_time");
				DB::rollback();
				return false;
			}
		}

		$post_result = json_decode($post_result, true);

		if (isset($post_result['files'])) {
			$files = $post_result['files'];
			unset($post_result['files']);
		}

		$set_q = [
			'status' => 'Judged',
			'status_details' => '',
		];

		if ($submission->info['status'] == 'Judged, Judging') { // for UOJ-OI
			$result = json_decode($submission->info['result'], true);
			$result['final_result'] = $post_result;
			$result['final_result']['details'] = uojTextEncode($result['final_result']['details']);

			$set_q += [
				'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
			];
		} elseif ($submission->info['status'] == 'Judging') {
			$result = $post_result;

			if (isset($result['details'])) {
				$result['details'] = uojTextEncode($result['details']);
			} else {
				$result['details'] = '<error>No Comment</error>';
			}

			$set_q += [
				'result' => json_encode($result, JSON_UNESCAPED_UNICODE),
			];

			if (isset($result["error"])) {
				$actual_score = null;
				$set_q += [
					'result_error' => $result['error'],
					'used_time' => 0,
					'used_memory' => 0
				];
			} else {
				$actual_score = $result['score'];
				$set_q += [
					'result_error' => null,
					'used_time' => $result['time'],
					'used_memory' => $result['memory']
				];
			}

			if ($submission->getContent('final_test_config')) {
				$set_q['status'] = 'Judged, Waiting';
			}

			if ($submission->isLatest()) {
				if ($submission->info['hide_score_to_others']) { // for contests that have been frozen
					$set_q += [
						'score' => null,
						'hidden_score' => $actual_score
					];
				} else {
					$set_q += [
						'score' => $actual_score,
						'hidden_score' => null
					];
				}
			} else {
				$set_q += [
					'score' => $actual_score
				];
			}
		} else {
			// do nothing...
			return;
		}

		if (isset($files) && !empty($files)) {
			$zip_file = new ZipArchive();
			$zip_file->open(UOJContext::storagePath() . $submission->getContent('file_name'), ZipArchive::CREATE);
			$tot_size = 0;
			$language = $submission->info['language'];

			foreach ($files as $file) {
				$zip_file->addFromString($file['name'], $file['content']);

				if (isset($file['lang'])) {
					$language = UOJLang::getUpgradedLangCode($file['lang']);
				}

				$tot_size += $zip_file->statName($file['name'])['size'];
			}

			$set_q += [
				'tot_size' => $tot_size,
				'language' => $language,
			];

			$zip_file->close();
		}

		if ($submission->isLatest()) {
			DB::update([
				"update submissions",
				"set", $set_q,
				"where", ['id' => $submission->info['id']]
			]);
		} else {
			DB::update([
				"update submissions_history",
				"set", $set_q,
				"where", [
					'submission_id' => $submission->info['id'],
					'judge_time' => $submission->info['judge_time']
				]
			]);
		}
		DB::commit();

		if ($submission->isLatest()) {
			updateBestACSubmissions($submission->info['submitter'], $submission->info['problem_id']);
		}
	}

	public static function rejudgeAll($conds, array $cfg = []) {
		$res = DB::selectAll([
			"select id from submissions",
			"where", $conds
		]);
		foreach ($res as &$row) {
			$row = $row['id'];
		}
		static::rejudgeByIds($res, $cfg);
	}

	public static function rejudgeProblem(UOJProblem $problem, array $cfg = []) {
		$cfg += [
			'reason_text' => '管理员手动重测本题所有提交记录'
		];
		static::rejudgeAll([
			"problem_id" => $problem->info['id']
		], $cfg);
	}
	public static function rejudgeProblemAC(UOJProblem $problem, array $cfg = []) {
		$cfg += [
			'reason_text' => '管理员手动重测本题所有获得100分的提交记录'
		];
		static::rejudgeAll([
			"problem_id" => $problem->info['id'],
			"score" => 100
		], $cfg);
	}
	public static function rejudgeProblemGe97(UOJProblem $problem, array $cfg = []) {
		$cfg += [
			'reason_text' => '管理员手动重测本题所有得分≥97分的提交记录'
		];
		static::rejudgeAll([
			"problem_id" => $problem->info['id'],
			["score", ">=", 97]
		], $cfg);
	}
	public static function rejudgeById($id, $cfg = []) {
		return static::rejudgeByIds([$id], $cfg);
	}
	public static function rejudgeByIds($ids, $cfg = []) {
		$cfg += [
			'reason_text' => '管理员手动重测该提交记录',
			'requestor' => Auth::check() ? Auth::id() : '',
			'reason_url' => null,
			'set_q' => [],
			'batch_size' => 16,
			'major' => true,
		];

		$cfg['set_q'] += [
			'judge_time' => null,
			'result' => '',
			'score' => null,
			'status' => 'Waiting Rejudge',
			'judge_reason' => json_encode([
				'text' => $cfg['reason_text'],
				'requestor' => $cfg['requestor'],
				'url' => $cfg['reason_url']
			])
		];

		foreach (array_chunk($ids, $cfg['batch_size']) as $batch) {
			if ($cfg['major']) {
				DB::transaction(function () use (&$batch, &$cfg) {
					if (count($batch) == 1) {
						$cond = ['id', '=', $batch[0]];
					} else {
						$cond = ['id', 'in', DB::rawtuple($batch)];
					}

					$history_fields = [
						'submission_id', 'judge_reason', 'judge_time', 'judger',
						'result', 'status', 'result_error', 'score', 'used_time', 'used_memory',
						'major'
					];

					$cur_fields = [
						'id', 'judge_reason', 'judge_time', 'judger',
						'result', 'status', 'result_error', UOJSubmission::sqlForActualScore(), 'used_time', 'used_memory',
						DB::value(1)
					];
					DB::insert([
						"insert into submissions_history",
						DB::bracketed_fields($history_fields),
						"select", DB::fields($cur_fields), "from submissions",
						"where", [
							$cond,
							['judge_time', 'is not', null],
							DB::lor([
								['result_error', 'is', null],
								DB::land([
									['result_error', '!=', 'Judgement Failed'],
									['result_error', '!=', 'Judgment Failed']
								])
							])
						], DB::for_update()
					]);
					DB::update(["update submissions", "set", $cfg['set_q'], "where", [$cond]]);
				});
			} else {
				$history_fields = [
					'submission_id', 'judge_reason', 'result', 'status', 'major'
				];
				$cur_vals = [];
				foreach ($batch as $id) {
					$cur_vals[] = [
						$id, $cfg['set_q']['judge_reason'], $cfg['set_q']['result'], $cfg['set_q']['status'], 0
					];
				}
				DB::insert([
					"insert into submissions_history",
					DB::bracketed_fields($history_fields),
					"values", DB::tuples($cur_vals)
				]);
			}
		}
	}

	public function __construct($info) {
		$this->info = $info;
		static::roundScoreInArray($this->info, 'score');
		static::roundScoreInArray($this->info, 'hidden_score');
	}

	public function hasFullyJudged() {
		return $this->info['status'] === 'Judged';
	}

	public function viewerCanSeeScore(array $user = null) {
		// assert($this->userCanView($user));
		if ($this->info['hide_score_to_others']) {
			return $this->userIsSubmitter($user);
		} else {
			return true;
		}
	}

	public function viewerCanSeeComponents(array $user = null) {
		// assert($this->userCanView($user));
		$pec = $this->problem->getExtraConfig();

		$perm = ['manager_view' => $this->userCanManageProblemOrContest($user)];
		if ($perm['manager_view']) {
			$user = UOJUser::query($this->info['submitter']);
		}

		$perm['content'] = $this->userPermissionCodeCheck($user, $pec['view_content_type']);
		$perm['score'] = $this->viewerCanSeeScore($user);
		$perm['high_level_details'] = $perm['score'] && $this->userPermissionCodeCheck($user, $pec['view_all_details_type']);
		$perm['low_level_details'] = $perm['high_level_details'] && $this->userPermissionCodeCheck($user, $pec['view_details_type']);
		foreach ($this->problem->additionalSubmissionComponentsCannotBeSeenByUser($user, $this) as $com) {
			$perm[$com] = false;
		}

		return $perm;
	}

	public function viewerCanSeeStatusDetailsHTML(array $user = null) {
		return $this->isLatest() && $this->userIsSubmitter($user) && !$this->hasJudged();
	}

	public function userCanSeeMinorVersions(array $user = null) {
		if ($this->problem->info['type'] == 'remote') {
			return false;
		}

		if (isSuperUser($user)) {
			return true;
		}
		return $this->userCanManageProblemOrContest($user);
	}

	public function userCanRejudge(array $user = null) {
		if ($this->getContent('no_rejudge')) {
			return false;
		}

		if (isSuperUser($user)) {
			return true;
		}

		return $this->userCanManageProblemOrContest($user) && $this->hasFullyJudged();
	}

	public function preHackCheck(array $user = null) {
		if ($this->getContent('remote_judge')) {
			return false;
		}

		return $this->info['score'] == 100 && $this->problem->preHackCheck($user);
	}

	public function isLatest() {
		return !$this->latest_version || ($this->info['type'] == 'major' && $this->info['judge_time'] === $this->latest_version['judge_time']);
	}

	public function isMajor() {
		return !$this->latest_version || $this->info['type'] == 'major';
	}

	public function getTID() {
		return !$this->latest_version ? 0 : $this->info['tid'];
	}

	public function getUri() {
		$uri = "/submission/{$this->info['id']}";
		if ($this->isLatest()) {
			return $uri;
		} elseif ($this->info['type'] == 'major') {
			return $uri . '?time=' . (new DateTime($this->info['judge_time']))->format('Y.m.d-H.i.s');
		} else {
			return $uri . "?tid={$this->info['tid']}";
		}
	}

	public function getUriForLatest() {
		return "/submission/{$this->info['id']}";
	}

	public function getUriForNewTID($tid) {
		return "/submission/{$this->info['id']}?tid={$tid}";
	}

	public function echoStatusBarTD($name, array $cfg) {
		$cfg += [
			'result_badge' => false,
		];

		switch ($name) {
			case 'result':
				if (empty($cfg['no_link'])) {
					$tag_st = '<a href="' . $this->getUri() . '"';
					$tag_ed = '</a>';
				} else {
					$tag_st = '<span';
					$tag_ed = '</span>';
				}
				if ($this->hasJudged()) {
					if ($this->info['hide_score_to_others']) {
						$cfg += [
							'show_actual_score' => true,
							'visible_score' => $this->info['score']
						];
					}
					if (!$cfg['show_actual_score']) {
						echo '<strong class="text-muted">?</strong>';
					} else {
						$actual_score = $this->getActualScore();
						if ($actual_score === null) {
							if ($cfg['result_badge']) {
								echo $tag_st, ' class="fs-5 uoj-submission-result-td"><span class="badge text-white bg-warning uoj-result-badge">', $this->info['result_error'], '</span>', $tag_ed;
							} else {
								echo $tag_st, ' class="small text-decoration-none uoj-submission-result-td">', $this->info['result_error'], $tag_ed;
							}
						} else {
							echo $tag_st, ' class="vstack d-inline-flex">';
							if ($cfg['result_badge']) {
								echo '<span class="fs-5 uoj-submission-result-td">';
								if ($actual_score == 100) {
									// rgb(0, 204, 0)
									echo '<span class="badge text-white uoj-result-badge uoj-accepted-badge">', 'Accepted', '</span>';
								} else {
									// rgb(204, 0, 0)
									echo '<span class="badge text-white uoj-result-badge uoj-unaccepted-badge">', 'Unaccepted', '</span>';
								}
								echo '</span>';
							}
							echo '<span class="uoj-score">', $actual_score, '</span>';
							echo $tag_ed;
						}
					}
				} else {
					if ($cfg['result_badge']) {
						echo $tag_st, ' class="fs-5 uoj-submission-result-td"><span class="badge text-white bg-primary uoj-result-badge">', $this->publicStatus(), '</span>', $tag_ed;
					} else {
						echo $tag_st, ' class="small text-decoration-none uoj-submission-result-td">', $this->publicStatus(), $tag_ed;
					}
				}
				break;
			case 'language':
				echo '<a class="text-decoration-none" href="', $this->getUri(), '">', UOJLang::getLanguageDisplayName($this->info['language']), '</a>';
				break;
			default:
				$this->echoStatusBarTDBase($name, $cfg);
				break;
		}
	}

	public function echoStatusTableRow(array $cfg, array $viewer = null) {
		if (!isset($cfg['show_actual_score'])) {
			$cfg['show_actual_score'] = $this->viewerCanSeeScore($viewer);
		}

		$show_status_details = $this->viewerCanSeeStatusDetailsHTML($viewer);
		if (!$show_status_details) {
			echo '<tr>';
		} else {
			echo '<tr class="table-warning">';
		}
		$cols = ['id', 'problem', 'submitter', 'result', 'used_time', 'used_memory', 'language', 'tot_size', 'submit_time', 'judge_time'];
		foreach ($cols as $name) {
			if (!isset($cfg["{$name}_hidden"])) {
				echo '<td>';
				$this->echoStatusBarTD($name, $cfg);
				echo '</td>';
			}
		}
		echo '</tr>';
		if ($show_status_details) {
			echo '<tr id="', "status_details_{$this->info['id']}", '" class="">';
			echo $this->getStatusDetailsHTML();
			echo '</tr>';
			echo '<script type="text/javascript">update_judgement_status_details(' . $this->info['id'] . ')</script>';
		}
	}

	function echoStatusTable(array $cfg, array $viewer = null) {
		echo '<div class="table-responsive card mb-3">';
		echo '<table class="table text-center mb-0 uoj-table">';
		echo '<thead>';
		echo '<tr>';
		if (!isset($cfg['id_hidden'])) {
			echo '<th>ID</th>';
		}
		if (!isset($cfg['problem_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::problem') . '</th>';
		}
		if (!isset($cfg['submitter_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::submitter') . '</th>';
		}
		if (!isset($cfg['result_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::result') . '</th>';
		}
		if (!isset($cfg['used_time_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::used time') . '</th>';
		}
		if (!isset($cfg['used_memory_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::used memory') . '</th>';
		}
		echo '<th>' . UOJLocale::get('problems::language') . '</th>';
		echo '<th>' . UOJLocale::get('problems::file size') . '</th>';
		if (!isset($cfg['submit_time_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::submit time') . '</th>';
		}
		if (!isset($cfg['judge_time_hidden'])) {
			echo '<th>' . UOJLocale::get('problems::judge time') . '</th>';
		}
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		$this->echoStatusTableRow($cfg, $viewer);
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	public function echoStatusCard(array $cfg, array $viewer = null) {
		if (!isset($cfg['show_actual_score'])) {
			$cfg['show_actual_score'] = $this->viewerCanSeeScore($viewer);
		}

		$rows = [
			'submitter' => UOJLocale::get('problems::submitter'),
			'id' => 'ID',
			'problem' => UOJLocale::get('problems::problem'),
			'contest' => UOJLocale::get('contests::contest'),
			'result' => UOJLocale::get('problems::result'),
			'used_time' => UOJLocale::get('problems::used time'),
			'used_memory' => UOJLocale::get('problems::used memory'),
			'language' => UOJLocale::get('problems::language'),
			'tot_size' => UOJLocale::get('problems::file size'),
			'submit_time' => UOJLocale::get('problems::submit time'),
			'judge_time' => UOJLocale::get('problems::judge time'),
		];

		if (!$this->problem || !($this->problem instanceof UOJContestProblem)) {
			$cfg['contest_hidden'] = true;
		}

		echo '<div class="card mb-3">';
		echo     '<div class="card-body vstack gap-2">';

		foreach ($rows as $id => $name) {
			if (!isset($cfg["{$id}_hidden"])) {
				switch ($id) {
					case 'submitter':
						$submitter = UOJUser::query($this->info['submitter']);
						$user_profile_url = HTML::url('/user/' . $this->info['submitter']);

						echo '<div class="pb-2">';
						echo HTML::tag('a', [
							'href' => $user_profile_url,
							'class' => 'd-inline-block me-2',
						], HTML::empty_tag('img', [
							'src' => HTML::avatar_addr($submitter, 64),
							'class' => 'uoj-user-avatar rounded',
							'style' => 'width: 2rem; height: 2rem;',
						]));
						echo UOJUser::getLink($submitter);
						echo '</div>';

						break;
					default:
						echo '<div class="d-flex justify-content-between align-items-center">';
						echo     '<span class="flex-shrink-0 me-2">', $name, '</span>';
						echo     '<span class="text-end text-truncate d-inline-block">', $this->echoStatusBarTD($id, $cfg + ['time_font_size' => 'normal']), '</span>';
						echo '</div>';

						break;
				}
			}
		}

		echo     '</div>';
		echo '</div>';
	}

	public function delete() {
		unlink(UOJContext::storagePath() . $this->getContent('file_name'));
		DB::delete([
			"delete from submissions",
			"where", ["id" => $this->info['id']]
		]);
		DB::delete([
			"delete from submissions_history",
			"where", ["submission_id" => $this->info['id']]
		]);
		updateBestACSubmissions($this->info['submitter'], $this->info['problem_id']);
	}

	public function deleteThisMinorVersion() {
		if ($this->isMajor()) {
			return false;
		}
		return DB::delete([
			"delete from submissions_history",
			"where", [
				'id' => $this->getTID()
			]
		]);
	}

	public function loadHistory(array $his) {
		if (!$this->latest_version) {
			$this->latest_version = $this->info;
		}
		foreach ($his as $key => $val) {
			if ($key == 'message') {
				$this->info['judge_reason'] = $val;
			} elseif ($key == 'time') {
				$this->info['judge_time'] = $val;
			} elseif ($key == 'actual_score') {
				$this->info['score'] = $val;
				$this->info['hidden_score'] = null;
				$this->info['hide_score_to_others'] = false;
			} else {
				$this->info[$key] = $val;
			}
		}
		return $this;
	}

	public function loadHistoryByTime($time, $format = 'Y.m.d-H.i.s') {
		$time = DateTime::createFromFormat($format, $time);
		if ($time === false) {
			return false;
		}
		$time = $time->format(UOJTime::FORMAT);
		if ($this->info['judge_time'] == $time) {
			return $this;
		}

		$his = DB::selectFirst([
			"select", DB::fields(UOJSubmissionHistory::$fields),
			"from submissions_history",
			"where", [
				'submission_id' => $this->info['id'],
				'judge_time' => $time
			],
		]);
		if (!$his) {
			return false;
		}
		static::roundScoreInArray($his, 'actual_score');
		return $this->loadHistory($his);
	}

	public function loadHistoryByTID($tid) {
		if (!isset($tid) || !validateUInt($tid)) {
			return null;
		}
		$his = DB::selectFirst([
			"select", DB::fields(UOJSubmissionHistory::$fields),
			"from submissions_history",
			"where", [
				'submission_id' => $this->info['id'],
				'id' => $tid
			],
		]);
		if ($his['type'] !== 'minor') {
			return false;
		}
		if (!$his) {
			return false;
		}
		static::roundScoreInArray($his, 'actual_score');
		return $this->loadHistory($his);
	}
}
