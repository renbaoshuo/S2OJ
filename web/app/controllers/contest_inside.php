<?php
	requirePHPLib('form');

	$REQUIRE_LIB['bootstrap5'] = '';
	$REQUIRE_LIB['mathjax'] = '';

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}
	
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}
	genMoreContestInfo($contest);

	if (!hasContestPermission(Auth::user(), $contest)) {
		if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
			header("Location: /contest/{$contest['id']}/register");
			die();
		} elseif ($contest['cur_progress'] == CONTEST_IN_PROGRESS) {
			if ($myUser == null || !hasRegistered(Auth::user(), $contest)) {
				becomeMsgPage("<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。比赛结束后再来看吧～</p>");
			}
		} else {
			if (!isNormalUser($myUser)) {
				become403Page();
			}
		}
	}
	
	if (isset($_GET['tab'])) {
		$cur_tab = $_GET['tab'];
	} else {
		$cur_tab = 'dashboard';
	}
	
	$tabs_info = array(
		'dashboard' => array(
			'name' => UOJLocale::get('contests::contest dashboard'),
			'url' => "/contest/{$contest['id']}"
		),
		'submissions' => array(
			'name' => UOJLocale::get('contests::contest submissions'),
			'url' => "/contest/{$contest['id']}/submissions"
		),
		'standings' => array(
			'name' => UOJLocale::get('contests::contest standings'),
			'url' => "/contest/{$contest['id']}/standings"
		),
	);

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
	
	if (hasContestPermission(Auth::user(), $contest)) {
		$tabs_info['backstage'] = array(
			'name' => UOJLocale::get('contests::contest backstage'),
			'url' => "/contest/{$contest['id']}/backstage"
		);
	}
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
	
	if (isset($_POST['check_notice'])) {
		$result = DB::query("select * from contests_notice where contest_id = '${contest['id']}' order by time desc limit 10");
		$ch = array();
		$flag = false;
		try {
			while ($row = DB::fetch($result)) {
				if (new DateTime($row['time']) > new DateTime($_POST['last_time'])) {
					$ch[] = $row['title'].': '.$row['content'];
				}
			}
		} catch (Exception $e) {
		}
		global $myUser;
		$result = DB::query("select * from contests_asks where contest_id='${contest['id']}' and username='${myUser['username']}' order by reply_time desc limit 10");
		try {
			while ($row = DB::fetch($result)) {
				if (new DateTime($row['reply_time']) > new DateTime($_POST['last_time'])) {
					$ch[] = $row['question'].': '.$row['answer'];
				}
			}
		} catch (Exception $e) {
		}
		if ($ch) {
			die(json_encode(array('msg' => $ch, 'time' => UOJTime::$time_now_str)));
		} else {
			die(json_encode(array('time' => UOJTime::$time_now_str)));
		}
	}
	
	if (isSuperUser($myUser) || isContestJudger($myUser)) {
		if (CONTEST_PENDING_FINAL_TEST <= $contest['cur_progress']) {
			$start_test_form = new UOJForm('start_test');
			$start_test_form->handle = function() {
				global $contest;
				$result = DB::query("select id, problem_id, content from submissions where contest_id = {$contest['id']}");
				while ($submission = DB::fetch($result, MYSQLI_ASSOC)) {
					if (!isset($contest['extra_config']["problem_{$submission['problem_id']}"])) {
						$content = json_decode($submission['content'], true);
						if (isset($content['final_test_config'])) {
							$content['config'] = $content['final_test_config'];
							unset($content['final_test_config']);
						}
						if (isset($content['first_test_config'])) {
							unset($content['first_test_config']);
						}
						$esc_content = DB::escape(json_encode($content));
						DB::update("update submissions set judge_time = NULL, result = '', score = NULL, status = 'Waiting Rejudge', content = '$esc_content' where id = {$submission['id']}");
					}
				}
				DB::query("update contests set status = 'testing' where id = {$contest['id']}");
			};
			$start_test_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
			$start_test_form->submit_button_config['smart_confirm'] = '';
			if ($contest['cur_progress'] < CONTEST_TESTING) {
				$start_test_form->submit_button_config['text'] = '开始最终测试';
			} else {
				$start_test_form->submit_button_config['text'] = '重新开始最终测试';
			}

			$start_test_form->runAtServer();
		}
		if ($contest['cur_progress'] >= CONTEST_TESTING) {
			$publish_result_form = new UOJForm('publish_result');
			$publish_result_form->handle = function() {
				// time config
				set_time_limit(0);
				ignore_user_abort(true);

				global $contest;
				$contest_data = queryContestData($contest);
				calcStandings($contest, $contest_data, $score, $standings, true);

				for ($i = 0; $i < count($standings); $i++) {
					$user = queryUser($standings[$i][2][0]);
					$user_link = getUserLink($user['username']);

					DB::query("update contests_registrants set rank = {$standings[$i][3]} where contest_id = {$contest['id']} and username = '{$standings[$i][2][0]}'");
				}
				DB::query("update contests set status = 'finished' where id = {$contest['id']}");
			};
			$publish_result_form->submit_button_config['class_str'] = 'btn btn-danger d-block w-100';
			$publish_result_form->submit_button_config['smart_confirm'] = '';
			$publish_result_form->submit_button_config['text'] = '公布成绩';
			
			$publish_result_form->runAtServer();
		}
	}
	
	if ($cur_tab == 'dashboard') {
		if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
			$post_question = new UOJForm('post_question');
			$post_question->addVTextArea('qcontent', '问题', '', 
				function($content) {
					if (!Auth::check()) {
						return '您尚未登录';
					}
					if (!$content || strlen($content) == 0) {
						return '问题不能为空';
					}
					if (strlen($content) > 140 * 4) {
						return '问题太长';
					}
					return '';
				},
				null
			);
			$post_question->handle = function() {
				global $contest;
				$content = DB::escape($_POST['qcontent']);
				$username = Auth::id();
				DB::query("insert into contests_asks (contest_id, question, username, post_time, is_hidden) values ('{$contest['id']}', '$content', '$username', now(), 1)");
			};
			$post_question->runAtServer();
		} else {
			$post_question = null;
		}
	} elseif ($cur_tab == 'backstage') {
		if (isSuperUser(Auth::user())) {
			$post_notice = new UOJForm('post_notice');
			$post_notice->addInput('title', 'text', '标题', '',
				function($title) {
					if (!$title) {
						return '标题不能为空';
					}
					return '';
				},
				null
			);
			$post_notice->addTextArea('content', '正文', '', 
				function($content) {
					if (!$content) {
						return '公告不能为空';
					}
					return '';
				},
				null
			);
			$post_notice->handle = function() {
				global $contest;
				$title = DB::escape($_POST['title']);
				$content = DB::escape($_POST['content']);
				DB::insert("insert into contests_notice (contest_id, title, content, time) values ('{$contest['id']}', '$title', '$content', now())");
			};
			$post_notice->runAtServer();
		} else {
			$post_notice = null;
		}
		
		if (hasContestPermission(Auth::user(), $contest)) {
			$reply_question = new UOJForm('reply_question');
			$reply_question->addHidden('rid', '0',
				function($id) {
					global $contest;
				    
					if (!validateUInt($id)) {
						return '无效ID';
					}
					$q = DB::selectFirst("select * from contests_asks where id = $id");
					if ($q['contest_id'] != $contest['id']) {
						return '无效ID';
					}
					return '';
				},
				null
			);
			$reply_question->addVSelect('rtype', [
				'public' => '公开',
				'private' => '非公开',
				'statement' => '请仔细阅读题面（非公开）',
				'no_comment' => '无可奉告（非公开）',
				'no_play' => '请认真比赛（非公开）',
			], '回复类型', 'private');
			$reply_question->addVTextArea('rcontent', '回复', '', 
				function($content) {
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
					return '';
				},
				null
			);
			$reply_question->handle = function() {
				global $contest;
				$content = DB::escape($_POST['rcontent']);
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
				DB::update("update contests_asks set answer = '$content', reply_time = now(), is_hidden = {$is_hidden} where id = {$_POST['rid']}");
			};
			$reply_question->runAtServer();
		} else {
			$reply_question = null;
		}
	} elseif ($cur_tab == 'self_reviews') {
		if (hasParticipated(Auth::user(), $contest)) {
			$self_reviews_update_form = new UOJForm('self_review_update');
			$self_reviews_update_form->ctrl_enter_submit = true;
			
			$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']} order by dfn, problem_id");
			for ($i = 0; $i < count($contest_problems); $i++) {
				$contest_problems[$i]['problem'] = queryProblemBrief($contest_problems[$i]['problem_id']);
			}
			
			for ($i = 0; $i < count($contest_problems); $i++) {
				$content = DB::selectFirst("select content from contests_reviews where contest_id = {$contest['id']} and problem_id = {$contest_problems[$i]['problem_id']} and poster = '{$myUser['username']}'")['content'];
				$self_reviews_update_form->addVTextArea('self_review_update__problem_' . chr(ord('A') + $i), '<b>' . chr(ord('A') + $i) . '</b>: ' . $contest_problems[$i]['problem']['title'], $content,
					function ($content) {
						return '';
					},
					null,
					true
				);
			}

			$content = DB::selectFirst("select content from contests_reviews where contest_id = {$contest['id']} and problem_id = -1 and poster = '{$myUser['username']}'")['content'];
			$self_reviews_update_form->addVTextArea('self_review_update__overall', '比赛总结', $content,
				function ($content) {
					return '';
				},
				null,
				true
			);

			$self_reviews_update_form->handle = function() {
				global $contest, $contest_problems, $myUser;

				for ($i = 0; $i < count($contest_problems); $i++) {
					if (isset($_POST['self_review_update__problem_' . chr(ord('A') + $i)])) {
						$esc_content = DB::escape($_POST['self_review_update__problem_' . chr(ord('A') + $i)]);
						$problem_id = $contest_problems[$i]['problem_id'];

						DB::query("replace into contests_reviews (contest_id, problem_id, poster, content) values ({$contest['id']}, $problem_id, '{$myUser['username']}', '$esc_content')");
					}
				}

				if (isset($_POST['self_review_update__overall'])) {
					$esc_content = DB::escape($_POST['self_review_update__overall']);
					DB::query("replace into contests_reviews (contest_id, problem_id, poster, content) values ({$contest['id']}, -1, '{$myUser['username']}', '$esc_content')");
				}
			};

			$self_reviews_update_form->runAtServer();
		}
	}
	
	function echoDashboard() {
		global $contest, $post_notice, $post_question, $reply_question, $REQUIRE_LIB;
		
		$myname = Auth::id();
		$contest_problems = DB::selectAll("select contests_problems.problem_id, best_ac_submissions.submission_id from contests_problems left join best_ac_submissions on contests_problems.problem_id = best_ac_submissions.problem_id and submitter = '{$myname}' where contest_id = {$contest['id']} order by contests_problems.dfn, contests_problems.problem_id");
		
		for ($i = 0; $i < count($contest_problems); $i++) {
			$contest_problems[$i]['problem'] = queryProblemBrief($contest_problems[$i]['problem_id']);
		}
		
		$contest_notice = DB::selectAll("select * from contests_notice where contest_id = {$contest['id']} order by time desc");
		
		if (Auth::check()) {
			$my_questions = DB::selectAll("select * from contests_asks where contest_id = {$contest['id']} and username = '{$myname}' order by post_time desc");
			$my_questions_pag = new Paginator([
				'data' => $my_questions
			]);
		} else {
			$my_questions_pag = null;
		}
		
		$others_questions_pag = new Paginator([
			'col_names' => array('*'),
			'table_name' => 'contests_asks',
			'cond' => "contest_id = {$contest['id']} and username != '{$myname}' and is_hidden = 0",
			'tail' => 'order by reply_time desc',
			'page_len' => 10
		]);
		
		uojIncludeView('contest-dashboard', [
			'contest' => $contest,
			'contest_notice' => $contest_notice,
			'contest_problems' => $contest_problems,
			'post_question' => $post_question,
			'my_questions_pag' => $my_questions_pag,
			'others_questions_pag' => $others_questions_pag,
			'REQUIRE_LIB' => $REQUIRE_LIB,
		]);
	}
	
	function echoBackstage() {
		global $contest, $post_notice, $reply_question;
		
		$questions_pag = new Paginator([
			'col_names' => array('*'),
			'table_name' => 'contests_asks',
			'cond' => "contest_id = {$contest['id']}",
			'tail' => 'order by post_time desc',
			'page_len' => 50
		]);
		
		if ($contest['cur_progress'] < CONTEST_TESTING) {
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
		global $contest, $myUser;

		$show_all_submissions_status = Cookie::get('show_all_submissions') !== null ? 'checked="checked" ' : '';
		$show_all_submissions = UOJLocale::get('contests::show all submissions');
		echo <<<EOD
			<div class="text-end">
				<div class="form-check d-inline-block">
					<input type="checkbox" class="form-check-input" id="input-show_all_submissions" $show_all_submissions_status />
					<label class="form-check-label" for="input-show_all_submissions">
						$show_all_submissions
					</label>
				</div>
			</div>
			<script type="text/javascript">
				$('#input-show_all_submissions').click(function() {
					if (this.checked) {
						$.cookie('show_all_submissions', '');
					} else {
						$.removeCookie('show_all_submissions');
					}
					location.reload();
				});
			</script>
EOD;

		$config = array(
			'judge_time_hidden' => '',
			'table_config' => array(
				'div_classes' => array('card', 'mb-3', 'overflow-auto'),
				'table_classes' => array('table', 'mb-0', 'uoj-table', 'text-center')
			),
		);

		if (Cookie::get('show_all_submissions') !== null) {
			echoSubmissionsList("contest_id = {$contest['id']}", 'order by id desc', $config, $myUser);
		} else {
			echoSubmissionsList("submitter = '{$myUser['username']}' and contest_id = {$contest['id']}", 'order by id desc', $config, $myUser);
		}
	}
	
	function echoStandings($is_after_contest_query = false) {
		global $contest;
		
		$contest_data = queryContestData($contest, array(), $is_after_contest_query);
		calcStandings($contest, $contest_data, $score, $standings);
		
		uojIncludeView('contest-standings', [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data
		]);
	}
	
	function echoReviews() {
		global $contest;
		
		$contest_data = queryContestData($contest, array());
		calcStandings($contest, $contest_data, $score, $standings, false, true);
		
		uojIncludeView('contest-standings', [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data,
			'show_self_reviews' => true
		]);
	}
	
	$page_header = HTML::stripTags($contest['name']) . ' - ';
	?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . $tabs_info[$cur_tab]['name'] . ' - ' . UOJLocale::get('contests::contest')) ?>

<div class="text-center d-md-none">
	<h1 class="h2"><?= $contest['name'] ?></h1>
</div>


<div class="row">
	<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews'): ?>
	<div class="col-12">
	<?php else: ?>
	<div class="col-md-9">
	<?php endif ?>
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
		<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings' || $cur_tab == 'self_reviews'): ?>
		<h1 class="h2 text-center d-none d-md-block mt-2"><?= $contest['name'] ?></h1>
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
					echoReviews();
				}
	?>
		</div>
	</div>
	
	<?php if ($cur_tab == 'standings' || $cur_tab == 'after_contest_standings'): ?>
	<?php elseif ($cur_tab == 'self_reviews'): ?>
		<?php if (isset($self_reviews_update_form)) : ?>
		<hr />

		<div class="col-md-6">
			<h4>修改我的赛后总结</h4>
			<?php $self_reviews_update_form->printHTML(); ?>
		</div>
		<?php endif ?>	
	<?php else: ?>
	<div class="d-md-none">
		<hr />
	</div>
	<div class="col-md-3">
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
					<script type="text/javascript">
						$('#contest-countdown').countdown(<?= $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp() ?>, function(){}, '1.75rem', false);
						checkContestNotice(<?= $contest['id'] ?>, '<?= UOJTime::$time_now_str ?>');
					</script>
				<?php elseif ($contest['cur_progress'] <= CONTEST_TESTING): ?>
					<?php if ($contest['cur_progress'] < CONTEST_TESTING): ?>
						<?= UOJLocale::get('contests::contest pending final test') ?>
					<?php else: ?>
						<?php
							$total = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']}");
						$n_judged = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']} and status = 'Judged'");
						$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
						?>
						<?= UOJLocale::get('contests::contest final testing') ?>
						(<?= $rop ?>%)
					<?php endif ?>
				<?php else: ?>
					<?= UOJLocale::get('contests::contest ended') ?>
				<?php endif ?>
				</div>
			</div>
			<div class="card-footer bg-transparent">
				比赛评价：<?= getClickZanBlock('C', $contest['id'], $contest['zan']) ?>
			</div>
		</div>

		<?php if (!isset($contest['extra_config']['contest_type']) || $contest['extra_config']['contest_type'] == 'OI'): ?>
			<p>此次比赛为 OI 赛制。</p>
			<p><strong>注意：比赛时只显示测样例的结果。</strong></p>
		<?php elseif ($contest['extra_config']['contest_type'] == 'IOI'): ?>
			<p>此次比赛为 IOI 赛制。</p>
			<p><strong>注意：比赛时显示测试所有数据的结果，但无法看到详细信息。</strong></p>
		<?php endif ?>
	
		<a href="/contest/<?= $contest['id'] ?>/registrants" class="btn btn-info d-block mt-2">
			<?= UOJLocale::get('contests::contest registrants') ?>
		</a>
		<?php if (isSuperUser($myUser)): ?>
			<a href="/contest/<?=$contest['id']?>/manage" class="btn btn-primary d-block mt-2">
				管理
			</a>
		<?php endif ?>
		<?php if (isset($start_test_form)): ?>
		<div class="mt-2">
			<?php $start_test_form->printHTML(); ?>
		</div>
		<?php endif ?>
		<?php if (isset($publish_result_form)): ?>
		<div class="mt-2">
			<?php $publish_result_form->printHTML(); ?>
		</div>
		<?php endif ?>
	</div>
		<?php if ($contest['extra_config']['links']): ?>
			<div class="card border-info top-buffer-lg">
				<div class="card-header bg-info">
					<h3 class="card-title">比赛资料</h3>
				</div>
				<div class="list-group">
				<?php foreach ($contest['extra_config']['links'] as $link): ?>
					<a href="/blogs/<?=$link[1]?>" class="list-group-item"><?=$link[0]?></a>
				<?php endforeach ?>
				</div>
			</div>
		</div>
		<?php endif ?>
	<?php endif ?>
</div>

<?php echoUOJPageFooter() ?>
