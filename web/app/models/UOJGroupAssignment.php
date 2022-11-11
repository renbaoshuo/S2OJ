<?php

class UOJGroupAssignment extends UOJList {
	public $group = null;

	public static function query($id, UOJGroup $group = null) {
		$list = parent::query($id);
		if ($list === null) {
			return $list;
		}
		if ($group === null) {
			$group = UOJGroup::cur();
		}
		return new UOJGroupAssignment($list->info, $group);
	}

	public function __construct($info, UOJGroup $group) {
		parent::__construct($info);
		$this->group = $group;
		$this->completeInfo();
	}

	public function completeInfo() {
		if ($this->info['end_time_str']) {
			return;
		}

		if (!$this->info['end_time']) {
			$this->info['end_time'] = DB::selectSingle([
				"select end_time from groups_assignments",
				"where", [
					"list_id" => $this->info['id'],
					"group_id" => $this->group->info['id'],
				],
			]);
		}

		$this->info['end_time_str'] = $this->info['end_time'] ?: UOJTime::$time_now_str;
		$this->info['end_time'] = new DateTime($this->info['end_time_str']);
	}

	public function valid() {
		return $this->group && $this->group->hasAssignment($this);
	}

	public function getUri($where = '') {
		return $this->group->getUri("/assignment/{$this->info['id']}");
	}

	public function getLink($cfg = []) {
		$cfg += [
			'class' => '',
			'text' => $this->info['title'],
			'with' => 'none',
		];

		if ($cfg['with'] == 'sup') {
			if ($this->info['end_time'] < UOJTime::$time_now) {
				$cfg['text'] .= HTML::tag('sup', ["class" => "fw-normal text-danger ms-1"], 'overdue');
			} elseif ($this->info['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() < 86400) {
				$cfg['text'] .= HTML::tag('sup', ["class" => "fw-normal text-danger ms-1"], 'soon');
			}
		}

		return HTML::tag('a', [
			'href' => $this->getUri(),
			'class' => $cfg['class'],
		], $cfg['text']);
	}

	public function userCanView(array $user = null, $cfg = []) {
		$cfg += ['ensure' => false];

		return parent::userCanView($user, $cfg) && $this->group->userCanView($user, $cfg);
	}
}
