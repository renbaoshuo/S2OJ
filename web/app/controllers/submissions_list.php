<?php
	requireLib('bootstrap5');
	requirePHPLib('judger');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	$conds = array();
	
	$q_problem_id = isset($_GET['problem_id']) && validateUInt($_GET['problem_id']) ? $_GET['problem_id'] : null;
	$q_submitter = isset($_GET['submitter']) && validateUsername($_GET['submitter']) ? $_GET['submitter'] : null;
	$q_min_score = isset($_GET['min_score']) && validateUInt($_GET['min_score']) ? $_GET['min_score'] : null;
	$q_max_score = isset($_GET['max_score']) && validateUInt($_GET['max_score']) ? $_GET['max_score'] : null;
	$q_language = isset($_GET['language']) ? $_GET['language'] : null;
	if ($q_problem_id != null) {
		$conds[] = "problem_id = $q_problem_id";
	}
	if ($q_submitter != null) {
		$conds[] = "submitter = '$q_submitter'";
	}
	if ($q_min_score != null) {
		$conds[] = "score >= $q_min_score";
	}
	if ($q_max_score != null) {
		$conds[] = "score <= $q_max_score";
	}
	if ($q_language != null) {
		$conds[] = sprintf("language = '%s'", DB::escape($q_language));
	}
	
	$html_esc_q_language = htmlspecialchars($q_language);
	
	if ($conds) {
		$cond = join($conds, ' and ');
	} else {
		$cond = '1';
	}
	?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>

<h1 class="h2">
	<?= UOJLocale::get('submissions') ?>
</h1>

<div class="d-none d-sm-block mb-3">
	<form id="form-search" class="row gy-2 gx-3 align-items-end mb-3" target="_self" method="GET">
		<div id="form-group-problem_id" class="col-auto">
			<label for="input-problem_id" class="form-label">
				<?= UOJLocale::get('problems::problem id')?>:
			</label>
			<input type="text" class="form-control form-control-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" style="width:4em" />
		</div>
		<div id="form-group-submitter" class="col-auto">
			<label for="input-submitter" class="control-label">
				<?= UOJLocale::get('username')?>:
			</label>
			<div class="input-group input-group-sm">
				<input type="text" class="form-control form-control-sm" name="submitter" id="input-submitter" value="<?= $q_submitter ?>" maxlength="20" style="width:10em" />
				<?php if (Auth::check()): ?>
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
			<label for="input-min_score" class="control-label">
				<?= UOJLocale::get('score range')?>:
			</label>
			<div class="input-group input-group-sm">
				<input type="text" class="form-control" name="min_score" id="input-min_score" value="<?= $q_min_score ?>" maxlength="3" style="width:4em" placeholder="0" />
				<span class="input-group-text" id="basic-addon3">~</span>
				<input type="text" class="form-control" name="max_score" id="input-max_score" value="<?= $q_max_score ?>" maxlength="3" style="width:4em" placeholder="100" />
			</div>
		</div>
		<div id="form-group-language" class="col-auto">
			<label for="input-language" class="control-label"><?= UOJLocale::get('problems::language')?>:</label>
			<select class="form-select form-select-sm" id="input-language" name="language">
				<option value="">All</option>
				<?php foreach ($uojSupportedLanguages as $lang): ?>
				<option value="<?= HTML::escape($lang) ?>" <?= $lang == $q_language ? 'selected' : '' ?>><?= HTML::escape($lang) ?></option>
				<?php endforeach ?>
			</select>
		</div>
		<div class="col-auto">
			<button type="submit" id="submit-search" class="btn btn-secondary btn-sm ml-2"><?= UOJLocale::get('search')?></button>
		</div>
	</form>
</div>

<?php
	echoSubmissionsList($cond,
		'order by id desc',
		array(
			'judge_time_hidden' => '',
			'table_config' => (isset($REQUIRE_LIB['bootstrap5']) 
				? array(
					'div_classes' => array('card', 'mb-3', 'table-responsive'),
					'table_classes' => array('table', 'mb-0', 'uoj-table', 'text-center')
				)
				: array()
			),
		),
		$myUser);
	?>

<?php echoUOJPageFooter() ?>
