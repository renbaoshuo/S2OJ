<?php

return function ($type) {
	if ($type == 'up') {
		DB::init();

		$purifier = HTML::purifier();
		$parsedown = new ParsedownMath([
			'math' => [
				'enabled' => true,
				'matchSingleDollar' => true
			]
		]);

		$blogs = DB::query("SELECT * from blogs");

		while ($blog = DB::fetch($blogs)) {
			$id = $blog['id'];
			$type = $blog['type'];
			$content_md = $blog['content_md'];
			$content = '';

			echo "Processing blog $id...\n";

			if ($type == 'B') {
				$dom = new DOMDocument;
				$dom->loadHTML(mb_convert_encoding($parsedown->text($content_md), 'HTML-ENTITIES', 'UTF-8'));
				$elements = $dom->getElementsByTagName('table');
				foreach ($elements as $element) {
					$element->setAttribute('class',
						$element->getAttribute('class') . ' table table-bordered');
				}
				$content = $purifier->purify($dom->saveHTML());

				if (preg_match('/^.*<!--.*readmore.*-->.*$/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
					$content_less = substr($content, 0, $matches[0][1]);
					$content_more = substr($content, $matches[0][1] + strlen($matches[0][0]));
					$content = $purifier->purify($content_less).'<!-- readmore -->'.$purifier->purify($content_more);
				} else {
					$content = $purifier->purify($content);
				}
			} elseif ($type == 'S') {
				$content_array = yaml_parse($content_md);
				if ($content_array === false || !is_array($content_array)) {
					continue;
				}
		
				$marked = function($md) use ($parsedown, $purifier) {
					$dom = new DOMDocument;
					$dom->loadHTML(mb_convert_encoding($parsedown->text($md), 'HTML-ENTITIES', 'UTF-8'));
					$elements = $dom->getElementsByTagName('li');
				
					foreach ($elements as $element) {
						$element->setAttribute('class', 
							$element->getAttribute('class') . ' fragment');
					}

					return $purifier->purify($dom->saveHTML());
				};
		
				$config = array();
				foreach ($content_array as $slide_name => $slide_content) {
					if (is_array($slide_content) && is_array($slide_content['config'])) {
						foreach (array('theme') as $config_key) {
							if (is_string($slide_content['config'][$config_key]) && strlen($slide_content['config'][$config_key]) <= 30) {
								$config[$config_key] = $slide_content['config'][$config_key];
							}
						}
						continue;
					}
			
					$content .= '<section>';
			
					if (is_string($slide_content)) {
						$content .= $marked($slide_content);
					} elseif (is_array($slide_content)) {
						if (is_array($slide_content['children'])) {
							foreach ($slide_content['children'] as $cslide_name => $cslide_content) {
								$content .= '<section>';
								$content .= $marked($cslide_content);
								$content .= '</section>';
							}
						}
					}
					$content .= "</section>\n";
				}
				$content = json_encode($config) . "\n" . $content;
			}

			DB::update("UPDATE blogs SET content = '" . DB::escape($content) . "' WHERE id = $id");
		}

		$problems_contents = DB::query("SELECT * from problems_contents");

		while ($problem = DB::fetch($problems_contents)) {
			$content_md = $problem['statement_md'];
			$content = '';

			echo "Processing problem {$problem['id']}...\n";

			$dom = new DOMDocument;
			$dom->loadHTML(mb_convert_encoding($parsedown->text($content_md), 'HTML-ENTITIES', 'UTF-8'));
			$elements = $dom->getElementsByTagName('table');
			foreach ($elements as $element) {
				$element->setAttribute('class',
					$element->getAttribute('class') . ' table table-bordered');
			}
			$content = $purifier->purify($dom->saveHTML());

			DB::update("UPDATE problems_contents set statement = '" . DB::escape($content) . "' where id = {$problem['id']}");
		}
	} elseif ($type == 'down') {
		//
	}
};
