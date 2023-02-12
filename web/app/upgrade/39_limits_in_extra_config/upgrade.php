<?php

requirePHPLib('data');

return function ($type) {
	if ($type == 'up') {
		DB::init();

		$problems = DB::selectAll("select * from problems");

		foreach ($problems as $info) {
			$problem = new UOJProblem($info);

			$extra_config = $problem->getExtraConfig();
			$problem_conf = $problem->getProblemConf();

			if (!($problem_conf instanceof UOJProblemConf)) {
				continue;
			}

			$extra_config['time_limit'] = (float)$problem_conf->getVal('time_limit', 1);
			$extra_config['memory_limit'] = (int)$problem_conf->getVal('memory_limit', 256);

			DB::update([
				"update problems",
				"set", [
					"extra_config" => json_encode($extra_config, JSON_FORCE_OBJECT),
				],
				"where", [
					"id" => $problem->info['id'],
				],
			]);

			echo "Problem {$problem->info['id']} upgraded.\n";
		}
	}
};
