<?php

class UOJRanklist {
	public static function printHTML($cfg = []) {
		$cfg += [
			'top10' => false,
			'card' => false,
			'group_id' => null,
			'page_len' => 50,
		];

		$conds = [];

		if ($cfg['group_id']) {
			$conds[] = [
				"username", "in", DB::rawtuple(UOJGroup::query($cfg['group_id'])->getUsernames()),
			];
		}

		if (empty($conds)) {
			$conds = '1';
		}

		$last_user = null;
		$parsedown = HTML::parsedown(['username_with_color' => true]);
		$purifier = HTML::purifier_inline();
		$print_row = function ($user, $now_cnt) use (&$last_user, &$conds, &$parsedown, &$purifier) {
			if ($last_user === null) {
				$rank = DB::selectCount([
					"select count(*) from user_info",
					"where", [
						["ac_num", ">", $user['ac_num']],
						DB::conds($conds)
					]
				]);
				$rank++;
			} elseif ($user['ac_num'] == $last_user['ac_num']) {
				$rank = $last_user['rank'];
			} else {
				$rank = $now_cnt;
			}

			$user['rank'] = $rank;

			$userpro = HTML::url('/user/' . $user['username']);
			$userlink = UOJUser::getLink($user['username']);
			$asrc = HTML::avatar_addr($user, 100);
			$esc_motto = $purifier->purify($parsedown->line($user['motto']));
			$solved_text = UOJLocale::get('solved');
			echo <<<EOD
            <div class="list-group-item">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <a href="{$userpro}"><img class="rounded" src="{$asrc}" width="50" height="50" /></a>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="row">
                            <h5 class="col-sm-8">#{$user['rank']}: {$userlink}</h5>
                            <div class="col-sm-4 text-end"><strong>$solved_text: {$user['ac_num']}</strong></div>
                        </div>
                        <div>{$esc_motto}</div>
                    </div>
                </div>
            </div>
            EOD;

			$last_user = $user;
		};

		$pag_config = [
			'get_row_index' => '',
			'table_name' => 'user_info',
			'col_names' => ['username', 'ac_num', 'email', 'qq', 'motto', 'extra'],
			'cond' => $conds,
			'tail' => 'order by ac_num desc, username asc'
		];

		if ($cfg['top10']) {
			$pag_config['tail'] .= ' limit 10';
			$pag_config['echo_full'] = '';
		} else {
			$pag_config['page_len'] = $cfg['page_len'];
		}

		$pag = new Paginator($pag_config);

		if ($cfg['card']) {
			echo '<div class="card my-3">';
			echo '<div class="list-group list-group-flush">';
		} else {
			echo '<div class="list-group">';
		}
		foreach ($pag->get() as $idx => $row) {
			$print_row($row, $idx);
		}
		if ($pag->isEmpty()) {
			echo <<<EOD
            <div class="list-group-item">
            无
            </div>
            EOD;
		}
		echo '</div>';
		if ($cfg['card']) {
			echo '</div>';
		}
		echo $pag->pagination();
	}

	/**
	 * Old style of ranklist
	 */
	public static function printTableHTML($cfg = []) {
		$cfg += [
			'top10' => false,
			'group_id' => null,
			'page_len' => 100,
		];

		$conds = [];

		if ($cfg['group_id']) {
			$conds[] = [
				"username", "in", DB::rawtuple(UOJGroup::query($cfg['group_id'])->getUsernames()),
			];
		}

		if (empty($conds)) {
			$conds = '1';
		}

		$header_row = '';
		$header_row .= '<tr>';
		$header_row .= '<th style="width: 5em;">#</th>';
		$header_row .= '<th style="width: 14em;">' . UOJLocale::get('username') . '</th>';
		$header_row .= '<th style="width: 50em;">' . UOJLocale::get('motto') . '</th>';
		$header_row .= '<th style="width: 5em;">' . UOJLocale::get('solved') . '</th>';
		$header_row .= '</tr>';

		$last_user = null;
		$parsedown = HTML::parsedown(['username_with_color' => true]);
		$purifier = HTML::purifier_inline();
		$print_row = function ($user, $now_cnt) use (&$last_user, &$conds, &$parsedown, &$purifier) {
			if ($last_user === null) {
				$rank = DB::selectCount([
					"select count(*) from user_info",
					"where", [
						["ac_num", ">", $user['ac_num']],
						DB::conds($conds)
					]
				]);
				$rank++;
			} elseif ($user['ac_num'] == $last_user['ac_num']) {
				$rank = $last_user['rank'];
			} else {
				$rank = $now_cnt;
			}

			$user['rank'] = $rank;

			echo '<tr>';
			echo '<td>' . $user['rank'] . '</td>';
			echo '<td>' . UOJUser::getLink($user['username']) . '</td>';
			echo '<td>' . $purifier->purify($parsedown->line($user['motto'])) . '</td>';
			echo '<td>' . $user['ac_num'] . '</td>';
			echo '</tr>';

			$last_user = $user;
		};

		$col_names = ['username', 'ac_num', 'motto'];

		$tail = 'order by ac_num desc, username asc';

		$table_config = [
			'get_row_index' => ''
		];

		if ($cfg['top10']) {
			$tail .= ' limit 10';
			$table_config['echo_full'] = '';
		} else {
			$table_config['page_len'] = $cfg['page_len'];
		}

		echoLongTable($col_names, 'user_info', $conds, $tail, $header_row, $print_row, $table_config);
	}
}
