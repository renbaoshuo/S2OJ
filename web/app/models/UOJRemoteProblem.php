<?php

class UOJRemoteProblem {
	static $providers = [
		'codeforces' => [
			'name' => 'Codeforces',
			'short_name' => 'CF',
			'url' => 'https://codeforces.com',
			'host' => 'https://codeforces.com',
			'not_exists_texts' => [
				'<th>Actions</th>',
				'Statement is not available on English language',
				'ограничение по времени на тест',
			],
			'languages' => ['C', 'C++', 'C++17', 'C++20', 'Java17', 'Pascal', 'Python2', 'Python3'],
		],
	];

	// 传入 ID 需确保有效
	static function getCodeforcesProblemBasicInfo($id) {
		$curl = new Curl();
		$curl->setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36 S2OJ/3.1.0');

		$remote_provider = static::$providers['codeforces'];
		$url_id = preg_replace_callback('/([1-9][0-9]{0,5})([A-Z][1-9]?)/', fn ($matches) => $matches[1] . '/' . $matches[2], $id);

		$html = retry_loop(function () use ($curl, $url_id, $remote_provider) {
			$curl->get($remote_provider['host'] . '/problemset/problem/' . $url_id);

			if ($curl->error) {
				return false;
			}

			return $curl->response;
		});

		if (!$html) return null;

		$html = preg_replace('/\$\$\$/', '$', $html);
		$dom = new \IvoPetkov\HTML5DOMDocument();
		$dom->loadHTML($html);

		$judgestatement = $dom->querySelector('html')->innerHTML;

		foreach ($remote_provider['not_exists_texts'] as $text) {
			if (str_contains($judgestatement, $text)) {
				return null;
			}
		}

		$statement_dom = $dom->querySelector('.problem-statement');
		$title = explode('. ', trim($statement_dom->querySelector('.title')->innerHTML))[1];
		$title = "【{$remote_provider['short_name']}{$id}】{$title}";
		$time_limit = intval(substr($statement_dom->querySelector('.time-limit')->innerHTML, 53));
		$memory_limit = intval(substr($statement_dom->querySelector('.memory-limit')->innerHTML, 55));
		$difficulty = -1;

		foreach ($dom->querySelectorAll('.tag-box') as &$elem) {
			$matches = [];

			if (preg_match('/\*([0-9]{3,4})/', trim($elem->innerHTML), $matches)) {
				$difficulty = intval($matches[1]);

				break;
			}
		}

		if ($difficulty != -1) {
			$closest = null;

			foreach (UOJProblem::$difficulty as $val) {
				if ($closest === null || abs($val - $difficulty) < abs($closest - $difficulty)) {
					$closest = $val;
				}
			}

			$difficulty = $closest;
		}

		$statement_dom->removeChild($statement_dom->querySelector('.header'));
		$statement_dom->childNodes->item(0)->insertBefore($dom->createElement('h3', 'Description'), $statement_dom->childNodes->item(0)->childNodes->item(0));

		foreach ($statement_dom->querySelectorAll('.section-title') as &$elem) {
			$elem->outerHTML = '<h3>' . $elem->innerHTML . '</h3>';
		}

		$sample_input_cnt = 0;
		$sample_output_cnt = 0;

		foreach ($statement_dom->querySelectorAll('.input') as &$input_dom) {
			$sample_input_cnt++;
			$input_text = '';

			if ($input_dom->querySelector('.test-example-line')) {
				foreach ($input_dom->querySelectorAll('.test-example-line') as &$line) {
					$input_text .= HTML::stripTags($line->innerHTML) . "\n";
				}
			} else {
				$input_text = HTML::stripTags($input_dom->querySelector('pre')->innerHTML);
			}

			$input_dom->outerHTML = HTML::tag('h4', [], "Input #{$sample_input_cnt}") . HTML::tag('pre', [], HTML::tag('code', [], $input_text));
		}

		foreach ($statement_dom->querySelectorAll('.output') as &$output_dom) {
			$sample_output_cnt++;
			$output_text = '';

			if ($output_dom->querySelector('.test-example-line')) {
				foreach ($output_dom->querySelectorAll('.test-example-line') as &$line) {
					$output_text .= HTML::stripTags($line->innerHTML) . "\n";
				}
			} else {
				$output_text = HTML::stripTags($output_dom->querySelector('pre')->innerHTML);
			}

			$output_dom->outerHTML = HTML::tag('h4', [], "Output #{$sample_output_cnt}") . HTML::tag('pre', [], HTML::tag('code', [], $output_text));
		}

		return [
			'title' => $title,
			'time_limit' => $time_limit,
			'memory_limit' => $memory_limit,
			'difficulty' => $difficulty,
			'statement' => $statement_dom->innerHTML,
		];
	}

	public static function getProblemBasicInfo($oj, $id) {
		if ($oj === 'codeforces') {
			return static::getCodeforcesProblemBasicInfo($id);
		}

		return null;
	}
}
