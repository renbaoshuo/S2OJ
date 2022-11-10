<?php

class UOJList {
	use UOJDataTrait;
	use UOJArticleTrait;

	public static function query($id) {
		if (!isset($id) || !validateUInt($id)) {
			return null;
		}
		$info = DB::selectFirst([
			"select * from lists",
			"where", ["id" => $id]
		]);
		if (!$info) {
			return null;
		}
		return new UOJList($info);
	}

	public function __construct($info) {
		$this->info = $info;
	}

	public function getProblemIDs() {
		return array_map(fn ($x) => $x['problem_id'], DB::selectAll([
			DB::lc(), "select problem_id from lists_problems",
			"where", ['list_id' => $this->info['id']],
			"order by problem_id"
		]));
	}

	public function hasProblem(UOJProblem $problem) {
		return DB::selectFirst([
			DB::lc(), "select 1 from lists_problems",
			"where", [
				'list_id' => $this->info['id'],
				'problem_id' => $problem->info['id']
			]
		]) != null;
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
}

UOJList::$table_for_content = 'lists_contents';
UOJList::$key_for_content = 'id';
UOJList::$fields_for_content = ['*'];
UOJList::$table_for_tags = 'lists_tags';
UOJList::$key_for_tags = 'list_id';
