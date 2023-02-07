<?php if ($contest['frozen']) : ?>
	<h4 class="text-center text-danger">
		封榜于 <?= $contest['frozen_time']->format(UOJTime::FORMAT) ?>
	</h4>
<?php endif ?>

<div id="standings"></div>

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
	var first_accepted = {};

	$(document).ready(showStandings());
</script>
