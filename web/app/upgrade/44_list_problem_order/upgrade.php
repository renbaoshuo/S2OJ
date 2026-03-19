<?php

return function (string $type) {
	if ($type == 'up') {
		DB::init();

		$lists = DB::selectAll("select distinct list_id from lists_problems");

		foreach ($lists as $list) {
			$problems = DB::selectAll([
				"select problem_id from lists_problems",
				"where", ["list_id" => $list['list_id']],
				"order by problem_id asc",
			]);

			$order = 1;
			foreach ($problems as $problem) {
				DB::update([
					"update lists_problems",
					"set", ["problem_order" => $order],
					"where", [
						"list_id" => $list['list_id'],
						"problem_id" => $problem['problem_id'],
					],
				]);
				$order++;
			}
		}
	}
};
