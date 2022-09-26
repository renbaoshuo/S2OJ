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
	if (!validateUInt($group_id) || !($group = queryGroup($group_id))) {
		become404Page();
	}
	?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<h2 style="margin-top: 24px"><?= $group['title'] ?></h2>
<p>(<b>小组 ID</b>: <?= $group['id'] ?>)</p>

<?php if (isSuperUser($myUser)): ?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item"><a class="nav-link" href="/group/<?= $group['id'] ?>/manage" role="tab">管理</a></li>
</ul>
<?php endif ?>

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

<?php echoUOJPageFooter() ?>
