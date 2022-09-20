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
	DB::update("update contests set player_num = (select count(*) from contests_registrants where contest_id = {$contest['id']}) where id = {$contest['id']}");
}

// problems: pos => id
// data    : id, submit_time, submitter, problem_pos, score
// people  : username
function queryContestData($contest, $config = array(), $is_after_contest_query = false) {
	mergeConfig($config, [
		'pre_final' => false
	]);
	
	$problems = [];
	$prob_pos = [];
	$n_problems = 0;
	$result = DB::query("select problem_id from contests_problems where contest_id = {$contest['id']} order by dfn, problem_id");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$prob_pos[$problems[] = (int)$row[0]] = $n_problems++;
	}

	$data = [];
	if ($config['pre_final']) {
		$result = DB::query("select id, submit_time, submitter, problem_id, result from submissions"
				." where contest_id = {$contest['id']} and score is not null order by id");
		while ($row = DB::fetch($result, MYSQLI_NUM)) {
			$r = json_decode($row[4], true);
			if (!isset($r['final_result'])) {
				continue;
			}
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = (int)($r['final_result']['score']);
			$data[] = $row;
		}
	} else {
		if ($contest['cur_progress'] < CONTEST_FINISHED) {
			$result = DB::query("select id, submit_time, submitter, problem_id, score from submissions"
				." where contest_id = {$contest['id']} and score is not null order by id");
		} elseif ($is_after_contest_query == true) {
			$result = DB::query("select id, submit_time, submitter, problem_id, score from submissions order by score");
		} else {
			$result = DB::query("select submission_id, date_add('{$contest['start_time_str']}', interval penalty second),"
				." submitter, problem_id, score from contests_submissions where contest_id = {$contest['id']}");
		}
		while ($row = DB::fetch($result, MYSQLI_NUM)) {
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = (int)$row[4];
			$data[] = $row;
		}
	}

	$people = [];
	$result = DB::query("select a.username, b.realname from contests_registrants a inner join user_info b on a.username = b.username where a.contest_id = {$contest['id']} and a.has_participated = 1");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$people[] = $row;
	}

	return ['problems' => $problems, 'data' => $data, 'people' => $people];
}

function calcStandings($contest, $contest_data, &$score, &$standings, $update_contests_submissions = false, $show_reviews = false) {
	// score: username, problem_pos => score, penalty, id, ?review
	$score = array();
	$n_people = count($contest_data['people']);
	$n_problems = count($contest_data['problems']);
	foreach ($contest_data['people'] as $person) {
		$score[$person[0]] = array();
	}
	foreach ($contest_data['data'] as $submission) {
		$penalty = (new DateTime($submission[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
		if ($contest['extra_config']['standings_version'] >= 2) {
			if ($submission[4] == 0) {
				$penalty = 0;
			}
		}

		$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0]);
	}

	if ($show_reviews) {
		foreach ($contest_data['people'] as $person) {
			foreach ($contest_data['problems'] as $key => $problem) {
				$review_result = DB::selectFirst("select content from contests_reviews where contest_id = {$contest['id']} and problem_id = {$problem} and poster = '{$person[0]}'");

				if (!isset($score[$person[0]][$key])) {
					$score[$person[0]][$key] = array(0, 0, 0);
				}

				if ($review_result['content']) {
					$score[$person[0]][$key][] = $review_result['content'];
				}
			}
		}
	}

	// standings: rank => score, penalty, [username, realname], virtual_rank, ?review
	$standings = array();
	foreach ($contest_data['people'] as $person) {
		$cur = array(0, 0, $person);
		for ($i = 0; $i < $n_problems; $i++) {
			if (isset($score[$person[0]][$i])) {
				$cur_row = $score[$person[0]][$i];
				$cur[0] += $cur_row[0];
				$cur[1] += $cur_row[1];
				if ($update_contests_submissions) {
					DB::insert("insert into contests_submissions (contest_id, submitter, problem_id, submission_id, score, penalty) values ({$contest['id']}, '{$person[0]}', {$contest_data['problems'][$i]}, {$cur_row[2]}, {$cur_row[0]}, {$cur_row[1]})");
				}
			}
		}

		if ($show_reviews) {
			$review_result = DB::selectFirst("select content from contests_reviews where contest_id = {$contest['id']} and problem_id = -1 and poster = '{$person[0]}'");

			if ($review_result['content']) {
				$cur[] = $review_result['content'];
			}
		}

		$standings[] = $cur;
	}

	usort($standings, function($lhs, $rhs) {
		if ($lhs[0] != $rhs[0]) {
			return $rhs[0] - $lhs[0];
		} elseif ($lhs[1] != $rhs[1]) {
			return $lhs[1] - $rhs[1];
		} else {
			return strcmp($lhs[2][0], $rhs[2][0]);
		}
	});

	$is_same_rank = function($lhs, $rhs) {
		return $lhs[0] == $rhs[0] && $lhs[1] == $rhs[1];
	};

	for ($i = 0; $i < $n_people; $i++) {
		if ($i == 0 || !$is_same_rank($standings[$i - 1], $standings[$i])) {
			if ($show_reviews && count($standings[$i]) == 4) {
				$standings[$i][] = $standings[$i][3];
				$standings[$i][3] = $i + 1;
			} else {
				$standings[$i][] = $i + 1;
			}
		} else {
			if ($show_reviews && count($standings[$i]) == 4) {
				$standings[$i][] = $standings[$i][3];
				$standings[$i][3] = $standings[$i - 1][3];
			} else {
				$standings[$i][] = $standings[$i - 1][3];
			}
		}
	}
}
