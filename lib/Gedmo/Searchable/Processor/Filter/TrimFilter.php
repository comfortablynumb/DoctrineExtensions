<?php

/**
 * Trims the value
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @package Gedmo.Searchable.Processor.Filter
 * @subpackage Filter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gedmo\Searchable\Processor\Filter;

use Gedmo\Searchable\Processor\AbstractProcessor,
    Gedmo\Exception\InvalidMappingException;

class TrimFilter extends AbstractProcessor
{
    const TRIM_SIDE_BOTH = 'both';
    const TRIM_SIDE_LEFT = 'left';
    const TRIM_SIDE_RIGHT = 'right';

    protected $parameters = array(
        'side'        => self::TRIM_SIDE_BOTH
    );

    /**
     * @inheritDoc
     */
    public function process()
    {
        $func = $this->parameters['side'] === self::TRIM_SIDE_BOTH ? 'trim' :
            ($this->parameters['side'] === self::TRIM_SIDE_RIGHT ? 'rtrim' : 'ltrim');

        foreach ($this->subject as $key => $value) {
            $this->subject[$key] = $func($value);
        }

        return $this->subject;
    }

    public function validateParameters(array $parameters)
    {
        if (isset($parameters['side'])) {
            switch ($parameters['side']) {
                case self::TRIM_SIDE_BOTH:
                case self::TRIM_SIDE_LEFT:
                case self::TRIM_SIDE_RIGHT:
                    break;
                default:
                    $msg = 'Parameter "side" have an invalid value: "%s". Accepted Values: "%s", "%s", "%s".';
                        
                    throw new InvalidMappingException(sprintf($msg,
                        $parameters['side'],
                        self::TRIM_SIDE_BOTH,
                        self::TRIM_SIDE_LEFT,
                        self::TRIM_SIDE_RIGHT
                    ));
            }
        }
    }
}
