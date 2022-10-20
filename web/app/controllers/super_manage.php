<?php
	requireLib('bootstrap5');
	requireLib('md5');
	requireLib('jquery.query');
	requirePHPLib('form');
	requirePHPLib('judger');

	define('SCRIPT_REFRESH_AS_GET', '<script>;window.location = window.location.origin + window.location.pathname + (window.location.search.length ? window.location.search + "&" : "?") + "_=" + (+new Date()) + window.location.hash;</script>');

	if (!isSuperUser($myUser)) {
		become403Page();
	}

	$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'index';

	$tabs_info = [
		'index' => [
			'name' => '首页管理',
			'url' => "/super_manage/index",
		],
		'users' => [
			'name' => '用户管理',
			'url' => "/super_manage/users",
		],
		'submissions' => [
			'name' => '提交记录',
			'url' => "/super_manage/submissions",
		],
		'custom_test' => [
			'name' => '自定义测试',
			'url' => "/super_manage/custom_test",
		],
		'image_hosting' => [
			'name' => '图床管理',
			'url' => "/super_manage/image_hosting",
		],
	];
	
	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

	if ($cur_tab == 'index') {
		// ========== 公告 ==========
		if (isset($_POST['submit-delete_announcement']) && $_POST['submit-delete_announcement'] == 'delete_announcement') {
			crsf_defend();

			$blog_id = $_POST['blog_id'];

			if (!validateUInt($blog_id)) {
				die('<script>alert("移除失败：博客 ID 无效");</script>' . SCRIPT_REFRESH_AS_GET);
			}
			
			DB::delete("DELETE FROM important_blogs WHERE blog_id = {$blog_id}");

			die('<script>alert("移除成功！");</script>' . SCRIPT_REFRESH_AS_GET);
		}

		$announcements = DB::selectAll("SELECT blogs.id as id, blogs.title as title, blogs.poster as poster, user_info.realname as realname, blogs.post_time as post_time, important_blogs.level as level, blogs.is_hidden as is_hidden FROM important_blogs INNER JOIN blogs ON important_blogs.blog_id = blogs.id INNER JOIN user_info ON blogs.poster = user_info.username ORDER BY level DESC, important_blogs.blog_id DESC");

		$add_announcement_form = new UOJForm('add_announcement');
		$add_announcement_form->addInput('blog_id', 'text', '博客 ID', '',
			function($id, &$vdata) {
				if (!validateUInt($id)) {
					return '博客 ID 无效';
				}

				if (!queryBlog($id)) {
					return '博客不存在';
				}

				$vdata['blog_id'] = $id;

				return '';
			},
			null
		);
		$add_announcement_form->addInput('blog_level', 'text', '置顶级别', '0',
			function ($x, &$vdata) {
				if (!validateUInt($x)) {
					return '数字不合法';
				}

				if ($x > 3) {
					return '该级别不存在';
				}

				$vdata['level'] = $x;
				
				return '';
			},
			null
		);
		$add_announcement_form->handle = function(&$vdata) {
			$blog_id = $vdata['blog_id'];
			$blog_level = $vdata['level'];
			
			if (DB::selectFirst("select * from important_blogs where blog_id = {$blog_id}")) {
				DB::update("update important_blogs set level = {$blog_level} where blog_id = {$blog_id}");
			} else {
				DB::insert("insert into important_blogs (blog_id, level) values ({$blog_id}, {$blog_level})");
			}
		};
		$add_announcement_form->submit_button_config['align'] = 'compressed';
		$add_announcement_form->submit_button_config['text'] = '提交';
		$add_announcement_form->succ_href = '/super_manage/index#announcements';
		$add_announcement_form->runAtServer();

		// ========== 倒计时 ==========
		if (isset($_POST['submit-delete_countdown']) && $_POST['submit-delete_countdown'] == 'delete_countdown') {
			crsf_defend();

			$countdown_id = $_POST['countdown_id'];

			if (!validateUInt($countdown_id)) {
				die('<script>alert("删除失败：倒计时 ID 无效");</script>' . SCRIPT_REFRESH_AS_GET);
			}

			DB::delete("DELETE FROM countdowns WHERE id = {$countdown_id}");

			die('<script>alert("删除成功！");</script>' . SCRIPT_REFRESH_AS_GET);
		}

		$countdowns = DB::selectAll("SELECT id, title, endtime FROM countdowns ORDER BY endtime ASC");

		$add_countdown_form = new UOJForm('add_countdown');
		$add_countdown_form->addInput('countdown_title', 'text', '标题', '',
			function($title, &$vdata) {
				if ($title == '') {
					return '标题不能为空';
				}

				$vdata['title'] = $title;

				return '';
			},
			null
		);
		$add_countdown_form->addInput('countdown_endtime', 'text', '结束时间', date("Y-m-d H:i:s"),
			function($endtime, &$vdata) {
				try {
					$vdata['endtime'] = new DateTime($endtime);
				} catch (Exception $e) {
					return '无效时间格式';
				}

				return '';
			},
			null
		);
		$add_countdown_form->handle = function(&$vdata) {
			$esc_title = DB::escape($vdata['title']);
			$esc_endtime = DB::escape($vdata['endtime']->format('Y-m-d H:i:s'));

			DB::insert("INSERT INTO countdowns (title, endtime) VALUES ('{$esc_title}', '{$esc_endtime}')");
		};
		$add_countdown_form->submit_button_config['align'] = 'compressed';
		$add_countdown_form->submit_button_config['text'] = '添加';
		$add_countdown_form->succ_href = '/super_manage/index#countdowns';
		$add_countdown_form->runAtServer();

		// ========== 常用链接 ==========
		if (isset($_POST['submit-delete_link']) && $_POST['submit-delete_link'] == 'delete_link') {
			crsf_defend();

			$item_id = $_POST['item_id'];

			if (!validateUInt($item_id)) {
				die('<script>alert("删除失败：ID 无效");</script>' . SCRIPT_REFRESH_AS_GET);
			}

			DB::delete("DELETE FROM links WHERE id = {$item_id}");

			die('<script>alert("删除成功！");</script>' . SCRIPT_REFRESH_AS_GET);
		}

		$links = DB::selectAll("SELECT `id`, `title`, `url`, `level` FROM `friend_links` ORDER BY `level` DESC, `id` ASC");

		$add_link_form = new UOJForm('add_link');
		$add_link_form->addInput('link_title', 'text', '标题', '',
			function($title, &$vdata) {
				if ($title == '') {
					return '标题不能为空';
				}

				$vdata['title'] = $title;

				return '';
			},
			null
		);
		$add_link_form->addInput('link_url', 'text', '链接', '',
			function($url, &$vdata) {
				if (!validateURL($url)) {
					return '链接不合法';
				}

				$vdata['url'] = $url;

				return '';
			},
			null
		);
		$add_link_form->addInput('link_level', 'text', '权重', '10',
			function($level, &$vdata) {
				if (!validateUInt($level)) {
					return '数字不合法';
				}

				$vdata['level'] = $level;
				
				return '';
			},
			null
		);
		$add_link_form->handle = function(&$vdata) {
			$esc_title = DB::escape($vdata['title']);
			$esc_url = DB::escape($vdata['url']);
			$level = $vdata['level'];
			
			DB::insert("INSERT INTO friend_links (title, url, level) VALUES ('{$esc_title}', '{$esc_url}', {$level})");
		};
		$add_link_form->submit_button_config['align'] = 'compressed';
		$add_link_form->submit_button_config['text'] = '添加';
		$add_link_form->succ_href = '/super_manage/index#links';
		$add_link_form->runAtServer();
	} elseif ($cur_tab == 'users') {
		//
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('system manage')) ?>

<h1 class="h2">
	<?= UOJLocale::get('system manage') ?>
</h1>


<div class="row mt-4">
<!-- left col -->
<div class="col-md-3">

<div class="list-group">
	<?php foreach ($tabs_info as $id => $tab): ?>
	<a
		role="button"
		class="list-group-item list-group-item-action <?= $cur_tab == $id ? 'active' : '' ?>"
		href="<?= $tab['url'] ?>">
		<?= $tab['name'] ?>
	</a>
	<?php endforeach ?>
</div>

</div>
<!-- end left col -->

<!-- right col -->
<div class="col-md-9">
<?php if ($cur_tab == 'index'): ?>
<div class="card">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" href="#announcements" data-bs-toggle="tab" data-bs-target="#announcements">公告</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#countdowns" data-bs-toggle="tab" data-bs-target="#countdowns">倒计时</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#links" data-bs-toggle="tab" data-bs-target="#links">常用链接</a>
			</li>
		</ul>
	</div>
	<div class="card-body">
		<div class="tab-content">
			<!-- 公告 -->
			<div class="tab-pane active" id="announcements">
				<div id="announcements-list"></div>

				<script>
					var announcements = <?= json_encode($announcements) ?>;

					$('#announcements-list').long_table(
						announcements,
						1,
						'<tr>' +
							'<th style="width:3em">ID</th>' +
							'<th style="width:14em">标题</th>' +
							'<th style="width:8em">发布者</th>' +
							'<th style="width:8em">发布时间</th>' +
							'<th style="width:6em">置顶等级</th>' +
							'<th style="width:8em">操作</th>' +
						'</tr>',
						function(row) {
							var col_tr = '';

							col_tr += '<tr>';

							col_tr += '<td>' + row['id'] + '</td>';
							col_tr += '<td>' +
									(row['is_hidden'] ? '<span class="text-danger">[隐藏]</span> ' : '') +
									'<a class="text-decoration-none" href="/blogs/' + row['id'] + '">' +
										row['title'] +
									'</a>' +
								'</td>';
							col_tr += '<td>' + getUserLink(row['poster'], row['realname']) + '</td>';
							col_tr += '<td>' + row['post_time'] + '</td>';
							col_tr += '<td>' + row['level'] + '</td>';
							col_tr += '<td>' +
										'<a class="text-decoration-none d-inline-block align-middle" href="/post/' + row['id'] + '/write">编辑</a>' +
										'<form class="d-inline-block ms-2" method="POST" onsubmit=\'return confirm("你真的要移除这条公告吗？")\'>' +
											'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
											'<input type="hidden" name="blog_id" value="' + row['id'] + '">' +
											'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_announcement" value="delete_announcement">移除</button>' +
										'</form>' +
									'</td>';

							col_tr += '</tr>';

							return col_tr;
						},
						{
							div_classes: ['table-responsive'],
							table_classes: ['table', 'align-middle'],
							page_len: 20,
						}
					);
				</script>

				<h5>添加/修改公告</h5>
				<?php $add_announcement_form->printHTML(); ?>
			</div>

			<!-- 倒计时 -->
			<div class="tab-pane" id="countdowns">
				<div id="countdowns-list"></div>

				<script>
					var countdowns = <?= json_encode($countdowns) ?>;

					$('#countdowns-list').long_table(
						countdowns,
						1,
						'<tr>' +
							'<th style="width:14em">标题</th>' +
							'<th style="width:8em">结束时间</th>' +
							'<th style="width:6em">操作</th>' +
						'</tr>',
						function(row) {
							var col_tr = '';

							col_tr += '<tr>';

							col_tr += '<td>' + row['title'] + '</td>';
							col_tr += '<td>' + row['endtime'] + '</td>';
							col_tr += '<td>' +
									'<form method="POST" onsubmit=\'return confirm("你真的要删除这个倒计时吗？")\'>' +
										'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
										'<input type="hidden" name="countdown_id" value="' + row['id'] + '">' +
										'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_countdown" value="delete_countdown">删除</button>' +
									'</form>' +
								'</td>';

							col_tr += '</tr>';

							return col_tr;
						},
						{
							div_classes: ['table-responsive'],
							table_classes: ['table', 'align-middle'],
							page_len: 20,
						}
					);
				</script>

				<h5>添加倒计时</h5>
				<?php $add_countdown_form->printHTML(); ?>
			</div>

			<!-- 常用链接 -->
			<div class="tab-pane" id="links">
				<div id="links-list"></div>

				<script>
					var links = <?= json_encode($links) ?>;

					$('#links-list').long_table(
						links,
						1,
						'<tr>' +
							'<th style="width:18em">标题</th>' +
							'<th style="width:26em">链接</th>' +
							'<th style="width:14em">操作</th>' +
						'</tr>',
						function(row) {
							var col_tr = '';

							col_tr += '<tr>';

							col_tr += '<td>' + row['title'] + '</td>';
							col_tr += '<td>' + row['url'] + '</td>';
							col_tr += '<td>' +
									'<form method="POST" onsubmit=\'return confirm("你真的要删除这条链接吗？")\'>' +
										'<input type="hidden" name="_token" value="<?= crsf_token() ?>">' +
										'<input type="hidden" name="link_id" value="' + row['id'] + '">' +
										'<button class="btn btn-link text-danger text-decoration-none p-0" type="submit" name="submit-delete_link" value="delete_link">删除</button>' +
									'</form>' +
								'</td>';

							col_tr += '</tr>';

							return col_tr;
						},
						{
							div_classes: ['table-responsive'],
							table_classes: ['table', 'align-middle'],
							page_len: 20,
						}
					);
				</script>

				<h5>添加常用链接</h5>
				<?php $add_link_form->printHTML(); ?>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	// Javascript to enable link to tab
	var hash = location.hash.replace(/^#/, '');
	if (hash) {
		bootstrap.Tab.jQueryInterface.call($('.nav-tabs a[href="#' + hash + '"]'), 'show').blur();
	}

	// Change hash for page-reload
	$('.nav-tabs a').on('shown.bs.tab', function(e) {
		window.location.hash = e.target.hash;
	});
});
</script>
<?php elseif ($cur_tab == 'users'): ?>
<div class="card">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs">
			<li class="nav-item">
				<a class="nav-link active" href="#" data-bs-toggle="tab" data-bs-target="#">Active</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#">2</a>
			</li>
		</ul>
	</div>
	<div class="card-body">
		<div class="tab-content">
			<div class="tab-pane" id="">1</div>
			<div class="tab-pane" id="">2</div>
		</div>
	</div>
</div>
<?php endif ?>
</div>
<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
