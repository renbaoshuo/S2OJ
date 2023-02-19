<?php
requirePHPLib('judger');

Auth::check() || redirectToLogin();

$conds = [
	UOJSubmission::sqlForUserCanView(Auth::user()),
];
$config = [
	'time_format' => 'friendly',
	'time_font_size' => 'normal',
	'judge_time_hidden' => true,
	'table_config' => [
		'div_classes' => ['card', 'mb-3', 'table-responsive'],
		'table_classes' => ['table', 'mb-0', 'uoj-table', 'text-center'],
	]
];

$q_problem_id = UOJRequest::get('problem_id', 'validateUInt', null);
$q_submitter = UOJRequest::get('submitter', 'validateUsername', null);
$q_min_score = UOJRequest::get('min_score', 'validateUFloat', null);
$q_max_score = UOJRequest::get('max_score', 'validateUFloat', null);
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

function echoSubmissionItem($info) {
	$submission = new UOJSubmission($info);
	$submission->setProblem();
	$submitter = UOJUser::query($submission->info['submitter']);
	$cfg = [
		'show_actual_score' => $submission->viewerCanSeeScore(Auth::user()),
		'unknown_char' => '?',
		'result_badge' => true,
	];
	$show_status_details = $submission->viewerCanSeeStatusDetailsHTML(Auth::user());

	if ($show_status_details) {
		echo '<div class="list-group-item bg-warning bg-opacity-25">';
	} else {
		echo '<div class="list-group-item">';
	}
	echo     '<div class="row gy-2 align-items-center">';

	echo         '<div class="col-lg-3 col-sm-8 d-flex gap-2">';
	echo             '<div class="d-flex align-items-center">';
	echo HTML::tag('a', [
		'href' => HTML::url('/user/' . $submitter['username']),
		'class' => 'd-inline-block me-2',
	], HTML::empty_tag('img', [
		'src' => HTML::avatar_addr($submitter, 64),
		'class' => 'uoj-user-avatar rounded',
		'style' => 'width: 2.5rem; height: 2.5rem;',
	]));
	echo             '</div>';
	echo             '<div class="d-flex flex-column gap-1">';
	echo                 '<div>', UOJUser::getLink($submitter), '</div>';
	echo                 '<div class="small text-muted">', '<i class="bi bi-clock"></i> ', UOJTime::userFriendlyFormat($submission->info['submit_time']), '</div>';
	echo             '</div>';
	echo         '</div>';

	echo         '<div class="col-lg-2 col-sm-4">';
	echo             '<div>', $submission->echoStatusBarTD('result', $cfg), '</div>';
	echo         '</div>';

	echo         '<div class="col-lg-4 col-sm-12 text-truncate">';
	echo             $submission->problem->getLink();
	echo         '</div>';

	$lang = UOJLang::getLanguageDisplayName($submission->info['language']);

	echo         '<div class="col-lg-3 ps-2 small text-muted">';
	echo             '<span class="d-inline-block">', '<i class="bi bi-hourglass-split"></i> ', $submission->echoStatusBarTD('used_time', $cfg), '</span>', ' / ';
	echo             '<span class="d-inline-block">', '<i class="bi bi-memory"></i> ', $submission->echoStatusBarTD('used_memory', $cfg), '</span>', ' / ';
	echo             '<span class="d-inline-block">', '<i class="bi bi-file-code"></i> ', $submission->echoStatusBarTD('tot_size', $cfg), '</span>';
	if ($lang != '/') {
		echo         ' / <span class="d-inline-block">', $lang, '</span> ';
	}
	echo         '</div>';

	echo     '</div>';
	echo '</div>';

	if ($show_status_details) {
		echo '<div class="list-group-item">';
		echo     '<table class="w-100">';
		echo         '<tr id="status_details_' . $submission->info['id'] . '">';
		echo             $submission->getStatusDetailsHTML();
		echo         '</tr>';
		echo         '<script>update_judgement_status_details(' . $submission->info['id'] . ')</script>';
		echo     '</table>';
		echo '</div>';
	}
}

$pag = new Paginator([
	'page_len' => 10,
	'table_name' => 'submissions',
	'col_names' => [
		'id',
		'problem_id',
		'contest_id',
		'submitter',
		'used_time',
		'used_memory',
		'tot_size',
		'language',
		'submit_time',
		'status_details',
		'status',
		'result_error',
		'score',
		'hide_score_to_others',
		'hidden_score',
	],
	'cond' => $conds,
	'tail' => 'order by id desc',
]);
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>

<h1>
	<?= UOJLocale::get('submissions') ?>
</h1>

<div class="d-none d-sm-block mb-3">
	<form id="form-search" class="row gy-2 gx-3 align-items-end mb-3" target="_self" method="GET">
		<div id="form-group-problem_id" class="col-auto">
			<label for="input-problem_id" class="form-label">
				<?= UOJLocale::get('problems::problem id') ?>
			</label>
			<input type="text" class="form-control form-control-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" style="width:4em" />
		</div>
		<div id="form-group-submitter" class="col-auto">
			<label for="input-submitter" class="form-label">
				<?= UOJLocale::get('username') ?>
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
				<?= UOJLocale::get('score range') ?>
			</label>
			<div class="input-group input-group-sm">
				<input type="text" class="form-control" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="15" style="width:4em" placeholder="0" />
				<span class="input-group-text" id="basic-addon3">~</span>
				<input type="text" class="form-control" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="15" style="width:4em" placeholder="100" />
			</div>
		</div>
		<div id="form-group-language" class="col-auto">
			<label for="input-language" class="form-label">
				<?= UOJLocale::get('problems::language') ?>
			</label>
			<select class="form-select form-select-sm" id="input-language" name="language">
				<option value="">All</option>
				<?php foreach (UOJLang::$supported_languages as $name => $lang) : ?>
					<option value="<?= HTML::escape($name) ?>" <?= $name == $q_lang ? 'selected' : '' ?>><?= HTML::escape($lang) ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<div class="col-auto">
			<button type="submit" id="submit-search" class="btn btn-secondary btn-sm ml-2">
				<?= UOJLocale::get('search') ?>
			</button>
		</div>
	</form>
</div>

<div class="card mb-3">
	<div class="list-group list-group-flush" style="--bs-list-group-item-padding-y: 0.325rem;">
		<?php if ($pag->isEmpty()) : ?>
			<div class="list-group-item text-center">
				<?= UOJLocale::get('none') ?>
			</div>
		<?php endif ?>

		<?php foreach ($pag->get() as $idx => $row) : ?>
			<?php echoSubmissionItem($row) ?>
		<?php endforeach ?>
	</div>
</div>

<div class="">
	<?= $pag->pagination() ?>
</div>

<?php echoUOJPageFooter() ?>
