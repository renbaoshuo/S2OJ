<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (!Auth::check()) {
		become403Page(UOJLocale::get('need login'));
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	$group_id = $_GET['id'];
	$group = queryGroup($group_id);

	if (isSuperUser($myUser)) {
		$group_editor = new UOJBlogEditor();
		$group_editor->name = 'group';
		$group_editor->blog_url = null;
		$group_editor->cur_data = array(
			'title' => $group['title'],
			'tags' => array(),
			'is_hidden' => $group['is_hidden']
		);
		$group_editor->label_text = array_merge($group_editor->label_text, array(
			'view blog' => '保存小组信息',
			'blog visibility' => '小组可见性'
		));
		$group_editor->show_editor = false;
		$group_editor->show_tags = false;

		$group_editor->save = function($data) {
			global $group_id, $group;
			DB::update("update `groups` set title = '".DB::escape($data['title'])."' where id = {$group_id}");

			if ($data['is_hidden'] != $group['is_hidden'] ) {
				DB::update("update `groups` set is_hidden = {$data['is_hidden']} where id = {$group_id}");
			}
		};
		$group_editor->runAtServer();

		$add_new_user_form = new UOJForm('add_new_user');
		$add_new_user_form->addInput('new_username', 'text', '用户名', '', 
			function ($x) {
				global $group_id;

				if (!validateUsername($x)) {
					return '用户名不合法';
				}
				$user = queryUser($x);
				if (!$user) {
					return '用户不存在';
				}

				if (queryUserInGroup($group_id, $x)) {
					return '该用户已经在小组中';
				}

				return '';
			},
			null
		);
		$add_new_user_form->submit_button_config['align'] = 'compressed';
		$add_new_user_form->submit_button_config['text'] = '添加到小组';
		$add_new_user_form->handle = function() {
			global $group_id, $myUser;
			$username = $_POST['new_username'];

			DB::insert("insert into groups_users (group_id, username) values ({$group_id}, '{$username}')");
		};
		$add_new_user_form->runAtServer();

		$delete_user_form = new UOJForm('delete_user');
		$delete_user_form->addInput('del_username', 'text', '用户名', '', 
			function ($x) {
				global $group_id;

				if (!validateUsername($x)) {
					return '用户名不合法';
				}
				if (!queryUserInGroup($group_id, $x)) {
					return '该用户不在小组中';
				}

				return '';
			},
			null
		);
		$delete_user_form->submit_button_config['align'] = 'compressed';
		$delete_user_form->submit_button_config['text'] = '从小组中删除';
		$delete_user_form->handle = function() {
			global $group_id, $myUser;
			$username = $_POST['del_username'];

			DB::query("delete from groups_users where username='{$username}' and group_id={$group_id}");
		};
		$delete_user_form->runAtServer();

		$add_new_assignment_form = new UOJForm('add_new_assignment');
		$add_new_assignment_form->addInput('new_assignment_list_id', 'text', '题单 ID', '', 
			function ($x) {
				global $group_id;

				if (!validateUInt($x)) {
					return '题单 ID 不合法';
				}
				$list = queryProblemList($x);
				if (!$list) {
					return '题单不存在';
				}
				if ($list['is_hidden'] != 0) {
					return '题单是隐藏的';
				}

				if (queryAssignmentByGroupListID($group_id, $x)) {
					return '该题单已经在作业中';
				}

				return '';
			},
			null
		);

		$default_ddl = new DateTime();
		$default_ddl->setTime(17, 0, 0);
		$default_ddl->add(new DateInterval("P7D"));

		$add_new_assignment_form->addInput('new_assignment_deadline', 'text', '截止时间', $default_ddl->format('Y-m-d H:i'), 
			function ($x) {
				$ddl = DateTime::createFromFormat('Y-m-d H:i', $x);
				if (!$ddl) {
					return '截止时间格式不正确，请以类似 "2020-10-1 17:00" 的格式输入';
				}

				return '';
			},
			null
		);

		$add_new_assignment_form->submit_button_config['align'] = 'compressed';
		$add_new_assignment_form->submit_button_config['text'] = '添加作业';
		$add_new_assignment_form->handle = function() {
			global $group_id, $myUser;
			$list_id = $_POST['new_assignment_list_id'];
			$ddl = DateTime::createFromFormat('Y-m-d H:i', $_POST['new_assignment_deadline']);
			$ddl_str = $ddl->format('Y-m-d H:i:s');

			DB::insert("insert into assignments (group_id, list_id, create_time, deadline) values ({$group_id}, '{$list_id}', now(), '{$ddl_str}')");
		};
		$add_new_assignment_form->runAtServer();

		$remove_assignment_form = new UOJForm('remove_assignment');
		$remove_assignment_form->addInput('remove_assignment_list_id', 'text', '题单 ID', '', 
			function ($x) {
				global $group_id;

				if (!validateUInt($x)) {
					return '题单 ID 不合法';
				}
				if (!queryAssignmentByGroupListID($group_id, $x)) {
					return '该题单不在作业中';
				}

				return '';
			},
			null
		);

		$remove_assignment_form->submit_button_config['align'] = 'compressed';
		$remove_assignment_form->submit_button_config['text'] = '删除作业';
		$remove_assignment_form->handle = function() {
			global $group_id, $myUser;
			$list_id = $_POST['remove_assignment_list_id'];

			DB::query("delete from assignments where list_id='{$list_id}' and group_id={$group_id}");
		};
		$remove_assignment_form->runAtServer();

        $announcement_form = new UOJForm('announcement_form');
        $announcement_form->addVTextArea('announcement_content', '公告', $group['announcement'], 
			function ($x) {
				return '';
			},
			null
		);
        $announcement_form->submit_button_config['align'] = 'compressed';
		$announcement_form->submit_button_config['text'] = '更新公告';
		$announcement_form->handle = function() {
			global $group_id, $myUser;

			$content = $_POST['announcement_content'];
			$esc_content = DB::escape($content);
			DB::query("update groups set announcement = '{$esc_content}' where id = {$group_id}");
		};
		$announcement_form->runAtServer();
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<h2 style="margin-top: 24px"><?= $group['title'] ?></h2>
<p>(<b>小组 ID</b>: <?= $group['id'] ?>)</p>

<div class="row">
	<div class="col-sm-12 mt-4">
		<h5><?= UOJLocale::get('news') ?></h5>
		<ul>
		<?php
				$current_ac = queryGroupCurrentAC($group['id']);
	foreach ($current_ac as $ac) {
		echo '<li>';
		echo getUserLink($ac['submitter']);
		echo ' 解决了问题 ';
		echo '<a href="/problem/', $ac['problem_id'], '">', $ac['problem_title'], '</a> ';
		echo '<time class="time">(', $ac['submit_time'], ')</time>';
		echo '</li>';
	}
	if (count($current_ac) == 0) {
		echo '暂无最新动态';
	}
	?>
		</ul>
	</div>
</div>

<div class="row">
	<div class="col-sm-12 mt-4">
		<h5><?= UOJLocale::get('assignments') ?></h5>
		<ul>
		<?php
			$assignments = queryGroupActiveAssignments($group['id']);
	foreach ($assignments as $ass) {
		$ddl = DateTime::createFromFormat('Y-m-d H:i:s', $ass['deadline']);
		$create_time = DateTime::createFromFormat('Y-m-d H:i:s', $ass['create_time']);
		$now = new DateTime();

		if ($now->getTimestamp() - $ddl->getTimestamp() > 604800) {
			continue;
		}  // 7d

		echo '<li>';
		echo "<a href=\"/problem_list/{$ass['list_id']}\">{$ass['title']} (题单 #{$ass['list_id']})</a>";

		if ($ddl < $now) {
			echo '<sup style="color:red">&nbsp;overdue</sup>';
		} elseif ($ddl->getTimestamp() - $now->getTimestamp() < 86400) {  // 1d
			echo '<sup style="color:red">&nbsp;soon</sup>';
		} elseif ($now->getTimestamp() - $create_time->getTimestamp() < 86400) {  // 1d
			echo '<sup style="color:red">&nbsp;new</sup>';
		}

		$ddl_str = $ddl->format('Y-m-d H:i');
		echo " (截止时间: {$ddl_str}，<a href=\"/assignment/{$ass['id']}\">查看完成情况</a>)";
		echo '</li>';
	}

	if (count($assignments) == 0) {
		echo '<p>暂无作业</p>';
	}
	?>
		</ul>
	</div>
</div>

<div class="row">
	<div class="col-sm-12 mt-4">
		<h5><?= UOJLocale::get('top solver') ?></h5>
		<?php echoRanklist(array('echo_full' => true, 'group_id' => $group_id, 'by_accepted' => true)) ?>
	</div>
</div>

<?php if (isSuperUser($myUser)): ?>
<h5>编辑小组信息</h5>
<div class="mb-4">
    <?php $group_editor->printHTML(); ?>
</div>

<h5>编辑小组公告</h5>
<div>
	<?php $announcement_form->printHTML(); ?>
</div>

<h5>添加用户到小组</h5>
<?php $add_new_user_form->printHTML(); ?>

<h5>从小组中删除用户</h5>
<?php $delete_user_form->printHTML(); ?>

<h5>为小组添加作业</h5>
<?php $add_new_assignment_form->printHTML(); ?>

<h5>删除小组的作业</h5>
<?php $remove_assignment_form->printHTML(); ?>
<?php endif ?>

<?php echoUOJPageFooter() ?>
