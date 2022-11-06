<?php requireLib('bootstrap5') ?>

<?php echoUOJPageHeader(UOJLocale::get('html to markdown')) ?>

<h1>
	<?= UOJLocale::get('html to markdown') ?>
</h1>

<style>
	#html,
	#markdown {
		font-family: Cascadia Mono, Ubuntu Mono, Roboto Mono, Jetbrains Mono, Fira Code, Consolas, '思源黑体 Regular', '思源宋体 Light', '宋体', 'Courier New', monospace;
		width: 100%;
		min-height: 300px;
	}
</style>

<div class="card">
	<div class="card-body">
		<div class="row row-cols-1 row-cols-md-2">
			<div class="col">
				<textarea class="form-control" id="html" placeholder="input html here"></textarea>
			</div>
			<div class="col">
				<textarea data-no-autosize readonly class="form-control" id="markdown" placeholder="output markdown here" style="height: 100%"></textarea>
			</div>
		</div>
	</div>
	<div class="card-footer bg-transparent text-end">
		<a href="https://s2oj.github.io/#/user/apps/html2markdown" target="_blank">使用教程</a>
	</div>
</div>

<?= HTML::js_src('/js/h2m.js') ?>

<script>
	$(document).ready(function() {
		$('#html').on('input', function() {
			$('#markdown').val(h2m($('#html').val(), {
				converter: 'Gfm'
			}));
		});
	});
</script>

<?php echoUOJPageFooter() ?>
