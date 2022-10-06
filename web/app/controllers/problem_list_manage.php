<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	$list_id = $_GET['id'];

	if (!validateUInt($list_id) || !($list = queryProblemList($list_id))) {
		become404Page();
	}

	if (!isSuperUser($myUser)) {
		become403Page();
	}
	
	$list_tags = queryProblemListTags($list_id);

	$list_editor = new UOJBlogEditor();
	$list_editor->name = 'list';
	$list_editor->blog_url = null;
	$list_editor->cur_data = array(
		'title' => $list['title'],
		'tags' => $list_tags,
		'is_hidden' => $list['is_hidden']
	);
	$list_editor->label_text = array_merge($list_editor->label_text, array(
		'view blog' => '保存题单信息',
		'blog visibility' => '题单可见性'
	));
	$list_editor->show_editor = false;

	$list_editor->save = function($data) {
		global $list_id, $list;
		DB::update("update lists set title = '" . DB::escape($data['title']) . "' where id = {$list_id}");

		if ($data['tags'] !== $list_tags) {
			DB::delete("delete from lists_tags where list_id = {$list_id}");
			foreach ($data['tags'] as $tag) {
				DB::insert("insert into lists_tags (list_id, tag) values ({$list_id}, '" . DB::escape($tag) . "')");
			}
		}

		if ($data['is_hidden'] != $list['is_hidden'] ) {
			DB::update("update lists set is_hidden = {$data['is_hidden']} where id = {$list_id}");
		}
	};

	$list_editor->runAtServer();

	$add_new_problem_form = new UOJForm('add_new_problem');
	$add_new_problem_form->addInput('problem_id', 'text', '题目 ID', '', 
		function ($x) {
			global $myUser, $list_id;

			if (!isSuperUser($myUser)) {
				return '只有超级用户可以编辑题单';
			}

			if (!validateUInt($x)) {
				return 'ID 不合法';
			}
			$problem = queryProblemBrief($x);
			if (!$problem) {
				return '题目不存在';
			}

			if (queryProblemInList($list_id, $x)) {
				return '该题目已经在题单中';
			}
			
			return '';
		},
		null
	);
	$add_new_problem_form->submit_button_config['align'] = 'compressed';
	$add_new_problem_form->submit_button_config['text'] = '添加到题单';
	$add_new_problem_form->handle = function() {
		global $list_id, $myUser;
		$problem_id = $_POST['problem_id'];

		DB::insert("insert into lists_problems (list_id, problem_id) values ({$list_id}, {$problem_id})");
	};
	$add_new_problem_form->runAtServer();

	function removeFromProblemListForm($problem_id) {
		$res_form = new UOJForm("remove_problem_{$problem_id}");
		$input_name = "problem_id_delete_{$problem_id}";
		$res_form->addHidden($input_name, $problem_id, function($problem_id) {
			global $myUser;
			if (!isSuperUser($myUser)) {
				return '只有超级用户可以编辑题单';
			}
		}, null);
		$res_form->handle = function() use ($input_name) {
			global $list_id;
			$problem_id = $_POST[$input_name];
			DB::query("delete from lists_problems where problem_id={$problem_id} and list_id={$list_id}");
		};
		$res_form->submit_button_config['class_str'] = 'btn btn-danger';
		$res_form->submit_button_config['text'] = '删除';
		$res_form->submit_button_config['align'] = 'inline';
		return $res_form;
	}

	$removeProblemForms = array();
	$problem_ids = DB::query("select problem_id from lists_problems where list_id = {$list_id}");
	while ($row = DB::fetch($problem_ids)) {
		$problem_id = $row['problem_id'];
		$removeForm = removeFromProblemListForm($problem_id);
		$removeForm->runAtServer();
		$removeProblemForms[$problem_id] = $removeForm;
	}

	function echoProblem($problem) {
		global $myUser, $removeProblemForms;

		echo '<tr>';
		echo '<td class="text-center">';
		echo '#', $problem['id'], '</td>';
		echo '<td class="text-left">';
		if ($problem['is_hidden']) {
			echo ' <span class="text-danger">[隐藏]</span> ';
		}
		if ($problem['uploader'] == $myUser['username']) {
			echo ' <span class="text-info">[我的题目]</span> ';
		}
		echo '<a href="/problem/', $problem['id'], '">', $problem['title'], '</a>';
		echo '</td>';
		echo '<td class="text-center">';
		$removeProblemForms[$problem['id']]->printHTML();
		echo '</td>';
		echo '</tr>';
	}

	$pag_config = array('page_len' => 40);
	$pag_config['col_names'] = array('problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.uploader as uploader');

	$pag_config['table_name'] = "problems inner join lists_problems lp on lp.list_id = {$list_id} and lp.problem_id = problems.id";

	$pag_config['cond'] = '1';
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);
	?>

<?php echoUOJPageHeader($list['title'] . ' 管理'); ?>

<h1 class="h2">
	<?= $list['title'] ?>（#<?= $list['id'] ?>）管理
</h1>

<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/problem_list/<?= $list['id'] ?>/manage" role="tab">管理</a></li>
	<li class="nav-item"><a class="nav-link" href="/problem_list/<?= $list['id'] ?>" role="tab">返回</a></li>
</ul>

<div class="mb-4">
	<?php $list_editor->printHTML(); ?>
</div>

<table class="table table-hover mb-4">
	<thead>
		<tr>
			<th class="text-center">ID</th>
			<th>题目名称</th>
			<th class="text-center">操作</th>
		</tr>
	</thead>
	<tbody>
	<?php
		foreach ($pag->get() as $idx => $row) {
			echoProblem($row);
			echo "\n";
		}
	?>
	<?php if ($pag->isEmpty()): ?>
		<tr>
			<td class="text-center" colspan="233">
				<?= UOJLocale::get('none') ?>
			</td>
		</tr>
	<?php endif ?>
	</tbody>
</table>
<div class="my-4">
	<?php $add_new_problem_form->printHTML() ?>
</div>

<?= $pag->pagination();	?>

<?php echoUOJPageFooter() ?>
