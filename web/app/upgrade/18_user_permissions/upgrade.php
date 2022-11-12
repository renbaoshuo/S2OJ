<?php

return function ($type) {
	if ($type === 'up') {
		DB::init();

		$users = DB::selectAll("select * from user_info");

		foreach ($users as $user) {
			$usertype = explode(',', $user['usertype']);
			$extra = UOJUser::getExtra($user);
			$new_permissions = [
				'_placeholder' => '',
				'problems' => [
					'_placeholder' => '',
				],
				'contests' => [
					'_placeholder' => '',
				],
				'lists' => [
					'_placeholder' => '',
				],
				'groups' => [
					'_placeholder' => '',
				],
				'blogs' => [
					'_placeholder' => '',
				],
				'users' => [
					'_placeholder' => '',
				]
			];

			if (in_array('problem_uploader', $usertype)) {
				$new_permissions['problems']['create'] = true;
			}

			if (in_array('problem_manager', $usertype)) {
				$new_permissions['problems']['create'] = true;
				$new_permissions['problems']['manage'] = true;
			}

			if (in_array('contest_judger', $usertype)) {
				$new_permissions['contests']['start_final_test'] = true;
			}

			if (in_array('teacher', $usertype)) {
				$usertype = 'teacher';
			} elseif (in_array('banned', $usertype)) {
				$usertype = 'banned';
			} else {
				$usertype = 'student';
			}

			$extra['permissions'] = $new_permissions;

			DB::update([
				"update user_info",
				"set", [
					"usertype" => $usertype,
					"extra" => json_encode($extra),
				],
				"where", [
					"username" => $user['username'],
				],
			]);
		}
	}
};
