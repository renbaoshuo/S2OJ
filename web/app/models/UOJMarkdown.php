<?php
class UOJMarkdown extends ParsedownMath {
	public function __construct($options = '') {
		if (method_exists(get_parent_class(),"__construct")) {
			parent::__construct($options);
		}

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
    		if (validateUsername($matches[1]) && ($user = queryUser($matches[1]))) {
    			return [
    				'extent' => strlen($matches[0]),
    				'element' => [
    					'name' => 'a',
    					'text' => '@' . $user['username'],
    					'attributes' => [
    						'href' => '/user/' . $user['username'],
    						'class' => 'uoj-username',
    						'data-realname' => $user['realname'],
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
