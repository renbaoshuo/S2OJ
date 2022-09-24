<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
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
		global $myUser, $REQUIRE_LIB;

		echo '<tr class="text-center">';
		if ($list['problem_count'] == $list['accepted'] && $list['problem_count'] > 0) {
			echo '<td class="success">';
		} else {
			echo '<td>';
		}
		echo '#', $list['list_id'], '</td>';

		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo '<td class="text-start">';
		} else {
			echo '<td class="text-left">';
		}

		if ($list['is_hidden']) {
			echo ' <span class="text-danger">[隐藏]</span> ';
		}
		echo '<a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}
		echo ' href="/problem_list/', $list['list_id'], '">', $list['title'], '</a>';
		foreach (queryProblemListTags($list['list_id']) as $tag) {
			if (isset($REQUIRE_LIB['bootstrap5'])) {
				echo '<a class="uoj-list-tag my-1">';
				echo '<span class="badge bg-secondary">';
			} else {
				echo '<a class="uoj-list-tag">';
				echo '<span class="badge badge-pill badge-secondary">';
			}

			echo HTML::escape($tag), '</span>', '</a>';
		}
		echo '</td>';

		echo "<td>{$list['accepted']}</td>";
		echo "<td>{$list['problem_count']}</td>";

		echo '</tr>';
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('problems lists')) ?>


<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="d-flex justify-content-between">
<?php endif ?>
<h1 class="h2">
	<?= UOJLocale::get('problems lists') ?>
</h1>

<?php if (isSuperUser($myUser)): ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="text-end mb-2">
	<?php endif ?>
		<?php $new_list_form->printHTML(); ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	</div>
</div>
<?php endif ?>
<?php endif ?>

<?php
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

	$table_config = array(
		'page_len' => 40,
		'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped'),
		'head_pagination' => true,
		'pagination_table' => 'lists'
	);

	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$table_config['div_classes'] = array('card', 'mb-3');
		$table_config['table_classes'] = array('table', 'uoj-table', 'mb-0');
	}

	echoLongTable(
		array('a.id as list_id', 'a.title as title', 'a.is_hidden as is_hidden', 'count(b.problem_id) as problem_count', 'count(c.submitter) as accepted'),
		$from, $cond, 'group by a.id order by a.id desc',
		$header,
		'echoList',
		$table_config,
	);
	?>

<?php echoUOJPageFooter() ?>
