<?php
requireLib('bootstrap5');
requireLib('mathjax');
requirePHPLib('form');

Auth::check() || redirectToLogin();
?>

<?php echoUOJPageHeader(UOJLocale::get('contests::contest self reviews')) ?>

<h1>
	<?= UOJUserBlog::id() ?> 的所有赛后总结
</h1>

<?php
$col_names = ['contest_id'];
$from = 'contests_registrants inner join contests on contests_registrants.contest_id = contests.id';
$cond = ["username" =>  UOJUserBlog::id(), "has_participated" =>  1];
$tail = 'order by start_time desc, id desc';
$config = [
	'page_len' => 10,
	'div_classes' => ['card', 'card-default', 'table-responsive'],
	'table_classes' => ['table', 'table-bordered', 'text-center', 'align-middle', 'uoj-table', 'mb-0'],
];

$header_row = '';
$header_row .= '<tr>';
$header_row .= '<th style="width:28em">' . UOJLocale::get('contests::contest name') . '</th>';
$header_row .= '<th style="width:14em">' . UOJLocale::get('problems::problem') . '</th>';
$header_row .= '<th style="width:35em">' . UOJLocale::get('contests::problem self review') . '</th>';
$header_row .= '<th style="width:35em">' . UOJLocale::get('contests::contest self review') . '</th>';
$header_row .= '</tr>';

$parsedown = HTML::parsedown();
$purifier = HTML::purifier_inline();

$print_row = function ($row) use ($parsedown, $purifier) {
	$contest = UOJContest::query($row['contest_id']);
	$problems = $contest->getProblemIDs();
	$result = '';

	for ($i = 0; $i < count($problems); $i++) {
		$problem = UOJContestProblem::query($problems[$i], $contest);
		$review = DB::selectSingle([
			"select content",
			"from contests_reviews",
			"where", [
				"contest_id" => $contest->info['id'],
				"problem_id" => $problem->info['id'],
				"poster" => UOJUserBlog::id(),
			]
		]);

		$result .= '<tr>';

		if ($i == 0) {
			$result .= '<td rowspan="' . count($problems) . '"><a href="' . $contest->getUri() . '">' . $contest->info['name'] . '</a></td>';
		}

		$result .= '<td>' . $problem->getLink(['with' => 'letter', 'simplify' => true]) . '</td>';
		$result .= '<td>' . $purifier->purify($review ? $parsedown->line($review) : '') . '</td>';

		if ($i == 0) {
			$review = DB::selectSingle([
				"select content",
				"from contests_reviews",
				"where", [
					"contest_id" => $contest->info['id'],
					"problem_id" => -1,
					"poster" => UOJUserBlog::id(),
				]
			]);

			$result .= '<td rowspan="' . count($problems) . '">' . $purifier->purify($review ? $parsedown->line($review) : '') . '</td>';
		}

		$result .= '</tr>';
	}

	echo $result;
};

echoLongTable($col_names, $from, $cond, $tail, $header_row, $print_row, $config);
?>

<?php echoUOJPageFooter() ?>
