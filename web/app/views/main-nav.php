<div class="navbar navbar-light navbar-expand-md bg-body shadow-sm
<?php if (!isset($disable_navbar_margin_bottom)) : ?>
mb-4
<?php endif ?>
" role="navigation">
	<div class="container">
		<a class="navbar-brand fw-normal" href="<?= HTML::url('/') ?>">
			<img src="<?= HTML::url('/images/logo_small.png') ?>" alt="Logo" width="24" height="24" class="d-inline-block align-text-top" />
			<?= UOJConfig::$data['profile']['oj-name-short'] ?>
		</a>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="nav navbar-nav mr-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/contests') ?>">
						<i class="bi bi-bar-chart"></i>
						<?= UOJLocale::get('contests') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/problems') ?>">
						<i class="bi bi-list-task"></i>
						<?= UOJLocale::get('problems') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/groups') ?>">
						<i class="bi bi-people"></i>
						<?= UOJLocale::get('groups') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/lists') ?>">
						<i class="bi bi-card-list"></i>
						<?= UOJLocale::get('problems lists') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/submissions') ?>">
						<i class="bi bi-pie-chart"></i>
						<?= UOJLocale::get('submissions') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/hacks') ?>">
						<i class="bi bi-flag"></i>
						<?= UOJLocale::get('hacks') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::blog_list_url() ?>">
						<i class="bi bi-pencil-square"></i>
						<?= UOJLocale::get('blogs') ?>
					</a>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<i class="bi bi-grid-3x3-gap"></i>
						<?= UOJLocale::get('apps') ?>
					</a>
					<ul class="dropdown-menu">
						<li>
							<a class="dropdown-item" href="<?= HTML::url('/apps/image_hosting') ?>">
								<i class="bi bi-images"></i>
								<?= UOJLocale::get('image hosting') ?>
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="<?= HTML::url('/apps/html2markdown') ?>">
								<i class="bi bi-markdown"></i>
								<?= UOJLocale::get('html to markdown') ?>
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="<?= HTML::url('/apps/diff_online') ?>">
								<i class="bi bi-file-earmark-diff"></i>
								<?= UOJLocale::get('diff online') ?>
							</a>
						</li>
					</ul>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/faq') ?>">
						<i class="bi bi-question-circle"></i>
						<?= UOJLocale::get('help') ?>
					</a>
				</li>
			</ul>

			<hr class="d-md-none text-muted">

			<?php uojIncludeView('nav-right', [
				'new_msg_tot' => $new_msg_tot,
				'new_user_msg_num' => $new_user_msg_num,
				'new_system_msg_num' => $new_system_msg_num,
			]) ?>
		</div>

	</div>
</div>

<script>
	var zan_link = '';
</script>
