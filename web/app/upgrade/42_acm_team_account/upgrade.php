<?php

return function ($type) {
	DB::init();

	if ($type == 'up') {
		$users = DB::selectAll("select * from user_info");

		foreach ($users as $user) {
			$extra = UOJUser::getExtra($user);

			$extra['school'] = $user['school'];

			DB::update([
				"update user_info",
				"set", [
					"extra" => json_encode($extra, JSON_UNESCAPED_UNICODE)
				],
				"where", [
					"username" => $user['username']
				]
			]);

			echo "Updated user {$user['username']}. \n";
		}
	}
};
