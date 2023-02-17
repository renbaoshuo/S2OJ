<?php
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();

if (UOJGroup::userCanCreateGroup(Auth::user())) {
	$new_group_form = new UOJForm('new_group');
	$new_group_form->handle = function () {
		DB::insert([
			"insert into `groups`",
			DB::bracketed_fields(['title', 'is_hidden']),
			"values", DB::tuple(['新小组', 1]),
		]);
	};
	$new_group_form->config['submit_container']['class'] = 'text-end';
	$new_group_form->config['submit_button']['class'] = 'btn btn-primary';
	$new_group_form->config['submit_button']['text'] = UOJLocale::get('add new group');
	$new_group_form->config['confirm']['smart'] = true;
	$new_group_form->runAtServer();
}
?>

<?php echoUOJPageHeader(UOJLocale::get('groups')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-md-9">
		<!-- title container -->
		<div class="d-flex justify-content-between">
			<h1>
				<?= UOJLocale::get('groups') ?>
			</h1>

			<?php if (isset($new_group_form)) : ?>
				<?php $new_group_form->printHTML() ?>
			<?php endif ?>
		</div>
		<!-- end title container -->

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

		echoLongTable(
			['*'],
			"`groups`",
			'1',
			'order by id asc',
			$header,
			function ($group) {
				$users_count = DB::selectCount([
					"select count(*)",
					"from", "groups_users",
					"where", [
						"group_id" => $group['id'],
					],
				]);

				echo '<tr class="text-center">';
				echo '<td>';
				echo '#', $group['id'], '</td>';

				echo '<td class="text-start">';
				echo '<a class="text-decoration-none" href="/group/', $group['id'], '">', $group['title'], '</a>';
				if ($group['is_hidden']) {
					echo ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ', UOJLocale::get('hidden'), '</span> ';
				}
				echo '</td>';

				echo "<td>{$users_count}</td>";

				echo '</tr>';
			},
			[
				'page_len' => 50,
				'div_classes' => ['card', 'my-3'],
				'table_classes' => ['table', 'uoj-table', 'mb-0'],
				'head_pagination' => true,
				'post_filter' => function ($info) {
					return (new UOJGroup($info))->userCanView(Auth::user());
				}
			]
		);
		?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-md-3 mt-3 mt-md-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
