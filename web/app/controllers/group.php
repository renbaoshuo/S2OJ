<?php
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

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<div class="d-flex justify-content-between">
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<h1 class="h2">
	<?php if ($group['is_hidden']): ?>
		<span class="fs-5 text-danger">[隐藏]</span>
	<?php endif ?>
	<?= $group['title'] ?>
	<span class="fs-5">(ID: #<?= $group['id'] ?>)</span>
</h1>
<?php else: ?>
<h2 style="margin-top: 24px"><?= $group['title'] ?></h2>
<p>(<b>小组 ID</b>: <?= $group['id'] ?>)</p>
<?php endif ?>

<?php if (isSuperUser($myUser)): ?>
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="text-end">
	<a class="btn btn-primary" href="/group/<?= $group['id'] ?>/manage" role="button">
		<?= UOJLocale::get('problems::manage') ?>
	</a>
</div>
<?php else: ?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/group/<?= $group['id'] ?>/manage" role="tab">管理</a></li>
</ul>
<?php endif ?>
<?php endif ?>


<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
<?php else: ?>
<div class="row">
	<div class="col-sm-12 mt-4">
		<h5>
<?php endif ?>
			<?= UOJLocale::get('news') ?>
		</h5>
		<ul
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	class="mb-0"
<?php endif ?>
>
		<?php
			$current_ac = queryGroupCurrentAC($group['id']);
	foreach ($current_ac as $ac) {
		echo '<li>';
		echo getUserLink($ac['submitter']);
		echo ' 解决了问题 ';
		echo '<a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}
		echo ' href="/problem/', $ac['problem_id'], '">', $ac['problem_title'], '</a> ';
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

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
<?php else: ?>
<div class="row">
	<div class="col-sm-12 mt-4">
		<h5>
<?php endif ?>
			<?= UOJLocale::get('assignments') ?>
		</h5>
		<ul
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	class="mb-0"
<?php endif ?>
	>
		<?php
	$assignments = queryGroupActiveAssignments($group['id']);
	foreach ($assignments as $ass) {
		$ddl = DateTime::createFromFormat('Y-m-d H:i:s', $ass['deadline']);
		$create_time = DateTime::createFromFormat('Y-m-d H:i:s', $ass['create_time']);
		$now = new DateTime();

		echo '<li>';
		echo '<a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}
		echo ' href="/problem_list/', $ass['list_id'], '">', $ass['title'], ' (题单 #', $ass['list_id'], ')</a>';

		if ($ddl < $now) {
			echo '<sup style="color:red">&nbsp;overdue</sup>';
		} elseif ($ddl->getTimestamp() - $now->getTimestamp() < 86400) {  // 1d
			echo '<sup style="color:red">&nbsp;soon</sup>';
		} elseif ($now->getTimestamp() - $create_time->getTimestamp() < 86400) {  // 1d
			echo '<sup style="color:red">&nbsp;new</sup>';
		}

		$ddl_str = $ddl->format('Y-m-d H:i');
		echo ' (截止时间: ', $ddl_str, '，<a ';
		if (isset($REQUIRE_LIB['bootstrap5'])) {
			echo ' class="text-decoration-none" ';
		}
		echo ' href="/assignment/', $ass['id'], '">查看完成情况</a>)';
		echo '</li>';
	}

	if (count($assignments) == 0) {
		echo '<p>暂无作业</p>';
	}
	?>
		</ul>
	</div>
</div>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="card card-default mb-3">
	<div class="card-body">
		<h2 class="card-title h4">
<?php else: ?>
<div class="row">
	<div class="col-sm-12 mt-4">
		<h5>
<?php endif ?>
			<?= UOJLocale::get('top solver') ?>
		</h5>
		<?php echoRanklist(array(
			'echo_full' => true,
			'group_id' => $group_id,
			'by_accepted' => true,
			'table_classes' => isset($REQUIRE_LIB['bootstrap5'])
							? array('table', 'text-center', 'mb-0')
							: array('table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center')
			)) ?>
	</div>
</div>


<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">

<?php uojIncludeView('sidebar', array()); ?>
</aside>

</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
