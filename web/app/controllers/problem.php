<?php
requireLib('bootstrap5');
requireLib('hljs');
requireLib('mathjax');
requireLib('pdf.js');
requirePHPLib('form');
requirePHPLib('judger');

Auth::check() || redirectToLogin();
UOJProblem::init(UOJRequest::get('id')) || UOJResponse::page404();

$problem = UOJProblem::cur()->info;
$problem_content = UOJProblem::cur()->queryContent();

if (UOJRequest::get('contest_id')) {
	UOJContest::init(UOJRequest::get('contest_id')) || UOJResponse::page404();
	UOJProblem::upgradeToContestProblem() || UOJResponse::page404();
}
UOJProblem::cur()->userCanView(Auth::user(), ['ensure' => true]);

$pre_submit_check_ret = UOJProblem::cur()->preSubmitCheck();

$is_participating = false;
$no_more_submission = false;
$submission_warning = null;
if (UOJContest::cur()) {
	if (UOJContest::cur()->userCanParticipateNow(Auth::user())) {
		if (!UOJContest::cur()->userHasMarkedParticipated(Auth::user())) {
			redirectTo(UOJContest::cur()->getUri("/confirm"));
		}
		$is_participating = true;
		$submit_time_limit = UOJContestProblem::cur()->submitTimeLimit();
		$max_cnt = UOJContest::cur()->maxSubmissionCountPerProblem();
		if ($submit_time_limit != -1) {
			$cur_contest_time = (UOJTime::$time_now->getTimestamp() - UOJContest::info('start_time')->getTimestamp()) / 60;
			if ($cur_contest_time > $submit_time_limit) {
				$no_more_submission = "本题只能在比赛的前 {$submit_time_limit} 分钟提交，没法再交咯";
			}
		}
		if (!$no_more_submission) {
			if ($max_cnt != -1) {
				$cnt = UOJContestProblem::cur()->queryUserSubmissionCountInContest(Auth::user());
				if ($cnt >= $max_cnt) {
					$no_more_submission = "提交次数已达到 {$cnt} 次，没法再交咯";
				}
			}
		}
		if (!$no_more_submission) {
			if ($max_cnt != -1) {
				$warning1 = "已使用 {$cnt}/{$max_cnt} 次提交机会";
			} else {
				$warning1 = null;
			}
			if ($submit_time_limit != -1) {
				$warning2 = "注意本题只能在比赛的前 {$submit_time_limit} 分钟提交";
			} else {
				$warning2 = null;
			}
			if ($warning1 && $warning2) {
				$submission_warning = "{$warning1}（{$warning2}）";
			} else {
				$submission_warning = $warning1 !== null ? $warning1 : $warning2;
			}
		}
	}

	// 比赛导航
	$tabs_info = [
		'dashboard' => [
			'name' => UOJLocale::get('contests::contest dashboard'),
			'url' => '/contest/' . UOJContest::info('id'),
		],
		'submissions' => [
			'name' => UOJLocale::get('contests::contest submissions'),
			'url' => '/contest/' . UOJContest::info('id') . '/submissions',
		],
		'standings' => [
			'name' => UOJLocale::get('contests::contest standings'),
			'url' => '/contest/' . UOJContest::info('id') . '/standings',
		],
	];

	if (UOJContest::cur()->progress() > CONTEST_TESTING) {
		$tabs_info['after_contest_standings'] = [
			'name' => UOJLocale::get('contests::after contest standings'),
			'url' => '/contest/' . UOJContest::info('id') . '/after_contest_standings',
		];
		$tabs_info['self_reviews'] = [
			'name' => UOJLocale::get('contests::contest self reviews'),
			'url' => '/contest/' . UOJContest::info('id') . '/self_reviews',
		];
	}

	if (UOJContest::cur()->userCanManage(Auth::user())) {
		$tabs_info['backstage'] = [
			'name' => UOJLocale::get('contests::contest backstage'),
			'url' => '/contest/' . UOJContest::info('id') . '/backstage',
		];
	}
}

$submission_requirement = UOJProblem::cur()->getSubmissionRequirement();
$custom_test_requirement = UOJProblem::cur()->getCustomTestRequirement();
$custom_test_enabled = $custom_test_requirement && $pre_submit_check_ret === true;

function handleUpload($zip_file_name, $content, $tot_size) {
	global $is_participating;
	UOJSubmission::onUpload($zip_file_name, $content, $tot_size, $is_participating);
}
function handleCustomTestUpload($zip_file_name, $content, $tot_size) {
	UOJCustomTestSubmission::onUpload($zip_file_name, $content, $tot_size);
}
if ($custom_test_enabled) {
	UOJCustomTestSubmission::init(UOJProblem::cur(), Auth::user());

	if (UOJRequest::get('get') == 'custom-test-status-details') {
		if (!UOJCustomTestSubmission::cur()) {
			echo json_encode(null);
		} elseif (!UOJCustomTestSubmission::cur()->hasJudged()) {
			echo json_encode([
				'judged' => false,
				'waiting' => true,
				'html' => UOJCustomTestSubmission::cur()->getStatusDetailsHTML(),
			]);
		} else {
			ob_start();
			$styler = new CustomTestSubmissionDetailsStyler();
			if (!UOJCustomTestSubmission::cur()->userPermissionCodeCheck(Auth::user(), UOJProblem::cur()->getExtraConfig('view_details_type'))) {
				$styler->fade_all_details = true;
			}
			echoJudgmentDetails(UOJCustomTestSubmission::cur()->getResult('details'), $styler, 'custom_test_details');
			$result = ob_get_contents();
			ob_end_clean();
			echo json_encode([
				'judged' => true,
				'waiting' => false,
				'html' => UOJCustomTestSubmission::cur()->getStatusDetailsHTML(),
				'result' => $result
			]);
		}
		die();
	}

	$custom_test_form = newSubmissionForm(
		'custom_test',
		$custom_test_requirement,
		'FS::randomAvailableTmpFileName',
		'handleCustomTestUpload'
	);
	$custom_test_form->appendHTML('<div id="div-custom_test_result"></div>');
	$custom_test_form->succ_href = 'none';
	$custom_test_form->extra_validator = function () {
		if (UOJCustomTestSubmission::cur() && !UOJCustomTestSubmission::cur()->hasJudged()) {
			return '上一个测评尚未结束';
		}
		return '';
	};
	$custom_test_form->setAjaxSubmit(<<<EOD
		function(response_text) {
			custom_test_onsubmit(
				response_text,
				$('#div-custom_test_result')[0],
				'{$_SERVER['REQUEST_URI']}?get=custom-test-status-details'
			)
		}
	EOD);
	$custom_test_form->config['submit_button']['text'] = UOJLocale::get('problems::run');
	$custom_test_form->runAtServer();
}

$can_use_zip_upload = true;
foreach ($submission_requirement as $req) {
	if ($req['type'] == 'source code') {
		$can_use_zip_upload = false;
	}
}

if ($pre_submit_check_ret === true && !$no_more_submission) {
	$submission_extra_validator = function () {
		if (!submission_frequency_check()) {
			UOJLog::warning('a user exceeds the submission frequency limit! ' . Auth::id() . ' at problem #' . UOJProblem::info('id'));
			return '交题交得太快啦，坐下来喝杯阿华田休息下吧？';
		}
		return '';
	};

	if (UOJProblem::cur()->userCanUploadSubmissionViaZip(Auth::user())) {
		$zip_answer_form = newZipSubmissionForm(
			'zip-answer',
			$submission_requirement,
			'FS::randomAvailableSubmissionFileName',
			'handleUpload'
		);
		$zip_answer_form->extra_validator = $submission_extra_validator;
		$zip_answer_form->succ_href = $is_participating ? '/contest/' . UOJContest::info('id') . '/submissions' : '/submissions';
		$zip_answer_form->runAtServer();
	}

	$answer_form = newSubmissionForm(
		'answer',
		$submission_requirement,
		'FS::randomAvailableSubmissionFileName',
		'handleUpload'
	);
	$answer_form->extra_validator = $submission_extra_validator;
	$answer_form->succ_href = $is_participating ? '/contest/' . UOJContest::info('id') . '/submissions' : '/submissions';
	$answer_form->runAtServer();
}

$conf = UOJProblem::cur()->getProblemConf();

if (UOJContest::cur()) {
	$pageTitle = UOJProblem::cur()->getTitle(['with' => 'letter', 'simplify' => true]);
} else {
	$pageTitle = UOJProblem::cur()->getTitle(['with' => 'id']);
}
?>

<?php echoUOJPageHeader(HTML::stripTags($pageTitle) . ' - ' . UOJLocale::get('problems::problem')) ?>

<div class="row">
	<!-- Left col -->
	<div class="col-lg-9">
		<?php if (isset($tabs_info)) : ?>
			<!-- 比赛导航 -->
			<div class="mb-2">
				<?= HTML::tablist($tabs_info, '', 'nav-pills') ?>
			</div>
		<?php endif ?>

		<div class="card card-default mb-2">
			<div class="card-body">
				<h1 class="card-title text-center">
					<?php if (UOJContest::cur()) : ?>
						<?= UOJProblem::cur()->getTitle(['with' => 'letter', 'simplify' => true]) ?>
					<?php else : ?>
						<?= UOJProblem::cur()->getTitle(['with' => 'id']) ?>
					<?php endif ?>
				</h1>

				<?php
				if (UOJProblem::info('type') == 'local') {
					$time_limit = $conf instanceof UOJProblemConf ? $conf->getVal('time_limit', 1) : null;
					$memory_limit = $conf instanceof UOJProblemConf ? $conf->getVal('memory_limit', 256) : null;
				} else if (UOJProblem::info('type') == 'remote') {
					$time_limit = UOJProblem::cur()->getExtraConfig('time_limit');
					$memory_limit = UOJProblem::cur()->getExtraConfig('memory_limit');
				}
				?>
				<div class="text-center small">
					时间限制: <?= $time_limit ? "$time_limit s" : "N/A" ?>
					&emsp;
					空间限制: <?= $memory_limit ? "$memory_limit MB" : "N/A" ?>
				</div>

				<hr>

				<div class="tab-content">
					<div class="tab-pane active" id="statement">
						<article class="mt-3 markdown-body">
							<?= $problem_content['statement'] ?>
						</article>

						<?php if (UOJProblem::info('type') == 'remote') : ?>
							<hr>

							<article class="mt-3 markdown-body remote-content">
								<?= UOJProblem::cur()->queryContent()['remote_content'] ?>
							</article>
						<?php endif ?>
					</div>
					<div class="tab-pane" id="submit">
						<?php if ($pre_submit_check_ret !== true) : ?>
							<h3 class="text-warning"><?= $pre_submit_check_ret ?></h3>
						<?php elseif ($no_more_submission) : ?>
							<h3 class="text-warning"><?= $no_more_submission ?></h3>
						<?php else : ?>
							<?php if ($submission_warning) : ?>
								<h3 class="text-warning"><?= $submission_warning ?></h3>
							<?php endif ?>
							<?php if (isset($zip_answer_form)) : ?>
								<?php $zip_answer_form->printHTML(); ?>
								<hr />
								<strong><?= UOJLocale::get('problems::or upload files one by one') ?><br /></strong>
							<?php endif ?>
							<?php $answer_form->printHTML(); ?>
						<?php endif ?>
					</div>
					<?php if ($custom_test_enabled) : ?>
						<div class="tab-pane" id="custom-test">
							<?php $custom_test_form->printHTML(); ?>
						</div>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php if (UOJContest::cur()) : ?>
			<!-- Contest card -->
			<div class="card card-default mb-2">
				<div class="card-body">
					<h3 class="h4 card-title text-center">
						<a class="text-decoration-none text-body" href="/contest/<?= UOJContest::info('id') ?>">
							<?= UOJContest::info('name') ?>
						</a>
					</h3>
					<div class="card-text text-center text-muted">
						<?php if (UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS) : ?>
							<span id="contest-countdown"></span>
						<?php else : ?>
							<?= UOJLocale::get('contests::contest ended') ?>
						<?php endif ?>
					</div>
				</div>
				<div class="card-footer bg-transparent">
					比赛评价：<?= UOJContest::cur()->getZanBlock() ?>
				</div>
			</div>
			<?php if (UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS) : ?>
				<script>
					$('#contest-countdown').countdown(<?= UOJContest::info('end_time')->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function() {}, '1.75rem', false);
				</script>
			<?php endif ?>
		<?php endif ?>

		<!-- 题目导航卡片 -->
		<div class="card card-default mb-2">
			<ul class="nav nav-pills nav-fill flex-column" role="tablist">
				<li class="nav-item text-start">
					<a href="#statement" class="nav-link active" role="tab" data-bs-toggle="pill" data-bs-target="#statement">
						<i class="bi bi-journal-text"></i>
						<?= UOJLocale::get('problems::statement') ?>
					</a>
				</li>
				<li class="nav-item text-start">
					<a href="#submit" class="nav-link" role="tab" data-bs-toggle="pill" data-bs-target="#submit">
						<i class="bi bi-upload"></i>
						<?= UOJLocale::get('problems::submit') ?>
					</a>
				</li>
				<?php if ($custom_test_enabled) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="#custom-test" role="tab" data-bs-toggle="pill" data-bs-target="#custom-test">
							<i class="bi bi-braces"></i>
							<?= UOJLocale::get('problems::custom test') ?>
						</a>
					</li>
				<?php endif ?>
				<?php if (!UOJContest::cur() || UOJContest::cur()->progress() >= CONTEST_FINISHED) : ?>
					<li class="nav-item text-start">
						<a href="/problem/<?= $problem['id'] ?>/solutions" class="nav-link" role="tab">
							<i class="bi bi-journal-bookmark"></i>
							<?= UOJLocale::get('problems::solutions') ?>
						</a>
					</li>
				<?php endif ?>
				<li class="nav-item text-start">
					<a class="nav-link" href="/submissions?problem_id=<?= UOJProblem::info('id') ?>">
						<i class="bi bi-list-ul"></i>
						<?= UOJLocale::get('submissions') ?>
					</a>
				</li>
				<?php if (UOJContest::cur() && UOJContest::cur()->userCanSeeProblemStatistics(Auth::user())) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="/contest/<?= UOJContest::info('id') ?>/problem/<?= $problem['id'] ?>/statistics">
							<i class="bi bi-graph-up"></i>
							<?= UOJLocale::get('problems::statistics') ?>
						</a>
					</li>
				<?php elseif (!UOJContest::cur()) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="/problem/<?= $problem['id'] ?>/statistics">
							<i class="bi bi-graph-up"></i>
							<?= UOJLocale::get('problems::statistics') ?>
						</a>
					</li>
				<?php endif ?>
				<?php if (UOJProblem::cur()->userCanManage(Auth::user())) : ?>
					<li class="nav-item text-start">
						<a class="nav-link" href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">
							<i class="bi bi-sliders"></i>
							<?= UOJLocale::get('problems::manage') ?>
						</a>
					</li>
				<?php endif ?>
			</ul>
		</div>

		<!-- 题目信息卡片 -->
		<div class="card mb-2">
			<ul class="list-group list-group-flush">
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<span class="flex-shrink-0">
						<?= UOJLocale::get('problems::uploader') ?>
					</span>
					<span>
						<?= UOJProblem::cur()->getUploaderLink() ?>
					</span>
				</li>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<span class="flex-shrink-0">
						题目来源
					</span>
					<span>
						<?= UOJProblem::cur()->getProviderLink() ?>
					</span>
				</li>
				<?php if (!UOJContest::cur() || UOJContest::cur()->progress() >= CONTEST_FINISHED) : ?>
					<li class="list-group-item d-flex justify-content-between align-items-center">
						<span class="flex-shrink-0">
							<?= UOJLocale::get('problems::difficulty') ?>
						</span>
						<span>
							<?= UOJProblem::cur()->getDifficultyHTML() ?>
						</span>
					</li>
					<?php if (Auth::check()) : ?>
						<li class="list-group-item d-flex justify-content-between align-items-center">
							<span class="flex-shrink-0">
								<?= UOJLocale::get('problems::historical score') ?>
							</span>
							<?php $his_score = DB::selectSingle(["select max(score)", "from submissions", "where", ["problem_id" => UOJProblem::info('id'), "submitter" => Auth::id()]]) ?>

							<a class="<?= is_null($his_score) ? '' : 'uoj-score' ?>" href="<?= HTML::url('/submissions', ['params' => ['problem_id' => UOJProblem::info('id'), 'submitter' => Auth::id()]]) ?>">
								<?= is_null($his_score) ? '无' : $his_score ?>
							</a>
						</li>
					<?php endif ?>
					<li class="list-group-item d-flex justify-content-between align-items-center">
						<span class="flex-shrink-0">
							<?= UOJLocale::get('problems::tags') ?>
						</span>
						<span>
							<?php if (UOJProblem::info('is_hidden')) : ?>
								<a href="<?= HTML::url('/problems', ['params' => ['is_hidden' => 'on']]) ?>">
									<span class="badge text-bg-danger">
										<i class="bi bi-eye-slash-fill"></i>
										<?= UOJLocale::get('hidden') ?>
									</span>
								</a>
							<?php endif ?>
							<?php foreach (UOJProblem::cur()->queryTags() as $tag) : ?>
								<?= HTML::tag(
									'a',
									['class' => 'uoj-problem-tag'],
									HTML::tag('span', ['class' => 'badge bg-secondary'], HTML::escape($tag))
								) ?>
							<?php endforeach ?>
						</span>
					</li>
				<?php endif ?>
				<li class="list-group-item d-flex justify-content-between align-items-center">
					<span class="flex-shrink-0">
						<?= UOJLocale::get('appraisal') ?>
					</span>
					<span>
						<?= UOJProblem::cur()->getZanBlock() ?>
					</span>
				</li>
			</ul>
		</div>

		<!-- 附件 -->
		<div class="card mb-2">
			<div class="card-header fw-bold">附件</div>
			<div class="list-group list-group-flush">
				<?php if (UOJProblem::cur()->userCanDownloadTestData(Auth::user())) : ?>
					<a class="list-group-item list-group-item-action" href="<?= HTML::url(UOJProblem::cur()->getMainDataUri()) ?>">
						<i class="bi bi-hdd-stack"></i>
						测试数据
					</a>
				<?php endif ?>
				<a class="list-group-item list-group-item-action" href="<?= HTML::url(UOJProblem::cur()->getAttachmentUri()) ?>">
					<i class="bi bi-download"></i>
					附件下载
				</a>
				<a class="list-group-item list-group-item-action" href="<?= HTML::url(UOJProblem::cur()->getResourcesBaseUri()) ?>">
					<i class="bi bi-folder2-open"></i>
					相关资源
				</a>
			</div>
		</div>

		<?php
		$sidebar_config = [];
		if (UOJContest::cur() && UOJContest::cur()->progress() <= CONTEST_IN_PROGRESS) {
			$sidebar_config['upcoming_contests_hidden'] = '';
		}

		uojIncludeView('sidebar', $sidebar_config);
		?>
	</aside>
	<!-- end right col -->

</div>

<script>
	$(document).ready(function() {
		// Javascript to enable link to tab
		var hash = location.hash.replace(/^#/, '');
		if (hash) {
			bootstrap.Tab.jQueryInterface.call($('.nav-pills a[href="#' + hash + '"]'), 'show').blur();
		}

		// Change hash for page-reload
		$('.nav-pills a').on('shown.bs.tab', function(e) {
			if (e.target.hash == '#statement') {
				window.location.hash = '';
			} else {
				window.location.hash = e.target.hash;
			}
		});
	});
</script>

<?php echoUOJPageFooter() ?>
