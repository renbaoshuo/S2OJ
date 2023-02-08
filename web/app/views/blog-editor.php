<form method="post" class="form-horizontal" id="form-<?= $editor->name ?>" enctype="multipart/form-data">
	<?= HTML::hiddenToken() ?>
	<div class="row">
		<div class="col-sm-6">
			<?= HTML::div_vinput("{$editor->name}_title", 'text', $editor->label_text['title'], html_entity_decode($editor->cur_data['title'])) ?>
		</div>
		<?php if ($editor->show_tags) : ?>
			<div class="col-sm-6">
				<?= HTML::div_vinput("{$editor->name}_tags", 'text', $editor->label_text['tags'], join(', ', $editor->cur_data['tags'])) ?>
			</div>
		<?php endif ?>
	</div>
	<?php if ($editor->show_editor) : ?>
		<div id="div-<?= $editor->name ?>_content_md">
			<div id="div_container-<?= $editor->name ?>_content_md" style="height: 500px">
				<div class="border d-flex justify-content-center align-items-center" style="width: 100%; height: 350px;">
					<div class="spinner-border text-muted" style="width: 3rem; height: 3rem;"></div>
				</div>
			</div>
			<div class="help-block" id="help-<?= $editor->name ?>_content_md"></div>
			<input type="hidden" id="input-<?= $editor->name ?>_content_md" name="<?= $editor->name ?>_content_md" value="<?= HTML::escape($editor->cur_data['content_md']) ?>" />
		</div>
	<?php endif ?>
	<div class="row mt-2">
		<div class="col-sm-6">
			<?php if ($editor->blog_url) : ?>
				<a id="a-<?= $editor->name ?>_view_blog" class="btn btn-secondary" href="<?= HTML::escape($editor->blog_url) ?>"><?= $editor->label_text['view blog'] ?></a>
			<?php else : ?>
				<a id="a-<?= $editor->name ?>_view_blog" class="btn btn-secondary" style="display: none;"><?= $editor->label_text['view blog'] ?></a>
			<?php endif ?>
		</div>
		<div class="col-sm-6 text-end">
			<?= HTML::checkbox("{$editor->name}_is_hidden", $editor->cur_data['is_hidden']) ?>
		</div>
	</div>
</form>
<script type="text/javascript">
	$('#<?= "input-{$editor->name}_is_hidden" ?>').bootstrapSwitch({
		onText: <?= json_encode($editor->label_text['private']) ?>,
		onColor: 'danger',
		offText: <?= json_encode($editor->label_text['public']) ?>,
		offColor: 'primary',
		labelText: <?= json_encode($editor->label_text['blog visibility']) ?>,
		handleWidth: 100
	});
	blog_editor_init("<?= $editor->name ?>", <?= json_encode(array('type' => $editor->type)) ?>);
</script>
