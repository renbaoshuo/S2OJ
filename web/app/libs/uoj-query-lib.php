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
function hasViewPermission($str, $user, $problem, $submission) {
	if ($str == 'ALL') {
		return true;
	}
	if ($str == 'ALL_AFTER_AC') {
		return hasAC($user,$problem);
	}
	if ($str == 'SELF') {
		return $submission['submitter'] == $user['username'];
	}
	return false;
}

function hasViewSolutionPermission($str, $user, $problem) {
	if (isSuperUser($user) || isProblemManager($user)) {
		return true;
	}
	if ($str == 'ALL') {
		return true;
	}
	if ($str == 'ALL_AFTER_AC') {
		return hasAC($user, $problem);
	}
	return false;
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
function hasAC($user, $problem) {
	return DB::selectFirst("select * from best_ac_submissions where submitter = '${user['username']}' and problem_id = ${problem['id']}") != null;
}
function hasParticipated($user, $contest) {
	$result = DB::selectFirst("select * from contests_registrants where username = '${user['username']}' and contest_id = ${contest['id']}");

	return $result != null && $result['has_participated'];
}

function queryUser($username) {
	if (!validateUsername($username)) {
		return null;
	}
	return DB::selectFirst("select * from user_info where username='$username'", MYSQLI_ASSOC);
}
function queryProblemContent($id) {
	return DB::selectFirst("select * from problems_contents where id = $id", MYSQLI_ASSOC);
}
function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQLI_ASSOC);
}

function queryProblemTags($id) {
	$tags = array();
	$result = DB::query("select tag from problems_tags where problem_id = $id order by id");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$tags[] = $row[0];
	}
	return $tags;
}

function queryProblemList($id) {
	return DB::selectFirst("select * from lists where id = $id", MYSQLI_ASSOC);
}
function queryProblemListTags($id) {
	$tags = array();
	$result = DB::query("select tag from lists_tags where list_id = $id order by id");
	if (!$result) {
		return $tags;
	}
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$tags[] = $row[0];
	}
	return $tags;
}
function queryProblemInList($list_id, $problem_id) {
	return DB::selectFirst("select * from lists_problems where list_id='$blog_id' and problem_id='$problem_id'", MYSQLI_ASSOC);
}

function querySolution($problem_id, $blog_id) {
	return DB::selectFirst("select * from problems_solutions where blog_id='$blog_id' and problem_id='$problem_id'", MYSQLI_ASSOC);
}

function queryContestProblemRank($contest, $problem) {
	if (!DB::selectFirst("select * from contests_problems where contest_id = {$contest['id']} and problem_id = {$problem['id']}")) {
		return null;
	}
	$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']} order by dfn, problem_id");
	return array_search(array('problem_id' => $problem['id']), $contest_problems) + 1;
}
function querySubmission($id) {
	return DB::selectFirst("select * from submissions where id = $id", MYSQLI_ASSOC);
}
function queryHack($id) {
	return DB::selectFirst("select * from hacks where id = $id", MYSQLI_ASSOC);
}
function queryContest($id) {
	return DB::selectFirst("select * from contests where id = $id", MYSQLI_ASSOC);
}
function queryContestProblem($id) {
	return DB::selectFirst("select * from contest_problems where contest_id = $id", MYSQLI_ASSOC);
}
function queryContestProblems($id) {
	return DB::selectAll("select * from contests_problems where contest_id = $id order by dfn, problem_id", MYSQLI_ASSOC);
}

function queryGroup($id) {
	return DB::selectFirst("select * from groups where id = $id", MYSQLI_ASSOC);
}
function queryUserInGroup($group_id, $username) {
	return DB::selectFirst("select * from groups_users where username='$username' and group_id='$group_id'", MYSQLI_ASSOC);
}
function queryGroupsOfUser($username) {
	return DB::selectAll("select b.title as title, b.id as id from groups_users a inner join groups b on a.group_id = b.id where a.username = '$username' and b.is_hidden = 0 order by id", MYSQLI_ASSOC);
}
function queryGroupmateCurrentAC($username) {
	return DB::selectAll("select a.problem_id as problem_id, a.submitter as submitter, a.submission_id as submission_id, b.submit_time as submit_time, c.group_id as group_id, c.group_name as group_name, d.title as problem_title, b.submit_time as submit_time, e.realname as realname from best_ac_submissions a inner join submissions b on (a.submission_id = b.id) inner join (select a.username as username, any_value(a.group_id) as group_id, any_value(c.title) as group_name from groups_users a inner join (select a.group_id as group_id from groups_users a inner join groups b on a.group_id = b.id where a.username = '$username' and b.is_hidden = 0) b on a.group_id = b.group_id inner join groups c on a.group_id = c.id group by a.username) c on a.submitter = c.username inner join problems d on (a.problem_id = d.id and d.is_hidden = 0) inner join user_info e on a.submitter = e.username where b.submit_time > addtime(now(), '-360:00:00') order by b.submit_time desc limit 10", MYSQLI_ASSOC);
}
function queryGroupCurrentAC($group_id) {
	return DB::selectAll("select a.problem_id as problem_id, a.submitter as submitter, a.submission_id as submission_id, b.submit_time as submit_time, d.title as problem_title, b.submit_time as submit_time, e.realname as realname from best_ac_submissions a inner join submissions b on (a.submission_id = b.id) inner join groups_users c on (a.submitter = c.username and c.group_id = $group_id) inner join problems d on (a.problem_id = d.id and d.is_hidden = 0) inner join user_info e on (a.submitter = e.username) where b.submit_time > addtime(now(), '-360:00:00') order by b.submit_time desc limit 10", MYSQLI_ASSOC);
}
function queryGroupAssignments($group_id) {
	return DB::selectAll("select a.id as id, a.list_id as list_id, a.create_time as create_time, a.deadline as deadline, b.title from assignments a left join lists b on a.list_id = b.id where a.group_id = $group_id order by a.deadline asc", MYSQLI_ASSOC);
}
function queryGroupActiveAssignments($group_id) {
	return DB::selectAll("select a.id as id, a.group_id as group_id, a.list_id as list_id, a.create_time as create_time, a.deadline as deadline, b.title from assignments a left join lists b on a.list_id = b.id where a.group_id = $group_id and a.deadline > addtime(now(), '-168:00:00') order by a.deadline asc", MYSQLI_ASSOC);
}

function queryAssignment($id) {
	return DB::selectFirst("select * from assignments where id = $id", MYSQLI_ASSOC);
}
function queryAssignmentByGroupListID($group_id, $list_id) {
	return DB::selectFirst("select * from assignments where list_id='$list_id' and group_id='$group_id'", MYSQLI_ASSOC);
}

function queryZanVal($id, $type, $user) {
	if ($user == null) {
		return 0;
	}
	$esc_type = DB::escape($type);
	$row = DB::selectFirst("select val from click_zans where username='{$user['username']}' and type='$esc_type' and target_id='$id'");
	if ($row == null) {
		return 0;
	}
	return $row['val'];
}

function queryBlog($id) {
	return DB::selectFirst("select * from blogs where id='$id'", MYSQLI_ASSOC);
}
function queryBlogTags($id) {
	$tags = array();
	$result = DB::select("select tag from blogs_tags where blog_id = $id order by id");
	while ($row = DB::fetch($result, MYSQLI_NUM)) {
		$tags[] = $row[0];
	}
	return $tags;
}
function queryBlogComment($id) {
	return DB::selectFirst("select * from blogs_comments where id='$id'", MYSQLI_ASSOC);
}

function isProblemVisibleToUser($problem, $user) {
	return !$problem['is_hidden'] || hasProblemPermission($user, $problem);
}
function isContestProblemVisibleToUser($problem, $contest, $user) {
	if (isProblemVisibleToUser($problem, $user)) {
		return true;
	}
	if ($contest['cur_progress'] >= CONTEST_PENDING_FINAL_TEST) {
		return true;
	}
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		return false;
	}
	return hasRegistered($user, $contest);
}

function isSubmissionVisibleToUser($submission, $problem, $user) {
	if (isProblemManager($user)) {
		return true;
	} elseif (!$submission['is_hidden']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}
function isHackVisibleToUser($hack, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif (!$hack['is_hidden']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function isSubmissionFullVisibleToUser($submission, $contest, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif ($submission['submitter'] == $user['username']) {
		return true;
	} elseif (isRegisteredRunningContestProblem($user, $problem)) {
		return false;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}
function isHackFullVisibleToUser($hack, $contest, $problem, $user) {
	if (isSuperUser($user)) {
		return true;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} elseif ($hack['hacker'] == $user['username']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function isRegisteredRunningContestProblem($user, $problem) {
	$result = DB::query("select contest_id from contests_problems where problem_id = {$problem['id']}");
	while (list($contest_id) = DB::fetch($result, MYSQLI_NUM)) {
		$contest = queryContest($contest_id);
		genMoreContestInfo($contest);
		if ($contest['cur_progress'] == CONTEST_IN_PROGRESS
			&& hasRegistered($user, $contest)
			&& !hasContestPermission($user, $contest)
			&& queryContestProblemRank($contest, $problem)) {
			return true;
		}
	}

	return false;
}

function deleteBlog($id) {
	if (!validateUInt($id)) {
		return;
	}
	DB::delete("delete from click_zans where type = 'B' and target_id = $id");
	DB::delete("delete from click_zans where type = 'BC' and target_id in (select id from blogs_comments where blog_id = $id)");
	DB::delete("delete from blogs where id = $id");
	DB::delete("delete from blogs_comments where blog_id = $id");
	DB::delete("delete from important_blogs where blog_id = $id");
	DB::delete("delete from blogs_tags where blog_id = $id");
}
