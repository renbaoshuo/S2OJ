<div id="standings"></div>

<script type="text/javascript">
var standings_version=<?=$contest['extra_config']['standings_version']?>;
var show_self_reviews=<?=isset($show_self_reviews) && $show_self_reviews ? 'true' : 'false' ?>;
var contest_id=<?=$contest['id']?>;
var standings=<?=json_encode($standings)?>;
var score=<?=json_encode($score)?>;
var problems=<?=json_encode($contest_data['problems'])?>;
var standings_config = <?=json_encode(isset($standings_config) ? $standings_config : ['_config' => true])?>;

$(document).ready(showStandings(standings_config));
</script>
