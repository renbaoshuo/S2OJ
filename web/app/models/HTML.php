<?php
class CustomClassDef extends HTMLPurifier_AttrDef {
	private $classes, $prefixes;

	public function __construct($classes, $prefixes) {
		$this->classes = $classes;
		$this->prefixes = is_array($prefixes) ? join('|', $prefixes) : $prefixes;
	}

	public function validate($string, $config, $context) {
		$classes = preg_split('/\s+/', $string);
		$valid_classes = [];

		foreach ($classes as $class) {
			if (
				in_array($class, $this->classes) ||
				preg_match("/^({$this->prefixes})/i", $class)
			) {

				$valid_classes[] = $class;
			}
		}

		return join(' ', $valid_classes);
	}
}

class HTML {
	public static function escape(?string $str, $cfg = []) {
		if ($str === null) {
			return '';
		} else {
			if (!empty($cfg['single_line'])) {
				$str = str_replace(["\n", "\r"], '', $str);
			}
			return htmlspecialchars($str);
		}
	}

	public static function unescape(?string $str) {
		if ($str === null) {
			return '';
		} else {
			return htmlspecialchars_decode($str);
		}
	}

	public static function stripTags($str) {
		return strip_tags($str);
	}

	public static function protocol(string $loc = 'main') {
		if (UOJConfig::$data['web'][$loc]['protocol'] === 'http/https') {
			if (UOJContext::isUsingHttps()) {
				return 'https';
			} else {
				return 'http';
			}
		} else {
			return UOJConfig::$data['web'][$loc]['protocol'];
		}
	}
	public static function port(string $loc = 'main') {
		if (UOJConfig::$data['web'][$loc]['port'] === '80/443') {
			return HTML::standard_port(HTML::protocol($loc));
		} else {
			return UOJConfig::$data['web'][$loc]['port'];
		}
	}
	public static function standard_port(string $protocol) {
		if ($protocol === 'http') {
			return 80;
		} elseif ($protocol === 'https') {
			return 443;
		} else {
			return null;
		}
	}

	public static function attr($attr) {
		$html = '';
		foreach ($attr as $key => $val) {
			if ($val === null) continue;
			$html .= ' ' . $key . '="';
			$html .= HTML::escape(is_array($val) ? implode(' ', $val) : $val);
			$html .= '"';
		}
		return $html;
	}

	public static function tag_begin(string $name, array $attr = []) {
		return '<' . $name . HTML::attr($attr) . '>';
	}
	public static function tag_end(string $name) {
		return '</' . $name . '>';
	}
	public static function tag(string $name, array $attr, $content) {
		return HTML::tag_begin($name, $attr) . (is_array($content) ? implode('', $content) : $content) . HTML::tag_end($name);
	}
	public static function empty_tag(string $name, array $attr) {
		return '<' . $name . HTML::attr($attr) . ' />';
	}

	public static function avatar_addr($user, $size) {
		$extra = UOJUser::getExtra($user);
		if ($extra['avatar_source'] == 'qq' && $user['qq']) {
			$s = '5';

			if ($size <= 40) {
				$s = '2';
			} elseif ($size <= 100) {
				$s = '3';
			} elseif ($size <= 140) {
				$s = '4';
			}

			return "https://q1.qlogo.cn/g?b=qq&nk={$user['qq']}&s=$s";
		}

		return 'https://cravatar.cn/avatar/' . md5(strtolower(trim($user['email']))) . "?d=404&amp;s=$size";
	}

	public static function tablist($tabs_info, $cur, $type = 'nav-tabs') {
		$html = HTML::tag_begin('ul', ['class' => "nav $type", 'role' => 'tablist']);
		foreach ($tabs_info as $id => $tab) {
			$html .= HTML::tag('li', ['class' => 'nav-item'], HTML::tag('a', [
				'class' => 'nav-link' . ($cur == $id ? ' active' : ''),
				'href' => $tab['url'],
				'role' => 'tab',
			], $tab['name']));
		}
		$html .= HTML::tag_end('ul');
		return $html;
	}

	public static function navListGroup($tabs_info, $cur) {
		$html = '<div class="list-group">';
		foreach ($tabs_info as $id => $tab) {
			$html .= '<a role="button" class="list-group-item list-group-item-action' . ($cur == $id ? ' active' : '') . '" href="' . $tab['url'] . '">' . $tab['name'] . '</a>';
		}
		$html .= '</div>';
		return $html;
	}

	public static function hiddenToken() {
		return HTML::empty_tag('input', ['type' => 'hidden', 'name' => '_token', 'value' => crsf_token()]);
	}
	public static function div_vinput($name, $type, $label_text, $default_value) {
		return '<div id="' . "div-$name" . '" class="mb-3">'
			. '<label for="' . "input-$name" . '" class="control-label form-label">' . $label_text . '</label>'
			. '<input type="' . $type . '" class="form-control" name="' . $name . '" id="' . "input-$name" . '" value="' . HTML::escape($default_value) . '" />'
			. '<span class="help-block invalid-feedback" id="' . "help-$name" . '"></span>'
			. '</div>';
	}
	public static function div_vtextarea($name, $label_text, $default_value) {
		return '<div id="' . "div-$name" . '" class="mb-3">'
			. '<label for="' . "input-$name" . '" class="control-label">' . $label_text . '</label>'
			. '<textarea class="form-control" name="' . $name . '" id="' . "input-$name" . '">' . HTML::escape($default_value) . '</textarea>'
			. '<span class="help-block" id="' . "help-$name" . '"></span>'
			. '</div>';
	}
	public static function checkbox($name, $default_value) {
		$status = $default_value ? 'checked="checked" ' : '';
		return '<input class="form-check-input" type="checkbox" id="' . "input-$name" . '" name="' . $name . '" ' . $status . '/>';
	}
	public static function option($value, $text, $selected) {
		return '<option value="' . HTML::escape($value) . '"'
			. ($selected ? ' selected="selected"' : '') . '>'
			. HTML::escape($text)
			. '</option>';
	}
	public static function tr_none() {
		return '<tr class="text-center"><td colspan="233">' . UOJLocale::get('none') . '</td></tr>';
	}

	public static function blog_url($username, $uri, array $cfg = []) {
		$cfg += [
			'escape' => true
		];

		switch (UOJConfig::$data['switch']['blog-domain-mode']) {
			case 1:
				$port = ((UOJConfig::$data['web']['blog']['protocol'] === "http" && UOJConfig::$data['web']['blog']['port'] == 80) || (UOJConfig::$data['web']['blog']['protocol'] === "https" && UOJConfig::$data['web']['blog']['port'] == 443)) ? '' : (':' . UOJConfig::$data['web']['blog']['port']);
				$url = UOJConfig::$data['web']['blog']['protocol'] . '://' . blog_name_encode($username) . '.' . UOJConfig::$data['web']['blog']['host'] . $port;
				break;
			case 2:
				$port = ((UOJConfig::$data['web']['blog']['protocol'] === "http" && UOJConfig::$data['web']['blog']['port'] == 80) || (UOJConfig::$data['web']['blog']['protocol'] === "https" && UOJConfig::$data['web']['blog']['port'] == 443)) ? '' : (':' . UOJConfig::$data['web']['blog']['port']);
				$url = UOJConfig::$data['web']['blog']['protocol'] . '://' . UOJConfig::$data['web']['blog']['host'] . $port . '/' . blog_name_encode($username);
				break;
			case 3:
				$url = HTML::url('/blog/' . blog_name_encode($username));
				break;
		}
		$url .= $uri;
		$url = rtrim($url, '/');

		if ($cfg['escape']) {
			$url = HTML::escape($url);
		}

		return $url;
	}
	public static function blog_list_url() {
		switch (UOJConfig::$data['switch']['blog-domain-mode']) {
			case 1:
			case 2:
				$port = ((UOJConfig::$data['web']['blog']['protocol'] === "http" && UOJConfig::$data['web']['blog']['port'] == 80) || (UOJConfig::$data['web']['blog']['protocol'] === "https" && UOJConfig::$data['web']['blog']['port'] == 443)) ? '' : (':' . UOJConfig::$data['web']['blog']['port']);
				$url = UOJConfig::$data['web']['blog']['protocol'] . '://' . UOJConfig::$data['web']['blog']['host'] . $port;
				break;
			case 3:
				$url = HTML::url('/blogs');
				break;
		}
		return HTML::escape(rtrim($url, '/'));
	}

	public static function url($uri, array $cfg = []) {
		$cfg += [
			'location' => 'main',
			'params' => null,
			'remove_all_params' => false,
			'with_token' => false,
			'escape' => true
		];

		if ($cfg['location'] == 'cdn' && !UOJContext::hasCDN()) {
			$cfg['location'] = 'main';
		}

		if (strStartWith($uri, '?')) {
			$path = strtok(UOJContext::requestURI(), '?');
			$qs = strtok($uri, '?');
		} else {
			$path = strtok($uri, '?');
			$qs = strtok('?');
		}

		parse_str($qs, $param);


		if ($cfg['remove_all_params']) {
			$param = [];
		} elseif ($cfg['params'] != null) {
			$param = array_merge($param, $cfg['params']);
		}
		if ($cfg['with_token']) {
			$param['_token'] = crsf_token();
		}

		$protocol = HTML::protocol($cfg['location']);
		$url = $protocol . '://' . UOJConfig::$data['web'][$cfg['location']]['host'];
		if (HTML::port($cfg['location']) != HTML::standard_port($protocol)) {
			$url .= ':' . HTML::port($cfg['location']);
		}
		if ($param) {
			$url .= $path . '?' . HTML::query_string_encode($param);
		} elseif ($path != '/') {
			$url .= rtrim($path, '/');
		} else {
			$url .= $path;
		}

		if ($cfg['escape']) {
			$url = HTML::escape($url);
		}
		return $url;
	}
	public static function timeanddate_url(DateTime $time, array $cfg = []) {
		$url = HTML::protocol() . '://';
		$url .= 'www.timeanddate.com/worldclock/fixedtime.html';
		$url .= '?' . 'iso=' . $time->format('Ymd\THi');
		$url .= '&' . 'p1=33';
		if (isset($cfg['duration']) && $cfg['duration'] < 3600) {
			$url .= '&' . 'ah=' . floor($cfg['duration'] / 60);
			if ($cfg['duration'] % 60 != 0) {
				$url .= '&' . 'am=' . ($cfg['duration'] % 60);
			}
		}
		return HTML::escape($url);
	}

	public static function relative_time_str($time, $gran = -1) {
		$d = [
			[1, 'seconds'],
			[60, 'minutes'],
			[3600, 'hours'],
			[86400, 'days'],
			[604800, 'weeks'],
			[2592000, 'months'],
			[31536000, 'years'],
		];
		$w = [];

		$res = "";
		$diff = time() - $time;
		$secondsLeft = $diff;
		$stopat = 0;
		for ($i = 6; $i > $gran; $i--) {
			$w[$i] = intval($secondsLeft / $d[$i][0]);
			$secondsLeft -= ($w[$i] * $d[$i][0]);
			if ($w[$i] != 0) {
				$res .= UOJLocale::get('time::x ' . $d[$i][1], abs($w[$i])) . " ";
				switch ($i) {
					case 6: // shows years and months
						if ($stopat == 0) {
							$stopat = 5;
						}
						break;
					case 5: // shows months and weeks
						if ($stopat == 0) {
							$stopat = 4;
						}
						break;
					case 4: // shows weeks and days
						if ($stopat == 0) {
							$stopat = 3;
						}
						break;
					case 3: // shows days and hours
						if ($stopat == 0) {
							$stopat = 2;
						}
						break;
					case 2: // shows hours and minutes
						if ($stopat == 0) {
							$stopat = 1;
						}
						break;
					case 1: // shows minutes and seconds if granularity is not set higher
						break;
				}
				if ($i <= $stopat) {
					break;
				}
			}
		}

		$res .= ($diff > 0) ? UOJLocale::get('time::ago') : UOJLocale::get('time::left');

		return $res;
	}

	public static function link(?string $uri, $text, $cfg = []) {
		$cfg += ['location' => 'main', 'escape' => true, 'class' => ''];

		if ($cfg['escape']) {
			$text = HTML::escape($text);
		}

		if ($uri === null) {
			return HTML::tag('a', ['class' => $cfg['class']], $text);
		}

		return HTML::tag('a', ['href' => HTML::url($uri, $cfg), 'class' => $cfg['class']], $text);
	}

	public static function autolink(string $url, array $attr = [], $cfg = []) {
		$cfg += ['escape' => true];
		$text = $url;

		if ($cfg['escape']) {
			$text = HTML::escape($text);
		}

		return '<a href="' . $url . '"' . HTML::attr($attr) . '>' . $text . '</a>';
	}
	public static function js_src(string $uri, array $cfg = []) {
		$cfg += [
			'location' => 'cdn',
			'async' => false
		];
		$async = empty($cfg['async']) ? '' : 'async ';
		return '<script ' . $async . 'src="' . HTML::url($uri, $cfg) . '"></script>';
	}
	public static function css_link(string $uri, $cfg = []) {
		$cfg += ['location' => 'cdn'];
		return '<link rel="stylesheet" href="' . HTML::url($uri, $cfg) . '" />';
	}

	public static function table($header, iterable $data, $cfg = []) {
		mergeConfig($cfg, [
			'th' => function ($c) {
				return "<th>{$c}</th>";
			},
			'td' => function ($d) {
				return "<td>{$d}</td>";
			},
			'tr' => false, // if tr is a function, then td and tr_attr is disabled
			'empty' => 'HTML::tr_none',
			'table_attr' => [
				'class' => ['table'],
			],
			'thead_attr' => [],
			'tbody_attr' => [],
			'tr_attr' => function ($row, $idx) {
				return [];
			}
		]);

		$html = HTML::tag_begin('table', $cfg['table_attr']);
		$html .= HTML::tag_begin('thead', $cfg['thead_attr']);
		if (is_array($header)) {
			$html .= '<tr>' . implode(' ', array_map($cfg['th'], array_values($header), array_keys($header))) . '</tr>';
		} else {
			$html .= $header;
		}
		$html .= HTML::tag_end('thead');
		$html .= HTML::tag_begin('tbody', $cfg['tbody_attr']);
		if (is_iterable($data)) {
			$data_html = [];
			if (is_callable($cfg['tr'])) {
				foreach ($data as $idx => $row) {
					$data_html[] = $cfg['tr']($row, $idx);
				}
			} else {
				foreach ($data as $idx => $row) {
					$data_html[] = HTML::tag_begin('tr', $cfg['tr_attr']($row, $idx));
					if (is_array($row)) {
						foreach ($row as $cidx => $c) {
							$data_html[] = $cfg['td']($c, $cidx);
						}
					} else {
						$data_html[] = $row;
					}
					$data_html[] = HTML::tag_end('tr');
				}
			}
			$data_html = implode($data_html);
		} else {
			$data_html = $data;
		}
		$html .= $data_html !== '' ? $data_html : $cfg['empty']();
		$html .= HTML::tag_end('tbody');
		$html .= HTML::tag_end('table');
		return $html;
	}

	public static function responsive_table($header, $data, $cfg = []) {
		return HTML::tag_begin('div', ['class' => 'table-responsive']) . HTML::table($header, $data, $cfg) . HTML::tag_end('div');
	}

	public static function query_string_encode($q, $array_name = null) {
		if (!is_array($q)) {
			return false;
		}
		$r = array();
		foreach ((array)$q as $k => $v) {
			if ($array_name !== null) {
				if (is_numeric($k)) {
					$k = $array_name . "[]";
				} else {
					$k = $array_name . "[$k]";
				}
			}
			if (is_array($v) || is_object($v)) {
				$r[] = self::query_string_encode($v, $k);
			} else {
				$r[] = urlencode($k) . "=" . urlencode($v);
			}
		}
		return implode("&", $r);
	}

	public static function purifier($extra_allowed_html = []) {
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Output.Newline', true);
		$def = $config->getHTMLDefinition(true);

		$def->addElement('section', 'Block', 'Flow', 'Common');
		$def->addElement('nav',     'Block', 'Flow', 'Common');
		$def->addElement('article', 'Block', 'Flow', 'Common');
		$def->addElement('aside',   'Block', 'Flow', 'Common');
		$def->addElement('header',  'Block', 'Flow', 'Common');
		$def->addElement('footer',  'Block', 'Flow', 'Common');

		mergeConfig($extra_allowed_html, [
			'div' => [
				'data-pdf' => 'Text',
				'data-src' => 'URI',
			],
			'span' => [
				'class' => new CustomClassDef(['uoj-username'], ['uoj-username-']),
				'data-realname' => 'Text',
				'data-color' => 'Color',
			],
			'img' => ['width' => 'Text'],
		]);

		foreach ($extra_allowed_html as $element => $attributes) {
			foreach ($attributes as $attribute => $type) {
				$def->addAttribute($element, $attribute, $type);
			}
		}

		return new HTMLPurifier($config);
	}

	public static function purifier_inline() {
		$allowed_html = [
			'a' => ['href' => 'URI'],
			'b' => [],
			'i' => [],
			'u' => [],
			's' => [],
			'em' => [],
			'strong' => [],
			'sub' => [],
			'sup' => [],
			'small' => [],
			'del' => [],
			'br' => [],
			'span' => [
				'class' => 'Enum#uoj-username,' . implode(',', array_map(fn ($c) => "uoj-username-{$c}", UOJUser::AVAILABLE_COLORS)),
				'data-realname' => 'Text',
				'data-color' => 'Enum#' . implode(',', UOJUser::AVAILABLE_COLORS),
			],
		];

		$allowed_elements = [];
		$allowed_attributes = [];

		foreach ($allowed_html as $element => $attributes) {
			$allowed_elements[$element] = true;
			foreach ($attributes as $attribute => $type) {
				$allowed_attributes["$element.$attribute"] = true;
			}
		}

		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.AllowedElements', $allowed_elements);
		$config->set('HTML.AllowedAttributes', $allowed_attributes);
		$def = $config->getHTMLDefinition(true);

		foreach ($allowed_html as $element => $attributes) {
			foreach ($attributes as $attribute => $type) {
				$def->addAttribute($element, $attribute, $type);
			}
		}

		return new HTMLPurifier($config);
	}

	public static function parsedown($config = []) {
		return new UOJMarkdown($config + [
			'math' => [
				'enabled' => true,
				'matchSingleDollar' => true
			]
		]);
	}

	public static function echoPanel($cls = [], $title, $body, $other = null) {
		if (is_string($cls)) {
			$cls = ['card' => $cls];
		}

		$cls += [
			'card' => '',
			'header' => '',
			'body' => '',
		];
		echo '<div class="card ', $cls['card'], '">';
		echo '<div class="card-header fw-bold ', $cls['header'], '">', $title, '</div>';
		if ($body !== null) {
			echo '<div class="card-body ', $cls['body'], '">';
			if (is_string($body)) {
				echo $body;
			} else {
				$body();
			}
			echo '</div>';
		}
		if ($other !== null) {
			if (is_string($other)) {
				echo $other;
			} else {
				$other();
			}
		}
		echo '</div>';
	}
}
