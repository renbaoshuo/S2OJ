<?php

Route::pattern('username', '[a-zA-Z0-9_]{1,20}');
Route::pattern('id', '[1-9][0-9]{0,9}');
Route::pattern('contest_id', '[1-9][0-9]{0,9}');
Route::pattern('list_id', '[1-9][0-9]{0,9}');
Route::pattern('tab', '\S{1,20}');
Route::pattern('rand_str_id', '[0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ]{20}');
Route::pattern('image_name', '[0-9a-z]{1,20}');
Route::pattern('upgrade_name', '[a-zA-Z0-9_]{1,50}');

Route::group(
	[
		'domain' => '(' . UOJConfig::$data['web']['main']['host'] . '|127.0.0.1' . ')'
	],
	function () {
		Route::any('/', '/index.php');
		Route::any('/problems', '/problem_set.php');
		Route::any('/problems/template', '/problem_set.php?tab=template');
		Route::any('/problem/{id}', '/problem.php');
		Route::any('/problem/{id}/solutions', '/problem_solutions.php');
		Route::any('/problem/{id}/statistics', '/problem_statistics.php');
		Route::any('/problem/{id}/manage/statement', '/problem_statement_manage.php');
		Route::any('/problem/{id}/manage/managers', '/problem_managers_manage.php');
		Route::any('/problem/{id}/manage/data', '/problem_data_manage.php');
		Route::any('/download/problem/{id}/data.zip', '/download.php?type=problem');
		Route::any('/download/problem/{id}/attachment.zip', '/download.php?type=attachment');

		Route::any('/lists', '/lists.php');
		Route::any('/list/{id}', '/list.php');
		Route::any('/list/{id}/edit(?:/{tab})?', '/list_edit.php');

		Route::any('/contests', '/contests.php');
		Route::any('/contest/new', '/add_contest.php');
		Route::any('/contest/{id}', '/contest_inside.php');
		Route::any('/contest/{id}/registrants', '/contest_members.php');
		Route::any('/contest/{id}/register', '/contest_registration.php');
		Route::any('/contest/{id}/confirm', '/contest_confirmation.php');
		Route::any('/contest/{id}/manage(?:/{tab})?', '/contest_manage.php');
		Route::any('/contest/{id}/submissions', '/contest_inside.php?tab=submissions');
		Route::any('/contest/{id}/standings', '/contest_inside.php?tab=standings');
		Route::any('/contest/{id}/after_contest_standings', '/contest_inside.php?tab=after_contest_standings');
		Route::any('/contest/{id}/self_reviews', '/contest_inside.php?tab=self_reviews');
		Route::any('/contest/{id}/backstage', '/contest_inside.php?tab=backstage');
		Route::any('/contest/{id}/standings_unfrozen', '/contest_inside.php?tab=standings_unfrozen');
		Route::any('/contest/{contest_id}/problem/{id}', '/problem.php');
		Route::any('/contest/{contest_id}/problem/{id}/statistics', '/problem_statistics.php');

		Route::any('/submissions', '/submissions_list.php');
		Route::any('/submission/{id}', '/submission.php');
		Route::any('/submission-status-details', '/submission_status_details.php');

		Route::any('/hacks', '/hack_list.php');
		Route::any('/hack/{id}', '/hack.php');

		Route::any('/groups', '/groups.php');
		Route::any('/group/{id}', '/group.php');
		Route::any('/group/{id}/manage(?:/{tab})?', '/group_manage.php');
		Route::any('/group/{id}/assignment/{list_id}', '/group_assignment.php');

		Route::any('/blogs', '/blogs.php');
		if (UOJConfig::$data['switch']['blog-domain-mode'] != 3) {
			Route::any('/blog/{id}', '/blog_show.php');
		}
		Route::any('/blogs/{id}', '/blog_show.php');
		Route::any('/post/{id}', '/blog_show.php');
		Route::any('/post/{id}/write', '/blog_show.php?sub=%2Fwrite');

		Route::any('/announcements', '/announcements.php');

		Route::any('/faq', '/faq.php');
		Route::any('/solverlist', '/ranklist.php?type=accepted');

		Route::any('/captcha', '/captcha.php');
		Route::any('/login', '/login.php');
		Route::any('/logout', '/logout.php');
		Route::any('/register', '/register.php');
		Route::any('/forgot-password', '/forgot_pw.php');
		Route::any('/reset-password', '/reset_pw.php');

		Route::any('/user/{username}', '/user_info.php');
		Route::any('/user/{username}/edit(?:/{tab})?', '/user_info_edit.php');
		Route::any('/user_msg', '/user_msg.php');
		Route::any('/user/{username}/system_msg', '/user_system_msg.php');

		Route::any('/super_manage(?:/{tab})?', '/super_manage.php');

		Route::any('/download.php', '/download.php');

		Route::any('/check-notice', '/check_notice.php');
		Route::any('/click-zan', '/click_zan.php');

		// Apps
		Route::any('/image_hosting', '/image_hosting/index.php');
		Route::get('/image_hosting/{image_name}.png', '/image_hosting/get_image.php');
		Route::any('/html2markdown', '/html2markdown.php');
	}
);

Route::post('/judge/submit', '/judge/submit.php');
Route::post('/judge/sync-judge-client', '/judge/sync_judge_client.php');

Route::post('/judge/download/submission/{id}/{rand_str_id}', '/download.php?type=submission&auth=judger');
Route::post('/judge/download/tmp/{rand_str_id}', '/download.php?type=tmp&auth=judger');
Route::post('/judge/download/problem/{id}', '/download.php?type=problem&auth=judger');
