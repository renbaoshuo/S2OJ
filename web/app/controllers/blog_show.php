<?php

Auth::check() || redirectToLogin();
UOJBlog::init(UOJRequest::get('id')) || UOJResponse::page404();

redirectTo(HTML::blog_url(UOJBlog::info('poster'), '/post/' . UOJBlog::info('id'), ['escape' => false]));
