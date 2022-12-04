<?php

return function ($type) {
	if ($type == 'up_after_sql') {
		DB::init();

		for ($i = 0; $i < 10; $i++) {
			for ($j = 0; $j < 10; $j++) {
				echo "Tag: {$i}.{$j}\n";

				$tag_info = DB::selectAll([
					"select *",
					"from problems_tags",
					"where", [
						"tag" => "{$i}.{$j}",
					]
				]);

				foreach ($tag_info as $tag) {
					DB::delete([
						"delete from problems_tags",
						"where", [
							"id" => $tag['id'],
						],
					]);

					$problem = UOJProblem::query($tag['problem_id']);
					$extra_config = $problem->getExtraConfig();

					$extra_config['difficulty'] = doubleval("{$i}.{$j}");

					DB::update([
						"update problems",
						"set", [
							"extra_config" => json_encode($extra_config),
						],
						"where", [
							"id" => $problem->info['id'],
						],
					]);

					echo "{$problem->info['id']}: {$extra_config['difficulty']}\n";
				}
			}
		}


		echo "Tag: DONE";

		$problems = DB::selectAll("select * from problems");

		foreach ($problems as $info) {
			$problem = new UOJProblem($info);

			$difficulty = -1;
			$extra_config = $problem->getExtraConfig();

			if (isset($extra_config['difficulty'])) {
				$old_difficulty = $extra_config['difficulty'];

				if (0 <= $old_difficulty && $old_difficulty < 2) {
					$difficulty = 1;
				} else if (2 <= $old_difficulty && $old_difficulty < 3) {
					$difficulty = 2;
				} else if (3 <= $old_difficulty && $old_difficulty < 4) {
					$difficulty = 2;
				} else if (4 <= $old_difficulty && $old_difficulty < 5) {
					$difficulty = 4;
				} else if (5 <= $old_difficulty && $old_difficulty < 6) {
					$difficulty = 6;
				} else if (6 <= $old_difficulty && $old_difficulty < 8) {
					$difficulty = 8;
				} else if (8 <= $old_difficulty) {
					$difficulty = 10;
				}
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

			echo "Problem: {$problem->info['id']}";
		}
	}
};
