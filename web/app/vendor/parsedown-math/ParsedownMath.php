<?php

class ParsedownMath extends Parsedown
{
    const VERSION = '1.2';

    public function __construct($options = '')
    {
		if (version_compare(parent::version, '1.7.3') < 0) {
			throw new Exception('ParsedownMath requires a later version of Parsedown');
		}
        // Blocks
        $this->BlockTypes['\\'][] = 'Math';
        $this->BlockTypes['$'][] = 'Math';

        // Inline
        $this->InlineTypes['\\'][] = 'Math';
        $this->inlineMarkerList .= '\\';

        $this->InlineTypes['$'][] = 'Math';
        $this->inlineMarkerList .= '$';

        $this->options['math']['enabled'] = (isset($options['math']['enabled']) ? $options['math']['enabled'] : false);
        $this->options['math']['inline']['enabled'] = (isset($options['math']['inline']['enabled']) ? $options['math']['inline']['enabled'] : true);
        $this->options['math']['block']['enabled'] = (isset($options['math']['block']['enabled']) ? $options['math']['block']['enabled'] : true);
        $this->options['math']['matchSingleDollar'] = (isset($options['math']['matchSingleDollar']) ? $options['math']['matchSingleDollar'] : false);
    }

    protected function element(array $Element)
    {
        if ($this->safeMode) {
            $Element = $this->sanitiseElement($Element);
        }

        if (isset($Element['name'])) {
            $markup = '<'.$Element['name'];
        } else {
            $markup = '';
        }

        if (isset($Element['attributes'])) {
            foreach ($Element['attributes'] as $name => $value) {
                if ($value === null) {
                    continue;
                }

                $markup .= ' '.$name.'="'.self::escape($value).'"';
            }
        }

        if (isset($Element['text'])) {
            if (isset($Element['name'])) {
                $markup .= '>';
            }

            if (!isset($Element['nonNestables'])) {
                $Element['nonNestables'] = array();
            }

            if (isset($Element['handler'])) {
                $markup .= $this->{$Element['handler']}($Element['text'], $Element['nonNestables']);
            } else {
                $markup .= self::escape($Element['text'], true);
            }

            if (isset($Element['name'])) {
                $markup .= '</'.$Element['name'].'>';
            }
        } else {
            if (isset($Element['name'])) {
                $markup .= ' />';
            }
        }

        return $markup;
    }





    // -------------------------------------------------------------------------
    // -----------------------         Inline         --------------------------
    // -------------------------------------------------------------------------


    //
    // Inline Math
    // -------------------------------------------------------------------------

    protected function inlineMath($Excerpt)
    {
        if (!$this->options['math']['enabled'] === true && !$this->options['math']['inline']['enabled'] !== false) {
            return;
        }

        $matchSignleDollar = $this->options['math']['matchSingleDollar'] ?? false;


        // Using inline detection to detect Block single-line math.

        if (preg_match('/^(?<!\\\\)(?<!\$)\${2}(?!\$)[^$]*?(?<!\$)\${2}(?!\$)$/', $Excerpt['text'], $matches)) {
            $Block = array(
                'element' => array(
                    'text' => '',
                ),
            );

            $Block['end'] = '$$';
            $Block['complete'] = true;
            $Block['latex'] = true;
            $Block['element']['text'] = $matches[0];
            $Block['extent'] = strlen($Block['element']['text']);
            return $Block;
        }


        // Inline Matches
        if ($matchSignleDollar === true) {
            // Experimental
            if (preg_match('/^(?<!\\\\)((?<!\$)\$(?!\$)(.*?)(?<!\$)\$(?!\$)|(?<!\\\\\()\\\\\((.*?)(?<!\\\\\()\\\\\)(?!\\\\\)))/s', $Excerpt['text'], $matches)) {
                $this->mathMatch = $matches[0];
            }
        } else {
            if (preg_match('/^(?<!\\\\)(?<!\\\\\()\\\\\((.*?)(?<!\\\\\()\\\\\)(?!\\\\\))/s', $Excerpt['text'], $matches)) {
                $this->mathMatch = $matches[0];
            }
        }

        if (isset($this->mathMatch)) {
            return array(
                'extent' => strlen($this->mathMatch),
                'element' => array(
                    'text' =>  $this->mathMatch,
                ),
            );
        }





    }

    protected $specialCharacters = array(
        '\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '<', '>', '#', '+', '-', '.', '!', '|', '~', '^', '='
    );


    //
    // Inline Escape
    // -------------------------------------------------------------------------

    protected function inlineEscapeSequence($Excerpt)
    {
        $Element = array(
            'element' => array(
                'rawHtml' => $Excerpt['text'][1],
            ),
            'extent' => 2,
        );

        if ($this->options['math']['enabled'] === true) {
            if (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], $this->specialCharacters) && !preg_match('/^(?<!\\\\)((?<!\\\\\()\\\\\((?!\\\\\())(.*?)(?<!\\\\)(?<!\\\\\()((?<!\\\\\))\\\\\)(?!\\\\\)))(?!\\\\\()/s', $Excerpt['text'])) {
                return $Element;
            } elseif (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], $this->specialCharacters) && !preg_match('/^(?<!\\\\)(?<!\\\\\()\\\\\((.{1,}?)(?<!\\\\\()\\\\\)(?!\\\\\))/s', $Excerpt['text'])) {
                return $Element;
            }
        } else {
            if (isset($Excerpt['text'][1]) && in_array($Excerpt['text'][1], $this->specialCharacters)) {
                return $Element;
            }
        }
    }



    // -------------------------------------------------------------------------
    // -----------------------         Blocks         --------------------------
    // -------------------------------------------------------------------------


    //
    // Block Math
    // --------------------------------------------------------------------------

    protected function blockMath($Line)
    {
        if (!$this->options['math']['enabled'] === true && !$this->options['math']['block']['enabled'] !== false) {
            return;
        }

        $Block = array(
            'element' => array(
                'text' => '',
            ),
        );

        if (preg_match('/^(?<!\\\\)(\\\\\[)(?!.)$/', $Line['text'])) {
            $Block['end'] = '\]';
            return $Block;
        } elseif (preg_match('/^(?<!\\\\)(\$\$)(?!.)$/', $Line['text'])) {
            $Block['end'] = '$$';
            return $Block;
        }
    }

    // ~

    protected function blockMathContinue($Line, $Block)
    {
        if (isset($Block['complete'])) {
            return;
        }

        if (isset($Block['interrupted'])) {
            $Block['element']['text'] .= str_repeat("\n", $Block['interrupted']);

            unset($Block['interrupted']);
        }

        if (preg_match('/^(?<!\\\\)(\\\\\])$/', $Line['text']) && $Block['end'] === '\]') {
            $Block['complete'] = true;
            $Block['latex'] = true;
            $Block['element']['text'] = "\\[".$Block['element']['text']."\\]";
            return $Block;
        } elseif (preg_match('/^(?<!\\\\)(\$\$)$/', $Line['text']) && $Block['end'] === '$$') {
            $Block['complete'] = true;
            $Block['latex'] = true;
            $Block['element']['text'] = "$$".$Block['element']['text']."$$";
            return $Block;
        }


        $Block['element']['text'] .= "\n" . $Line['body'];

        // ~

        return $Block;
    }

    // ~

    protected function blockMathComplete($Block)
    {
        return $Block;
    }
}
