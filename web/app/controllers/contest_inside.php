<?php
requireLib('bootstrap5');
requireLib('mathjax');
requirePHPLib('form');
requirePHPLib('judger');

Auth::check() || redirectToLogin();

UOJContest::init(UOJRequest::get('id')) || UOJResponse::page404();
UOJContest::cur()->userCanView(Auth::user(), ['ensure' => true, 'check-register' => true]);

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
		$start_test_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
		$start_test_form->config['submit_button']['text'] = UOJContest::cur()->labelForFinalTest();
		$start_test_form->config['confirm']['smart'] = true;
		$start_test_form->runAtServer();
	}
	if ($contest['cur_progress'] >= CONTEST_TESTING && UOJContest::cur()->queryJudgeProgress()['fully_judged']) {
		$publish_result_form = new UOJForm('publish_result');
		$publish_result_form->handle = function () {
			UOJContest::announceOfficialResults();
		};
		$publish_result_form->config['submit_button']['class'] = 'btn btn-danger d-block w-100';
		$publish_result_form->config['submit_button']['text'] = '公布成绩';
		$publish_result_form->config['confirm']['smart'] = true;
		$publish_result_form->runAtServer();
	}
}

if ($cur_tab == 'dashboard') {
	if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
		$post_question = new UOJBs4Form('post_question');
		$post_question->addVTextArea(
			'qcontent',
			'问题',
			'',
			function ($content, &$vdata) {
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
			null
		);
		$post_question->handle = function (&$vdata) use ($contest) {
			DB::insert([
				"insert into contests_asks",
				"(contest_id, question, answer, username, post_time, is_hidden)",
				"values", DB::tuple([$contest['id'], $vdata['content'], '', Auth::id(), DB::now(), 1])
			]);
		};
		$post_question->runAtServer();
	} else {
		$post_question = null;
	}
} elseif ($cur_tab == 'backstage') {
	if ($is_manager) {
		$post_notice = new UOJBs4Form('post_notice');
		$post_notice->addInput(
			'title',
			'text',
			'标题',
			'',
			function ($title, &$vdata) {
				if (!$title) {
					return '标题不能为空';
				}

				$vdata['title'] = HTML::escape($title);

				return '';
			},
			null
		);
		$post_notice->addTextArea(
			'content',
			'正文',
			'',
			function ($content, &$vdata) {
				if (!$content) {
					return '公告不能为空';
				}

				$vdata['content'] = HTML::escape($content);

				return '';
			},
			null
		);
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
		$reply_question = new UOJBs4Form('reply_question');
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
		$reply_question->addVSelect('rtype', [
			'public' => '公开（如果该问题反复被不同人提出，或指出了题目中的错误，请选择该项）',
			'private' => '非公开',
			'statement' => '请仔细阅读题面（非公开）',
			'no_comment' => '无可奉告（非公开）',
			'no_play' => '请认真比赛（非公开）',
		], '回复类型', 'private');
		$reply_question->addVTextArea(
			'rcontent',
			'回复',
			'',
			function ($content, &$vdata) {
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
			null
		);
		$reply_question->handle = function (&$vdata) use ($contest) {
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
} elseif ($cur_tab == 'self_reviews') {
	if (UOJContest::cur()->userHasMarkedParticipated(Auth::user())) {
		$self_reviews_update_form = new UOJBs4Form('self_review_update');
		$self_reviews_update_form->ctrl_enter_submit = true;

		$contest_problems = DB::selectAll([
			"select problem_id",
			"from", "contests_problems",
			"where", ["contest_id" => $contest['id']],
			"order by level, problem_id"
		]);
		for ($i = 0; $i < count($contest_problems); $i++) {
			$contest_problems[$i]['problem'] = UOJContestProblem::query($contest_problems[$i]['problem_id']);
		}

		for ($i = 0; $i < count($contest_problems); $i++) {
			$content = DB::selectSingle([
				"select content",
				"from", "contests_reviews",
				"where", [
					"contest_id" => $contest['id'],
					"problem_id" => $contest_problems[$i]['problem_id'],
					"poster" => Auth::id(),
				],
			]);
			$self_reviews_update_form->addVTextArea(
				'self_review_update__problem_' . $contest_problems[$i]['problem']->getLetter(),
				'<b>' . $contest_problems[$i]['problem']->getLetter() . '</b>: ' . $contest_problems[$i]['problem']->info['title'],
				$content,
				function ($content) {
					if (strlen($content) > 200) {
						return '总结不能超过200字';
					}

					return '';
				},
				null,
				true
			);
		}

		$content = DB::selectSingle([
			"select content",
			"from", "contests_reviews",
			"where", [
				"contest_id" => $contest['id'],
				"problem_id" => -1,
				"poster" => Auth::id(),
			],
		]);
		$self_reviews_update_form->addVTextArea(
			'self_review_update__overall',
			'比赛总结',
			$content,
			function ($content) {
				if (strlen($content) > 200) {
					return '总结不能超过200字';
				}

				return '';
			},
			null,
			true
		);

		$self_reviews_update_form->handle = function () use ($contest, $contest_problems) {
			for ($i = 0; $i < count($contest_problems); $i++) {
				if (isset($_POST['self_review_update__problem_' . $contest_problems[$i]['problem']->getLetter()])) {
					DB::query([
						"replace into contests_reviews",
						"(contest_id, problem_id, poster, content)",
						"values", DB::tuple([
							$contest['id'],
							$contest_problems[$i]['problem_id'],
							Auth::id(),
							$_POST['self_review_update__problem_' . $contest_problems[$i]['problem']->getLetter()],
						]),
					]);
				}
			}

			if (isset($_POST['self_review_update__overall'])) {
				DB::query([
					"replace into contests_reviews",
					"(contest_id, problem_id, poster, content)",
					"values", DB::tuple([
						$contest['id'],
						-1,
						Auth::id(),
						$_POST['self_review_update__overall'],
					]),
				]);
			}
		};

		$self_reviews_update_form->runAtServer();
	}
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
		$letter = chr(ord('A') + $i);
		$options[] = ['value' => $letter, 'text' => "{$letter} 题"];
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
		'conds' => $conds
	]);
}

function echoStandings($is_after_contest_query = false) {
	global $contest;
	uojIncludeView('contest-standings', ['contest' => $contest] + UOJContest::cur()->queryResult(['after_contest' => $is_after_contest_query]));
}

function echoSelfReviews() {
	global $contest;

	uojIncludeView('contest-reviews', ['contest' => $contest] + UOJContest::cur()->queryResult());
}
?>
<?php echoUOJPageHeader($tabs_info[$cur_tab]['name'] . ' - ' . UOJContest::info('name') . ' - ' . UOJLocale::get('contests::contest')) ?>

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
	<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews') : ?>
		<div class="col-12">
		<?php else : ?>
			<div class="col-md-9">
			<?php endif ?>
			<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
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

			<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings') : ?>
			<?php elseif ($cur_tab == 'self_reviews') : ?>
				<?php if (isset($self_reviews_update_form)) : ?>
					<hr />

					<div class="col-md-6">
						<h4>修改我的赛后总结</h4>
						<div class="small">赛后总结支持 Markdown 语法。</div>
						<?php $self_reviews_update_form->printHTML(); ?>
					</div>
				<?php endif ?>
			<?php else : ?>
				<div class="d-md-none">
					<hr />
				</div>
				<div class="col-md-3">
					<div class="card card-default mb-2">
						<div class="card-body">
							<h3 class="h4 card-title text-center">
								<a class="text-decoration-none text-body" href="/contest/<?= $contest['id'] ?>">
									<?= $contest['name'] ?>
								</a>
							</h3>
							<div class="card-text text-center text-muted">
								<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) : ?>
									<span id="contest-countdown"></span>
									<script type="text/javascript">
										$('#contest-countdown').countdown(<?= $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function() {}, '1.75rem', false);
									</script>
								<?php elseif ($contest['cur_progress'] <= CONTEST_TESTING) : ?>
									<?php if ($contest['cur_progress'] < CONTEST_TESTING) : ?>
										<?= UOJLocale::get('contests::contest pending final test') ?>
									<?php else : ?>
										<?php
										$total = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']}");
										$n_judged = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']} and status = 'Judged'");
										$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
										?>
										<?= UOJLocale::get('contests::final testing') ?>
										(<?= $rop ?>%)
									<?php endif ?>
								<?php else : ?>
									<?= UOJLocale::get('contests::contest ended') ?>
								<?php endif ?>
							</div>
						</div>
						<div class="card-footer bg-transparent">
							比赛评价：<?= UOJContest::cur()->getZanBlock() ?>
						</div>
					</div>

					<?php if (UOJContest::cur()->basicRule() === 'OI') : ?>
						<p>此次比赛为 OI 赛制。</p>
						<p><strong>注意：比赛时只显示测样例的结果。</strong></p>
					<?php elseif (UOJContest::cur()->basicRule() === 'ACM') : ?>
						<p>此次比赛为 ACM 赛制。</p>
						<p><strong>封榜时间：<?= $contest['frozen_time']->format('Y-m-d H:i:s') ?></strong></p>
					<?php elseif (UOJContest::cur()->basicRule() === 'IOI') : ?>
						<p>此次比赛为 IOI 赛制。</p>
						<p>比赛时显示的得分即最终得分。</p>
					<?php endif ?>

					<a href="/contest/<?= $contest['id'] ?>/registrants" class="btn btn-secondary d-block mt-2">
						<?= UOJLocale::get('contests::contest registrants') ?>
					</a>
					<?php if ($is_manager) : ?>
						<a href="/contest/<?= $contest['id'] ?>/manage" class="btn btn-primary d-block mt-2">
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
					<?php if ($contest['extra_config']['links']) : ?>
						<div class="card card-default border-info mt-3">
							<div class="card-header bg-info">
								<h3 class="card-title">比赛资料</h3>
							</div>
							<div class="list-group list-group-flush">
								<?php foreach ($contest['extra_config']['links'] as $link) : ?>
									<a href="/blogs/<?= $link[1] ?>" class="list-group-item"><?= $link[0] ?></a>
								<?php endforeach ?>
							</div>
						</div>
					<?php endif ?>
				</div>
			<?php endif ?>
		</div>

		<?php echoUOJPageFooter() ?>
