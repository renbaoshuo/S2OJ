<?php
	if (isset($REQUIRE_LIB['bootstrap5'])) {
		$new_user_msg_num = DB::selectCount("select count(*) from user_msg where receiver = '".Auth::id()."' and read_time is null");
		$new_system_msg_num = DB::selectCount("select count(*) from user_system_msg where receiver = '".Auth::id()."' and read_time is null");
		$new_msg_tot = $new_user_msg_num + $new_system_msg_num;
	}
	?>

<div class="navbar navbar-light navbar-expand-md
<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
bg-white shadow-sm
<?php else: ?>
bg-light
<?php endif ?>
mb-4" role="navigation">
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	<div class="container">
	<?php endif ?>
		<a class="navbar-brand" href="<?= HTML::url('/') ?>">
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
				<img src="<?= HTML::url('/images/logo_small.png') ?>" alt="Logo" width="24" height="24" class="d-inline-block align-text-top"/>
			<?php endif ?>
			<?= UOJConfig::$data['profile']['oj-name-short'] ?>
		</a>

		<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		<?php else: ?>
		<button type="button" class="navbar-toggler collapsed" data-toggle="collapse" data-target=".navbar-collapse">
		<?php endif ?>
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="nav navbar-nav mr-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/contests') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-bar-chart"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-stats"></span>
						<?php endif ?>
						<?= UOJLocale::get('contests') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/problems') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-list-task"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-th-list"></span>
						<?php endif ?>
						<?= UOJLocale::get('problems') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/groups') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-people"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-education"></span>
						<?php endif ?>
						<?= UOJLocale::get('groups') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/lists') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-card-list"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-list-alt"></span>
						<?php endif ?>
						<?= UOJLocale::get('problems lists') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/submissions') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-pie-chart"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-tasks"></span>
						<?php endif ?>
						<?= UOJLocale::get('submissions') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/hacks') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-flag"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-flag"></span>
						<?php endif ?>
						<?= UOJLocale::get('hacks') ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::blog_list_url() ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-pencil-square"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-edit"></span>
						<?php endif ?>
						<?= UOJLocale::get('blogs') ?>
					</a>
				</li>
				<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							<i class="bi bi-grid-3x3-gap"></i>
							<?= UOJLocale::get('apps') ?>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/image_hosting') ?>">
									<i class="bi bi-images"></i>
									<?= UOJLocale::get('image hosting') ?>
								</a>
							</li>
							<li>
								<a class="dropdown-item" href="<?= HTML::url('/html2markdown') ?>">
									<i class="bi bi-markdown"></i>
									<?= UOJLocale::get('html to markdown') ?>
								</a>
							</li>
						</ul>
					</li>
				<?php endif ?>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/faq') ?>">
						<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
							<i class="bi bi-question-circle"></i>
						<?php else: ?>
							<span class="glyphicon glyphicon-info-sign"></span>
						<?php endif ?>
						<?= UOJLocale::get('help') ?>
					</a>
				</li>
				<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
					<li class="nav-item">
						<a class="nav-link" href="#" id="try-bs5">
							<span class="glyphicon glyphicon-share"></span>
							体验新版
						</a>
						<script>
							$('#try-bs5').click(function() {
								$.removeCookie('bootstrap4', { path: '/' });
								location.reload();
							});
						</script>
					</li>
				<?php endif ?>
			</ul>
			<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
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
				<?php if (Auth::check()): ?>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
						<i class="bi bi-person-fill"></i>
						<span class="position-relative">
							<?= Auth::id() ?>
							<?php if ($new_msg_tot): ?>
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
								<?php if ($new_user_msg_num): ?>
								<span class="badge bg-danger rounded-pill">
									<?= $new_user_msg_num > 99 ? "99+" : $new_user_msg_num ?>
								</span>
								<?php endif ?>
							</a>
						</li>
						<li>
							<a class="dropdown-item" href="<?= HTML::url('/user/'.Auth::id().'/system_msg') ?>">
								<?= UOJLocale::get('system message') ?>
								<?php if ($new_system_msg_num): ?>
								<span class="badge bg-danger rounded-pill">
									<?= $new_system_msg_num > 99 ? "99+" : $new_system_msg_num ?>
								</span>
								<?php endif ?>
							</a>
						</li>
						<?php if (isSuperUser(Auth::user())): ?>
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
				<?php else: ?>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/login') ?>">
						<i class="bi bi-box-arrow-in-right"></i>
						<?= UOJLocale::get('login') ?>
					</a>
				</li>
				<?php if (UOJConfig::$data['switch']['open-register'] || !DB::selectCount("SELECT COUNT(*) FROM user_info")): ?>
				<li class="nav-item">
					<a class="nav-link" href="<?= HTML::url('/register') ?>">
						<i class="bi bi-person-plus-fill"></i>
						<?= UOJLocale::get('register') ?>
					</a>
				</li>
				<?php endif ?>
				<?php endif ?>
			</ul>
			<?php else: ?>
			<form id="form-search-problem" class="form-inline my-2 my-lg-0" method="get">
				<div class="input-group">
					<input type="text" class="form-control" name="search" id="input-search" placeholder="<?= UOJLocale::get('search')?>" />  
					<div class="input-group-append">
						<button type="submit" class="btn btn-search btn-outline-primary" id="submit-search"><span class="glyphicon glyphicon-search"></span></button>
					</div>
				</div>
			</form>
			<?php endif ?>
		</div>
	
	<?php if (isset($REQUIRE_LIB['bootstrap5'])): ?>
	</div>
	<?php endif ?>
</div>

<script>
	var zan_link = '';
</script>

<?php if (!isset($REQUIRE_LIB['bootstrap5'])): ?>
<script type="text/javascript">
	$('#form-search-problem').submit(function(e) {
		e.preventDefault();
		
		url = '<?= HTML::url('/problems') ?>';
		qs = [];
		$(['search']).each(function () {
			if ($('#input-' + this).val()) {
				qs.push(this + '=' + encodeURIComponent($('#input-' + this).val()));
			}
		});
		if (qs.length > 0) {
			url += '?' + qs.join('&');
		}
		location.href = url;
	});
</script>
<?php endif ?>
