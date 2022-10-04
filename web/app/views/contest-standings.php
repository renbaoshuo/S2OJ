<div class="card card-default" id="standings"></div>

<div class="table-responsive">
	<table id="standings-table" class="table table-bordered table-striped table-text-center table-vertical-middle"></table>
</div>

<script type="text/javascript">
standings_version=<?=$contest['extra_config']['standings_version']?>;
show_self_reviews=<?=isset($show_self_reviews) && $show_self_reviews ? 'true' : 'false' ?>;
contest_id=<?=$contest['id']?>;
standings=<?=json_encode($standings)?>;
score=<?=json_encode($score)?>;
problems=<?=json_encode($contest_data['problems'])?>;
$(document).ready(showStandings());
</script>
