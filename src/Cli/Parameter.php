<?php
/**
 * Part of the Joomla Framework Application Package
 *
 * @copyright  Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Application\Cli;

/**
 * Class Parameter
 *
 * @since  1.0
 */
class Parameter
{
    /**
     * Argumets on string format
     *
     * @var   array
     * @since 1.0
     */ 
    private $arguments = array();

    /**
     * Constructor.
     *
     * @param string  $arguments
     * @param array   $option
     *
     * @since 1.0
     */ 
    public function __construct($arguments)
    {
        $this->arguments = $this->fromString($arguments);
    }

    /**
     * Verify if a parameter exists.
     *
     * @param string  $arguments Verify if argument has passed.
     *
     * @since 1.0
     */  
    public function has($argument)
    {
        return isset($this->arguments[$argument]);
    }

    /**
     * Retrieve a value of parameter.
     *
     * @param string  $arguments Retrieve value for this argument.
     *
     * @since 1.0
     */ 
    public function getParameter($argument)
    {
        if ($this->has($argument)) {
            return $this->arguments[$argument];
        }
        return false;
    }

    /**
     * Create array with string arguments 
     *
     * @param   string  $string  The parameter string.
     *
     * @return  array   Formated and papulated with parameters information.
     *
     * @since   1.0
     */
    private function fromString($string)
    {
        $argumentCollection = array();
        $parts = explode(' ', $string);

        foreach ($parts as $part)
        {
            $subParts = explode('=', $part);
            $argumentCollection[$subParts[0]] = isset($subParts[1]) ? trim($subParts[1], '\'"') : true; 
        }
        return $argumentCollection;
    }
}