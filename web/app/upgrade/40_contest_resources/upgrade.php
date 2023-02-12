<?php
return function ($type) {
	if ($type == 'up_after_sql') {
		DB::init();

		$contests = DB::selectAll("select * from contests");

		foreach ($contests as $contest) {
			$extra_config = json_decode($contest['extra_config'], true);

			if (isset($extra_config['links'])) {
				$new_links = [];

				foreach ($extra_config['links'] as $link) {
					if (isset($link['name'])) continue;

					$new_links[] = [
						'name' => $link[0],
						'url' => '/blogs/' . $link[1],
					];
				}

				$extra_config['links'] = $new_links;
			}

			DB::update([
				"update contests",
				"set", [
					"extra_config" => json_encode($extra_config, JSON_FORCE_OBJECT),
				],
				"where", [
					"id" => $contest['id'],
				],
			]);
		}
	}
};
