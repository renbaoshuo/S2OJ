<?php if ($contest['frozen']) : ?>
	<h4 class="text-center text-danger">
		封榜于 <?= $contest['frozen_time']->format(UOJTime::FORMAT) ?>
	</h4>
<?php endif ?>

<div id="standings" class="container"></div>

<script type="text/javascript">
	var contest_rule = <?= json_encode($contest['extra_config']['basic_rule']) ?>;
	var bonus = <?= json_encode($contest['extra_config']['bonus']) ?>;
	var standings_version = <?= $contest['extra_config']['standings_version'] ?>;
	var contest_id = <?= $contest['id'] ?>;
	var standings = <?= json_encode($standings) ?>;
	var score = <?= json_encode($score) ?>;
	var problems = <?= json_encode($contest_data['problems']) ?>;
	var standings_config = <?= json_encode(isset($standings_config) ? $standings_config : ['_config' => true]) ?>;
	var myname = <?= json_encode(Auth::id()) ?>;
	var after_contest = <?= json_encode(isset($after_contest) && $after_contest) ?>;
	var first_accepted = {};

	if (problems.length > 8) {
		$('#standings').removeClass('container');
	}

	$(document).ready(showStandings());
</script>
