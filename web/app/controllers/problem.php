<?php
	requirePHPLib('form');
	requirePHPLib('judger');	

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}
	
	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	$problem_content = queryProblemContent($problem['id']);
	
	$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;
	if ($contest != null) {
		genMoreContestInfo($contest);
		$problem_rank = queryContestProblemRank($contest, $problem);
		if ($problem_rank == null) {
			become404Page();
		} else {
			$problem_letter = chr(ord('A') + $problem_rank - 1);
		}
	}
	
	$is_in_contest = false;
	$ban_in_contest = false;
	if ($contest != null) {
		if (!hasContestPermission($myUser, $contest)) {
			if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
				become404Page();
			} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
				if ($myUser == null || !hasRegistered($myUser, $contest)) {
					becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
				} else {
					$is_in_contest = true;
					DB::update("update contests_registrants set has_participated = 1 where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
				}
			} else {
				$ban_in_contest = !isProblemVisibleToUser($problem, $myUser);
			}
		} else {
			if ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
				$is_in_contest = true;
				DB::update("update contests_registrants set has_participated = 1 where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
			}
		}
	} else {
		if (!isProblemVisibleToUser($problem, $myUser)) {
			become404Page();
		}

		if (!isNormalUser($myUser)) {
			become403Page();
		}
	}

	$submission_requirement = json_decode($problem['submission_requirement'], true);
	$problem_extra_config = getProblemExtraConfig($problem);
	$custom_test_requirement = getProblemCustomTestRequirement($problem);

	if ($custom_test_requirement && Auth::check()) {
		$custom_test_submission = DB::selectFirst("select * from custom_test_submissions where submitter = '".Auth::id()."' and problem_id = {$problem['id']} order by id desc limit 1");
		$custom_test_submission_result = json_decode($custom_test_submission['result'], true);
	}
	if ($custom_test_requirement && $_GET['get'] == 'custom-test-status-details' && Auth::check()) {
		if ($custom_test_submission == null) {
			echo json_encode(null);
		} elseif ($custom_test_submission['status'] != 'Judged') {
			echo json_encode(array(
				'judged' => false,
				'html' => getSubmissionStatusDetails($custom_test_submission)
			));
		} else {
			ob_start();
			$styler = new CustomTestSubmissionDetailsStyler();
			if (!hasViewPermission($problem_extra_config['view_details_type'], $myUser, $problem, $submission)) {
				$styler->fade_all_details = true;
			}
			echoJudgementDetails($custom_test_submission_result['details'], $styler, 'custom_test_details');
			$result = ob_get_contents();
			ob_end_clean();
			echo json_encode(array(
				'judged' => true,
				'html' => getSubmissionStatusDetails($custom_test_submission),
				'result' => $result
			));
		}
		die();
	}
	
	$can_use_zip_upload = true;
	foreach ($submission_requirement as $req) {
		if ($req['type'] == 'source code') {
			$can_use_zip_upload = false;
		}
	}
	
	function handleUpload($zip_file_name, $content, $tot_size) {
		global $problem, $contest, $myUser, $is_in_contest;
		
		$content['config'][] = array('problem_id', $problem['id']);
		if ($is_in_contest && $contest['extra_config']["contest_type"]!='IOI' && !isset($contest['extra_config']["problem_{$problem['id']}"])) {
			$content['final_test_config'] = $content['config'];
			$content['config'][] = array('test_sample_only', 'on');
		}
		$esc_content = DB::escape(json_encode($content));

		$language = '/';
		foreach ($content['config'] as $row) {
			if (strEndWith($row[0], '_language')) {
				$language = $row[1];
				break;
			}
		}
		if ($language != '/') {
			Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
		}
		$esc_language = DB::escape($language);
 		
		$result = array();
		$result['status'] = "Waiting";
		$result_json = json_encode($result);
		
		if ($is_in_contest) {
			DB::query("insert into submissions (problem_id, contest_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, ${contest['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', 0)");
		} else {
			DB::query("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result, is_hidden) values (${problem['id']}, now(), '${myUser['username']}', '$esc_content', '$esc_language', $tot_size, '${result['status']}', '$result_json', {$problem['is_hidden']})");
		}
	}
	function handleCustomTestUpload($zip_file_name, $content, $tot_size) {
		global $problem, $contest, $myUser;
		
		$content['config'][] = array('problem_id', $problem['id']);
		$content['config'][] = array('custom_test', 'on');
		$esc_content = DB::escape(json_encode($content));

		$language = '/';
		foreach ($content['config'] as $row) {
			if (strEndWith($row[0], '_language')) {
				$language = $row[1];
				break;
			}
		}
		if ($language != '/') {
			Cookie::set('uoj_preferred_language', $language, time() + 60 * 60 * 24 * 365, '/');
		}
		$esc_language = DB::escape($language);
 		
		$result = array();
		$result['status'] = "Waiting";
		$result_json = json_encode($result);
		
		DB::insert("insert into custom_test_submissions (problem_id, submit_time, submitter, content, status, result) values ({$problem['id']}, now(), '{$myUser['username']}', '$esc_content', '{$result['status']}', '$result_json')");
	}
	
	if ($can_use_zip_upload) {
		$zip_answer_form = newZipSubmissionForm('zip_answer',
			$submission_requirement,
			'uojRandAvaiableSubmissionFileName',
			'handleUpload');
		$zip_answer_form->extra_validator = function() {
			global $ban_in_contest;
			if ($ban_in_contest) {
				return '请耐心等待比赛结束后题目对所有人可见了再提交';
			}
			return '';
		};
		$zip_answer_form->succ_href = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
		$zip_answer_form->runAtServer();
	}
	
	$answer_form = newSubmissionForm('answer',
		$submission_requirement,
		'uojRandAvaiableSubmissionFileName',
		'handleUpload');
	$answer_form->extra_validator = function() {
		global $ban_in_contest;
		if ($ban_in_contest) {
			return '请耐心等待比赛结束后题目对所有人可见了再提交';
		}
		return '';
	};
	$answer_form->succ_href = $is_in_contest ? "/contest/{$contest['id']}/submissions" : '/submissions';
	$answer_form->runAtServer();

	if ($custom_test_requirement) {
		$custom_test_form = newSubmissionForm('custom_test',
			$custom_test_requirement,
			function() {
				return uojRandAvaiableFileName('/tmp/');
			},
			'handleCustomTestUpload');
		$custom_test_form->appendHTML(<<<EOD
<div id="div-custom_test_result"></div>
EOD
		);
		$custom_test_form->succ_href = 'none';
		$custom_test_form->extra_validator = function() {
			global $ban_in_contest, $custom_test_submission;
			if ($ban_in_contest) {
				return '请耐心等待比赛结束后题目对所有人可见了再提交';
			}
			if ($custom_test_submission && $custom_test_submission['status'] != 'Judged') {
				return '上一个测评尚未结束';
			}
			return '';
		};
		$custom_test_form->ctrl_enter_submit = true;
		$custom_test_form->setAjaxSubmit(<<<EOD
function(response_text) {custom_test_onsubmit(response_text, $('#div-custom_test_result')[0], '{$_SERVER['REQUEST_URI']}?get=custom-test-status-details')}
EOD
		);
		$custom_test_form->submit_button_config['text'] = UOJLocale::get('problems::run');
		$custom_test_form->runAtServer();
	}
	?>
<?php
	$REQUIRE_LIB['mathjax'] = '';
	$REQUIRE_LIB['shjs'] = '';
	?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - ' . UOJLocale::get('problems::problem')) ?>
<?php
	$limit = getUOJConf("/var/uoj_data/{$problem['id']}/problem.conf");
	$time_limit = $limit['time_limit'];
	$memory_limit = $limit['memory_limit'];

	$problem_uploader = $problem['uploader'];
	?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<?php endif ?>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row d-flex justify-content-center">
	<span class="badge badge-secondary mr-1">时间限制:<?=$time_limit!=null?"$time_limit s":"N/A"?></span>
	<span class="badge badge-secondary mr-1">空间限制:<?=$memory_limit!=null?"$memory_limit MB":"N/A"?></span>
	<span class="badge badge-secondary mr-1">上传者:<?= $problem_uploader ?: "root" ?></span>
</div>
<div class="float-right">
	<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-2">
<div class="card-body">
<?php endif ?>

<?php if ($contest): ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<h1 class="h2 card-title text-center">
	<?= $problem_letter ?>. <?= $problem['title'] ?>
</h1>
<?php else: ?>
<div class="page-header row">
	<h1 class="col-md-3 text-left"><small><?= $contest['name'] ?></small></h1>
	<h1 class="col-md-7 text-center"><?= $problem_letter ?>. <?= $problem['title'] ?></h1>
	<div class="col-md-2 text-right" id="contest-countdown"></div>
</div>
<div class="btn-group float-right" role="group">
<a role="button" class="btn btn-primary" href="<?= HTML::url("/download.php?type=attachment&id={$problem['id']}") ?>"><span class="glyphicon glyphicon-download-alt"></span> 附件下载</a>
<a role="button" class="btn btn-info" href="/contest/<?= $contest['id'] ?>/problem/<?= $problem['id'] ?>/statistics"><span class="glyphicon glyphicon-stats"></span> <?= UOJLocale::get('problems::statistics') ?></a>
</div>
<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
$('#contest-countdown').countdown(<?= $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>);
</script>
<?php endif ?>
<?php endif ?>

<?php else: ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<h1 class="h2 card-title text-center">
<?php else: ?>
<h1 class="page-header text-center">
<?php endif ?>
	#<?= $problem['id']?>. <?= $problem['title'] ?>
</h1>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="btn-group float-right" role="group">
<a role="button" class="btn btn-primary" href="<?= HTML::url("/download.php?type=problem&id={$problem['id']}") ?>"><span class="glyphicon glyphicon-tasks"></span> 测试数据</a>
<a role="button" class="btn btn-primary" href="<?= HTML::url("/download.php?type=attachment&id={$problem['id']}") ?>"><span class="glyphicon glyphicon-download-alt"></span> 附件下载</a>
<a role="button" class="btn btn-info" href="/problem/<?= $problem['id'] ?>/statistics"><span class="glyphicon glyphicon-stats"></span> <?= UOJLocale::get('problems::statistics') ?></a>
</div>
<?php endif ?>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="text-center small">
	时间限制: <?= $time_limit != null ? "$time_limit s" : "N/A" ?>
	&emsp;
	空间限制: <?= $memory_limit != null ? "$memory_limit MB" : "N/A" ?>
	&emsp;
	上传者: <?= getUserLink($problem_uploader ?: "root") ?>
</div>

<hr>
<?php endif ?>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link active" href="#tab-statement" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-book"></span> <?= UOJLocale::get('problems::statement') ?></a></li>
	<li class="nav-item"><a class="nav-link" href="#tab-submit-answer" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-upload"></span> <?= UOJLocale::get('problems::submit') ?></a></li>
	<?php if ($custom_test_requirement): ?>
	<li class="nav-item"><a class="nav-link" href="#tab-custom-test" role="tab" data-toggle="tab"><span class="glyphicon glyphicon-console"></span> <?= UOJLocale::get('problems::custom test') ?></a></li>
	<?php endif ?>
	<?php if (!$contest): ?>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/solutions" role="tab"><?= UOJLocale::get('problems::solutions') ?></a></li>
	<?php endif ?>
	<?php if (hasProblemPermission($myUser, $problem)): ?>
	<li class="nav-item"><a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab"><?= UOJLocale::get('problems::manage') ?></a></li>
	<?php endif ?>
	<?php if ($contest): ?>
	<li class="nav-item"><a class="nav-link" href="/contest/<?= $contest['id'] ?>" role="tab"><?= UOJLocale::get('contests::back to the contest') ?></a></li>
	<?php endif ?>
</ul>
<?php endif ?>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<link rel="stylesheet" type="text/css" href="<?= HTML::url('/css/markdown.css') ?>">
<?php endif ?>

<div class="tab-content">
	<div class="tab-pane active" id="tab-statement">
		<article class="mt-3 markdown-body"><?= $problem_content['statement'] ?></article>
	</div>
	<div class="tab-pane" id="tab-submit-answer">
		<div class="top-buffer-sm"></div>
		<?php if ($can_use_zip_upload): ?>
		<?php $zip_answer_form->printHTML(); ?>
		<hr />
		<strong><?= UOJLocale::get('problems::or upload files one by one') ?><br /></strong>
		<?php endif ?>
		<?php $answer_form->printHTML(); ?>
	</div>
	<?php if ($custom_test_requirement): ?>
	<div class="tab-pane" id="tab-custom-test">
		<div class="top-buffer-sm"></div>
		<?php $custom_test_form->printHTML(); ?>
	</div>
	<?php endif ?>
</div>


<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">

<?php if ($contest): ?>
<div class="card card-default mb-2">
	<div class="card-body">
		<h3 class="h5 card-title text-center">
			<a class="text-decoration-none text-body" href="/contest/<?= $contest['id'] ?>">
				<?= $contest['name'] ?>
			</a>
		</h3>
		<div class="card-text text-center text-muted">
			<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
				<span id="contest-countdown"></span>
			<?php else: ?>
				<?= UOJLocale::get('contests::contest ended') ?>
			<?php endif ?>
		</div>
	</div>
	<div class="card-footer bg-transparent">
		比赛评价：<?= getClickZanBlock('C', $contest['id'], $contest['zan']) ?>
	</div>
</div>
<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
$('#contest-countdown').countdown(<?= $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function(){}, '1.75rem', false);
</script>
<?php endif ?>
<?php endif ?>

<div class="card card-default mb-2">
	<ul class="nav nav-pills nav-fill flex-column" role="tablist">
		<li class="nav-item text-start">
			<a href="#tab-statement" class="nav-link active" role="tab" data-bs-toggle="pill" data-bs-target="#tab-statement">
				<i class="bi bi-journal-text"></i>
				<?= UOJLocale::get('problems::statement') ?>
			</a>
		</li>
		<li class="nav-item text-start">
			<a href="#tab-submit-answer" class="nav-link" role="tab" data-bs-toggle="pill" data-bs-target="#tab-submit-answer">
				<i class="bi bi-upload"></i>
				<?= UOJLocale::get('problems::submit') ?>
			</a>
		</li>
		<?php if ($custom_test_requirement): ?>
		<li class="nav-item text-start">
			<a class="nav-link" href="#tab-custom-test" role="tab" data-bs-toggle="pill" data-bs-target="#tab-custom-test">
				<i class="bi bi-braces"></i>
				<?= UOJLocale::get('problems::custom test') ?>
			</a>
		</li>
		<?php endif ?>
		<?php if (!$contest): ?>
		<li class="nav-item text-start">
			<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
				<i class="bi bi-journal-bookmark"></i>
				<?= UOJLocale::get('problems::solutions') ?>
			</a>
		</li>
		<?php endif ?>
		<?php if (hasProblemPermission($myUser, $problem)): ?>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
				<i class="bi bi-sliders"></i>
				<?= UOJLocale::get('problems::manage') ?>
			</a>
		</li>
		<?php endif ?>
		<?php if ($contest): ?>
		<li class="nav-item text-start">
			<a class="nav-link" href="/contest/<?= $contest['id'] ?>" role="tab">
				<i class="bi bi-arrow-90deg-left"></i>
				<?= UOJLocale::get('contests::back to the contest') ?>
			</a>
		</li>
		<?php endif ?>
	</ul>
</div>

<div class="card card-default mb-2">
	<ul class="nav nav-fill flex-column">
		<li class="nav-item text-start">
			<a class="nav-link" href="<?= HTML::url("/download.php?type=problem&id={$problem['id']}") ?>">
				<i class="bi bi-hdd-stack"></i>
				测试数据
			</a>
		</li>
		<li class="nav-item text-start">
			<a class="nav-link" href="<?= HTML::url("/download.php?type=attachment&id={$problem['id']}") ?>">
				<i class="bi bi-download"></i>
				附件下载
			</a>
		</li>
		<li class="nav-item text-start">
			<a class="nav-link" href="/problem/<?= $problem['id'] ?>/statistics">
				<i class="bi bi-graph-up"></i>
				<?= UOJLocale::get('problems::statistics') ?>
			</a>
		</li>
	</ul>
	<div class="card-footer bg-transparent">
		评价：<?= getClickZanBlock('P', $problem['id'], $problem['zan']) ?>
	</div>
</div>

<?php
	$sidebar_config = array();
	if ($contest && $contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
		$sidebar_config['upcoming_contests_hidden'] = '';
	}
	uojIncludeView('sidebar', $sidebar_config);
	?>
</aside>

</div>


<script>
	$(document).ready(function() {
		$('.markdown-body table').each(function() {
			$(this).addClass('table table-bordered table-striped');
		});
	});
</script>
<?php endif ?>

<?php if ($contest && $contest['cur_progress'] <= CONTEST_IN_PROGRESS): ?>
<script type="text/javascript">
checkContestNotice(<?= $contest['id'] ?>, '<?= UOJTime::$time_now_str ?>');
</script>
<?php endif ?>

<?php echoUOJPageFooter() ?>
