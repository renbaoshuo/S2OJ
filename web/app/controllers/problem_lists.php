<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (isSuperUser($myUser)) {
		$new_list_form = new UOJForm('new_list');
		$new_list_form->handle = function() {
			DB::query("insert into lists (title, is_hidden) values ('未命名题单', 1)");
		};
		$new_list_form->submit_button_config['align'] = 'right';
		$new_list_form->submit_button_config['class_str'] = 'btn btn-primary';
		$new_list_form->submit_button_config['text'] = UOJLocale::get('problems::add new list');
		$new_list_form->submit_button_config['smart_confirm'] = '';
		
		$new_list_form->runAtServer();
	}
    
	function echoList($list) {
		global $myUser;

		echo '<tr class="text-center">';
		if ($list['problem_count'] == $list['accepted'] && $list['problem_count'] > 0) {
			echo '<td class="success">';
		} else {
			echo '<td>';
		}
		echo '#', $list['list_id'], '</td>';

		echo '<td class="text-left">';
		if ($list['is_hidden']) {
			echo ' <span class="text-danger">[隐藏]</span> ';
		}
		echo '<a href="/problem_list/', $list['list_id'], '">', $list['title'], '</a>';
		foreach (queryProblemListTags($list['list_id']) as $tag) {
			echo '<a class="uoj-list-tag">', '<span class="badge badge-pill badge-secondary">', HTML::escape($tag), '</span>', '</a>';
		}
		echo '</td>';

		echo "<td>{$list['accepted']}</td>";
		echo "<td>{$list['problem_count']}</td>";

		echo '</tr>';
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('problems lists')) ?>

<?php
	    if (isSuperUser($myUser)) {
	    	global $new_list_form;
	    	$new_list_form->printHTML();
	    }

	    $problem_list_caption = UOJLocale::get('problems::problem list');
	$ac_caption = UOJLocale::get('problems::ac');
	$total_caption = UOJLocale::get('problems::total');
	$header = <<<EOD
<tr>
    <th class="text-center" style="width:5em;">ID</th>
    <th>{$problem_list_caption}</th>
    <th class="text-center" style="width:5em;">{$ac_caption}</th>
    <th class="text-center" style="width:5em;">{$total_caption}</th>
</tr>
EOD;

	$cond = array();
	
	$search_tag = null;
	if (isset($_GET['tag'])) {
		$search_tag = $_GET['tag'];
	}
	if ($search_tag) {
		$cond[] = "'" . DB::escape($search_tag) . "' in (select tag from lists_tags where lists_tags.list_id = a.id)";
	}
	if (!isSuperUser($myUser)) {
		$cond[] = "is_hidden = 0";
	}
	
	if ($cond) {
		$cond = join($cond, ' and ');
	} else {
		$cond = '1';
	}
    
	$from = "lists a left join lists_problems b on a.id = b.list_id left join best_ac_submissions c on (b.problem_id = c.problem_id and c.submitter = '{$myUser['username']}')";

	echoLongTable(
		array('a.id as list_id', 'a.title as title', 'a.is_hidden as is_hidden', 'count(b.problem_id) as problem_count', 'count(c.submitter) as accepted'),
		$from, $cond, 'group by a.id order by a.id desc',
		$header,
		'echoList',
		array('page_len' => 40,
			'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped'),
			'print_after_table' => function() {
				global $myUser;
			},
			'head_pagination' => true,
			'pagination_table' => 'lists'
		)
	);
	?>

<?php echoUOJPageFooter() ?>
