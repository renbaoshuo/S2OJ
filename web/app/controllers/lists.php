<?php

requireLib('bootstrap5');
requirePHPLib('form');
requirePHPLib('judger');
requirePHPLib('data');

Auth::check() || redirectToLogin();
UOJUser::checkPermission(Auth::user(), 'lists.view') || UOJResponse::page403();

if (UOJList::userCanCreateList(Auth::user())) {
	$new_list_form = new UOJBs4Form('new_list');
	$new_list_form->handle = function () {
		DB::insert("insert into lists (title, is_hidden) values ('未命名题单', 1)");
		$id = DB::insert_id();
		DB::insert("insert into lists_contents (id, content, content_md) values ($id, '', '')");
	};
	$new_list_form->submit_button_config['align'] = 'right';
	$new_list_form->submit_button_config['class_str'] = 'btn btn-primary';
	$new_list_form->submit_button_config['text'] = UOJLocale::get('problems::add new list');
	$new_list_form->submit_button_config['smart_confirm'] = '';
	$new_list_form->runAtServer();
}

function getListTR($info) {
	$list = new UOJList($info);
	$problems = $list->getProblemIDs();
	if (Auth::check() && !empty($problems)) {
		$accepted = DB::selectCount([
			"select count(*)",
			"from best_ac_submissions",
			"where", [
				"submitter" => Auth::id(),
				["problem_id", "in", DB::rawtuple($problems)],
			],
		]);
	} else {
		$accepted = -1;
	}

	$html = HTML::tag_begin('tr', ['class' => 'text-center']);
	$html .= HTML::tag('td', ['class' => $accepted == count($problems) ? 'table-success' : ''], "#{$list->info['id']}");
	$html .= HTML::tag_begin('td', ['class' => 'text-start']);
	$html .= $list->getLink();
	if ($list->info['is_hidden']) {
		$html .= ' <span class="badge text-bg-danger"><i class="bi bi-eye-slash-fill"></i> ' . UOJLocale::get('hidden') . '</span> ';
	}
	if (isset($_COOKIE['show_tags_mode'])) {
		foreach ($list->queryTags() as $tag) {
			$html .= ' <a class="uoj-list-tag"><span class="badge text-bg-secondary">' . HTML::escape($tag) . '</span></a> ';
		}
	}
	$html .= HTML::tag('td', [], max(0, $accepted));
	$html .= HTML::tag('td', [], count($problems));
	$html .= HTML::tag_end('td');

	return $html;
}

$cond = [];
$search_tag = UOJRequest::get('tag', 'is_string', null);
if (is_string($search_tag)) {
	$cond[] = [
		DB::rawvalue($search_tag), "in", DB::rawbracket([
			"select tag from lists_tags",
			"where", ["lists_tags.list_id" => DB::raw("lists.id")]
		])
	];
}

if (empty($cond)) {
	$cond = '1';
}

$header = HTML::tag('tr', [], [
	HTML::tag('th', ['class' => 'text-center', 'style' => 'width:5em'], 'ID'),
	HTML::tag('th', [], UOJLocale::get('problems::problem list')),
	HTML::tag('th', ['class' => 'text-center', 'style' => 'width:5em'], UOJLocale::get('problems::ac')),
	HTML::tag('th', ['class' => 'text-center', 'style' => 'width:5em'], UOJLocale::get('problems::total')),
]);

$pag = new Paginator([
	'col_names' => ['*'],
	'table_name' => 'lists',
	'cond' => $cond,
	'tail' => "order by id desc",
	'page_len' => 40,
	'post_filter' => function ($info) {
		return (new UOJList($info))->userCanView(Auth::user());
	}
]);
?>

<?php echoUOJPageHeader(UOJLocale::get('problems lists')) ?>

<div class="row">
	<!-- left col -->
	<div class="col-lg-9">
		<!-- title container -->
		<div class="d-flex justify-content-between">
			<h1>
				<?= UOJLocale::get('problems lists') ?>
			</h1>

			<?php if (isset($new_list_form)) : ?>
				<div class="text-end">
					<?php $new_list_form->printHTML(); ?>
				</div>
			<?php endif ?>
		</div>
		<!-- end title container -->

		<div class="text-end">
			<div class="form-check d-inline-block me-2">
				<input type="checkbox" id="input-show_tags_mode" class="form-check-input" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ' : '' ?> />
				<label class="form-check-label" for="input-show_tags_mode">
					<?= UOJLocale::get('problems::show tags') ?>
				</label>
			</div>
		</div>

		<script type="text/javascript">
			$('#input-show_tags_mode').click(function() {
				if (this.checked) {
					$.cookie('show_tags_mode', '', {
						path: '/lists',
						expires: 365,
					});
				} else {
					$.removeCookie('show_tags_mode', {
						path: '/lists',
					});
				}
				location.reload();
			});
		</script>

		<?= $pag->pagination() ?>

		<div class="card my-3">
			<?=
			HTML::responsive_table($header, $pag->get(), [
				'table_attr' => [
					'class' => ['table', 'uoj-table', 'mb-0'],
				],
				'tr' => function ($row, $idx) {
					return getListTR($row);
				}
			]);
			?>
		</div>

		<?= $pag->pagination() ?>
	</div>
	<!-- end left col -->

	<!-- right col -->
	<aside class="col-lg-3 mt-3 mt-lg-0">
		<?php uojIncludeView('sidebar') ?>
	</aside>
	<!-- end right col -->
</div>

<?php echoUOJPageFooter() ?>
