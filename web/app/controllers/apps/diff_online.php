<?php
requireLib('bootstrap5');
?>

<?php echoUOJPageHeader(UOJLocale::get('diff online')) ?>

<h1>
	<?= UOJLocale::get('diff online') ?>
</h1>

<div class="card">
	<div class="card-body">
		<div style="height: 700px" id="diff-editor-container">
			<div class="border d-flex justify-content-center align-items-center w-100 h-100">
				<div class="spinner-border text-muted" style="width: 3rem; height: 3rem;"></div>
			</div>
		</div>
	</div>
	<div class="card-footer bg-transparent text-end">
		<a href="https://s2oj.github.io/#/user/apps/diff_online" target="_blank">使用教程</a>
	</div>
</div>

<script>
	var div_editor = $('#diff-editor-container');

	require_monaco({}, function() {
		$(div_editor).html('');

		var originalModel = monaco.editor.createModel("", "text/plain");
		var modifiedModel = monaco.editor.createModel("", "text/plain");

		var diffEditor = monaco.editor.createDiffEditor(div_editor[0], {
			originalEditable: true, // for left pane
			readOnly: false, // for right pane
			fontSize: "16px",
			automaticLayout: true,
		});

		diffEditor.setModel({
			original: originalModel,
			modified: modifiedModel,
		});

		$(div_editor).addClass('border overflow-hidden');
	});
</script>

<?php echoUOJPageFooter() ?>
