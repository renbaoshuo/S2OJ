<div id="standings" class="
<?php if (!isset($no_bs5_card)): ?>
	card card-default
<?php endif ?>"></div>

<script type="text/javascript">
standings_version=<?=$contest['extra_config']['standings_version']?>;
show_self_reviews=<?=isset($show_self_reviews) && $show_self_reviews ? 'true' : 'false' ?>;
contest_id=<?=$contest['id']?>;
standings=<?=json_encode($standings)?>;
score=<?=json_encode($score)?>;
problems=<?=json_encode($contest_data['problems'])?>;
$(document).ready(showStandings());
</script>
