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
    	global $myUser;

    	echo '<tr class="text-center">';
    	echo '<td>';
    	echo '#', $group['group_id'], '</td>';

    	echo '<td class="text-left">';
    	if ($group['is_hidden']) {
    		echo ' <span class="text-danger">[隐藏]</span> ';
    	}
    	echo '<a href="/group/', $group['group_id'], '">', $group['title'], '</a>';
    	echo '</td>';

    	echo "<td>{$group['user_count']}</td>";

    	echo '</tr>';
    }
    ?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

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

    echoLongTable(
    	array('a.id as group_id', 'a.title as title', 'a.is_hidden as is_hidden', 'count(b.username) as user_count'),
    	$from, $cond, 'group by a.id order by a.id asc',
    	$header,
    	'echoGroup',
    	array('page_len' => 40,
    		'table_classes' => array('table', 'table-bordered', 'table-hover', 'table-striped'),
    		'print_after_table' => function() {
    			global $myUser;
    			if (isSuperUser($myUser)) {
    				global $new_group_form;
    				$new_group_form->printHTML();
    			}
    		},
    		'head_pagination' => true
    	)
    );
    ?>

<?php echoUOJPageFooter() ?>
