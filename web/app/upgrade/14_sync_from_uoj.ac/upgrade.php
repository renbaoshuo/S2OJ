<?php

return function (string $type) {
	if ($type == 'up_after_sql') {
		DB::init();

		// === 用户 ===

		$users = DB::selectAll("SELECT * FROM user_info");

		foreach ($users as $user) {
			$extra = UOJUser::getExtra($user);

			$extra['avatar_source'] = $user['avatar_source'];
			$extra['social']['codeforces'] = $user['codeforces_handle'];
			$extra['social']['github'] = $user['github'];
			$extra['social']['website'] = $user['website'];
			$extra['image_hosting']['total_size_limit'] = $user['images_size_limit'];

			DB::update([
				"update user_info",
				"set", [
					"extra" => json_encode($extra),
				],
				"where", [
					"username" => $user['username'],
				],
			]);
		}

		DB::query("ALTER TABLE `user_info` DROP avatar_source, DROP website, DROP github, DROP codeforces_handle, DROP images_size_limit");

		// === 题目 ===
		$problems = DB::selectAll("SELECT * FROM `problems`");

		foreach ($problems as $info) {
			$problem = new UOJProblem($info);

			UOJSystemUpdate::updateProblem($problem, [
				'text' => '系统更新：S2OJ 开始保存测评记录历史',
				'url' => 'https://sjzezoj.com/blog/baoshuo/post/599',
			]);
		}

		// === Hacks ===
		$hacks = DB::selectAll([
			"select * from hacks",
			"order by id",
		]);

		foreach ($hacks as $hack) {
			DB::update([
				"update hacks",
				"set", [
					"status" => $hack['judge_time'] ? "Judged" : "Waiting",
				],
				"where", [
					"id" => $hack['id'],
				]
			]);
		}
	}
};
