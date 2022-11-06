<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requireLib('bootstrap5');
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (isSuperUser($myUser)) {
		$new_list_form = new UOJBs4Form('new_list');
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

		if (isListVisibleToUser($list, $myUser)) {
			echo '<tr class="text-center">';
			if ($list['problem_count'] == $list['accepted'] && $list['problem_count'] > 0) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo '#', $list['list_id'], '</td>';

			echo '<td class="text-start">';
			echo '<a class="text-decoration-none" href="/list/', $list['list_id'], '">', $list['title'], '</a> ';

			if ($list['is_hidden']) {
				echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
			}

			foreach (queryProblemListTags($list['list_id']) as $tag) {
				echo '<a class="uoj-list-tag my-1">', '<span class="badge bg-secondary">',  HTML::escape($tag), '</span>', '</a> ';
			}
			echo '</td>';

			echo "<td>{$list['accepted']}</td>";
			echo "<td>{$list['problem_count']}</td>";

			echo '</tr>';
		}
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('problems lists')) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<!-- title container -->
<div class="d-flex justify-content-between">

<h1>
	<?= UOJLocale::get('problems lists') ?>
</h1>

<?php if (isset($new_list_form)): ?>
<div class="text-end mb-2">
	<?php $new_list_form->printHTML(); ?>
</div>
<?php endif ?>

</div>
<!-- end title container -->

<?php
	$list_caption = UOJLocale::get('problems::problem list');
	$ac_caption = UOJLocale::get('problems::ac');
	$total_caption = UOJLocale::get('problems::total');
	$header = <<<EOD
	<tr>
		<th class="text-center" style="width:5em;">ID</th>
		<th>{$list_caption}</th>
		<th class="text-center" style="width:5em;">{$ac_caption}</th>
		<th class="text-center" style="width:5em;">{$total_caption}</th>
	</tr>
EOD;

	$cond = [];
	$search_tag = null;
	
	if (isset($_GET['tag'])) {
		$search_tag = $_GET['tag'];
	}

	if ($search_tag) {
		$cond[] = "'" . DB::escape($search_tag) . "' in (select tag from lists_tags where lists_tags.list_id = a.id)";
	}

	if ($cond) {
		$cond = join($cond, ' and ');
	} else {
		$cond = '1';
	}

	echoLongTable(
		array('a.id as list_id', 'a.title as title', 'a.is_hidden as is_hidden', 'count(b.problem_id) as problem_count', 'count(c.submitter) as accepted'),
		"lists a left join lists_problems b on a.id = b.list_id left join best_ac_submissions c on (b.problem_id = c.problem_id and c.submitter = '{$myUser['username']}')",
		$cond,
		'group by a.id order by a.id desc',
		$header,
		function($list) use ($myUser) {
			if (isListVisibleToUser($list, $myUser)) {
				echo '<tr class="text-center">';
				if ($list['problem_count'] == $list['accepted'] && $list['problem_count'] > 0) {
					echo '<td class="success">';
				} else {
					echo '<td>';
				}
				echo '#', $list['list_id'], '</td>';
	
				echo '<td class="text-start">';
				echo '<a class="text-decoration-none" href="/list/', $list['list_id'], '">', $list['title'], '</a> ';
	
				if ($list['is_hidden']) {
					echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
				}
	
				foreach (queryProblemListTags($list['list_id']) as $tag) {
					echo '<a class="uoj-list-tag my-1">', '<span class="badge bg-secondary">',  HTML::escape($tag), '</span>', '</a> ';
				}
				echo '</td>';
	
				echo "<td>{$list['accepted']}</td>";
				echo "<td>{$list['problem_count']}</td>";
	
				echo '</tr>';
			}
		},
		[
			'page_len' => 40,
			'table_classes' => ['table', 'table-bordered', 'table-hover', 'table-striped'],
			'head_pagination' => true,
			'div_classes' => ['card', 'my-3'],
			'table_classes' => ['table', 'uoj-table', 'mb-0'],
		]
	);
	?>
</div>
<!-- end left col -->

<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar') ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
