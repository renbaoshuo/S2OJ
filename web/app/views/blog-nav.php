<div class="navbar navbar-light navbar-expand-md bg-white shadow-sm mb-4" role="navigation">

	<div class="container">

		<a class="navbar-brand" href="<?= HTML::blog_url(UOJUserBlog::id(), '/') ?>">
			<img src="<?= HTML::avatar_addr(UOJUserBlog::user(), 48) ?>" alt="Logo" width="24" height="24" class="d-inline-block align-text-top uoj-user-avatar" />
			<?= UOJUserBlog::id() ?>
		</a>

		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="navbar-collapse collapse" id="navbarSupportedContent">
			<ul class="nav navbar-nav">
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::blog_url(UOJUserBlog::id(), '/archive') ?>">
						<i class="bi bi-inbox-fill"></i>
						归档
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::blog_url(UOJUserBlog::id(), '/self_reviews') ?>">
						<i class="bi bi-sunglasses"></i>
						赛后总结
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::blog_url(UOJUserBlog::id(), '/aboutme') ?>">
						<i class="bi bi-person-lines-fill"></i>
						关于我
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/') ?>">
						<i class="bi bi-link-45deg"></i>
						<?= UOJConfig::$data['profile']['oj-name-short'] ?>
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

<script type="text/javascript">
	var uojBlogUrl = '<?= HTML::blog_url(UOJUserBlog::id(), '') ?>';
	var zan_link = uojBlogUrl;
</script>
