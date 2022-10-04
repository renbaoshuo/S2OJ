<?php
return [
	'ago' => 'ago',
	'left' => 'left',
	'x years' => function($x) {
		return $x . ' year' . ($x > 1 ? 's' : '');
	},
	'x months' => function($x) {
		return $x . ' month' . ($x > 1 ? 's' : '');
	},
	'x weeks' => function($x) {
		return $x . ' week' . ($x > 1 ? 's' : '');
	},
	'x days' => function($x) {
		return $x . ' day' . ($x > 1 ? 's' : '');
	},
	'x hours' => function($x) {
		return $x . ' hour' . ($x > 1 ? 's' : '');
	},
	'x minutes' => function($x) {
		return $x . ' minute' . ($x > 1 ? 's' : '');
	},
	'x seconds' => function($x) {
		return $x . ' second' . ($x > 1 ? 's' : '');
	},
];
