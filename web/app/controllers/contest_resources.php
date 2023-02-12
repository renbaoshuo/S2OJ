<?php
UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true, 'check-register' => true]);

// Create directory if not exists
if (!is_dir(UOJContest::cur()->getResourcesPath())) {
	mkdir(UOJContest::cur()->getResourcesPath(), 0755, true);
}

define('APP_TITLE', '比赛资源 - ' . UOJContest::info('title'));
define('FM_EMBED', true);
define('FM_DISABLE_COLS', true);
define('FM_DATETIME_FORMAT', UOJTime::FORMAT);
define('FM_ROOT_PATH', UOJContest::cur()->getResourcesFolderPath());
define('FM_ROOT_URL', UOJContest::cur()->getResourcesBaseUri());

$sub_path = UOJRequest::get('sub_path', 'is_string', '');

if ($sub_path) {
	$filepath = realpath(UOJContest::cur()->getResourcesPath(rawurldecode($sub_path)));
	$realbasepath = realpath(UOJContest::cur()->getResourcesPath());

	if (!strStartWith($filepath, $realbasepath)) {
		UOJResponse::page406();
	}

	UOJResponse::xsendfile($filepath);
}

$global_readonly = !UOJContest::cur()->userCanManage(Auth::user());

include(__DIR__ . '/tinyfilemanager/tinyfilemanager.php');
