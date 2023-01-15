<?php
$reviews = [];

$parsedown = HTML::parsedown(['username_with_color' => true]);
$purifier = HTML::purifier_inline();

foreach ($contest_data['people'] as $person) {
	$reviews[$person[0]] = [];

	foreach ($contest_data['problems'] as $problem) {
		$content = DB::selectSingle([
			"select content",
			"from contests_reviews",
			"where", [
				"contest_id" => $contest['id'],
				"problem_id" => $problem,
				"poster" => $person[0],
			],
		]);

		$reviews[$person[0]][$problem] = $purifier->purify($parsedown->line($content));
	}

	$content = DB::selectSingle([
		"select content",
		"from contests_reviews",
		"where", [
			"contest_id" => $contest['id'],
			"problem_id" => -1,
			"poster" => $person[0],
		],
	]);

	$reviews[$person[0]]['all'] = $purifier->purify($parsedown->line($content));
}
?>

<div id="standings"></div>

<script type="text/javascript">
	var contest_id = <?= $contest['id'] ?>;
	var standings = <?= json_encode($standings) ?>;
	var score = <?= json_encode($score) ?>;
	var problems = <?= json_encode($contest_data['problems']) ?>;
	var reviews = <?= json_encode($reviews) ?>;
	var standings_config = <?= json_encode(isset($standings_config) ? $standings_config : ['_config' => true]) ?>;

	$(document).ready(function() {
		$("#standings").long_table(
			standings,
			1,
			'<tr>' +
			'<th style="width:2em">#</th>' +
			'<th style="width:8em">' + uojLocale('username') + '</th>' +
			'<th style="width:5em">' + uojLocale('contests::total score') + '</th>' +
			$.map(problems, function(col, idx) {
				return '<th style="width:10em">' + '<a href="/contest/' + contest_id + '/problem/' + col + '" class="text-decoration-none">' + String.fromCharCode('A'.charCodeAt(0) + idx) + '</a>' + '</th>';
			}).join('') +
			'<th style="width:16em">赛后总结</th>' +
			'</tr>',
			function(row) {
				var col_tr = '<tr>';
				col_tr += '<td>' + row[3] + '</td>';
				col_tr += '<td>' + getUserLink(row[2][0], row[2][1]) + '</td>';
				col_tr += '<td>' + '<span class="uoj-score" data-max="' + problems.length * 100 + '" style="color:' + getColOfScore(row[0] / problems.length) + '">' + row[0] + '</span>' + '</td>';
				for (var i = 0; i < problems.length; i++) {
					col_tr += '<td class="align-text-top">';
					col = score[row[2][0]][i];
					if (col != undefined) {
						col_tr += '<div>';

						if (col[2]) col_tr += '<a href="/submission/' + col[2] + '" class="uoj-score" style="color:' + getColOfScore(col[0]) + '">' + col[0] + '</a>';
						else col_tr += '<span class="uoj-score" style="color:' + getColOfScore(col[0]) + '">' + col[0] + '</span>';

						col_tr += '</div>';
					} else {
						col_tr += '<div>&nbsp;</div>';
					}

					if (reviews[row[2][0]][problems[i]]) {
						col_tr += '<div class="mt-2 pt-2 border-top">' + reviews[row[2][0]][problems[i]] + '</div>';
					}

					col_tr += '</td>';
				}
				col_tr += '<td>' + reviews[row[2][0]]['all'] + '</td>';
				col_tr += '</tr>';
				return col_tr;
			}, {
				div_classes: standings_config.div_classes ? standings_config.div_classes : ['table-responsive', 'card', 'my-3'],
				table_classes: standings_config.table_classes ? standings_config.table_classes : ['table', 'table-bordered', 'text-center', 'align-middle', 'uoj-table', 'uoj-standings-table', 'mb-0'],
				page_len: standings_config.page_len ? standings_config.page_len : 50,
				print_after_table: function() {
					return '<div class="card-footer bg-transparent text-end text-muted">' + uojLocale("contests::n participants", standings.length) + '</div><script>if (window.MathJax) window.MathJax.typeset();</scr' + 'ipt>';
				}
			}
		);
	});
</script>
