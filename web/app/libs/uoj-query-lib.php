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

function queryGroup($id) {
	return DB::selectFirst("select * from `groups` where id = $id", MYSQLI_ASSOC);
}
function queryGroupUsers($id) {
	return DB::selectAll("SELECT * FROM groups_users WHERE group_id = $id");
}
function queryUserInGroup($group_id, $username) {
	return DB::selectFirst("select * from groups_users where username='$username' and group_id='$group_id'", MYSQLI_ASSOC);
}
function queryGroupsOfUser($username) {
	return DB::selectAll([
		"select", DB::fields([
			"title" => "groups.title",
			"id" => "groups.id",
		]),
		"from groups_users",
		"inner join `groups`", "on", [
			"groups_users.group_id" => DB::raw("groups.id"),
		],
		"where", [
			"groups_users.username" => $username,
			"groups.is_hidden" => false,
		],
	]);
}
function queryGroupmateCurrentAC($username) {
	return DB::selectAll("select a.problem_id as problem_id, a.submitter as submitter, a.submission_id as submission_id, b.submit_time as submit_time, c.group_id as group_id, c.group_name as group_name, d.title as problem_title, b.submit_time as submit_time, e.realname as realname from best_ac_submissions a inner join submissions b on (a.submission_id = b.id) inner join (select a.username as username, any_value(a.group_id) as group_id, any_value(c.title) as group_name from groups_users a inner join (select a.group_id as group_id from groups_users a inner join `groups` b on a.group_id = b.id where a.username = '$username' and b.is_hidden = 0) b on a.group_id = b.group_id inner join `groups` c on a.group_id = c.id group by a.username) c on a.submitter = c.username inner join problems d on (a.problem_id = d.id and d.is_hidden = 0) inner join user_info e on a.submitter = e.username where b.submit_time > addtime(now(), '-360:00:00') order by b.submit_time desc limit 10", MYSQLI_ASSOC);
}
function queryGroupCurrentAC($group_id) {
	return DB::selectAll("select a.problem_id as problem_id, a.submitter as submitter, a.submission_id as submission_id, b.submit_time as submit_time, d.title as problem_title, b.submit_time as submit_time, e.realname as realname from best_ac_submissions a inner join submissions b on (a.submission_id = b.id) inner join groups_users c on (a.submitter = c.username and c.group_id = $group_id) inner join problems d on (a.problem_id = d.id and d.is_hidden = 0) inner join user_info e on (a.submitter = e.username) where b.submit_time > addtime(now(), '-360:00:00') order by b.submit_time desc limit 10", MYSQLI_ASSOC);
}
function queryGroupAssignments($group_id) {
	return DB::selectAll("select a.list_id as list_id, a.end_time as end_time, b.title from groups_assignments a left join lists b on a.list_id = b.id where a.group_id = $group_id order by a.end_time asc", MYSQLI_ASSOC);
}
function queryGroupActiveAssignments($group_id) {
	return DB::selectAll("select a.group_id as group_id, a.list_id as list_id, a.end_time as end_time, b.title from groups_assignments a left join lists b on a.list_id = b.id where a.group_id = $group_id and a.end_time >= addtime(now(), '-168:00:00') order by a.end_time asc", MYSQLI_ASSOC);
}
function queryAssignmentByGroupListID($group_id, $list_id) {
	return DB::selectFirst("select * from groups_assignments where list_id='$list_id' and group_id='$group_id'", MYSQLI_ASSOC);
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
