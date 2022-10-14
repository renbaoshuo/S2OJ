<?php
	requireLib('bootstrap5');
	?>

<?php echoUOJPageHeader(UOJLocale::get('html to markdown')) ?>

<h1 class="h2">
	<?= UOJLocale::get('html to markdown') ?>
</h1>

<style>
#html, #markdown {
	font-family: Cascadia Mono, Ubuntu Mono, Roboto Mono, Jetbrains Mono, Fira Code, Consolas, '思源黑体 Regular', '思源宋体 Light', '宋体', 'Courier New', monospace;
	width: 100%;
	min-height: 300px;
}
</style>

<div class="card">
	<div class="card-body">
		<div class="row row-cols-1 row-cols-md-2">
			<div class="col">
				<h2 class="h4">HTML 源码</h2>
				<textarea class="form-control" id="html" placeholder="input html here"></textarea>
			</div>
			<div class="col">
				<h2 class="h4">Markdown 源码</h2>
				<textarea readonly class="form-control" id="markdown" placeholder="output markdown here"></textarea>
			</div>
		</div>
	</div>
</div>

<?= HTML::js_src('/js/h2m.js') ?>

<script>
$(document).ready(function() {
	$('#html').on('input', function() {
		$('#markdown').val(h2m($('#html').val(), { converter: 'Gfm' }));
	});
});
</script>

<?php echoUOJPageFooter() ?>
