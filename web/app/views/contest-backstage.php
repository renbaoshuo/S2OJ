<div class="card">
<div class="card-header">
	<ul class="nav nav-tabs card-header-tabs" role="tablist">
		<li class="nav-item"><a class="nav-link active" href="#tab-question" role="tab" data-bs-toggle="tab" data-bs-target="#tab-question">提问</a></li>
		<?php if ($post_notice): ?>
			<li class="nav-item"><a class="nav-link" href="#tab-notice" role="tab" data-bs-toggle="tab" data-bs-target="#tab-notice">公告</a></li>
		<?php endif ?>
		<?php if ($standings_data): ?>
			<li class="nav-item"><a class="nav-link" href="#tab-standings" role="tab" data-bs-toggle="tab" data-bs-target="#tab-standings">终榜</a></li>
		<?php endif ?>
	</ul>
</div>
<div class="tab-content">
	<div class="tab-pane card-body active" id="tab-question">
		<?php uojIncludeView('contest-question-table', ['pag' => $questions_pag, 'can_reply' => true, 'reply_question' => $reply_question, 'no_bs5_card' => '']) ?>
	</div>
	<?php if ($post_notice): ?>
		<div class="tab-pane card-body" id="tab-notice">
			<h4>发布比赛公告</h4>
			<?php $post_notice->printHTML() ?>
		</div>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<div class="tab-pane" id="tab-standings">
			<?php 
			uojIncludeView('contest-standings', array_merge(
				$standings_data,
				[
					'standings_config' => [
						'div_classes' => ['table-responsive', 'mb-3']
					]
				]
			)); ?>
		</div>
	<?php endif ?>
</div>
</div>


<script>
$(document).ready(function() {
	// Javascript to enable link to tab
	var hash = location.hash.replace(/^#/, '');
	if (hash) {
		bootstrap.Tab.jQueryInterface.call($('.nav-tabs a[href="#' + hash + '"]'), 'show').blur();
	}

	// Change hash for page-reload
	$('.nav-tabs a').on('shown.bs.tab', function(e) {
		if (window.history.pushState) {
			// Update the address bar
			window.history.pushState({}, '', e.target.hash);
		} else {
			// Fallback for the old browsers which do not have `history.pushState()`
			window.location.hash = e.target.hash;
		}
	});
});
</script>
