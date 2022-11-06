<?php

if (!Auth::check()) {
	become403Page(UOJLocale::get('need login'));
}

$name = $_GET['image_name'];
if (!validateString($name)) {
	become404Page();
}

$file_name = UOJContext::storagePath() . "/image_hosting/$name.png";

$finfo = finfo_open(FILEINFO_MIME);
$mimetype = finfo_file($finfo, $file_name);
if ($mimetype === false) {
	become404Page();
}
finfo_close($finfo);

header("X-Sendfile: $file_name");
header("Content-type: $mimetype");
header("Cache-Control: max-age=604800", true);
