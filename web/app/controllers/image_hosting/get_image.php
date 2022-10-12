<?php
	requirePHPLib('form');

	if (!Auth::check() && UOJConfig::$data['switch']['force-login']) {
		redirectToLogin();
	}
