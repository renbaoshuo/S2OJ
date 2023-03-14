<?php

call_user_func(function () { // to prevent variable scope leak
	Route::group(
		[
			'domain' => UOJConfig::$data['web']['main']['host'],
		],
		function () {
			Route::post("/api/remote_judge/custom_account_validator", '/subdomain/api/remote_judge/custom_account_validator.php');

			Route::any('/api/submission/submission_status_details', '/subdomain/api/submission/submission_status_details.php');
		}
	);
});
