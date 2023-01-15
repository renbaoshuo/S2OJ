<?php
class UOJMarkdown extends ParsedownMath {
	public function __construct($options = '') {
		if (method_exists(get_parent_class(), "__construct")) {
			parent::__construct($options);
		}

		$this->options['username_with_color'] = $options['username_with_color'] ?: false;

		// https://gist.github.com/ShNURoK42/b5ce8baa570975db487c
		$this->InlineTypes['@'][] = 'UserMention';
		$this->inlineMarkerList .= '@';
	}

	// https://github.com/taufik-nurrohman/parsedown-extra-plugin/blob/1653418c5a9cf5277cd28b0b23ba2d95d18e9bc4/ParsedownExtraPlugin.php#L340-L345
	protected function doGetAttributes($Element) {
		if (isset($Element['attributes'])) {
			return (array) $Element['attributes'];
		}
		return array();
	}

	// https://github.com/taufik-nurrohman/parsedown-extra-plugin/blob/1653418c5a9cf5277cd28b0b23ba2d95d18e9bc4/ParsedownExtraPlugin.php#L347-L358
	protected function doGetContent($Element) {
		if (isset($Element['text'])) {
			return $Element['text'];
		}
		if (isset($Element['rawHtml'])) {
			return $Element['rawHtml'];
		}
		if (isset($Element['handler']['argument'])) {
			return implode("\n", (array) $Element['handler']['argument']);
		}
		return null;
	}

	// https://github.com/taufik-nurrohman/parsedown-extra-plugin/blob/1653418c5a9cf5277cd28b0b23ba2d95d18e9bc4/ParsedownExtraPlugin.php#L369-L378
	protected function doSetAttributes(&$Element, $From, $Args = array()) {
		$Attributes = $this->doGetAttributes($Element);
		$Content = $this->doGetContent($Element);
		if (is_callable($From)) {
			$Args = array_merge(array($Content, $Attributes, &$Element), $Args);
			$Element['attributes'] = array_replace($Attributes, (array) call_user_func_array($From, $Args));
		} else {
			$Element['attributes'] = array_replace($Attributes, (array) $From);
		}
	}

	// Add classes to <table>
	protected function blockTableComplete($Block) {
		$this->doSetAttributes($Block['element'], ['class' => 'table table-bordered']);

		return $Block;
	}

	// https://gist.github.com/ShNURoK42/b5ce8baa570975db487c
	protected function inlineUserMention($Excerpt) {
		if (preg_match('/^@([^\s]+)/', $Excerpt['text'], $matches)) {
			$mentioned_user = UOJUser::query($matches[1]);

			if ($mentioned_user) {
				$color = '#0d6efd';

				if ($this->options['username_with_color']) {
					$color = UOJUser::getUserColor($mentioned_user);
				}

				return [
					'extent' => strlen($matches[0]),
					'element' => [
						'name' => 'span',
						'text' => '@' . $mentioned_user['username'],
						'attributes' => [
							'class' => 'uoj-username',
							'data-realname' => UOJUser::getRealname($mentioned_user),
							'data-color' => $color,
						],
					],
				];
			}

			return [
				'extent' => strlen($matches[0]),
				'markup' => $matches[0],
			];
		}
	}
}
