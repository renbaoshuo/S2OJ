<?php
	requireLib('bootstrap5');
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$group_id = $_GET['id'];
	if (!validateUInt($group_id) || !($group = queryGroup($group_id))) {
		become404Page();
	}

	if (!isSuperUser($myUser) && $group['is_hidden']) {
		become403Page();
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<div class="row">
<!-- left col -->
<div class="col-lg-9">

<!-- title -->
<div class="d-flex justify-content-between">
<h1 class="h2">
	<?php if ($group['is_hidden']): ?>
		<span class="fs-5 text-danger">[隐藏]</span>
	<?php endif ?>
	<?= $group['title'] ?>
	<span class="fs-5">(ID: #<?= $group['id'] ?>)</span>
</h1>

<?php if (isSuperUser($myUser)): ?>
<div class="text-end">
	<a class="btn btn-primary" href="/group/<?= $group['id'] ?>/manage" role="button">
		<?= UOJLocale::get('problems::manage') ?>
	</a>
</div>
<?php endif ?>
</div>
<!-- end title -->

<!-- main content -->
<div class="card mb-3">
	<div class="card-body">
		<h2 class="h4">
			<?= UOJLocale::get('group announcement') ?>
		</h2>
		<?php if ($group['announcement']): ?>
		<div class="text-break">
			<?= HTML::purifier_inline()->purify(HTML::parsedown()->line($group['announcement'])) ?>
		</div>
		<?php else: ?>
		<div class="text-muted">
			<?= UOJLocale::get('none') ?>
		</div>
		<?php endif ?>
	</div>
</div>

<div class="card mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
			<?= UOJLocale::get('news') ?>
		</h5>
		<ul class="mb-0">
		<?php
			$current_ac = queryGroupCurrentAC($group['id']);
	foreach ($current_ac as $ac) {
		echo '<li>';
		echo getUserLink($ac['submitter']);
		echo ' 解决了问题 ';
		echo '<a class="text-decoration-none" href="/problem/', $ac['problem_id'], '">', $ac['problem_title'], '</a> ';
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

<div class="card card-default mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
			<?= UOJLocale::get('assignments') ?>
		</h5>
				<?php
						$now = new DateTime();
	echoLongTable(
		['groups_assignments.list_id as list_id', 'lists.title as title', 'groups_assignments.end_time as end_time'],
		'groups_assignments left join lists on lists.id = groups_assignments.list_id',
		"groups_assignments.group_id = {$group['id']} and groups_assignments.end_time > addtime(now(), '-168:00:00')",
		'order by end_time desc, list_id desc',
		<<<EOD
	<tr>
		<th style="width:3em" class="text-center">ID</th>
		<th style="width:12em">标题</th>
		<th style="width:4em">状态</th>
		<th style="width:8em">结束时间</th>
	</tr>
EOD,
		function($row) use ($group, $now) {
			$end_time = DateTime::createFromFormat('Y-m-d H:i:s', $row['end_time']);

			echo '<tr>';
			echo '<td class="text-center">', $row['list_id'], '</td>';
			echo '<td>', '<a class="text-decoration-none" href="/group/', $group['id'], '/assignment/', $row['list_id'],'">', HTML::escape($row['title']), '</a>', '</td>';
			if ($end_time < $now) {
				echo '<td class="text-danger">已结束</td>';
			} else {
				echo '<td class="text-success">进行中</td>';
			}
			echo '<td>', $end_time->format('Y-m-d H:i:s'), '</td>';
			echo '</tr>';
		},
		[
			'echo_full' => true,
			'div_classes' => ['table-responsive'],
			'table_classes' => ['table', 'align-middle', 'mb-0'],
		]
	);
	?>
	</div>
</div>

<div class="card card-default mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
			<?= UOJLocale::get('top solver') ?>
		</h5>
		<?php echoRanklist([
			'page_len' => 50,
			'group_id' => $group_id,
			'by_accepted' => true,
			'div_classes' => ['table-responsive', 'mb-3'],
			'table_classes' => ['table', 'text-center', 'mb-0'],
			]) ?>
	</div>
</div>

<!-- end left col -->
</div>

<!-- right col -->
<aside class="col-lg-3 mt-3 mt-lg-0">
<?php uojIncludeView('sidebar'); ?>
</aside>

</div>

<?php echoUOJPageFooter() ?>
