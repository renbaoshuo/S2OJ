<?php
requirePHPLib('judger');

$auth = false;
if (UOJRequest::get('auth') === 'judger') {
	authenticateJudger() || UOJResponse::page403();
	$auth = true;
} else {
	Auth::check() || redirectToLogin();
}

switch (UOJRequest::get('type')) {
	case 'attachment':
		UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();
		if (!$auth) {
			UOJProblem::cur()->userCanDownloadAttachments(Auth::user()) || UOJResponse::page404();
		}

		$file_name = UOJProblem::cur()->getDataFolderPath() . '/download.zip';
		$download_name = 'problem_' . UOJProblem::info('id') . '_attachment.zip';

		break;

	case 'problem':
		UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();

		if (!$auth) {
			UOJProblem::cur()->userCanDownloadTestData(Auth::user()) || UOJResponse::page403();
		}

		$file_name = UOJProblem::cur()->getDataZipPath();
		$download_name = 'problem_' . UOJProblem::info('id') . '.zip';

		break;

	case 'submission':
		if (!$auth) {
			isSuperUser(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJContext::storagePath() . "/submission/{$_GET['id']}/{$_GET['rand_str_id']}";
		$download_name = "submission.zip";
		break;

	case 'tmp':
		if (!$auth) {
			isSuperUser(Auth::user()) || UOJResponse::page404();
		}
		$file_name = UOJContext::storagePath() . "/tmp/{$_GET['rand_str_id']}";
		$download_name = "tmp";
		break;

	case 'testlib.h':
		$file_name = UOJLocalRun::$judger_include_path . '/testlib.h';
		$download_name = 'testlib.h';
		break;

	default:
		UOJResponse::page404();
}

UOJResponse::xsendfile($file_name, ['attachment' => $download_name]);
