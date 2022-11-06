<div id="standings"></div>

<script type="text/javascript">
	var standings_version = <?= $contest['extra_config']['standings_version'] ?>;
	var contest_id = <?= $contest['id'] ?>;
	var standings = <?= json_encode($standings) ?>;
	var score = <?= json_encode($score) ?>;
	var problems = <?= json_encode($contest_data['problems']) ?>;
	var standings_config = <?= json_encode(isset($standings_config) ? $standings_config : ['_config' => true]) ?>;
	var first_accepted = {};

	$(document).ready(function() {
		for (var i = 0; i < problems.length; i++) {
			Object.keys(score).forEach(function(key) {
				var person = score[key];

				if (person[i] === undefined) return;
				if (person[i][0] === 100 && (!first_accepted[i] || first_accepted[i] > person[i][2])) {
					first_accepted[i] = person[i][2];
				}
			});
		}

		$("#standings").long_table(
			standings,
			1,
			'<tr>' +
			'<th style="width:5em">#</th>' +
			'<th style="width:14em">' + uojLocale('username') + '</th>' +
			'<th style="width:5em">' + uojLocale('contests::total score') + '</th>' +
			$.map(problems, function(col, idx) {
				return '<th style="width:8em;">' + '<a href="/contest/' + contest_id + '/problem/' + col + '" class="text-decoration-none">' + String.fromCharCode('A'.charCodeAt(0) + idx) + '</a>' + '</th>';
			}).join('') +
			'</tr>',
			function(row) {
				var col_tr = '<tr>';
				col_tr += '<td>' + row[3] + '</td>';
				col_tr += '<td>' + getUserLink(row[2][0], row[2][1]) + '</td>';
				col_tr += '<td>' + '<div><span class="uoj-score" data-max="' + problems.length * 100 + '" style="color:' + getColOfScore(row[0] / problems.length) + '">' + row[0] + '</span></div>' + '<div>' + getPenaltyTimeStr(row[1]) + '</div></td>';
				for (var i = 0; i < problems.length; i++) {
					col = score[row[2][0]][i];
					if (col != undefined) {
						col_tr += col[2] === first_accepted[i] ? '<td class="table-success">' : '<td>';
						col_tr += '<div>';

						if (col[2]) col_tr += '<a href="/submission/' + col[2] + '" class="uoj-score" style="color:' + getColOfScore(col[0]) + '">' + col[0] + '</a>';
						else col_tr += '<span class="uoj-score" style="color:' + getColOfScore(col[0]) + '">' + col[0] + '</span>';

						col_tr += '</div>';
						if (standings_version < 2) {
							col_tr += '<div>' + getPenaltyTimeStr(col[1]) + '</div>';
						} else {
							if (col[0] > 0) {
								col_tr += '<div>' + getPenaltyTimeStr(col[1]) + '</div>';
							}
						}
						col_tr += '</td>';
					} else {
						col_tr += '<td></td>';
					}
				}

				col_tr += '</tr>';
				return col_tr;
			}, {
				div_classes: standings_config.div_classes ? standings_config.div_classes : ['table-responsive', 'card', 'my-3'],
				table_classes: standings_config.table_classes ? standings_config.table_classes : ['table', 'table-bordered', 'text-center', 'align-middle', 'uoj-table', 'uoj-standings-table', 'mb-0'],
				page_len: standings_config.page_len ? standings_config.page_len : 50,
				print_after_table: function() {
					return '<div class="card-footer bg-transparent text-end text-muted">' + uojLocale("contests::n participants", standings.length) + '</div>';
				}
			}
		);
	});
</script>
