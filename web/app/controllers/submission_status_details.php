<?php

if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
	redirectToLogin();
}

if (!is_array($_GET['get'])) {
	become404Page();
}

$res = [];

foreach ($_GET['get'] as $id) {
	if (!validateUInt($id)) {
		become404Page();
	}
	$submission = querySubmission($id);
	if ($submission['submitter'] !== Auth::id()) {
		become403Page();
	}
	if ($submission['contest_id'] == null && !(isNormalUser($myUser) && UOJConfig::$data['switch']['force-login'])) {
		become403Page();
	}
	
	$problem = queryProblemBrief($submission['problem_id']);
	if (!isSubmissionVisibleToUser($submission, $problem, Auth::user())) {
		become403Page();
	}
	
	$out_status = explode(', ', $submission['status'])[0];
	
	$res[] = [
		'judged' => $out_status == 'Judged',
		'html' => getSubmissionStatusDetails($submission)
	];
}

die(json_encode($res));
