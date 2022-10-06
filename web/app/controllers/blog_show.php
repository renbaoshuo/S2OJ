<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isNormalUser($myUser)) {
		become403Page();
	}

	if (!validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id']))) {
		become404Page();
	}
	
	redirectTo(HTML::blog_url($blog['poster'], '/post/'.$_GET['id']));
	?>
