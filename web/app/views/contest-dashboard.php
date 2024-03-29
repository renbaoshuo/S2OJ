<div class="card card-default table-responsive mb-3">
	<table class="table uoj-table text-center mb-0">
		<thead>
			<tr>
				<th style="width:5em">#</th>
				<th><?= UOJLocale::get('problems::problem') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php for ($i = 0; $i < count($contest_problems); $i++): ?>
				<tr>
					<?php
						echo $contest_problems[$i]['submission_id'] ? '<td class="table-success">' : '<td>';
						echo $contest_problems[$i]['problem']->getLetter();
						echo '</td>';
					?>
					<td><?= $contest_problems[$i]['problem']->getLink(['with' => null, 'simplify' => true]) ?></td>
				</tr>
			<?php endfor ?>
		</tbody>
	</table>
</div>

<h3><?= UOJLocale::get('contests::contest notice') ?></h3>
<div class="card card-default table-responsive mb-3">
	<table class="table uoj-table text-center mb-0">
		<thead>
			<tr>
				<th style="width:10em"><?= UOJLocale::get('title') ?></th>
				<th><?= UOJLocale::get('content') ?></th>
				<th style="width:12em"><?= UOJLocale::get('time') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (empty($contest_notice)): ?>
				<tr><td colspan="233"><?= UOJLocale::get('none') ?></td></tr>
			<?php else: foreach ($contest_notice as $notice): ?>
				<tr>
					<td><?= HTML::escape($notice['title']) ?></td>
					<td style="white-space:pre-wrap; text-align: left"><?= $notice['content'] ?></td>
					<td><?= $notice['time'] ?></td>
				</tr>
			<?php endforeach; endif ?>
		</tbody>
	</table>
</div>


<?php if ($post_notice): ?>
	<div class="text-center">
		<button id="button-display-post-notice" type="button" class="btn btn-danger btn-xs">发布比赛公告</button>
	</div>
	<div id="div-form-post-notice" style="display:none" class="bot-buffer-md">
		<?php $post_notice->printHTML() ?>
	</div>
	<script type="text/javascript">
	$(document).ready(function() {
		$('#button-display-post-notice').click(function() {
			$('#div-form-post-notice').toggle('fast');
		});
	});
	</script>
<?php endif ?>

<h3>提问</h3>
<?php if ($my_questions_pag != null): ?>
	<div>
		<div class="d-flex justify-content-between align-items-center mb-2">
			<h4 class="mb-0">我的提问</h4>
			<?php if ($post_question): ?>
				<button id="button-display-post-question" type="button" class="btn btn-primary btn-xs">提问题</button>
			<?php endif ?>
		</div>
		<?php if ($post_question): ?>
			<div id="div-form-post-question" style="display:none" class="bot-buffer-md">
				<?php $post_question->printHTML() ?>
			</div>
			<script type="text/javascript">
			$(document).ready(function() {
				$('#button-display-post-question').click(function() {
					$('#div-form-post-question').toggle('fast');
				});
			});
			</script>
		<?php endif ?>
		<?php uojIncludeView('contest-question-table', ['pag' => $my_questions_pag]) ?>
	</div>
<?php endif ?>

<div>
	<?php if ($my_questions_pag != null): ?>
		<h4>其他人的提问</h4>
	<?php else: ?>
		<h4>所有人的提问</h4>
	<?php endif ?>
	<?php uojIncludeView('contest-question-table', ['pag' => $others_questions_pag]) ?>
</div>
