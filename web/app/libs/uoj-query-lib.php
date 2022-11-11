<?php

function hasProblemPermission($user, $problem) {
	if ($user == null) {
		return false;
	}
	if (isSuperUser($user) || isProblemManager($user)) {
		return true;
	}
	if ($problem['uploader'] == $user['username']) {
		return true;
	}
	return DB::selectFirst("select * from problems_permissions where username = '{$user['username']}' and problem_id = {$problem['id']}") != null;
}

function hasContestPermission($user, $contest) {
	if ($user == null) {
		return false;
	}
	if (isSuperUser($user)) {
		return true;
	}
	return DB::selectFirst("select * from contests_permissions where username = '{$user['username']}' and contest_id = {$contest['id']}") != null;
}

function hasRegistered($user, $contest) {
	return DB::selectFirst("select * from contests_registrants where username = '${user['username']}' and contest_id = ${contest['id']}") != null;
}

function queryUser($username) {
	if (!validateUsername($username)) {
		return null;
	}
	return DB::selectFirst("select * from user_info where username='$username'", MYSQLI_ASSOC);
}
function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQLI_ASSOC);
}

function querySolution($problem_id, $blog_id) {
	return DB::selectFirst("select * from problems_solutions where blog_id='$blog_id' and problem_id='$problem_id'", MYSQLI_ASSOC);
}

function queryContestProblemRank($contest, $problem) {
	if (!DB::selectFirst("select * from contests_problems where contest_id = {$contest['id']} and problem_id = {$problem['id']}")) {
		return null;
	}
	$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']} order by level, problem_id");
	return array_search(array('problem_id' => $problem['id']), $contest_problems) + 1;
}
function queryContest($id) {
	return DB::selectFirst("select * from contests where id = $id", MYSQLI_ASSOC);
}

function queryBlog($id) {
	return DB::selectFirst("select * from blogs where id='$id'", MYSQLI_ASSOC);
}
function queryBlogComment($id) {
	return DB::selectFirst("select * from blogs_comments where id='$id'", MYSQLI_ASSOC);
}

function isProblemVisibleToUser($problem, $user) {
	return !$problem['is_hidden'] || hasProblemPermission($user, $problem);
}
function isListVisibleToUser($list, $user) {
	return !$list['is_hidden'] || isSuperUser($user);
}

function isRegisteredRunningContestProblem($user, $problem) {
	$result = DB::query("select contest_id from contests_problems where problem_id = {$problem['id']}");
	while (list($contest_id) = DB::fetch($result, MYSQLI_NUM)) {
		$contest = queryContest($contest_id);
		genMoreContestInfo($contest);
		if (
			$contest['cur_progress'] == CONTEST_IN_PROGRESS
			&& hasRegistered($user, $contest)
			&& !hasContestPermission($user, $contest)
			&& queryContestProblemRank($contest, $problem)
		) {
			return true;
		}
	}

	return false;
}
