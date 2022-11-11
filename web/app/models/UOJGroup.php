<?php

class UOJGroup {
	use UOJDataTrait;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from `groups`",
			"where", ["id" => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJGroup($info);
	}

	public static function queryGroupsOfUser(array $user = null) {
		if ($user == null) {
			return [];
		}

		return array_map(fn ($x) => UOJGroup::query($x['group_id']), DB::selectAll([
			DB::lc(), "select group_id from groups_users",
			"where", ['username' => $user['username']],
			"order by group_id"
		]));
	}

	public function __construct($info) {
		$this->info = $info;
	}

	public function userCanManage(array $user = null) {
		return isSuperUser($user);
	}

	public function userCanView(array $user = null, array $cfg = []) {
		$cfg += ['ensure' => false];
		if ($this->info['is_hidden'] && !$this->userCanManage($user)) {
			$cfg['ensure'] && UOJResponse::page404();
			return false;
		}
		return true;
	}

	public function getUri($where = '') {
		return "/group/{$this->info['id']}{$where}";
	}

	public function getLink($cfg = []) {
		$cfg += [
			'where' => '',
			'class' => '',
			'text' => $this->info['title'],
		];

		return HTML::tag('a', [
			'href' => $this->getUri($cfg['where']),
			'class' => $cfg['class'],
		], $cfg['text']);
	}

	public function getUsernames() {
		return array_map(fn ($x) => $x['username'], DB::selectAll([
			DB::lc(), "select username from groups_users",
			"where", ['group_id' => $this->info['id']],
			"order by username",
		]));
	}

	public function getLatestGroupmatesAcceptedSubmissionIds(array $user = null, int $limit = 10) {
		return array_map(fn ($x) => $x['id'], DB::selectAll([
			"select", DB::fields(["id" => "max(id)"]),
			"from submissions",
			"where", [
				"score" => 100,
				["submitter", "in", DB::rawtuple($this->getUsernames())],
				UOJSubmission::sqlForUserCanView($user),
			],
			"group by problem_id",
			"order by id desc",
		]));
	}

	public function getAssignmentIds($limit = -1) {
		return array_map(fn ($x) => $x['list_id'], DB::selectAll([
			DB::lc(), "select list_id from groups_assignments",
			"where", ['group_id' => $this->info['id']],
			"order by end_time desc, list_id asc",
			$limit == -1 ? "" : DB::limit($limit),
		]));
	}

	public function getActiveAssignmentIds($limit = -1) {
		return array_map(fn ($x) => $x['list_id'], DB::selectAll([
			DB::lc(), "select list_id from groups_assignments",
			"where", [
				"group_id" => $this->info['id'],
				["end_time", ">=", DB::raw("addtime(now(), '-72:00:00')")],
			],
			"order by end_time desc, list_id asc",
			$limit == -1 ? "" : DB::limit($limit),
		]));
	}

	public function hasUser(array $user = null) {
		if ($user == null) {
			return false;
		}
		return DB::selectFirst([
			DB::lc(), "select 1 from groups_users",
			"where", [
				'group_id' => $this->info['id'],
				'username' => $user['username'],
			],
		]) != null;
	}

	public function hasAssignment(UOJList $assignment) {
		return DB::selectFirst([
			DB::lc(), "select 1 from groups_assignments",
			"where", [
				'group_id' => $this->info['id'],
				'list_id' => $assignment->info['id']
			]
		]) != null;
	}
}
