<?php
Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();

$user_can_view = UOJProblem::cur()->userCanView(Auth::user());

if (!$user_can_view) {
	foreach (UOJProblem::cur()->findInContests() as $cp) {
		if ($cp->contest->progress() >= CONTEST_IN_PROGRESS && $cp->contest->userHasRegistered(Auth::user())) {
			$user_can_view = true;

			break;
		}
	}
}

if (!$user_can_view) {
	UOJResponse::page403();
}

// Create directory if not exists
if (!is_dir(UOJProblem::cur()->getResourcesPath())) {
	mkdir(UOJProblem::cur()->getResourcesPath(), 0755, true);
}

define('APP_TITLE', '题目资源 - ' . UOJProblem::cur()->getTitle(['with' => false]));
define('FM_EMBED', true);
define('FM_DISABLE_COLS', true);
define('FM_DATETIME_FORMAT', UOJTime::FORMAT);
define('FM_ROOT_PATH', UOJProblem::cur()->getResourcesFolderPath());
define('FM_ROOT_URL', UOJProblem::cur()->getResourcesBaseUri());

$sub_path = UOJRequest::get('sub_path', 'is_string', '');

if ($sub_path) {
	$filepath = realpath(UOJProblem::cur()->getResourcesPath(rawurldecode($sub_path)));
	$realbasepath = realpath(UOJProblem::cur()->getResourcesPath());
	$filename = basename($filepath);

	if (!strStartWith($filepath, $realbasepath)) {
		UOJResponse::page406();
	}

	UOJResponse::xsendfile($filepath, [
		'attachment' => $filename,
	]);
}

$global_readonly = !UOJProblem::cur()->userCanManage(Auth::user());

include(__DIR__ . '/tinyfilemanager/tinyfilemanager.php');
