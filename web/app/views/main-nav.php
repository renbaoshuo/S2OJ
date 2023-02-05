<?php
$new_user_msg_num = DB::selectCount("select count(*) from user_msg where receiver = '" . Auth::id() . "' and read_time is null");
$new_system_msg_num = DB::selectCount("select count(*) from user_system_msg where receiver = '" . Auth::id() . "' and read_time is null");
$new_msg_tot = $new_user_msg_num + $new_system_msg_num;
?>

<div class="navbar navbar-light navbar-expand-md bg-white shadow-sm
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

			<ul class="nav navbar-nav ms-md-auto">
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<i class="bi bi-translate"></i>
						<?= UOJLocale::get('_common_name') ?>
					</a>
					<ul class="dropdown-menu">
						<li>
							<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'zh-cn'))) ?>">
								中文
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'en'))) ?>">
								English
							</a>
						</li>
					</ul>
				</li>
				<?php if (Auth::check()) : ?>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<i class="bi bi-person-fill"></i>
							<span class="position-relative">
								<?= Auth::id() ?>
								<?php if ($new_msg_tot) : ?>
									<span class="badge bg-danger rounded-pill">
										<?= $new_msg_tot > 99 ? "99+" : $new_msg_tot ?>
									</span>
								<?php endif ?>
							</span>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/user/' . Auth::id()) ?>">
									<?= UOJLocale::get('my profile') ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/user_msg') ?>">
									<?= UOJLocale::get('private message') ?>
									<?php if ($new_user_msg_num) : ?>
										<span class="badge bg-danger rounded-pill">
											<?= $new_user_msg_num > 99 ? "99+" : $new_user_msg_num ?>
										</span>
									<?php endif ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/user/' . Auth::id() . '/system_msg') ?>">
									<?= UOJLocale::get('system message') ?>
									<?php if ($new_system_msg_num) : ?>
										<span class="badge bg-danger rounded-pill">
											<?= $new_system_msg_num > 99 ? "99+" : $new_system_msg_num ?>
										</span>
									<?php endif ?>
								</a>
							</li>
							<?php if (isSuperUser(Auth::user())) : ?>
								<li>
									<a class="dropdown-item" href="<?= HTML::url('/super_manage') ?>">
										<?= UOJLocale::get('system manage') ?>
									</a>
								</li>
							<?php endif ?>
							<li>
								<hr class="dropdown-divider">
							</li>
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/logout?_token=' . crsf_token()) ?>">
									<?= UOJLocale::get('logout') ?>
								</a>
							</li>
						</ul>
					</li>
				<?php else : ?>
					<li class="nav-item">
						<a class="nav-link" href="<?= HTML::url('/login') ?>">
							<i class="bi bi-box-arrow-in-right"></i>
							<?= UOJLocale::get('login') ?>
						</a>
					</li>
					<?php if (UOJConfig::$data['switch']['open-register'] || !DB::selectCount("SELECT COUNT(*) FROM user_info")) : ?>
						<li class="nav-item">
							<a class="nav-link" href="<?= HTML::url('/register') ?>">
								<i class="bi bi-person-plus-fill"></i>
								<?= UOJLocale::get('register') ?>
							</a>
						</li>
					<?php endif ?>
				<?php endif ?>
			</ul>
		</div>

	</div>
</div>

<script>
	var zan_link = '';
</script>
