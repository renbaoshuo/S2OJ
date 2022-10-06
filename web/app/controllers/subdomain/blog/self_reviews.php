<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	requirePHPLib('form');
	
	$username = UOJContext::user()['username'];

	$REQUIRE_LIB['mathjax'] = '';
	?>

<?php echoUOJPageHeader(UOJLocale::get('contests::contest self reviews')) ?>

<h2 style="margin-top: 24px">
	<?= $username ?> 的所有赛后总结
</h2>

<?php
		$col_names = array('contest_id');
	$from = 'contests_registrants a left join contests b on a.contest_id = b.id';
	$cond = "username = '$username' and has_participated = 1";
	$tail = 'order by start_time desc, id desc';
	$config = array(
		'pagination_table' => 'contests_registrants',
		'page_len' => 10,
		'table_classes' => array('table', 'table-bordered', 'table-text-center', 'table-vertical-middle'),
	);

	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 28em;">'.UOJLocale::get('contests::contest name').'</th>';
	$header_row .= '<th style="width: 14em;">'.UOJLocale::get('problems::problem').'</th>';
	$header_row .= '<th style="width: 35em;">'.UOJLocale::get('contests::problem self review').'</th>';
	$header_row .= '<th style="width: 35em;">'.UOJLocale::get('contests::contest self review').'</th>';
	$header_row .= '</tr>';

	$print_row =  function($row) {
		global $username;

		$contest_id = $row['contest_id'];
		$contest = queryContest($contest_id);
		$contest_problems = queryContestProblems($contest_id);
		$n_contest_problems = count($contest_problems);

		$result = '';
		$dom_sanitize_config = DOM_SANITIZE_CONFIG;

		for ($i = 0; $i < $n_contest_problems; $i++) {
			$problem_id = $contest_problems[$i]['problem_id'];
			$problem = queryProblemBrief($problem_id);
			$problem_self_review = DB::selectFirst("select content from contests_reviews where contest_id = $contest_id and problem_id = $problem_id and poster = '$username'");

			$result .= '<tr>';

			if ($i == 0) {
				$result .= '<td rowspan="' . $n_contest_problems . '"><a href="' . HTML::url("/contest/$contest_id") . '">' . $contest['name'] . '</a></td>';
			}

			$problem_review_id = "review-$contest_id-$i";
			$result .= '<td>' . chr(ord('A') + $i) . '. <a href="/problem/' . $problem_id . '">' . $problem['title'] . '</a></td>';
			$result .= '<td><div id="' . $problem_review_id . '"></div></td>';
			$esc_problem_self_review = rawurlencode($problem_self_review != null ? $problem_self_review['content'] : '');
			$result .= '<script type="text/javascript">'
					 . "(function() { $('#$problem_review_id').html(DOMPurify.sanitize(decodeURIComponent(\"{$esc_problem_self_review}\"), $dom_sanitize_config)); })();"
					 . '</script>';

			if ($i == 0) {
				$contest_review_id = "review-$contest_id-overall";
				$contest_self_review = DB::selectFirst("select content from contests_reviews where contest_id = $contest_id and problem_id = -1 and poster = '$username'");
				$esc_contest_self_review = rawurlencode($contest_self_review != null ? $contest_self_review['content'] : '');
				$result .= '<td rowspan="' . $n_contest_problems . '"><div id="' . $contest_review_id . '"></div></td>';
				$result .= '<script type="text/javascript">'
						 . "(function() { $('#$contest_review_id').html(DOMPurify.sanitize(decodeURIComponent(\"{$esc_contest_self_review}\"), $dom_sanitize_config)); })();"
						 . '</script>';
			}

			$result .= '</tr>';
		}

		echo $result;
	};

	echoLongTable($col_names, $from, $cond, $tail, $header_row, $print_row, $config);
	?>
<script>if (window.MathJax) MathJax.typeset();</script>
<?php echoUOJPageFooter() ?>
