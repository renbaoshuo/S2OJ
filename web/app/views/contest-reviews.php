<?php
$reviews = [];

$parsedown = HTML::parsedown(['username_with_color' => true]);
$purifier = HTML::purifier_inline();

foreach ($contest_data['people'] as $person) {
	$reviews[$person[0]] = [
		'_can_edit' => Auth::id() == $person[0] || UOJContest::cur()->userCanManage(Auth::user()),
	];

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

		$reviews[$person[0]][$problem]['raw'] = $content;
		$reviews[$person[0]][$problem]['html'] = $purifier->purify($parsedown->line($content));
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

	$reviews[$person[0]]['_all']['raw'] = $content;
	$reviews[$person[0]]['_all']['html'] = $purifier->purify($parsedown->line($content));
}
?>

<div id="standings"></div>

<div class="modal fade" id="UpdateSelfReviewsModal" tabindex="-1" aria-labelledby="UpdateSelfReviewsModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h1 class="modal-title fs-5" id="UpdateSelfReviewsModalLabel">更新赛后总结</h1>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<?php $self_reviews_update_form->printHTML(); ?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var contest_id = <?= $contest['id'] ?>;
	var standings = <?= json_encode($standings) ?>;
	var score = <?= json_encode($score) ?>;
	var problems = <?= json_encode($contest_data['problems']) ?>;
	var reviews = <?= json_encode($reviews, JSON_FORCE_OBJECT) ?>;
	var standings_config = <?= json_encode(isset($standings_config) ? $standings_config : [], JSON_FORCE_OBJECT) ?>;

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
				if (reviews[row[2][0]]['_can_edit']) {
					var button_update_review = $('<button class="btn btn-sm btn-outline-secondary ms-2" />').append('<i class="bi bi-pencil"></i>');

					button_update_review.click(function() {
						$('#UpdateSelfReviewsModalLabel').text('更新 ' + row[2][0] + ' 的赛后总结');
						$('#input-self_reviews_update__username').val(row[2][0]);

						$.map(problems, function(col, idx) {
							$('#input-self_reviews_update__problem_' + String.fromCharCode('A'.charCodeAt(0) + idx)).val(reviews[row[2][0]][col]['raw'] || '');
						});

						$('#input-self_reviews_update__overall').val(reviews[row[2][0]]['_all']['raw'] || '');
						$('#UpdateSelfReviewsModal').modal('show');
					});
				} else {
					var button_update_review = '';
				}

				var res = $('<tr />')
					.append($('<td />').append(row[3]))
					.append($('<td />').append(getUserLink(row[2][0], row[2][1], row[2][3])).append(button_update_review))
					.append($('<td />').append($('<span class="uoj-score" />').attr('data-max', problems.length * 100).css('color', getColOfScore(row[0] / problems.length)).append(row[0])));

				for (var i = 0; i < problems.length; i++) {
					var td = $('<td class="align-text-top" />');

					col = score[row[2][0]][i];

					if (col != undefined) {
						if (col[2]) {
							td.append(
								$('<div />').append(
									$('<a class="uoj-score" />')
									.attr('href', '/submission/' + col[2])
									.attr('data-max', 100)
									.css('color', getColOfScore(col[0]))
									.append(col[0])
								)
							);
						} else {
							td.append(
								$('<div />').append(
									$('<span class="uoj-score" />')
									.data('max', 100)
									.css('color', getColOfScore(col[0]))
									.append(col[0])
								)
							);
						}
					} else {
						td.append($('<div />').append('&nbsp;'));
					}

					if (reviews[row[2][0]][problems[i]]) {
						td.append($('<div class="mt-2 pt-2 border-top" />').append(reviews[row[2][0]][problems[i]]['html']));
					}

					res.append(td);
				}

				if (reviews[row[2][0]]['_all']) {
					res.append($('<td />').append(reviews[row[2][0]]['_all']['html']));
				} else {
					res.append($('<td />').append('&nbsp;'));
				}

				return res.uoj_highlight();
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
