<?php
	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser) && UOJConfig::$data['switch']['force-login']) {
		become403Page();
	}

	if (!isset($_COOKIE['bootstrap4'])) {
		$REQUIRE_LIB['bootstrap5'] = '';
	}

	$conds = array();

	$q_problem_id = isset($_GET['problem_id']) && validateUInt($_GET['problem_id']) ? $_GET['problem_id'] : null;
	$q_submission_id = isset($_GET['submission_id']) && validateUInt($_GET['submission_id']) ? $_GET['submission_id'] : null;
	$q_hacker = isset($_GET['hacker']) && validateUsername($_GET['hacker']) ? $_GET['hacker'] : null;
	$q_owner = isset($_GET['owner']) && validateUsername($_GET['owner']) ? $_GET['owner'] : null;
	if ($q_problem_id != null) {
		$conds[] = "problem_id = $q_problem_id";
	}
	if ($q_submission_id != null) {
		$conds[] = "submission_id = $q_submission_id";
	}
	if ($q_hacker != null) {
		$conds[] = "hacker = '$q_hacker'";
	}
	if ($q_owner != null) {
		$conds[] = "owner = '$q_owner'";
	}
	
	$selected_all = ' selected="selected"';
	$selected_succ ='';
	$selected_fail ='';
	if (isset($_GET['status']) && validateUInt($_GET['status'])) {
		if ($_GET['status'] == 1) {
			$selected_all = '';
			$selected_succ =' selected="selected"';
			$conds[] = 'success = 1';
		}
		if ($_GET['status'] == 2) {
			$selected_all = '';
			$selected_fail = ' selected="selected"';
			$conds[] = 'success = 0';
		}
	}
	
	if ($conds) {
		$cond = join($conds, ' and ');
	} else {
		$cond = '1';
	}
	
	?>
<?php echoUOJPageHeader(UOJLocale::get('hacks')) ?>

<h1 class="h2">
<?= UOJLocale::get('hacks') ?>
</h1>

<div class="d-none d-sm-block">
	<?php if (Auth::check()): ?>
	<div class="float-right
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
btn-group
<?php endif ?>">
		<a href="/hacks?hacker=<?= Auth::id() ?>" class="btn btn-success btn-sm"><?= UOJLocale::get('problems::hacks by me') ?></a>
		<a href="/hacks?owner=<?= Auth::id() ?>" class="btn btn-danger btn-sm"><?= UOJLocale::get('problems::hacks to me') ?></a>
	</div>
	<?php endif ?>
	<form id="form-search" class="form-inline" role="form">
		<div id="form-group-submission_id" class="form-group
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
d-inline-block
<?php endif ?>
		">
			<label for="input-submission_id" class="control-label"><?= UOJLocale::get('problems::submission id') ?>:</label>
			<input type="text" class="form-control input-sm" name="submission_id" id="input-submission_id" value="<?= $q_submission_id ?>" maxlength="6" style="width:5em" />
		</div>
		<div id="form-group-problem_id" class="form-group
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
d-inline-block
<?php endif ?>
		">
			<label for="input-problem_id" class="control-label"><?= UOJLocale::get('problems::problem id') ?>:</label>
			<input type="text" class="form-control input-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" maxlength="4" style="width:4em" />
		</div>
		<div id="form-group-hacker" class="form-group
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
d-inline-block
<?php endif ?>
		">
			<label for="input-hacker" class="control-label"><?= UOJLocale::get('problems::hacker') ?>:</label>
			<input type="text" class="form-control input-sm" name="hacker" id="input-hacker" value="<?= $q_hacker ?>" maxlength="100" style="width:10em" />
		</div>
		<div id="form-group-owner" class="form-group
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
d-inline-block
<?php endif ?>
		">
			<label for="input-owner" class="control-label"><?= UOJLocale::get('problems::owner') ?>:</label>
			<input type="text" class="form-control input-sm" name="owner" id="input-owner" value="<?= $q_owner ?>" maxlength="100" style="width:10em" />
		</div>
		<div id="form-group-status" class="form-group
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
d-inline-block
<?php endif ?>
		">
			<label for="input-status" class="control-label"><?= UOJLocale::get('problems::result') ?>:</label>
			<select class="form-control input-sm" id="input-status" name="status">
				<option value=""<?= $selected_all?>>All</option>
				<option value="1"<?= $selected_succ ?>>Success!</option>
				<option value="2"<?= $selected_fail ?>>Failed.</option>
			</select>
		</div>
		<button type="submit" id="submit-search" class="btn btn-secondary btn-sm ml-2"><?= UOJLocale::get('search') ?></button>
	</form>
	<script type="text/javascript">
		$('#form-search').submit(function(e) {
			e.preventDefault();
			
			url = '/hacks';
			qs = [];
			$(['submission_id', 'problem_id', 'hacker', 'owner', 'status']).each(function () {
				if ($('#input-' + this).val()) {
					qs.push(this + '=' + encodeURIComponent($('#input-' + this).val()));
				}
			});
			if (qs.length > 0) {
				url += '?' + qs.join('&');
			}
			location.href = url;
		});
	</script>
	
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="mt-3"></div>
<?php else: ?>
	<div class="top-buffer-sm"></div>
<?php endif ?>
		
</div>

<?php echoHacksList($cond,
	'order by id desc',
	array(
		'judge_time_hidden' => '',
		'table_config' => (isset($REQUIRE_LIB['bootstrap5']) 
			? array(
				'div_classes' => array('card', 'mb-3', 'overflow-auto'),
				'table_classes' => array('table', 'mb-0', 'uoj-table', 'text-center')
			)
			: array()
		),
	),
	$myUser);
	?>

<?php echoUOJPageFooter() ?>
