<?php
	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	$list_id = $_GET['id'];

	if (!validateUInt($list_id) || !($list = queryProblemList($list_id))) {
		become404Page();
	}
	
	if (isSuperUser($myUser)) {
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
	}

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
	if (isSuperUser($myUser)) {
		$problem_ids = DB::query("select problem_id from lists_problems where list_id = {$list_id}");
		while ($row = DB::fetch($problem_ids)) {
			$problem_id = $row['problem_id'];
			$removeForm = removeFromProblemListForm($problem_id);
			$removeForm->runAtServer();
			$removeProblemForms[$problem_id] = $removeForm;
		}
	}

	if (isSuperUser($myUser)) {
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
	}

	function echoProblem($problem) {
		global $myUser, $removeProblemForms;

		if (isProblemVisibleToUser($problem, $myUser)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo '#', $problem['id'], '</td>';
			echo '<td class="text-left">';
			if (isSuperUser($myUser)) {
				$form = $removeProblemForms[$problem['id']];
				$form->printHTML();
			}
			if ($problem['is_hidden']) {
				echo ' <span class="text-danger">[隐藏]</span> ';
			}
			if ($problem['uploader'] == $myUser['username']) {
				echo ' <span class="text-info">[我的题目]</span> ';
			}
			echo '<a href="/problem/', $problem['id'], '">', $problem['title'], '</a>';
			
			if (isset($_COOKIE['show_tags_mode'])) {
				echo ' <span class="text-info" style="font-size: 10px">' . $problem["uploader"] . '</span> ';

				foreach (queryProblemTags($problem['id']) as $tag) {
					echo '<a class="uoj-problem-tag">', '<span class="badge badge-pill badge-secondary">', HTML::escape($tag), '</span>', '</a>';
				}
			}
			echo '</td>';
			if (isset($_COOKIE['show_submit_mode'])) {
				$perc = $problem['submit_num'] > 0 ? round(100 * $problem['ac_num'] / $problem['submit_num']) : 0;
				echo <<<EOD
				<td><a href="/submissions?problem_id={$problem['id']}&min_score=100&max_score=100">&times;{$problem['ac_num']}</a></td>
				<td><a href="/submissions?problem_id={$problem['id']}">&times;{$problem['submit_num']}</a></td>
				<td>
					<div class="progress bot-buffer-no">
						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$perc" aria-valuemin="0" aria-valuemax="100" style="width: $perc%; min-width: 20px;">{$perc}%</div>
					</div>
				</td>
EOD;
			}
			if (isset($_COOKIE['show_difficulty'])) {
				$extra_config = getProblemExtraConfig($problem);
				if ($extra_config['difficulty'] == 0) {
					echo "<td></td>";
				} else {
					echo "<td>{$extra_config['difficulty']}</td>";
				}
			}
			echo '<td class="text-left">', getClickZanBlock('P', $problem['id'], $problem['zan']), '</td>';
			echo '</tr>';
		}
	}
	
	$header = '<tr>';
	$header .= '<th class="text-center" style="width:5em;">ID</th>';
	$header .= '<th>'.UOJLocale::get('problems::problem').'</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::ac').'</th>';
		$header .= '<th class="text-center" style="width:5em;">'.UOJLocale::get('problems::submit').'</th>';
		$header .= '<th class="text-center" style="width:150px;">'.UOJLocale::get('problems::ac ratio').'</th>';
	}
	if (isset($_COOKIE['show_difficulty'])) {
		$header .= '<th class="text-center" style="width:7em;">'.UOJLocale::get('problems::difficulty').'</th>';
	}
	$header .= '<th class="text-center" style="width:180px;">'.UOJLocale::get('appraisal').'</th>';
	$header .= '</tr>';

	$pag_config = array('page_len' => 40);
	$pag_config['col_names'] = array('best_ac_submissions.submission_id as submission_id', 'problems.id as id', 'problems.is_hidden as is_hidden', 'problems.title as title', 'problems.submit_num as submit_num', 'problems.ac_num as ac_num', 'problems.zan as zan', 'problems.extra_config as extra_config', 'problems.uploader as uploader');

	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$myUser['username']}' and problems.id = best_ac_submissions.problem_id inner join lists_problems lp on lp.list_id = {$list_id} and lp.problem_id = problems.id";

	$pag_config['cond'] = '1';
	$pag_config['tail'] = "order by id asc";
	$pag = new Paginator($pag_config);

	$div_classes = array('table-responsive');
	$table_classes = array('table', 'table-bordered', 'table-hover', 'table-striped');
	?>
<?php echoUOJPageHeader(UOJLocale::get('problems lists')); ?>
<?php
	if (isSuperUser($myUser)) {
		echo '<h5>编辑题单信息</h5>';
		echo '<div class="mb-4">';
		$list_editor->printHTML();
		echo '</div>';

		echo '<h5>添加题目到题单</h5>';
		$add_new_problem_form->printHTML();
	}
	?>
<div class="row">
	<div class="col-sm-4">
		<h5>"<?= $list['title'] ?>" 中的题目: </h5>
		<p>(题单 ID: #<?= $list['id'] ?>)</p>
	</div>
	<div class="col-sm-4 order-sm-9 checkbox text-right fine-tune-down">
		<label class="checkbox-inline" for="input-show_tags_mode"><input type="checkbox" id="input-show_tags_mode" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show tags') ?></label>
		<label class="checkbox-inline" for="input-show_submit_mode"><input type="checkbox" id="input-show_submit_mode" <?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show statistics') ?></label>
	</div>
	<div class="col-sm-4 order-sm-5">
	<?php echo $pag->pagination(); ?>
	</div>
</div>
<div class="top-buffer-sm"></div>
<script type="text/javascript">
$('#input-show_tags_mode').click(function() {
	if (this.checked) {
		$.cookie('show_tags_mode', '', {path: '/'});
	} else {
		$.removeCookie('show_tags_mode', {path: '/'});
	}
	location.reload();
});
$('#input-show_submit_mode').click(function() {
	if (this.checked) {
		$.cookie('show_submit_mode', '', {path: '/'});
	} else {
		$.removeCookie('show_submit_mode', {path: '/'});
	}
	location.reload();
});
$('#input-show_difficulty').click(function() {
	if (this.checked) {
		$.cookie('show_difficulty', '', {path: '/'});
	} else {
		$.removeCookie('show_difficulty', {path: '/'});
	}
	location.reload();
});
</script>

<?php
	echo '<div class="', join($div_classes, ' '), '">';
	echo '<table class="', join($table_classes, ' '), '">';
	echo '<thead>';
	echo $header;
	echo '</thead>';
	echo '<tbody>';
	
	foreach ($pag->get() as $idx => $row) {
		echoProblem($row);
		echo "\n";
	}
	if ($pag->isEmpty()) {
		echo '<tr><td class="text-center" colspan="233">'.UOJLocale::get('none').'</td></tr>';
	}
	
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	
	echo $pag->pagination();
	?>

<?php echoUOJPageFooter() ?>
