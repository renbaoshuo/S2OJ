<?php assert($form instanceof DropzoneForm) ?>

<form action="<?= $form->url ?>" id="<?= $form->formID() ?>">
	<?= $form->introduction ?>
	<div id="<?= $form->divDropzoneID() ?>" class="dropzone border border-2 text-muted"></div>
	<div id="<?= $form->helpBlockID() ?>">
		<span class="help-block"></span>
	</div>
	<div class="text-center mt-3">
		<?= HTML::tag('button', [
			'type' => 'submit', 'id' => "button-submit-{$form->name}", 'name' => "submit-{$form->name}",
			'value' => $form->name, 'class' => 'btn btn-primary'
		], $form->submit_button_config['text']) ?>
	</div>
</form>

<div class="d-none" id="<?= $form->formID() ?>-template">
	<div class="dz-preview dz-file-preview">
		<div class="dz-image">
			<img data-dz-thumbnail />
		</div>
		<div class="dz-details">
			<div class="dz-size"><span data-dz-size></span></div>
			<div class="dz-filename"><span data-dz-name></span></div>
			<a class="text-danger dz-remove" href="javascript:;" data-dz-remove>移除</a>
		</div>
		<div class="dz-progress">
			<span class="dz-upload" data-dz-uploadprogress></span>
		</div>
		<div class="dz-error-message"><span data-dz-errormessage></span></div>
		<div class="dz-success-mark">
			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
				<title>Check</title>
				<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
					<path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" stroke-opacity="0.198794158" stroke="#198754" fill-opacity="0.816519475" fill="#198754"></path>
				</g>
			</svg>
		</div>
		<div class="dz-error-mark">
			<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
				<title>Error</title>
				<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
					<g stroke="#dc3545" stroke-opacity="0.198794158" fill="#dc3545" fill-opacity="0.816519475">
						<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
					</g>
				</g>
			</svg>
		</div>
	</div>
</div>
<script type="text/javascript">
	Dropzone.options.<?= lcfirst(camelize($form->divDropzoneID())) ?> = false;

	$(function() {
		var cfg = <?= json_encode($form->dropzone_config + ['url' => $form->url]) ?>;
		cfg.previewTemplate = document.querySelector('#<?= $form->formID() ?>-template').innerHTML;
		<?php foreach ($form->dropzone_config_direct as $key => $func) : ?>
			cfg[<?= json_encode($key) ?>] = <?= $func ?>;
		<?php endforeach ?>
		<?php if (!isset($form->dropzone_config_direct['successmultiple']) && $form->succ_href !== null) : ?>
			cfg['successmultiple'] = function(files) {
				window.location.href = <?= json_encode($form->succ_href) ?>;
			}
		<?php endif ?>
		<?php if (!isset($form->dropzone_config_direct['errormultiple'])) : ?>
			cfg['errormultiple'] = function(files, message) {
				$('#<?= $form->helpBlockID() ?>').addClass('invalid-feedback');
				$('#<?= $form->helpBlockID() ?> span').text(message);
			}
		<?php endif ?>
		var myDropzone = new Dropzone('div#<?= $form->divDropzoneID() ?>', cfg);
		myDropzone.on('addedfile', function(file) {
			$('#<?= $form->helpBlockID() ?>').removeClass('invalid-feedback');
			$('#<?= $form->helpBlockID() ?> span').text('');
		});
		myDropzone.on('removedfile', function(file) {
			$('#<?= $form->helpBlockID() ?>').removeClass('invalid-feedback');
			$('#<?= $form->helpBlockID() ?> span').text('');
		});
		$('form#<?= $form->formID() ?>').submit(function(e) {
			e.preventDefault();
			if ((<?= $form->submit_condition ?>)(myDropzone)) {
				myDropzone.processQueue();
			}
		});
	});
</script>
