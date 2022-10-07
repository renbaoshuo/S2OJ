<?php
	requirePHPLib('judger');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login'] && $_GET['type'] != 'attachment') {
		become403Page();
	}

	switch ($_GET['type']) {
		case 'attachment':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}
			
			$visible = isProblemVisibleToUser($problem, $myUser);
			if (!$visible && $myUser != null) {
				$result = DB::query("select contest_id from contests_problems where problem_id = {$_GET['id']}");
				while (list($contest_id) = DB::fetch($result, MYSQLI_NUM)) {
					$contest = queryContest($contest_id);
					genMoreContestInfo($contest);
					if ($contest['cur_progress'] != CONTEST_NOT_STARTED && hasRegistered($myUser, $contest) && queryContestProblemRank($contest, $problem)) {
						$visible = true;
					}
				}
			}
			if (!$visible) {
				become404Page();
			}

			$id = $_GET['id'];
			
			$file_name = "/var/uoj_data/$id/download.zip";
			$download_name = "problem_{$id}_attachment.zip";
			break;

		case 'problem':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}

			if (!isProblemVisibleToUser($problem, $myUser)) {
				become404Page();
			}

			$id = $_GET['id'];
			$file_name = "/var/uoj_data/$id.zip";
			$download_name = "problem_$id.zip";

			break;

		case 'testcase':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}

			if (!isProblemVisibleToUser($problem, $myUser)) {
				become404Page();
			}

			$id = $_GET['id'];
			$problem_conf = getUOJConf("/var/uoj_data/$id/problem.conf");

			if ($problem_conf == -1 || $problem_conf == -2) {
				become404Page();
			}

			if (!validateUInt($_GET['testcase_id'])) {
				become404Page();
			}

			$testcase_id = $_GET['testcase_id'];
			$testcase_group = isset($_GET['testcase_group']) && $_GET['testcase_group'] == 'extra' ? 'extra' : 'normal';

			if ($testcase_group == 'extra') {
				$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', 0);

				if ($testcase_id < 1 || $testcase_id > $n_ex_tests) {
					become404Page();
				}

				switch ($_GET['testcase_type']) {
					case 'input':
						$file_name = "/var/uoj_data/$id/" . getUOJProblemExtraInputFileName($problem_conf, $testcase_id);
						$download_name = getUOJProblemExtraInputFileName($problem_conf, $testcase_id);
						break;

					case 'output':
						$file_name = "/var/uoj_data/$id/" . getUOJProblemExtraOutputFileName($problem_conf, $testcase_id);
						$download_name = getUOJProblemExtraOutputFileName($problem_conf, $testcase_id);
						break;

					default:
						become404Page();
				}
			} else {
				$n_tests = getUOJConfVal($problem_conf, 'n_tests', 10);

				if ($testcase_id < 1 || $testcase_id > $n_tests) {
					become404Page();
				}

				switch ($_GET['testcase_type']) {
					case 'input':
						$file_name = "/var/uoj_data/$id/" . getUOJProblemInputFileName($problem_conf, $testcase_id);
						$download_name = getUOJProblemInputFileName($problem_conf, $testcase_id);
						break;
					case 'output':
						$file_name = "/var/uoj_data/$id/" . getUOJProblemOutputFileName($problem_conf, $testcase_id);
						$download_name = getUOJProblemOutputFileName($problem_conf, $testcase_id);
						break;
					default:
						become404Page();
				}
			}

			break;

		case 'testlib.h':
			$file_name = "/opt/uoj/judger/uoj_judger/include/testlib.h";
			$download_name = "testlib.h";
			break;

		default:
			become404Page();
	}
	
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_name);
	if ($mimetype === false) {
		become404Page();
	}
	finfo_close($finfo);
	
	header("X-Sendfile: $file_name");
	header("Content-type: $mimetype");
	header("Content-Disposition: attachment; filename=$download_name");
	?>
