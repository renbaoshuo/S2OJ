<?php

return function (string $type) {
	if ($type == 'up_after_sql') {
		DB::init();

		$lists = DB::selectAll([
			"select * from lists",
		]);
		$parsedown = HTML::parsedown();
		$purifier = HTML::purifier();

		foreach ($lists as $info) {
			DB::insert([
				"insert into lists_contents",
				DB::bracketed_fields(['id', 'content', 'content_md']),
				"values",
				DB::tuple([
					$info['id'],
					$purifier->purify($parsedown->text($info['description'])),
					$info['description'],
				]),
			]);
		}

		DB::query("alter table lists drop column description");
	}
};
