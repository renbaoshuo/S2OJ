<?php

Auth::check() || redirectToLogin();

$parsedown_type = UOJRequest::post('parsedown_type', 'is_string', 'default');
$purifier_type = UOJRequest::post('purifier_type', 'is_string', 'default');

$markdown = UOJRequest::post('markdown', 'is_string', '');

$parsedown = HTML::parsedown();

if ($purifier_type == 'inline') {
	$purifier = HTML::purifier_inline();
} else {
	$purifier = HTML::purifier();
}

if ($parsedown_type == 'inline') {
	$html = $purifier->purify($parsedown->line(UOJRequest::post('markdown', 'is_string')));
} else {
	$html = $purifier->purify($parsedown->text(UOJRequest::post('markdown', 'is_string')));
}

die($html);
