<?php

return function ($type) {
	if ($type == 'up') {
		DB::init();

		$problems = DB::selectAll("select * from problems");

		foreach ($problems as $info) {
			$problem = new UOJProblem($info);

			$difficulty = -1;
			$extra_config = $problem->getExtraConfig();

			if (isset($extra_config['difficulty'])) {
				$old_difficulty = (float)$extra_config['difficulty'];

				$difficulty = (int)(3.0 * $old_difficulty + 5) * 100;
				$difficulty = (function ($search, $arr) {
					$closest = null;
					foreach ($arr as $item) {
						if ($closest === null || abs($search - $closest) > abs($item - $search)) {
							$closest = $item;
						}
					}
					return $closest;
				})($difficulty, UOJProblem::$difficulty);
			}

			DB::update([
				"update problems",
				"set", [
					"difficulty" => $difficulty,
				],
				"where", [
					"id" => $problem->info['id'],
				]
			]);

			echo "Problem: {$problem->info['id']} ({$difficulty})\n";
		}
	}
};
