<?php
requireLib('bootstrap5');

Auth::check() || redirectToLogin();

$q_problem_id = isset($_GET['problem_id']) && validateUInt($_GET['problem_id']) ? $_GET['problem_id'] : null;
$q_submission_id = isset($_GET['submission_id']) && validateUInt($_GET['submission_id']) ? $_GET['submission_id'] : null;
$q_hacker = isset($_GET['hacker']) && validateUsername($_GET['hacker']) ? $_GET['hacker'] : null;
$q_owner = isset($_GET['owner']) && validateUsername($_GET['owner']) ? $_GET['owner'] : null;

$conds = [];
if ($q_problem_id != null) {
	$conds[] = ["problem_id" => $q_problem_id];
}
if ($q_submission_id != null) {
	$conds[] = ["submission_id" => $q_submission_id];
}
if ($q_hacker != null) {
	$conds[] = ["hacker" => $q_hacker];
}
if ($q_owner != null) {
	$conds[] = ["owner" => $q_owner];
}

$selected_all = ' selected="selected"';
$selected_succ = '';
$selected_fail = '';
if (isset($_GET['status']) && validateUInt($_GET['status'])) {
	if ($_GET['status'] == 1) {
		$selected_all = '';
		$selected_succ = ' selected="selected"';
		$conds[] = 'success = 1';
	}
	if ($_GET['status'] == 2) {
		$selected_all = '';
		$selected_fail = ' selected="selected"';
		$conds[] = 'success = 0';
	}
}

if ($conds) {
	$cond = $conds;
} else {
	$cond = '1';
}

?>
<?php echoUOJPageHeader(UOJLocale::get('hacks')) ?>

<h1>
	<?= UOJLocale::get('hacks') ?>
</h1>

<div class="d-none d-sm-block mb-3">
	<form id="form-search" class="row gy-2 gx-3 align-items-end mb-3" target="_self" method="GET">
		<div id="form-group-submission_id" class="col-auto">
			<label for="input-submission_id" class="form-label">
				<?= UOJLocale::get('problems::submission id') ?>:
			</label>
			<input type="text" class="form-control form-control-sm" name="submission_id" id="input-submission_id" value="<?= $q_submission_id ?>" style="width:6em" />
		</div>
		<div id="form-group-problem_id" class="col-auto">
			<label for="input-problem_id" class="form-label">
				<?= UOJLocale::get('problems::problem id') ?>:
			</label>
			<input type="text" class="form-control form-control-sm" name="problem_id" id="input-problem_id" value="<?= $q_problem_id ?>" style="width:6em" />
		</div>
		<div id="form-group-hacker" class="col-auto">
			<label for="input-hacker" class="form-label">
				<?= UOJLocale::get('problems::hacker') ?>:
			</label>
			<div class="input-group">
				<input type="text" class="form-control form-control-sm" name="hacker" id="input-hacker" value="<?= $q_hacker ?>" maxlength="20" style="width:10em" />
				<?php if (Auth::check()) : ?>
					<a id="my-hacks" href="/hacks?hacker=<?= Auth::id() ?>" class="btn btn-outline-secondary btn-sm">
						我的
					</a>
				<?php endif ?>
			</div>
			<script>
				$('#my-hacks').click(function(event) {
					event.preventDefault();
					$('#input-hacker').val('<?= Auth::id() ?>');
					$('#form-search').submit();
				});
			</script>
		</div>
		<div id="form-group-owner" class="col-auto">
			<label for="input-owner" class="form-label">
				<?= UOJLocale::get('problems::owner') ?>:
			</label>
			<div class="input-group">
				<input type="text" class="form-control form-control-sm" name="owner" id="input-owner" value="<?= $q_owner ?>" maxlength="20" style="width:10em" />
				<?php if (Auth::check()) : ?>
					<a id="my-owners" href="/hacks?owner=<?= Auth::id() ?>" class="btn btn-outline-secondary btn-sm">
						我的
					</a>
				<?php endif ?>
			</div>
			<script>
				$('#my-owners').click(function(event) {
					event.preventDefault();
					$('#input-owner').val('<?= Auth::id() ?>');
					$('#form-search').submit();
				});
			</script>
		</div>
		<div id="form-group-status" class="col-auto">
			<label for="input-status" class="form-label">
				<?= UOJLocale::get('problems::result') ?>:
			</label>
			<select class="form-select form-select-sm" id="input-status" name="status">
				<option value="" <?= $selected_all ?>>All</option>
				<option value="1" <?= $selected_succ ?>>Success!</option>
				<option value="2" <?= $selected_fail ?>>Failed.</option>
			</select>
		</div>
		<div class="col-auto">
			<button type="submit" id="submit-search" class="btn btn-secondary btn-sm ml-2">
				<?= UOJLocale::get('search') ?>
			</button>
		</div>
	</form>
</div>

<?php
echoHacksList(
	$cond,
	'order by id desc',
	[
		'judge_time_hidden' => '',
		'table_config' => [
			'div_classes' => ['card', 'mb-3', 'table-responsive'],
			'table_classes' => ['table', 'mb-0', 'uoj-table', 'text-center'],
		],
	],
	Auth::user()
);
?>

<?php echoUOJPageFooter() ?>
