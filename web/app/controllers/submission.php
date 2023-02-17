<?php
requireLib('hljs');
requirePHPLib('form');
requirePHPLib('judger');

Auth::check() || redirectToLogin();
UOJSubmission::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJSubmission::initProblemAndContest() || UOJResponse::page404();
UOJSubmission::cur()->userCanView(Auth::user(), ['ensure' => true]);

$perm = UOJSubmission::cur()->viewerCanSeeComponents(Auth::user());

$can_see_minor = false;
if ($perm['score']) {
	$can_see_minor = UOJSubmission::cur()->userCanSeeMinorVersions(Auth::user());
	UOJSubmissionHistory::init(UOJSubmission::cur(), ['minor' => $can_see_minor]) || UOJResponse::page404();
	if (isset($_GET['time'])) {
		$history_time = UOJRequest::get('time', 'is_short_string');
		!empty($history_time) || UOJResponse::page404();
		UOJSubmission::cur()->loadHistoryByTime($history_time) || UOJResponse::page404();
		UOJSubmission::cur()->isMajor() || UOJResponse::page404();
	} elseif (isset($_GET['tid'])) {
		$can_see_minor || UOJResponse::page404();
		UOJSubmission::cur()->loadHistoryByTID(UOJRequest::get('tid', 'validateUInt')) || UOJResponse::page404();
		!UOJSubmission::cur()->isMajor() || UOJResponse::page404();
	}
}

$submission = UOJSubmission::info();
$submission_result = UOJSubmission::cur()->getResult();
$problem = UOJProblem::info();

if ($can_see_minor) {
	$minor_rejudge_form = new UOJForm('minor_rejudge');
	$minor_rejudge_form->handle = function () {
		UOJSubmission::rejudgeById(UOJSubmission::info('id'), [
			'reason_text' => '管理员偷偷重测该提交记录',
			'major' => false
		]);
		$tid = DB::insert_id();
		redirectTo(UOJSubmission::cur()->getUriForNewTID($tid));
	};
	$minor_rejudge_form->config['submit_button']['class'] = 'list-group-item list-group-item-action border-start-0 border-end-0 list-group-item-secondary';
	$minor_rejudge_form->config['submit_button']['text'] = '偷偷重新测试';
	$minor_rejudge_form->config['submit_container']['class'] = '';
	$minor_rejudge_form->runAtServer();
}

if (UOJSubmission::cur()->isLatest()) {
	if (UOJSubmission::cur()->preHackCheck() && ($perm['content'] || $perm['manager_view'])) {
		$hack_form = new UOJForm('hack');
		$hack_form->addTextFileInput('input', ['filename' => 'input.txt']);
		$hack_form->addCheckBox('use_formatter', [
			'label' => '帮我整理文末回车、行末空格、换行符',
			'checked' => true,
		]);
		$hack_form->handle = function (&$vdata) {
			global $problem, $submission;
			Auth::check() || redirectToLogin();

			if ($_POST["input_upload_type"] == 'file') {
				$tmp_name = UOJForm::uploadedFileTmpName("input_file");
				if ($tmp_name == null) {
					UOJResponse::message('你在干啥……怎么什么都没交过来……？');
				}
			}

			$fileName = FS::randomAvailableTmpFileName();
			$fileFullName = UOJContext::storagePath() . $fileName;
			if ($_POST["input_upload_type"] == 'editor') {
				file_put_contents($fileFullName, $_POST['input_editor']);
			} else {
				move_uploaded_file($_FILES["input_file"]['tmp_name'], $fileFullName);
			}
			$input_type = isset($_POST['use_formatter']) ? "USE_FORMATTER" : "DONT_USE_FORMATTER";
			DB::insert([
				"insert into hacks",
				"(problem_id, submission_id, hacker, owner, input, input_type, submit_time, status, details, is_hidden)",
				"values", DB::tuple([
					$problem['id'], $submission['id'], Auth::id(), $submission['submitter'],
					$fileName, $input_type, DB::now(), 'Waiting', '', $problem['is_hidden']
				])
			]);
		};
		$hack_form->config['max_post_size'] = 25 * 1024 * 1024;
		$hack_form->config['max_file_size_mb'] = 25;
		$hack_form->succ_href = "/hacks";
		$hack_form->runAtServer();
	}

	if (UOJSubmission::cur()->userCanRejudge(Auth::user())) {
		$rejudge_form = new UOJForm('rejudge');
		$rejudge_form->handle = function () {
			UOJSubmission::rejudgeById(UOJSubmission::info('id'));
		};
		$rejudge_form->config['submit_button']['class'] = 'list-group-item list-group-item-action border-start-0 border-end-0 list-group-item-secondary';
		$rejudge_form->config['submit_button']['text'] = '重新测试';
		$rejudge_form->config['submit_container']['class'] = '';
		$rejudge_form->runAtServer();
	}

	if (UOJSubmission::cur()->userCanDelete(Auth::user())) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function () {
			UOJSubmission::cur()->delete();
		};
		$delete_form->config['submit_button']['class'] = 'list-group-item list-group-item-action border-start-0 border-end-0 list-group-item-danger';
		$delete_form->config['submit_button']['text'] = '删除此提交记录';
		$delete_form->config['submit_container']['class'] = '';
		$delete_form->config['confirm']['text'] = '你真的要删除这条提交记录吗？';
		$delete_form->succ_href = "/submissions";
		$delete_form->runAtServer();
	}
} else {
	if (UOJSubmission::cur()->userCanDelete(Auth::user()) && !UOJSubmission::cur()->isMajor()) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function () {
			UOJSubmission::cur()->deleteThisMinorVersion();
		};
		$delete_form->config['submit_button']['class'] = 'list-group-item list-group-item-action border-start-0 border-end-0 list-group-item-danger';
		$delete_form->config['submit_button']['text'] = '删除当前历史记录（保留其他历史记录）';
		$delete_form->config['submit_container']['class'] = '';
		$delete_form->config['confirm']['text'] = '你真的要删除这条历史记录吗？删除这条历史记录不会影响其他的历史记录。';
		$delete_form->succ_href = UOJSubmission::cur()->getUriForLatest();
		$delete_form->runAtServer();
	}
}

$tabs = [];

if (UOJSubmission::cur()->hasJudged()) {
	if ($perm['high_level_details']) {
		$tabs['details'] = [
			'name' => '详细信息',
			'card_body' => false,
			'displayer' => function () use ($perm, $submission_result) {
				echo '<div class="card-body p-0">';
				$styler = new SubmissionDetailsStyler();
				if (!$perm['low_level_details']) {
					$styler->fade_all_details = true;
					$styler->show_small_tip = false;
				}
				echoJudgmentDetails($submission_result['details'], $styler, 'details');
				echo '</div>';
			}
		];

		if ($perm['manager_view'] && !$perm['low_level_details']) {
			$tabs['all-details'] = [
				'name' => '详细信息（管理员）',
				'displayer' => function () use ($submission_result) {
					echo '<div class="card-body p-0">';
					echoSubmissionDetails($submission_result['details'], 'all_details');
					echo '</div>';
				},
				'card_body' => false,
			];
		}
	} else if ($perm['manager_view']) {
		$tabs['manager-details'] = [
			'name' => '详细信息（管理员）',
			'displayer' => function () use ($submission_result) {
				echo '<div class="card-body p-0">';
				echoSubmissionDetails($submission_result['details'], 'details');
				echo '</div>';
			},
			'card_body' => false,
		];
	} else {
		// TODO: 您当前无法查看详细信息
	}

	if ($perm['manager_view'] && isset($submission_result['final_result'])) {
		$tabs['final-details'] = [
			'name' => '终测结果预测（管理员）',
			'displayer' => function () use ($submission_result) {
				echo '<div class="card-body p-0">';
				echoSubmissionDetails($submission_result['final_result']['details'], 'final_details');
				echo '</div>';
			},
			'card_body' => false,
		];
	}
} else {
	// TODO: move judge_status from UOJSubmission::echoStatusCard() to here
}

if ($perm['content'] || $perm['manager_view']) {
	$tabs['source'] = [
		'name' => '源代码',
		'displayer' => function () {
			echo '<div class="list-group list-group-flush">';
			UOJSubmission::cur()->echoContent(['list_group' => true]);
			echo '</div>';
		},
		'card_body' => false,
	];
}

if ($perm['manager_view']) {
	$tabs['judger'] = [
		'name' => '测评机信息',
		'displayer' => function () {
			if (empty(UOJSubmission::info('judger'))) {
				echo '暂无';
			} else {
				$judger = DB::selectFirst([
					"select * from judger_info",
					"where", [
						"judger_name" => UOJSubmission::info('judger')
					]
				]);
				if (!$judger) {
					echo '测评机信息损坏';
				} else {
					echo '<strong>', $judger['display_name'], ': </strong>', $judger['description'];
				}
			}
		},
	];
}

if (isset($hack_form)) {
	$tabs['hack'] = [
		'name' => 'Hack!',
		'displayer' => function () use (&$hack_form) {
			echo <<<EOD
				<div class="small text-danger mb-3">
					Hack 功能是给大家互相查错用的。请勿故意提交错误代码，然后自己 Hack 自己、贼喊捉贼哦（故意贼喊捉贼会予以封禁处理）。
				</div>
			EOD;
			$hack_form->printHTML();
		},
	];
}
?>

<?php echoUOJPageHeader(UOJLocale::get('problems::submission') . ' #' . $submission['id'], [
	'PageContainerClass' => 'container-xxl',
]) ?>

<h1>
	<?= UOJLocale::get('problems::submission') . ' #' . $submission['id'] ?>
</h1>

<style>
	.submission-layout {
		/* display: grid; */
		grid-template-columns: minmax(0, calc(100% - 25% - var(--bs-gutter-x))) auto;
		grid-template-rows: auto 1fr;
	}

	.submission-left-col {
		grid-column: 1;
		grid-row: 1 / span 2;
	}

	.submission-right-col {
		grid-column: 2;
		grid-row: 1;
	}

	.submission-right-control-panel {
		grid-column: 2;
		grid-row: 2;
	}
</style>

<div class="row mt-3 submission-layout d-md-grid">
	<div class="submission-right-col">
		<?php UOJSubmission::cur()->echoStatusCard(['show_actual_score' => $perm['score'], 'id_hidden' => true], Auth::user()) ?>
	</div>

	<div class="submission-left-col">
		<?php
		if ($perm['score']) {
			HTML::echoPanel('mb-3', '测评历史', function () {
				UOJSubmissionHistory::cur()->echoTimeline();
			});
		}
		?>

		<div class="card mb-3">
			<div class="card-header">
				<ul class="nav nav-tabs card-header-tabs" role="tablist">
					<?php $idx = 0; ?>
					<?php foreach ($tabs as $id => $tab) : ?>
						<li class="nav-item">
							<a class="nav-link <?php if ($idx++ == 0) : ?>active<?php endif ?>" href="#<?= $id ?>" data-bs-toggle="tab" data-bs-target="#<?= $id ?>">
								<?= $tab['name'] ?>
							</a>
						</li>
					<?php endforeach ?>
				</ul>
			</div>

			<div class="tab-content">
				<?php $idx = 0; ?>
				<?php foreach ($tabs as $id => $tab) : ?>
					<div class="tab-pane fade <?php if ($idx++ == 0) : ?>active show<?php endif ?>" id="<?= $id ?>" role="tabpanel">
						<?php if (!isset($tab['card_body']) || $tab['card_body']) : ?>
							<div class="card-body">
							<?php endif ?>

							<?php $tab['displayer']() ?>

							<?php if (!isset($tab['card_body']) || $tab['card_body']) : ?>
							</div>
						<?php endif ?>
					</div>
				<?php endforeach ?>
			</div>
		</div>
	</div>

	<div class="submission-right-control-panel">

		<?php if (
			isset($minor_rejudge_form) ||
			isset($rejudge_form) ||
			isset($delete_form)
		) : ?>
			<div class="card mb-3">
				<div class="card-header fw-bold">
					操作
				</div>

				<div class="list-group list-group-flush">
					<?php if (isset($minor_rejudge_form)) : ?>
						<?php $minor_rejudge_form->printHTML() ?>
					<?php endif ?>

					<?php if (isset($rejudge_form)) : ?>
						<?php $rejudge_form->printHTML() ?>
					<?php endif ?>

					<?php if (isset($delete_form)) : ?>
						<?php $delete_form->printHTML() ?>
					<?php endif ?>
				</div>
			</div>
		<?php endif ?>
	</div>
</div>


<?php echoUOJPageFooter() ?>
