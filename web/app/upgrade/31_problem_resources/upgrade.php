<?php

return function ($type) {
	if ($type == 'up') {
		DB::init();

		$problems = DB::selectAll("select id from problems");

		foreach ($problems as $row) {
			mkdir(UOJContext::storagePath() . "/problem_resources/{$row['id']}");
		}
	}
};
