<ul class="nav navbar-nav ms-md-auto">
	<li class="nav-item dropdown">
		<button class="btn btn-link nav-link py-2 px-0 px-lg-2 dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static">
			<i class="bi bi-circle-half theme-icon-active" data-icon="bi-circle-half"></i>
			<span class="d-md-none">&nbsp;切换主题</span>
		</button>
		<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 6rem;">
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto">
					<i class="bi bi-circle-half me-2 opacity-50 theme-icon" data-icon="bi-circle-half"></i>
					系统
				</button>
			</li>
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
					<i class="bi bi-sun-fill me-2 opacity-50 theme-icon" data-icon="bi-sun-fill"></i>
					亮色
				</button>
			</li>
			<li>
				<button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
					<i class="bi bi-moon-stars-fill me-2 opacity-50 theme-icon" data-icon="bi-moon-stars-fill"></i>
					暗色
				</button>
			</li>
		</ul>
	</li>
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
