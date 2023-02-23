<?php

return function ($type) {
	if ($type == 'up') {
		DB::init();

		function getColorName($color) {
			switch ($color) {
				case '#0d6efd':
				case 'blue':
					return 'blue';
				case '#2da44e':
				case 'green':
					return 'green';
				case '#e85aad':
				case 'pink':
					return 'pink';
				case '#f32a38':
				case 'red':
					return 'red';
				case '#f57c00':
				case 'orange':
					return 'orange';
				case '#00acc1':
				case 'cyan':
					return 'cyan';
				case '#9d3dcf':
				case 'purple':
					return 'purple';
				case '#707070':
				case 'gray':
					return 'gray';
				case '#996600':
				case 'brown':
					return 'brown';
				default:
					return 'blue';
			}
		}

		$users = DB::selectAll("select * from user_info");

		foreach ($users as $user) {
			$extra = UOJUser::getExtra($user);
			$original_color = $extra['username_color'];
			$new_color = getColorName($original_color);
			$extra['username_color'] = $new_color;
			DB::update([
				"update user_info",
				"set", [
					"extra" => json_encode($extra)
				],
				"where", [
					"username" => $user['username']
				],
			]);
			echo "{$user['username']}: {$original_color} -> {$new_color}\n";
		}
	}
};
