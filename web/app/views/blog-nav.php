<div class="navbar navbar-light navbar-expand-md bg-body shadow-sm
<?php if (!isset($disable_navbar_margin_bottom)) : ?>
mb-4
<?php endif ?>
" role="navigation">
	<div class="container">
		<a class="navbar-brand fw-normal" href="<?= HTML::blog_url(UOJUserBlog::id(), '/') ?>">
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

			<?php uojIncludeView('nav-right', [
				'new_msg_tot' => $new_msg_tot,
				'new_user_msg_num' => $new_user_msg_num,
				'new_system_msg_num' => $new_system_msg_num,
			]) ?>
		</div>
	</div>
</div>

<script type="text/javascript">
	var uojBlogUrl = '<?= HTML::blog_url(UOJUserBlog::id(), '') ?>';
	var zan_link = uojBlogUrl;
</script>
