<?php
requireLib('bootstrap5');
requirePHPLib('judger');

Auth::check() || redirectToLogin();

$conds = [];
$config = [
	'judge_time_hidden' => true,
	'table_config' => [
		'div_classes' => ['card', 'mb-3', 'table-responsive'],
		'table_classes' => ['table', 'mb-0', 'uoj-table', 'text-center'],
	]
];

$q_problem_id = UOJRequest::get('problem_id', 'validateUInt', null);
$q_submitter = UOJRequest::get('submitter', 'validateUsername', null);
$q_min_score = UOJRequest::get('min_score', 'validateUInt', null);
$q_max_score = UOJRequest::get('max_score', 'validateUInt', null);
$q_lang = UOJRequest::get('language', 'is_short_string', null);

if ($q_problem_id !== null) {
	$problem = UOJProblem::query($q_problem_id);
	if ($problem) {
		$config['problem'] = $problem;
	}

	$conds['problem_id'] = $q_problem_id;
}
if ($q_submitter !== null) {
	$conds['submitter'] = $q_submitter;
}
if ($q_min_score !== null) {
	$conds[] = ['score', '>=', $q_min_score];
}
if ($q_max_score !== null) {
	$conds[] = ['score', '<=', $q_max_score];
}
if ($q_lang != null) {
	$conds['language'] = $q_lang;
}

if (!$conds) {
	$conds = '1';
}
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>

<h1>
	<?= UOJLocale::get('submissions') ?>
</h1>

<div class="d-none d-sm-block mb-3">
	<form id="form-search" class="row gy-2 gx-3 align-items-end mb-3" target="_self" method="GET">
		<div id="form-group-problem_id" class="col-auto">
			<label for="input-problem_id" class="form-label">
				<?= UOJLocale::get('problems::problem id') ?>:
			</label>
			<input type="text" class="form-control form-control-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" style="width:4em" />
		</div>
		<div id="form-group-submitter" class="col-auto">
			<label for="input-submitter" class="form-label">
				<?= UOJLocale::get('username') ?>:
			</label>
			<div class="input-group input-group-sm">
				<input type="text" class="form-control form-control-sm" name="submitter" id="input-submitter" value="<?= $q_submitter ?>" maxlength="20" style="width:10em" />
				<?php if (Auth::check()) : ?>
					<a id="my-submissions" href="/submissions?submitter=<?= Auth::id() ?>" class="btn btn-outline-secondary btn-sm">
						我的
					</a>
				<?php endif ?>
			</div>
			<script>
				$('#my-submissions').click(function(event) {
					event.preventDefault();
					$('#input-submitter').val('<?= Auth::id() ?>');
					$('#form-search').submit();
				});
			</script>
		</div>
		<div id="form-group-score" class="col-auto">
			<label for="input-min_score" class="form-label">
				<?= UOJLocale::get('score range') ?>:
			</label>
			<div class="input-group input-group-sm">
				<input type="text" class="form-control" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="3" style="width:4em" placeholder="0" />
				<span class="input-group-text" id="basic-addon3">~</span>
				<input type="text" class="form-control" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="3" style="width:4em" placeholder="100" />
			</div>
		</div>
		<div id="form-group-language" class="col-auto">
			<label for="input-language" class="form-label">
				<?= UOJLocale::get('problems::language') ?>:
			</label>
			<select class="form-select form-select-sm" id="input-language" name="language">
				<option value="">All</option>
				<?php foreach (UOJLang::$supported_languages as $name => $lang) : ?>
					<option value="<?= HTML::escape($name) ?>" <?= $name == $q_lang ? 'selected' : '' ?>><?= HTML::escape($lang) ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<div class="col-auto">
			<button type="submit" id="submit-search" class="btn btn-secondary btn-sm ml-2"><?= UOJLocale::get('search') ?></button>
		</div>
	</form>
</div>

<?php echoSubmissionsList($conds, 'order by id desc', $config, Auth::user()) ?>

<?php echoUOJPageFooter() ?>
