<?php
	$REQUIRE_LIB['md5'] = '';
	$REQUIRE_LIB['jquery.query'] = '';

	requirePHPLib('form');
	requirePHPLib('judger');
	
	if ($myUser == null || !isSuperUser($myUser)) {
		become403Page();
	}

	$register_form = new UOJForm('register');
	$register_form->submit_button_config['align'] = 'compressed';
	$register_form->addInput('new_username', 'text', '用户名', '',
		function ($new_username) {
			if (!validateUsername($new_username)) {
				return '用户名不合法';
			}
			if (queryUser($new_username)) {
				return '该用户已存在';
			}
			return '';
		},
		null
	);
	$register_form->addInput('new_password', 'password', '密码', '',
		function ($new_password) {
			return '';
		},
		null
	);
	$register_form->addInput('new_realname', 'text', '真实姓名', '',
		function ($new_realname) {
			return '';
		},
		null
	);
	$register_form->handle = function() {
		$new_username = $_POST['new_username'];
		$new_password = $_POST['new_password'];
		$new_realname = $_POST['new_realname'];
		$new_password = hash_hmac('md5', $new_password, getPasswordClientSalt());
		$new_password = getPasswordToStore($new_password, $new_username);
		$svn_pw = uojRandString(10);

		DB::query("insert into user_info (username, realname, password, svn_password, register_time, usergroup) values ('$new_username', '$new_realname', '$new_password', '$svn_pw', now(), 'U')");
	};
	$register_form->runAtServer();

	$change_password_form = new UOJForm('change_password');
	$change_password_form->submit_button_config['align'] = 'compressed';
	$change_password_form->addInput('p_username', 'text', '用户名', '',
		function ($p_username) {
			if (!validateUsername($p_username)) {
				return '用户名不合法';
			}
			if (!queryUser($p_username)) {
				return '用户不存在';
			}
			return '';
		},
		null
	);
	$change_password_form->addInput('p_password', 'password', '密码', '',
		function ($p_password) {
			return '';
		},
		null
	);
	$change_password_form->handle = function() {
		$p_username = $_POST['p_username'];
		$p_password = $_POST['p_password'];
		$p_password = hash_hmac('md5', $p_password, getPasswordClientSalt());
		$p_password = getPasswordToStore($p_password, $p_username);

		DB::query("update user_info set password = '$p_password' where username = '$p_username'");
	};
	$change_password_form->runAtServer();

	$change_realname_form = new UOJForm('change_realname');
	$change_realname_form->submit_button_config['align'] = 'compressed';
	$change_realname_form->addInput('r_username', 'text', '用户名', '',
		function ($r_username) {
			if (!validateUsername($r_username)) {
				return '用户名不合法';
			}
			if (!queryUser($r_username)) {
				return '用户不存在';
			}
			return '';
		},
		null
	);
	$change_realname_form->addInput('r_realname', 'text', '真实姓名', '',
		function ($r_realname) {
			return '';
		},
		null
	);
	$change_realname_form->handle = function() {
		$r_username = $_POST['r_username'];
		$r_realname = $_POST['r_realname'];

		DB::query("update user_info set realname = '$r_realname' where username = '$r_username'");
	};
	$change_realname_form->runAtServer();

	$user_form = new UOJForm('user');
	$user_form->submit_button_config['align'] = 'compressed';
	$user_form->addInput('username', 'text', '用户名', '',
		function ($username) {
			if (!validateUsername($username)) {
				return '用户名不合法';
			}
			if (!queryUser($username)) {
				return '用户不存在';
			}
			return '';
		},
		null
	);
	$options = array(
		'banneduser' => '设为封禁用户',
		'normaluser' => '设为普通用户',
		'superuser' => '设为超级用户'
	);
	$user_form->addSelect('op_type', $options, '操作类型', '');
	$user_form->handle = function() {
		global $user_form;
		
		$username = $_POST['username'];
		switch ($_POST['op_type']) {
			case 'banneduser':
				DB::update("update user_info set usergroup = 'B' where username = '{$username}'");
				break;
			case 'normaluser':
				DB::update("update user_info set usergroup = 'U' where username = '{$username}'");
				break;
			case 'superuser':
				DB::update("update user_info set usergroup = 'S' where username = '{$username}'");
				break;
		}
	};
	$user_form->runAtServer();
	
	$blog_link_contests = new UOJForm('blog_link_contests');
	$blog_link_contests->addInput('blog_id', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryBlog($x)) {
				return '博客不存在';
			}
			return '';
		},
		null
	);
	$blog_link_contests->addInput('contest_id', 'text', '比赛ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryContest($x)) {
				return '比赛不存在';
			}
			return '';
		},
		null
	);
	$blog_link_contests->addInput('title', 'text', '标题', '',
		function ($x) {
			return '';
		},
		null
	);
	$options = array(
		'add' => '添加',
		'del' => '删除'
	);
	$blog_link_contests->addSelect('op-type', $options, '操作类型', '');
	$blog_link_contests->handle = function() {
		$blog_id = $_POST['blog_id'];
		$contest_id = $_POST['contest_id'];
		$str = DB::selectFirst(("select * from contests where id='${contest_id}'"));
		$all_config = json_decode($str['extra_config'], true);
		$config = $all_config['links'];

		$n = count($config);
		
		if ($_POST['op-type'] == 'add') {
			$row = array();
			$row[0] = $_POST['title'];
			$row[1] = $blog_id;
			$config[$n] = $row;
		}
		if ($_POST['op-type'] == 'del') {
			for ($i = 0; $i < $n; $i++) {
				if ($config[$i][1] == $blog_id) {
					$config[$i] = $config[$n - 1];
					unset($config[$n - 1]);
					break;
				}
			}
		}

		$all_config['links'] = $config;
		$str = json_encode($all_config);
		$str = DB::escape($str);
		DB::query("update contests set extra_config='${str}' where id='${contest_id}'");
	};
	$blog_link_contests->runAtServer();
	
	$blog_link_index = new UOJForm('blog_link_index');
	$blog_link_index->addInput('blog_id2', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryBlog($x)) {
				return '博客不存在';
			}
			return '';
		},
		null
	);
	$blog_link_index->addInput('blog_level', 'text', '置顶级别（删除不用填）', '0',
		function ($x) {
			if (!validateUInt($x)) {
				return '数字不合法';
			}
			if ($x > 3) {
				return '该级别不存在';
			}
			return '';
		},
		null
	);
	$options = array(
		'add' => '添加',
		'del' => '删除'
	);
	$blog_link_index->addSelect('op-type2', $options, '操作类型', '');
	$blog_link_index->handle = function() {
		$blog_id = $_POST['blog_id2'];
		$blog_level = $_POST['blog_level'];
		if ($_POST['op-type2'] == 'add') {
			if (DB::selectFirst("select * from important_blogs where blog_id = {$blog_id}")) {
				DB::update("update important_blogs set level = {$blog_level} where blog_id = {$blog_id}");
			} else {
				DB::insert("insert into important_blogs (blog_id, level) values ({$blog_id}, {$blog_level})");
			}
		}
		if ($_POST['op-type2'] == 'del') {
			DB::delete("delete from important_blogs where blog_id = {$blog_id}");
		}
	};
	$blog_link_index->runAtServer();
	
	$blog_deleter = new UOJForm('blog_deleter');
	$blog_deleter->addInput('blog_del_id', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryBlog($x)) {
				return '博客不存在';
			}
			return '';
		},
		null
	);
	$blog_deleter->handle = function() {
		deleteBlog($_POST['blog_del_id']);
	};
	$blog_deleter->runAtServer();

	$contest_submissions_deleter = new UOJForm('contest_submissions');
	$contest_submissions_deleter->addInput('contest_id', 'text', '比赛ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryContest($x)) {
				return '博客不存在';
			}
			return '';
		},
		null
	);
	$contest_submissions_deleter->handle = function() {
		$contest = queryContest($_POST['contest_id']);
		genMoreContestInfo($contest);
		
		$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']}");
		foreach ($contest_problems as $problem) {
			$submissions = DB::selectAll("select * from submissions where problem_id = {$problem['problem_id']} and submit_time < '{$contest['start_time_str']}'");
			foreach ($submissions as $submission) {
				$content = json_decode($submission['content'], true);
				unlink(UOJContext::storagePath().$content['file_name']);
				DB::delete("delete from submissions where id = {$submission['id']}");
				updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
			}
		}
	};
	$contest_submissions_deleter->runAtServer();

	$custom_test_deleter = new UOJForm('custom_test_deleter');
	$custom_test_deleter->addInput('last', 'text', '删除末尾记录', '5',
		function ($x, &$vdata) {
			if (!validateUInt($x)) {
				return '不合法';
			}
			$vdata['last'] = $x;
			return '';
		},
		null
	);
	$custom_test_deleter->handle = function(&$vdata) {
		$all = DB::selectAll("select * from custom_test_submissions order by id asc limit {$vdata['last']}");
		foreach ($all as $submission) {
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath().$content['file_name']);
		}
		DB::delete("delete from custom_test_submissions order by id asc limit {$vdata['last']}");
	};
	$custom_test_deleter->runAtServer();

	$judger_adder = new UOJForm('judger_adder');
	$judger_adder->addInput('judger_adder_name', 'text', '评测机名称', '',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return '不合法';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'")!=0) {
				return '不合法';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_adder->handle = function(&$vdata) {
		$password=uojRandString(32);
		DB::insert("insert into judger_info (judger_name,password) values('{$vdata['name']}','{$password}')");
	};
	$judger_adder->runAtServer();
	
	$judger_deleter = new UOJForm('judger_deleter');
	$judger_deleter->addInput('judger_deleter_name', 'text', '评测机名称', '',
		function ($x, &$vdata) {
			if (!validateUsername($x)) {
				return '不合法';
			}
			if (DB::selectCount("select count(*) from judger_info where judger_name='$x'")!=1) {
				return '不合法';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$judger_deleter->handle = function(&$vdata) {
		DB::delete("delete from judger_info where judger_name='{$vdata['name']}'");
	};
	$judger_deleter->runAtServer();

	$paste_deleter = new UOJForm('paste_deleter');
	$paste_deleter->addInput('paste_deleter_name', 'text', 'Paste ID', '',
		function ($x, &$vdata) {
			if (DB::selectCount("select count(*) from pastes where `index`='$x'")==0) {
				return '不合法';
			}
			$vdata['name'] = $x;
			return '';
		},
		null
	);
	$paste_deleter->handle = function(&$vdata) {
		DB::delete("delete from pastes where `index` = '${vdata['name']}'");
	};
	$paste_deleter->runAtServer();
	
	$judgerlist_cols = array('judger_name', 'password');
	$judgerlist_config = array();
	$judgerlist_header_row = <<<EOD
	<tr>
		<th>评测机名称</th>
		<th>密码</th>
	</tr>
EOD;
	$judgerlist_print_row = function($row) {
		echo <<<EOD
			<tr>
				<td>{$row['judger_name']}</td>
				<td>{$row['password']}</td>
			</tr>
EOD;
	};
	
	$userlist_cols = array('username', 'usergroup', 'register_time');
	$userlist_config = array('page_len' => 20,
			'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped'));
	$userlist_header_row = <<<EOD
	<tr>
		<th>用户名</th>
		<th style="width: 6em">用户类别</th>
		<th style="width: 12em">注册时间</th>
	</tr>
EOD;

	$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

	$user_list_cond = array();
	if ($cur_tab === 'users') {
		if (isset($_GET['username']) && $_GET['username'] != "") {
			$user_list_cond[] = "username like '%" . DB::escape($_GET['username']) . "%'";
		}
		if (isset($_GET['usergroup']) && $_GET['usergroup'] != "") {
			$user_list_cond[] = "usergroup = '" . DB::escape($_GET['usergroup']) . "'";
		}
	}
	if ($user_list_cond) {
		$user_list_cond = join($user_list_cond, ' and ');
	} else {
		$user_list_cond = '1';
	}

	$userlist_print_row = function($row) {
		$hislink = getUserLink($row['username']);
		echo <<<EOD
			<tr>
				<td>${hislink}</td>
				<td>{$row['usergroup']}</td>
				<td>{$row['register_time']}</td>
			</tr>
EOD;
	};

	$tabs_info = array(
		'users' => array(
			'name' => '用户管理',
			'url' => "/super-manage/users"
		),
		'blogs' => array(
			'name' => '博客管理',
			'url' => "/super-manage/blogs"
		),
		'submissions' => array(
			'name' => '提交记录',
			'url' => "/super-manage/submissions"
		),
		'custom-test' => array(
			'name' => '自定义测试',
			'url' => '/super-manage/custom-test'
		),
		'judger' => array(
			'name' => '评测机管理',
			'url' => '/super-manage/judger'
		),
		'paste' => array(
			'name' => '剪贴板管理',
			'url' => '/super-manage/paste'
		)
	);
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}
?>
<?php
	requireLib('shjs');
	requireLib('morris');
?>
<?php echoUOJPageHeader('系统管理') ?>
<div class="row">
	<div class="col-sm-3">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills flex-column') ?>
	</div>
	
	<div class="col-sm-9">
		<?php if ($cur_tab === 'users'): ?>
			<h3>添加新用户</h3>
			<?php $register_form->printHTML(); ?>
			<h3>修改用户密码</h3>
			<?php $change_password_form->printHTML(); ?>
			<h3>用户类别设置</h3>
			<?php $user_form->printHTML(); ?>
			<h3>修改用户真实姓名</h3>
			<?php $change_realname_form->printHTML(); ?>
			<h3>用户名单</h3>
			<div id="user-query">
				<form class="form-horizontal uoj-form-compressed" target="_self" method="GET">
					<div class="form-group">
						<label for="username" class="col-sm-2 control-label">用户名</label>
						<div class="col-sm-3">
							<input type="text" class="form-control" name="username" id="user-query-username" value="" />
						</div>
					</div>
					<div class="form-group">
						<label for="usergroup" class="col-sm-2 control-label">用户类别</label>
						<div class="col-sm-3">
							<select class="form-control" id="user-query-usergroup" name="usergroup">
								<option value="">*: 所有用户</option>
								<option value="B">B: 封禁用户</option>
								<option value="U">U: 普通用户</option>
								<option value="S">S: 超级用户</option>
							</select>
						</div>
					</div><div class="text-center"><button type="submit" id="user-query-submit" class="mt-2 btn btn-secondary">查询</button></div>
				</form>
			</div>
			<?php echoLongTable($userlist_cols, 'user_info', $user_list_cond, 'order by username asc', $userlist_header_row, $userlist_print_row, $userlist_config) ?>
		<?php elseif ($cur_tab === 'blogs'): ?>
			<div>
				<h4>添加到比赛链接</h4>
				<?php $blog_link_contests->printHTML(); ?>
			</div>

			<div>
				<h4>添加到公告</h4>
				<?php $blog_link_index->printHTML(); ?>
			</div>
		
			<div>
				<h4>删除博客</h4>
				<?php $blog_deleter->printHTML(); ?>
			</div>
		<?php elseif ($cur_tab === 'submissions'): ?>
			<div>
				<h4>删除赛前提交记录</h4>
				<?php $contest_submissions_deleter->printHTML(); ?>
			</div>
			<div>
				<h4>测评失败的提交记录</h4>
				<?php echoSubmissionsList("result_error = 'Judgement Failed'", 'order by id desc', array('result_hidden' => ''), $myUser); ?>
			</div>
		<?php elseif ($cur_tab === 'custom-test'): ?>
		<?php $custom_test_deleter->printHTML() ?>
		<?php
			$submissions_pag = new Paginator(array(
				'col_names' => array('*'),
				'table_name' => 'custom_test_submissions',
				'cond' => '1',
				'tail' => 'order by id asc',
				'page_len' => 5
			));
			foreach ($submissions_pag->get() as $submission) {
				$problem = queryProblemBrief($submission['problem_id']);
				$submission_result = json_decode($submission['result'], true);
				echo '<dl class="dl-horizontal">';
				echo '<dt>id</dt>';
				echo '<dd>', "#{$submission['id']}", '</dd>';
				echo '<dt>problem_id</dt>';
				echo '<dd>', "#{$submission['problem_id']}", '</dd>';
				echo '<dt>submit time</dt>';
				echo '<dd>', $submission['submit_time'], '</dd>';
				echo '<dt>submitter</dt>';
				echo '<dd>', $submission['submitter'], '</dd>';
				echo '<dt>judge_time</dt>';
				echo '<dd>', $submission['judge_time'], '</dd>';
				echo '</dl>';
				echoSubmissionContent($submission, getProblemCustomTestRequirement($problem));
				echoCustomTestSubmissionDetails($submission_result['details'], "submission-{$submission['id']}-details");
			}
		?>
		<?= $submissions_pag->pagination() ?>
		<?php elseif ($cur_tab === 'judger'): ?>
			<div>
				<h4>添加评测机</h4>
				<?php $judger_adder->printHTML(); ?>
			</div>
			<div>
				<h4>删除评测机</h4>
				<?php $judger_deleter->printHTML(); ?>
			</div>
			<h3>评测机列表</h3>
			<?php echoLongTable($judgerlist_cols, 'judger_info', "1=1", '', $judgerlist_header_row, $judgerlist_print_row, $judgerlist_config) ?>
		<?php elseif ($cur_tab === 'paste'): ?>
			<div>
				<h4>Paste管理</h4>
				<?php echoPastesList() ?>
			</div>
		<?php endif ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
