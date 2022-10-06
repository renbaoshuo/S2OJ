<?php
    requirePHPLib('form');
    requirePHPLib('judger');
    requirePHPLib('data');

    if (!Auth::check()) {
    	redirectToLogin();
    }

    if (!isNormalUser($myUser)) {
    	become403Page();
    }

    if (!isset($_COOKIE['bootstrap4'])) {
    	$REQUIRE_LIB['bootstrap5'] = '';
    }

    if (isSuperUser($myUser)) {
    	$new_group_form = new UOJForm('new_group');
    	$new_group_form->handle = function() {
    		DB::query("insert into `groups` (title, is_hidden) values ('新小组', 1)");
    	};
    	$new_group_form->submit_button_config['align'] = 'right';
    	$new_group_form->submit_button_config['class_str'] = 'btn btn-primary';
    	$new_group_form->submit_button_config['text'] = UOJLocale::get('add new group');
    	$new_group_form->submit_button_config['smart_confirm'] = '';
    	$new_group_form->runAtServer();
    }

    function echoGroup($group) {
    	global $myUser, $REQUIRE_LIB;

    	echo '<tr class="text-center">';
    	echo '<td>';
    	echo '#', $group['group_id'], '</td>';

    	if (isset($REQUIRE_LIB['bootstrap5'])) {
    		echo '<td class="text-start">';
    	} else {
    		echo '<td class="text-left">';
    	}
    	if ($group['is_hidden']) {
    		echo ' <span class="text-danger">[隐藏]</span> ';
    	}
    	echo '<a ';
    	if (isset($REQUIRE_LIB['bootstrap5'])) {
    		echo ' class="text-decoration-none" ';
    	}
    	echo ' href="/group/', $group['group_id'], '">', $group['title'], '</a>';
    	echo '</td>';

    	echo "<td>{$group['user_count']}</td>";

    	echo '</tr>';
    }
    ?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
<div class="row">
<div class="col-lg-9">
<div class="d-flex justify-content-between">
<?php endif ?>
<h1 class="h2">
	<?= UOJLocale::get('groups') ?>
</h1>

<?php if (isSuperUser($myUser)): ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="text-end mb-2">
	<?php endif ?>
		<?php $new_group_form->printHTML(); ?>
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	</div>
<?php endif ?>
<?php endif ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>
<?php endif ?>

<?php
        $groups_caption = UOJLocale::get('groups');
    $users_caption = UOJLocale::get('users count');
    $header = <<<EOD
<tr>
    <th class="text-center" style="width:5em;">ID</th>
    <th>{$groups_caption}</th>
    <th class="text-center" style="width:8em;">{$users_caption}</th>
</tr>
EOD;

    if (isSuperUser($myUser)) {
    	$cond = "1";
    } else {
    	$cond = 'is_hidden = 0';
    }

    $from = "`groups` a left join groups_users b on a.id = b.group_id";

    $table_config = array('page_len' => 40,
    	'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped'),
    	'head_pagination' => true,
    	'pagination_table' => "`groups`"
    );

    if (isset($REQUIRE_LIB['bootstrap5'])) {
    	$table_config['div_classes'] = array('card', 'my-3');
    	$table_config['table_classes'] = array('table', 'uoj-table', 'mb-0');
    }

    echoLongTable(
    	array('a.id as group_id', 'a.title as title', 'a.is_hidden as is_hidden', 'count(b.username) as user_count'),
    	$from, $cond, 'group by a.id order by a.id asc',
    	$header,
    	'echoGroup',
    	$table_config
    );
    ?>

<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
</div>

<aside class="col mt-3 mt-lg-0">
<?php 
	uojIncludeView('sidebar', array());
	?>
</aside>

</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
