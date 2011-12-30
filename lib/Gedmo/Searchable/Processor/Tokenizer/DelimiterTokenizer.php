<?php

/**
 * This interface must be implemented by all tokenizers
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @package Gedmo.Searchable.Processor.Tokenizer
 * @subpackage Processor
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gedmo\Searchable\Processor\Tokenizer;

use Gedmo\Searchable\Processor\AbstractProcessor;

class DelimiterTokenizer extends AbstractProcessor
{
    protected $parameters = array(
        'times'        => 1,
        'delimiter'    => ' '
    );
    protected $delimiter = '';

    public function __construct($subject, array $parameters = array())
    {
        parent::__construct($subject, $parameters);

        $this->delimiter = str_repeat($this->parameters['delimiter'], $this->parameters['times']);
    }

    /**
     * @inheritDoc
     */
    public function process()
    {
        foreach ($this->subject as $key => $token) {
            if (strpos($token, $this->delimiter) !== false) {
                $tokens = explode($this->delimiter, $token);

                unset($this->subject[$key]);
                $processedTokens = array();
                
                foreach ($tokens as $value) {
                    if ($value !== '') {
                        $processedTokens[] = $value;
                    }
                }

                $this->subject = array_merge($this->subject, $processedTokens);
            }
        }

        return $this->subject;
    }
}
