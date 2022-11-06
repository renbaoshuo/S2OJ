<div class="row justify-content-between align-items-center mb-3">
    <div class="col-sm-3">
        <select class="form-select form-select-sm" id="input-show_problem" name="show_problem">
            <?php foreach ($options as $option): ?>
                <?= HTML::option($option['value'], $option['text'], $option['value'] == $chosen) ?>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-sm-3 mt-2 mt-sm-0 text-end">
		<div class="form-check d-inline-block">
			<?= HTML::checkbox('show_all_submissions', $show_all_submissions_status) ?>
			<label class="form-check-label" for="input-show_all_submissions">
				<?= UOJLocale::get('contests::show all submissions') ?>
			</label>
		</div>
    </div>
</div>
<script type="text/javascript">
    $('#input-show_all_submissions').click(function() {
        if (this.checked) {
            $.cookie('show_all_submissions', '');
        } else {
            $.removeCookie('show_all_submissions');
        }
        location.reload();
    });
    $('#input-show_problem').change(function() {
        if ($(this).val() == 'all') {
            window.location.href = <?= json_encode(HTML::url('?')) ?>;
        } else {
            window.location.href = <?= json_encode(HTML::url('?')) ?> + '?p=' + $(this).val();
        }
    });
</script>

<?php 
    echoSubmissionsList(
    	$conds, 'order by id desc', [
    	    'judge_time_hidden' => '',
    	    'problem_title' => [
    	        'with' => 'letter',
				'simplify' => true
			],
			'table_config' => [
				'div_classes' => ['card', 'mb-3', 'table-responsive'],
				'table_classes' => ['table', 'mb-0', 'uoj-table', 'text-center'],
			],
    	],
    	Auth::user()
    );
?>
