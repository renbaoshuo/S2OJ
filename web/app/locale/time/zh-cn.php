<?php
return [
	'ago' => '前',
	'left' => '后',
	'x years' => function($x) {
		return $x . ' 年';
	},
	'x months' => function($x) {
		return $x . ' 月';
	},
	'x weeks' => function($x) {
		return $x . ' 周';
	},
	'x days' => function($x) {
		return $x . ' 天';
	},
	'x hours' => function($x) {
		return $x . ' 小时';
	},
	'x minutes' => function($x) {
		return $x . ' 分钟';
	},
	'x seconds' => function($x) {
		return $x . '秒';
	},
];
