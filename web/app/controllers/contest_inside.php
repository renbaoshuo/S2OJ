<?php
requireLib('mathjax');
requirePHPLib('form');
requirePHPLib('judger');

Auth::check() || redirectToLogin();

UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true, 'check-register' => true]);

$PageContainerClass = 'container';

$contest = UOJContest::info();
$is_manager = UOJContest::cur()->userCanManage(Auth::user());

if (isset($_GET['tab'])) {
	$cur_tab = $_GET['tab'];
} else {
	$cur_tab = 'dashboard';
}

$tabs_info = [
	'dashboard' => [
		'name' => UOJLocale::get('contests::contest dashboard'),
		'url' => "/contest/{$contest['id']}"
	],
	'submissions' => [
		'name' => UOJLocale::get('contests::contest submissions'),
		'url' => "/contest/{$contest['id']}/submissions"
	],
	'standings' => [
		'name' => UOJLocale::get('contests::contest standings'),
		'url' => "/contest/{$contest['id']}/standings"
	],
];

if ($contest['cur_progress'] > CONTEST_TESTING) {
	$tabs_info['after_contest_standings'] = array(
		'name' => UOJLocale::get('contests::after contest standings'),
		'url' => "/contest/{$contest['id']}/after_contest_standings"
	);
	$tabs_info['self_reviews'] = array(
		'name' => UOJLocale::get('contests::contest self reviews'),
		'url' => "/contest/{$contest['id']}/self_reviews"
	);
}

if ($is_manager) {
	$tabs_info['backstage'] = array(
		'name' => UOJLocale::get('contests::contest backstage'),
		'url' => "/contest/{$contest['id']}/backstage"
	);
}

isset($tabs_info[$cur_tab]) || UOJResponse::page404();

if (UOJContest::cur()->userCanStartFinalTest(Auth::user())) {
	if (CONTEST_PENDING_FINAL_TEST == $contest['cur_progress']) {
		$start_test_form = new UOJForm('start_test');
		$start_test_form->handle = function () {
			UOJContest::cur()->finalTest();
		};
		$start_test_form->config['submit_container']['class'] = 'mt-2';
		$start_test_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
		$start_test_form->config['submit_button']['text'] = UOJContest::cur()->labelForFinalTest();
		$start_test_form->config['confirm']['smart'] = true;
		$start_test_form->runAtServer();
	}
	if ($contest['cur_progress'] == CONTEST_TESTING && UOJContest::cur()->queryJudgeProgress()['fully_judged']) {
		$publish_result_form = new UOJForm('publish_result');
		$publish_result_form->handle = function () {
			UOJContest::announceOfficialResults();
		};
		$publish_result_form->config['submit_container']['class'] = 'mt-2';
		$publish_result_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
		$publish_result_form->config['submit_button']['text'] = '公布成绩';
		$publish_result_form->config['confirm']['smart'] = true;
		$publish_result_form->runAtServer();
	}
}

if ($cur_tab == 'dashboard') {
	if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
		$post_question = new UOJForm('post_question');
		$post_question->addTextArea('qcontent', [
			'label' => '问题',
			'validator_php' => function ($content, &$vdata) {
				if (!Auth::check()) {
					return '您尚未登录';
				}
				if (!$content || strlen($content) == 0) {
					return '问题不能为空';
				}
				if (strlen($content) > 140 * 4) {
					return '问题太长';
				}

				$vdata['content'] = HTML::escape($content);

				return '';
			},
		]);
		$post_question->handle = function (&$vdata) use ($contest) {
			DB::insert([
				"insert into contests_asks",
				DB::bracketed_fields(["contest_id", "question", "answer", "username", "post_time", "is_hidden"]),
				"values", DB::tuple([$contest['id'], $vdata['content'], '', Auth::id(), DB::now(), 1])
			]);
		};
		$post_question->runAtServer();
	} else {
		$post_question = null;
	}
} elseif ($cur_tab == 'backstage') {
	if ($is_manager) {
		$post_notice = new UOJForm('post_notice');
		$post_notice->addInput('title', [
			'div_class' => 'mb-3',
			'label' => '标题',
			'validator_php' => function ($title, &$vdata) {
				if (!$title) {
					return '标题不能为空';
				}

				$vdata['title'] = HTML::escape($title);

				return '';
			},
		]);
		$post_notice->addTextArea('content', [
			'label' => '正文',
			'validator_php' => function ($content, &$vdata) {
				if (!$content) {
					return '公告不能为空';
				}

				$vdata['content'] = HTML::escape($content);

				return '';
			},
		]);
		$post_notice->handle = function (&$vdata) use ($contest) {
			DB::insert([
				"insert into contests_notice",
				"(contest_id, title, content, time)",
				"values", DB::tuple([$contest['id'], $vdata['title'], $vdata['content'], DB::now()])
			]);
		};
		$post_notice->runAtServer();
	} else {
		$post_notice = null;
	}

	if ($is_manager) {
		$reply_question = new UOJForm('reply_question');
		$reply_question->addHidden(
			'rid',
			'0',
			function ($id, &$vdata) use ($contest) {
				if (!validateUInt($id)) {
					return '无效ID';
				}
				$q = DB::selectFirst([
					"select * from contests_asks",
					"where", [
						"id" => $id,
						"contest_id" => $contest['id']
					]
				]);
				if (!$q) {
					return '无效ID';
				}
				$vdata['id'] = $id;
				return '';
			},
			null
		);
		$reply_question->addSelect('rtype', [
			'div_class' => 'mb-3',
			'label' => '回复类型',
			'default' => 'private',
			'options' => [
				'public' => '公开（如果该问题反复被不同人提出，或指出了题目中的错误，请选择该项）',
				'private' => '非公开',
				'statement' => '请仔细阅读题面（非公开）',
				'no_comment' => '无可奉告（非公开）',
				'no_play' => '请认真比赛（非公开）',
			],
		]);
		$reply_question->addTextArea('rcontent', [
			'label' => '回复',
			'validator_php' => function ($content, &$vdata) {
				if (!Auth::check()) {
					return '您尚未登录';
				}
				switch ($_POST['rtype']) {
					case 'public':
					case 'private':
						if (strlen($content) == 0) {
							return '回复不能为空';
						}
						break;
				}
				$vdata['content'] = HTML::escape($content);
				return '';
			},
		]);
		$reply_question->handle = function (&$vdata) {
			$content = $vdata['content'];
			$is_hidden = 1;
			switch ($_POST['rtype']) {
				case 'statement':
					$content = '请仔细阅读题面';
					break;
				case 'no_comment':
					$content = '无可奉告 ╮(╯▽╰)╭ ';
					break;
				case 'no_play':
					$content = '请认真比赛 (￣口￣)!!';
					break;
				case 'public':
					$is_hidden = 0;
					break;
				default:
					break;
			}
			DB::update([
				"update contests_asks",
				"set", [
					"answer" => $content,
					"reply_time" => DB::now(),
					"is_hidden" => $is_hidden
				], "where", ["id" => $vdata['id']]
			]);
		};
		$reply_question->runAtServer();
	} else {
		$reply_question = null;
	}
} elseif ($cur_tab == 'standings') {
	$PageContainerClass = 'container-fluid';
} elseif ($cur_tab == 'after_contest_standings') {
	$PageContainerClass = 'container-fluid';
} elseif ($cur_tab == 'self_reviews') {
	$PageContainerClass = 'container-fluid';

	$self_reviews_update_form = new UOJForm('self_reviews_update');
	$self_reviews_update_form->config['ctrl_enter_submit'] = true;
	$self_reviews_update_form->addHidden('self_reviews_update__username', '', function ($username, &$vdata) {
		if (!validateUsername($username)) {
			return '无效用户名';
		}

		if ($username != Auth::id() && !UOJContest::cur()->userCanManage(Auth::user())) {
			return '权限不足';
		}

		return '';
	}, null);

	$contest_problems = array_map(fn ($row) => UOJContestProblem::query($row['problem_id']), DB::selectAll([
		"select problem_id",
		"from", "contests_problems",
		"where", ["contest_id" => $contest['id']],
		"order by level, problem_id"
	]));

	foreach ($contest_problems as $cp) {
		$self_reviews_update_form->addTextArea('self_reviews_update__problem_' . $cp->getLetter(), [
			'div_class' => 'mb-3',
			'label' => $cp->getTitle(['with' => 'letter']),
			'default_value' => '',
			'validator_php' => function ($content) {
				if (strlen($content) > 200) {
					return '长度超过限制';
				}

				return '';
			},
		]);
	}

	$self_reviews_update_form->addTextArea('self_reviews_update__overall', [
		'label' => '比赛总结',
		'validator_php' => function ($content) {
			if (strlen($content) > 300) {
				return '长度超过限制';
			}

			return '';
		},
	]);

	$self_reviews_update_form->handle = function (&$vdata) use ($contest_problems) {
		foreach ($contest_problems as $cp) {
			DB::update([
				"replace into contests_reviews",
				DB::bracketed_fields([
					"contest_id",
					"problem_id",
					"poster",
					"content"
				]),
				"values", DB::tuple([
					UOJContest::info('id'),
					$cp->info['id'],
					$_POST['self_reviews_update__username'],
					$_POST['self_reviews_update__problem_' . $cp->getLetter()],
				]),
			]);
		}

		DB::update([
			"replace into contests_reviews",
			"(contest_id, problem_id, poster, content)",
			"values", DB::tuple([
				UOJContest::info('id'),
				-1,
				$_POST['self_reviews_update__username'],
				$_POST['self_reviews_update__overall'],
			]),
		]);
	};

	$self_reviews_update_form->runAtServer();
}

function echoDashboard() {
	global $contest, $post_question;

	$contest_problems = DB::selectAll([
		"select contests_problems.problem_id, best_ac_submissions.submission_id",
		"from", "contests_problems", "left join", "best_ac_submissions",
		"on", [
			"contests_problems.problem_id" => DB::raw("best_ac_submissions.problem_id"),
			"best_ac_submissions.submitter" => Auth::id()
		], "where", ["contest_id" => $contest['id']],
		"order by contests_problems.level, contests_problems.problem_id"
	]);

	for ($i = 0; $i < count($contest_problems); $i++) {
		$contest_problems[$i]['problem'] = UOJContestProblem::query($contest_problems[$i]['problem_id']);
		$contest_problems[$i]['problem']->problem_number = $i;
	}

	$contest_notice = DB::selectAll([
		"select * from contests_notice",
		"where", ["contest_id" => $contest['id']],
		"order by time desc"
	]);

	if (Auth::check()) {
		$my_questions = DB::selectAll([
			"select * from contests_asks",
			"where", [
				"contest_id" => $contest['id'],
				"username" => Auth::id()
			], "order by post_time desc"
		]);
		$my_questions_pag = new Paginator([
			'data' => $my_questions
		]);
	} else {
		$my_questions_pag = null;
	}

	$others_questions_pag = new Paginator([
		'col_names' => ['*'],
		'table_name' => 'contests_asks',
		'cond' => [
			"contest_id" => $contest['id'],
			["username", "!=", Auth::id()],
			"is_hidden" => 0
		],
		'tail' => 'order by reply_time desc',
		'page_len' => 10
	]);

	uojIncludeView('contest-dashboard', [
		'contest' => $contest,
		'contest_notice' => $contest_notice,
		'contest_problems' => $contest_problems,
		'post_question' => $post_question,
		'my_questions_pag' => $my_questions_pag,
		'others_questions_pag' => $others_questions_pag
	]);
}

function echoBackstage() {
	global $contest, $post_notice, $reply_question;

	$questions_pag = new Paginator([
		'col_names' => ['*'],
		'table_name' => 'contests_asks',
		'cond' => ["contest_id" => $contest['id']],
		'tail' => 'order by post_time desc',
		'page_len' => 50
	]);

	if (UOJContest::cur()->managerCanSeeFinalStandingsTab(Auth::user())) {
		$contest_data = queryContestData($contest, ['pre_final' => true]);
		calcStandings($contest, $contest_data, $score, $standings);

		$standings_data = [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data
		];
	} else {
		$standings_data = null;
	}

	uojIncludeView('contest-backstage', [
		'contest' => $contest,
		'post_notice' => $post_notice,
		'reply_question' => $reply_question,
		'questions_pag' => $questions_pag,
		'standings_data' => $standings_data
	]);
}

function echoMySubmissions() {
	$problems = UOJContest::cur()->getProblemIDs();

	$options = [];
	$options[] = ['value' => 'all', 'text' => '所有题目'];
	for ($i = 0; $i < count($problems); $i++) {
		$problem = UOJContestProblem::query($problems[$i]);
		$problem->problem_number = $i;
		$options[] = [
			'value' => $problem->getLetter(),
			'text' => $problem->getTitle(['with' => 'letter', 'simplify' => true]),
		];
	}

	$chosen = UOJRequest::get('p');
	$problem_id = null;
	if (strlen($chosen) == 1) {
		$num = ord($chosen) - ord('A');
		if (0 <= $num && $num < count($problems)) {
			$problem_id = $problems[$num];
		} else {
			$chosen = 'all';
		}
	} else {
		$chosen = 'all';
	}

	$conds = ['contest_id' => UOJContest::info('id')];
	if (Cookie::get('show_all_submissions') === null) {
		$conds += ['submitter' => Auth::id()];
	}
	if ($problem_id !== null) {
		$conds += ['problem_id' => $problem_id];
	}

	uojIncludeView('contest-submissions', [
		'show_all_submissions_status' => Cookie::get('show_all_submissions') !== null,
		'options' => $options,
		'chosen' => $chosen,
		'conds' => $conds,
	]);
}

function echoStandings($is_after_contest_query = false) {
	global $contest;

	uojIncludeView('contest-standings', [
		'contest' => $contest,
		'after_contest' => $is_after_contest_query,
	] + UOJContest::cur()->queryResult([
		'after_contest' => $is_after_contest_query,
	]));
}

function echoSelfReviews() {
	global $contest, $self_reviews_update_form;

	uojIncludeView('contest-reviews', [
		'contest' => $contest,
		'self_reviews_update_form' => $self_reviews_update_form,
	] + UOJContest::cur()->queryResult());
}
?>

<?php

echoUOJPageHeader($tabs_info[$cur_tab]['name'] . ' - ' . UOJContest::info('name') . ' - ' . UOJLocale::get('contests::contest'), [
	'PageContainerClass' => $PageContainerClass,
]);

?>

<div class="text-center d-md-none mb-3">
	<h1><?= $contest['name'] ?></h1>
	<div class="small text-muted">
		<?php if ($contest['cur_progress'] <= CONTEST_NOT_STARTED) : ?>
			<?= UOJLocale::get('contests::not started') ?>
		<?php elseif ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) : ?>
			<?= UOJLocale::get('contests::in progress') ?>
		<?php elseif ($contest['cur_progress'] <= CONTEST_PENDING_FINAL_TEST) : ?>
			<?= UOJLocale::get('contests::pending final test') ?>
		<?php elseif ($contest['cur_progress'] <= CONTEST_TESTING) : ?>
			<?= UOJLocale::get('contests::final testing') ?>
		<?php else : ?>
			<?= UOJLocale::get('contests::ended') ?>
		<?php endif ?>
	</div>
</div>


<div class="row">
	<div <?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews') : ?> class="col-12" <?php else : ?> class="col-md-9" <?php endif ?>>
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills container uoj-contest-nav') ?>
		<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews') : ?>
			<div class="d-none d-md-block text-center">
				<h1 class="mt-2 mb-3"><?= $contest['name'] ?></h1>
				<div class="small text-muted">
					<?php if ($contest['cur_progress'] <= CONTEST_NOT_STARTED) : ?>
						<?= UOJLocale::get('contests::not started') ?>
					<?php elseif ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) : ?>
						<?= UOJLocale::get('contests::in progress') ?>
					<?php elseif ($contest['cur_progress'] <= CONTEST_PENDING_FINAL_TEST) : ?>
						<?= UOJLocale::get('contests::pending final test') ?>
					<?php elseif ($contest['cur_progress'] <= CONTEST_TESTING) : ?>
						<?= UOJLocale::get('contests::final testing') ?>
					<?php else : ?>
						<?= UOJLocale::get('contests::ended') ?>
					<?php endif ?>
				</div>
			</div>
		<?php endif ?>
		<div class="mt-3">
			<?php
			if ($cur_tab == 'dashboard') {
				echoDashboard();
			} elseif ($cur_tab == 'submissions') {
				echoMySubmissions();
			} elseif ($cur_tab == 'standings') {
				echoStandings();
			} elseif ($cur_tab == 'after_contest_standings') {
				echoStandings(true);
			} elseif ($cur_tab == 'backstage') {
				echoBackstage();
			} elseif ($cur_tab == 'self_reviews') {
				echoSelfReviews();
			}
			?>
		</div>
	</div>

	<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews') : ?>
	<?php else : ?>
		<hr class="d-md-none" />

		<div class="col-md-3">
			<?= UOJContest::cur()->getContestCard() ?>

			<?php if (UOJContest::cur()->basicRule() === 'OI') : ?>
				<p>此次比赛为 OI 赛制。</p>
				<p><strong>注意：比赛时只显示测样例的结果。</strong></p>
			<?php elseif (UOJContest::cur()->basicRule() === 'ACM') : ?>
				<p>此次比赛为 ACM 赛制。</p>
				<p><strong>封榜时间：<?= $contest['frozen_time']->format(UOJTime::FORMAT) ?></strong></p>
			<?php elseif (UOJContest::cur()->basicRule() === 'IOI') : ?>
				<p>此次比赛为 IOI 赛制。</p>
				<p>比赛时显示的得分即最终得分。</p>
			<?php endif ?>

			<a href="<?= UOJContest::cur()->getUri('/registrants') ?>" class="btn btn-secondary d-block mt-2">
				<?= UOJLocale::get('contests::contest registrants') ?>
			</a>
			<?php if ($is_manager) : ?>
				<a href="<?= UOJContest::cur()->getUri('/manage') ?>" class="btn btn-primary d-block mt-2">
					管理
				</a>
			<?php endif ?>
			<?php if (isset($start_test_form)) : ?>
				<div class="mt-2">
					<?php $start_test_form->printHTML(); ?>
				</div>
			<?php endif ?>
			<?php if (isset($publish_result_form)) : ?>
				<div class="mt-2">
					<?php $publish_result_form->printHTML(); ?>
				</div>
			<?php endif ?>

			<!-- 附件 -->
			<div class="card mt-3">
				<div class="card-header fw-bold">
					<?= UOJLocale::get('contests::links') ?>
				</div>
				<div class="list-group list-group-flush">
					<a class="list-group-item list-group-item-action" href="<?= HTML::url(UOJContest::cur()->getResourcesBaseUri()) ?>">
						<?= UOJLocale::get('contests::resources') ?>
					</a>

					<?php foreach (UOJContest::cur()->getAdditionalLinks() as $link) : ?>
						<a class="list-group-item list-group-item-action" href="<?= $link['url'] ?>">
							<?= HTML::escape($link['name']) ?>
						</a>
					<?php endforeach ?>
				</div>
			</div>
		</div>
	<?php endif ?>
</div>

<?php echoUOJPageFooter() ?>
