<?php
define("CONTEST_NOT_STARTED", 0);
define("CONTEST_IN_PROGRESS", 1);
define("CONTEST_PENDING_FINAL_TEST", 2);
define("CONTEST_TESTING", 10);
define("CONTEST_FINISHED", 20);

function genMoreContestInfo(&$contest) {
	$contest['start_time_str'] = $contest['start_time'];
	$contest['start_time'] = new DateTime($contest['start_time']);
	$contest['end_time'] = clone $contest['start_time'];
	$contest['end_time']->add(new DateInterval("PT${contest['last_min']}M"));
	$contest['end_time_str'] = $contest['end_time']->format('Y-m-d H:i:s');

	if ($contest['status'] == 'unfinished') {
		if (UOJTime::$time_now < $contest['start_time']) {
			$contest['cur_progress'] = CONTEST_NOT_STARTED;
		} elseif (UOJTime::$time_now < $contest['end_time']) {
			$contest['cur_progress'] = CONTEST_IN_PROGRESS;
		} else {
			$contest['cur_progress'] = CONTEST_PENDING_FINAL_TEST;
		}
	} elseif ($contest['status'] == 'testing') {
		$contest['cur_progress'] = CONTEST_TESTING;
	} elseif ($contest['status'] == 'finished') {
		$contest['cur_progress'] = CONTEST_FINISHED;
	}
	$contest['extra_config'] = json_decode($contest['extra_config'], true);

	if (!isset($contest['extra_config']['standings_version'])) {
		$contest['extra_config']['standings_version'] = 2;
	}
}

function updateContestPlayerNum($contest) {
	DB::update([
		"update contests",
		"set", [
			"player_num" => DB::rawbracket([
				"select count(*) from contests_registrants",
				"where", ["contest_id" => $contest['id']]
			])
		], "where", ["id" => $contest['id']]
	]);
}

// return value: ['problems' => $problems, 'data' => $data, 'people' => $people]
// problems: pos => id
//
// for individual competition:
//	 people  : username, realname, null, username_color
// for team competition:
//	 people  : username, null, ['team_name' => team_name, 'members' => members], null
//
// for OI/IOI contest:
//	 data	: id, submit_time, submitter, problem_pos, score
// for ACM contest:
//	 data	: id, submit_time (plus penalty), submitter, problem_pos, score, cnt, n_failures
//	 if the contest is not finished, then cnt = null, n_failures = null;
//	 otherwise, cnt is the total number of submission of this subitter for this problem
//				(by the time of getting 100, including the first submission with score 100)
//				n_failures is the number of failure attempts of this submitter for this problem
function queryContestData($contest, $config = []) {
	mergeConfig($config, [
		'pre_final' => false,
		'after_contest' => false,
	]);

	$problems = [];
	$prob_pos = [];
	$n_problems = 0;
	$res = DB::selectAll([
		"select problem_id from contests_problems",
		"where", ["contest_id" => $contest['id']],
		"order by level, problem_id"
	], DB::NUM);
	foreach ($res as $row) {
		$prob_pos[$problems[] = (int)$row[0]] = $n_problems++;
	}

	if ($contest['extra_config']['basic_rule'] == 'OI' || $contest['extra_config']['basic_rule'] == 'IOI') {
		$data = queryOIorIOIContestSubmissionData($contest, $problems, $prob_pos, $config);
	} elseif ($contest['extra_config']['basic_rule'] == 'ACM') {
		$data = queryACMContestSubmissionData($contest, $problems, $prob_pos, $config);
	}

	$people = [];

	if ($contest['extra_config']['individual_or_team'] == 'individual') {
		$res = DB::selectAll([
			"select contests_registrants.username, user_info.realname, user_info.extra, user_info.usergroup from contests_registrants",
			"inner join user_info on contests_registrants.username = user_info.username",
			"where", [
				"contest_id" => $contest['id'],
				"has_participated" => 1
			]
		], DB::NUM);
		foreach ($res as $row) {
			$extra = json_decode($row[2], true);
			$people[] = [
				$row[0],
				trim(HTML::escape($row[1])),
				null,
				UOJUser::getUserColor2($row[3], $extra['username_color']),
			];
		}
	} elseif ($contest['extra_config']['individual_or_team'] == 'team') {
		$res = DB::selectAll([
			"select user_info.username, null, user_info.extra from contests_registrants, user_info",
			"where", [
				"contest_id" => $contest['id'],
				"has_participated" => 1,
				"contests_registrants.username = user_info.username"
			]
		], DB::NUM);
		foreach ($res as $row) {
			$extra = json_decode($row[2], true);
			$people[] = [
				$row[0],
				null,
				[
					'team_name' => $extra['acm']['team_name'],
					'members' => $extra['acm']['members'],
				],
				null,
			];
		}
	}

	return ['problems' => $problems, 'data' => $data, 'people' => $people];
}
function queryOIorIOIContestSubmissionData($contest, $problems, $prob_pos, $config = []) {
	$data = [];

	$use_final_res = $config['pre_final'] && $contest['extra_config']['basic_rule'] == 'OI';

	if ($use_final_res) {
		$res = DB::selectAll([
			"select id, submit_time, submitter, problem_id, result from submissions",
			"where", [
				"contest_id" => $contest['id'],
				["score", "is not", null]
			], "order by id"
		], DB::NUM);
		foreach ($res as $row) {
			$r = json_decode($row[4], true);
			if (!isset($r['final_result'])) {
				continue;
			}
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = $row[4] = UOJSubmission::roundedScore($r['final_result']['score']);
			$data[] = $row;
		}
	} else {
		if ($contest['cur_progress'] < CONTEST_FINISHED) {
			$res = DB::selectAll([
				"select id, submit_time, submitter, problem_id, score from submissions",
				"where", [
					"contest_id" => $contest['id'],
					["score", "is not", null]
				], "order by id"
			], DB::NUM);
		} elseif ($config['after_contest']) {
			$res = DB::selectAll([
				"select id, submit_time, submitter, problem_id, score from submissions",
				"where", [
					["problem_id", "in", DB::rawtuple($problems)],
					["submitter", "in", DB::rawbracket([
						"select username from contests_registrants",
						"where", [
							"contest_id" => $contest['id'],
							"has_participated" => 1,
						],
					])],
				], "order by score",
			], DB::NUM);
		} else {
			$esc_start_time_str = DB::escape($contest['start_time_str']);
			$res = DB::selectAll([
				"select submission_id, date_add('{$esc_start_time_str}', interval penalty second), submitter, problem_id, score from contests_submissions",
				"where", ["contest_id" => $contest['id']],
			], DB::NUM);
		}
		foreach ($res as $row) {
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = UOJSubmission::roundedScore($row[4]);
			$data[] = $row;
		}
	}
	return $data;
}

function queryACMContestSubmissionData($contest, $problems, $prob_pos, $config = []) {
	$data = [];

	$username_or_empty = Auth::id();
	if (!isset($username_or_empty)) {
		$username_or_empty = '';
	}

	$actual_score = UOJSubmission::sqlForActualScore();
	$visible_score = 'if(' . DB::land(['hide_score_to_others' => 1, 'submitter' => $username_or_empty]) . ', hidden_score, score)';

	if ($config['pre_final']) {
		$res = DB::selectAll([
			"select id, submit_time, submitter, problem_id, $actual_score as actual_score, null, null from submissions",
			"where", [
				"contest_id" => $contest['id'],
				[$actual_score, "is not", null]
			], "order by id"
		], DB::NUM);
	} else {
		if ($contest['cur_progress'] < CONTEST_FINISHED) {
			$res = DB::selectAll([
				"select id, submit_time, submitter, problem_id, $visible_score as visible_score, null, null from submissions",
				"where", [
					"contest_id" => $contest['id'],
					DB::lor([
						[$visible_score, "is not", null],
						DB::land([
							"hide_score_to_others" => 1,
							["submitter", "!=", $username_or_empty]
						])
					])
				], "order by id"
			], DB::NUM);
		} elseif ($config['after_contest']) {
			$res = DB::selectAll([
				"select id, submit_time, submitter, problem_id, score, null, null from submissions",
				"where", [
					["problem_id", "in", DB::rawtuple($problems)],
					["submitter", "in", DB::rawbracket([
						"select username from contests_registrants",
						"where", [
							"contest_id" => $contest['id'],
							"has_participated" => 1,
						],
					])],
				], "order by score",
			], DB::NUM);
		} else {
			$esc_start_time_str = DB::escape($contest['start_time_str']);
			$res = DB::selectAll([
				"select submission_id, date_add('{$esc_start_time_str}', interval penalty second), submitter, problem_id, score, cnt, n_failures from contests_submissions",
				"where", ["contest_id" => $contest['id']],
			], DB::NUM);
		}
	}
	foreach ($res as $row) {
		$row[0] = (int)$row[0];
		$row[3] = $prob_pos[$row[3]];
		if (isset($row[4])) {
			$row[4] = UOJSubmission::roundedScore($row[4]);
		}
		if (isset($row[5])) {
			$row[5] = (int)$row[5];
		}
		if (isset($row[6])) {
			$row[6] = (int)$row[6];
		}
		$data[] = $row;
	}
	return $data;
}

// standings: rank => score, penalty, [username, realname], virtual_rank
function calcStandings($contest, $contest_data, &$score, &$standings, $cfg = []) {
	$cfg += [
		'update_contests_submissions' => false,
	];

	// score for OI: username, problem_pos => score, penalty, id
	// score for ACM: username, problem_pos => score, penalty, id, cnt, n_failures, n_frozen
	$score = [];
	$n_people = count($contest_data['people']);
	$n_problems = count($contest_data['problems']);
	foreach ($contest_data['people'] as $person) {
		$score[$person[0]] = [];
	}

	if ($contest['extra_config']['basic_rule'] === 'OI') {
		foreach ($contest_data['data'] as $sub) {
			$penalty = (new DateTime($sub[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
			if ($contest['extra_config']['standings_version'] >= 2) {
				if ($sub[4] == 0) {
					$penalty = 0;
				}
			}
			$score[$sub[2]][$sub[3]] = array($sub[4], $penalty, $sub[0]);
		}
	} else if ($contest['extra_config']['basic_rule'] === 'ACM') {
		// sub: id, submit_time, submitter, problem_pos, score
		//	  id, submit_time (plus penalty), submitter, problem_pos, score, cnt, n_failures
		foreach ($contest_data['data'] as $sub) {
			if (!isset($score[$sub[2]][$sub[3]])) {
				$score[$sub[2]][$sub[3]] = [];
			}
			$score[$sub[2]][$sub[3]][] = $sub;
		}

		foreach ($contest_data['people'] as $person) {
			$uname = $person[0];
			for ($pr = 0; $pr < $n_problems; $pr++) {
				if (isset($score[$uname][$pr])) {
					// username, problem_pos => score, penalty, id, cnt, n_failures, n_frozen
					$final_scr = null;
					$penalty = 0;
					$key_sub = null;
					$cnt = 0;
					$n_failures = 0;
					$n_frozen = 0;

					if (isset($score[$uname][$pr][0][5])) { // the stored contest data is used
						$sub = $score[$uname][$pr][0];
						$final_scr = $sub[4];
						$penalty = (new DateTime($sub[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
						$key_sub = $sub;
						$cnt = $sub[5];
						$n_failures = $sub[6];
						$n_frozen = 0;
					} else {
						for ($i = 0; $i < count($score[$uname][$pr]); $i++) {
							$sub = $score[$uname][$pr][$i];
							$cnt++;
							if (!isset($sub[4])) {
								$n_frozen++;
							} elseif (!isset($final_scr) || $final_scr < $sub[4]) {
								$final_scr = $sub[4];
								if ($final_scr == 100) {
									break;
								}
							}
						}

						if (!isset($final_scr)) {
							$key_sub = end($score[$uname][$pr]);
						} else if ($final_scr == 0) {
							for ($i = 0; $i < count($score[$uname][$pr]); $i++) {
								$sub = $score[$uname][$pr][$i];
								if (!isset($sub[4])) {
									break;
								} else {
									$n_failures++;
									$key_sub = $sub;
								}
							}

							list($final_scr, $penalty) = calcACMScoreAndPenaltyForOneProblem(
								$contest,
								$contest_data['problems'][$pr],
								$key_sub,
								$n_failures
							);
						} else {
							$scr_set = [];
							for ($i = 0; $i < count($score[$uname][$pr]); $i++) {
								$sub = $score[$uname][$pr][$i];
								if ($sub[4] > 0 && $sub[4] != 97 && !isset($scr_set[$sub[4]])) {
									$scr_set[$sub[4]] = true;
								} else {
									$n_failures++;
								}
								if ($sub[4] === $final_scr) {
									$key_sub = $sub;
									break;
								}
							}

							list($final_scr, $penalty) = calcACMScoreAndPenaltyForOneProblem(
								$contest,
								$contest_data['problems'][$pr],
								$key_sub,
								$n_failures
							);
						}
					}

					$score[$uname][$pr] = [
						$final_scr,
						$penalty,
						$key_sub[0],
						$cnt,
						$n_failures,
						$n_frozen
					];
				}
			}
		}
	} else if ($contest['extra_config']['basic_rule'] === 'IOI') {
		foreach ($contest_data['data'] as $sub) {
			$penalty = (new DateTime($sub[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
			if ($sub[4] == 0) {
				$penalty = 0;
			}
			if (!isset($score[$sub[2]][$sub[3]]) || $score[$sub[2]][$sub[3]][0] < $sub[4]) {
				$score[$sub[2]][$sub[3]] = array($sub[4], $penalty, $sub[0]);
			}
		}
	}

	// standings: rank => score, penalty, [username, realname, null|array, null|color], virtual_rank, ?review
	$standings = [];
	foreach ($contest_data['people'] as $person) {
		$cur = array(0, 0, $person);
		for ($i = 0; $i < $n_problems; $i++) {
			if (isset($score[$person[0]][$i])) {
				$cur_row = $score[$person[0]][$i];
				$cur[0] = UOJSubmission::roundedScore($cur[0] + $cur_row[0]);
				$cur[1] += $cur_row[1];
				if ($cfg['update_contests_submissions']) {
					DB::insert([
						"replace into contests_submissions",
						"(contest_id, submitter, problem_id, submission_id, score, penalty, cnt, n_failures)",
						"values", DB::tuple([
							$contest['id'], $person[0], $contest_data['problems'][$i], $cur_row[2],
							$cur_row[0], $cur_row[1],
							isset($cur_row[3]) ? $cur_row[3] : null,
							isset($cur_row[4]) ? $cur_row[4] : null
						])
					]);
				}
			}
		}
		$standings[] = $cur;
	}

	usort($standings, function ($lhs, $rhs) {
		if ($lhs[0] != $rhs[0]) {
			return $rhs[0] - $lhs[0];
		} else if ($lhs[1] != $rhs[1]) {
			return $lhs[1] - $rhs[1];
		} else {
			return strcmp($lhs[2][0], $rhs[2][0]);
		}
	});

	$is_same_rank = function ($lhs, $rhs) {
		return $lhs[0] == $rhs[0] && $lhs[1] == $rhs[1];
	};

	for ($i = 0; $i < $n_people; $i++) {
		if ($i == 0 || !$is_same_rank($standings[$i - 1], $standings[$i])) {
			$standings[$i][] = $i + 1;
		} else {
			$standings[$i][] = $standings[$i - 1][3];
		}
	}
}

function calcACMScoreAndPenaltyForOneProblem($contest, $problem_id, $sub, $n_failures) {
	if (isset($contest['extra_config']['bonus']["problem_{$problem_id}"])) {
		if ($sub[4] == 100) {
			return [0, -60 * 20];
		} else {
			return [0, 0];
		}
	} else {
		$penalty = (new DateTime($sub[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
		$penalty += $n_failures * 60 * 20;
		if ($sub[4] === 0) {
			$penalty = 0;
		}
		return [$sub[4], $penalty];
	}
}

function getContestBlogLink($contest, $title) {
	if (!isset($contest['extra_config']['links'])) {
		return null;
	}
	foreach ($contest['extra_config']['links'] as $link) {
		if ($link[0] === $title) {
			return '/blogs/' . $link[1];
		}
	}
	return null;
}
